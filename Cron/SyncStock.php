<?php

declare(strict_types=1);

namespace DistriMedia\Connector\Cron;

use DistriMedia\Connector\Helper\ErrorHandlingHelper;
use DistriMedia\Connector\Model\ConfigInterface;
use DistriMedia\Connector\Model\Flag\LastExecutionFlag;
use DistriMedia\Connector\Model\Flag\Status;
use DistriMedia\Connector\Service\StockSyncInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;

/**
 * I am responsible for syncing the complete inventory once a day.
 * Class SyncStock
 */
class SyncStock
{
    private $stockSync;
    private $config;
    private $lastExecutionFlag;
    private $statusFlag;
    private $dateTime;
    private $statusFlagData;
    private $errorHandlingHelper;

    /**
     * SyncStock constructor.
     */
    public function __construct(
        StockSyncInterface $stockSync,
        ErrorHandlingHelper $errorHandlingHelper,
        LastExecutionFlag $lastExecutionFlag,
        Status $statusFlag,
        DateTime $dateTime,
        ConfigInterface $config
    ) {
        $this->stockSync = $stockSync;
        $this->errorHandlingHelper = $errorHandlingHelper;
        $this->lastExecutionFlag = $lastExecutionFlag;
        $this->statusFlag = $statusFlag;
        $this->dateTime = $dateTime;
        $this->config = $config;
    }

    /**
     * @throws \Exception
     */
    public function execute()
    {
        if ($this->config->isEnabled() && $this->config->isStockSyncEnabled()) {
            $lastExecutionFlag = $this->lastExecutionFlag->loadSelf();
            $now = $this->dateTime->gmtDate();
            $lastExecutionFlag->setFlagData($now);
            $lastExecutionFlag->save();

            $this->updateStatus(Status::STATUS_RUNNING);

            $this->processStock();
        }

        return $this;
    }

    /**
     * @throws \Exception
     */
    public function processStock()
    {
        $hasErrors = false;

        try {
            $errors = $this->stockSync->fetchAllStock();
            if (!empty($errors)) {
                $hasErrors = true;
            } else {
                $this->updateStatus(Status::STATUS_SUCCESS);
            }
        } catch (\Exception $exception) {
            $errors[] = $exception->getMessage();
            $hasErrors = true;
        }

        if ($hasErrors === true) {
            $subject = __('DistriMedia Connector Stock cron log')->getText();
            $title = __('DistriMedia Connector Stock cron log')->getText();
            $this->errorHandlingHelper->sendErrorEmail($errors, $subject, $title);
            $this->updateStatus(Status::STATUS_ERROR);
        }
    }

    /**
     * This updates the flagdata
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function updateStatus(string $status)
    {
        if ($this->statusFlagData === null) {
            $statusFlag = $this->statusFlag->loadSelf();
            $this->statusFlagData = $statusFlag;
        }

        $this->statusFlagData->setFlagData($status);
        $this->statusFlagData->save();
    }
}
