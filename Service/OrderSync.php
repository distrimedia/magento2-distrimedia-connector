<?php

declare(strict_types=1);

namespace DistriMedia\Connector\Service;

use DistriMedia\Connector\Cron\SyncOrders;
use DistriMedia\Connector\DistriMediaException;
use DistriMedia\Connector\Model\ConfigInterface;
use DistriMedia\Connector\Ui\Component\Listing\Column\SyncStatus\Options;
use DistriMedia\SoapClient\InvalidOrderException;
use DistriMedia\SoapClient\Service\Order as DistriMediaOrderService;
use DistriMedia\SoapClient\Struct\Order as DistriMediaOrderStruct;
use DistriMedia\SoapClient\Struct\Response\Order as DistriMediaOrderResponse;
use Magento\Sales\Api\Data\OrderExtensionFactory;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Psr\Log\LoggerInterface;

class OrderSync extends AbstractSync implements OrderSyncInterface
{
    /**
     * @var DistriMediaOrderService
     */
    private $distriMediaOrderService;
    private $orderRepository;
    private $extensionFactory;
    private $orderCollectionFactory;
    private $orderBuilderFactory;

    public function __construct(
        LoggerInterface $logger,
        ConfigInterface $config,
        OrderRepositoryInterface $orderRepository,
        OrderExtensionFactory $orderExtensionFactory,
        OrderBuilderFactory $orderBuilderFactory,
        OrderCollectionFactory $orderCollectionFactory
    ) {
        $this->logger = $logger;
        $this->orderRepository = $orderRepository;
        $this->extensionFactory = $orderExtensionFactory;
        $this->orderBuilderFactory = $orderBuilderFactory;
        $this->orderCollectionFactory = $orderCollectionFactory;
        parent::__construct($logger, $config);
    }

    /**
     * I create a new Inventory service
     * @throws DistriMediaException
     */
    private function init(): void
    {
        if ($this->distriMediaOrderService === null) {
            $uri = $this->config->getApiUri();
            $password = $this->config->getApiPassword();
            $webshopCode = $this->config->getWebshopCode();
            $maxTimeout = $this->config->getTimeoutAterInSeconds();
            if (!empty($uri) && !empty($password) && !empty($webshopCode) && !empty($maxTimeout)) {
                $this->distriMediaOrderService = new DistriMediaOrderService(
                    $uri,
                    $webshopCode,
                    $password,
                    $maxTimeout,
                    $this->logger
                );
            } else {
                throw new DistriMediaException(
                    'Invalid DistriMedia Configuration. Some fields are missing (uri, webshopcode or password)'
                );
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function syncDistriMediaOrder(DistriMediaOrderStruct $distriMediaOrder)
    {
        $this->init();

        return $this->distriMediaOrderService->createOrder($distriMediaOrder);
    }

    /**
     * {@inheritDoc}
     */
    public function cancelOrder(OrderInterface $order): ?bool
    {
        $this->init();
        try {
            $extensionAttrs = $order->getExtensionAttributes();
            $referenceId = $extensionAttrs->getDistriMediaIncrementId();

            $this->distriMediaOrderService->changeOrderStatusByOrderId(
                $referenceId,
                DistriMediaOrderService::ORDER_STATUS_CANCEL
            );

            //update the model
            $extensionAttrs->setDistriMediaSyncStatus(Options::SYNC_STATUS_PENDING_CANCELED);

            $order->setExtensionAttributes($extensionAttrs);
            $this->orderRepository->save($order);

            return true;
        } catch (InvalidOrderException $invalidOrderException) {
            $this->logger->critical($invalidOrderException->getMessage());
            throw $invalidOrderException;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function preprareOrderForSync(OrderInterface $order)
    {
        $extensionAttributes = $order->getExtensionAttributes() ?: $this->extensionFactory->create();

        $queueStatus = $extensionAttributes->getDistriMediaSyncStatus();

        $status = Options::SYNC_STATUS_FAILED;

        $attempts = (int) $extensionAttributes->getDistriMediaSyncAttempts();

        try {
            /* @var OrderBuilder $orderBuilder */
            $orderBuilder = $this->orderBuilderFactory->create();
            $distriMediaOrder = $orderBuilder->convert($order);

            /* @var DistriMediaOrderResponse $result */
            $result = $this->syncDistriMediaOrder($distriMediaOrder);

            if ($result instanceof DistriMediaOrderResponse) {
                $incrementId = $result->getOrderID();

                if ($incrementId !== null) {
                    $extensionAttributes->setDistriMediaIncrementId($incrementId);
                    $status = Options::SYNC_STATUS_SYNCED;
                } else {
                    throw new DistriMediaException($result->getReason());
                }
            }
        } catch (\Throwable $e) {
            //if the order already exists, we set the status to synced.
            if (strpos($e->getMessage(), __('Ordernumber %1 already exists', $order->getIncrementId())->render())
                !== false
            ) {
                $status = Options::SYNC_STATUS_SYNCED;
            } else {
                $this->logger->critical(
                    "DistriMedia SyncOrders Cron: failed to sync message for order 
                    {$order->getIncrementId()}: " . $e->getMessage()
                );
            }
        }

        ++$attempts;

        $extensionAttributes->setDistriMediaSyncAttempts($attempts);

        if ($status === Options::SYNC_STATUS_FAILED) {
            if ($attempts < SyncOrders::MAX_SYNC_ATTEMPTS) {
                $status = Options::SYNC_STATUS_RETRY;
            } else {
                $this->logger->critical(
                    "DistriMedia SyncOrders Cron: failed to sync order {$order->getIncrementId()}. Max attempts "
                    . SyncOrders::MAX_SYNC_ATTEMPTS . ' reached'
                );
            }
        }

        //update the model
        $extensionAttributes->setDistriMediaSyncStatus($status);

        $order->setExtensionAttributes($extensionAttributes);
        $this->orderRepository->save($order);
    }
}
