<?php

declare(strict_types=1);

namespace DistriMedia\Connector\Api\Data;

interface TrackIdInterface
{
    const NUMBER_COLLI = 'NumberColli';
    const CARRIER = 'Carrier';
    const EXECUTING_CARRIER = 'ExecutingCarrier';
    const WEIGHT = 'Weight';
    const AWB = 'AWB';
    const TRACK_ID = 'TrackID';
    const BOX_TYPE = 'BoxType';
    const REFERENCE = 'Reference';
    const SHIPPED_DATE = 'ShippedDate';
    const TRACK_AND_TRACE_URL = 'TrackAndTraceURL';
    const ORDERLINE = 'Orderline';
    const PACKAGE = 'Package';

    /**
     * @return string
     */
    public function getNumberColli();

    /**
     * @return \DistriMedia\Connector\Api\Data\TrackIdInterface
     */
    public function setNumberColli($numberColli);

    /**
     * @return string
     */
    public function getCarrier();

    /**
     * @return \DistriMedia\Connector\Api\Data\TrackIdInterface
     */
    public function setCarrier($carrier);

    /**
     * @return string
     */
    public function getExecutingCarrier();

    /**
     * @return \DistriMedia\Connector\Api\Data\TrackIdInterface
     */
    public function setExecutingCarrier($executingCarrier);

    /**
     * @return string
     */
    public function getWeight();

    /**
     * @return \DistriMedia\Connector\Api\Data\TrackIdInterface
     */
    public function setWeight($weight);

    /**
     * @return string
     */
    public function getAWB();

    /**
     * @return \DistriMedia\Connector\Api\Data\TrackIdInterface
     */
    public function setAWB($awb);

    /**
     * @return string
     */
    public function getTrackID();

    /**
     * @return \DistriMedia\Connector\Api\Data\TrackIdInterface
     */
    public function setTrackID($trackID);

    /**
     * @return string
     */
    public function getBoxType();

    /**
     * @return \DistriMedia\Connector\Api\Data\TrackIdInterface
     */
    public function setBoxType($boxType);

    /**
     * @return string
     */
    public function getReference();

    /**
     * @return \DistriMedia\Connector\Api\Data\TrackIdInterface
     */
    public function setReference($reference);

    /**
     * @return string
     */
    public function getShippedDate();

    /**
     * @return \DistriMedia\Connector\Api\Data\TrackIdInterface
     */
    public function setShippedDate($shippedDate);

    /**
     * @return string
     */
    public function getTrackAndTraceURL();

    /**
     * @return \DistriMedia\Connector\Api\Data\TrackIdInterface
     */
    public function setTrackAndTraceURL($trackAndTrace);

    /**
     * @return \DistriMedia\Connector\Api\Data\OrderlineInterface[]
     */
    public function getOrderline();

    /**
     * @param \DistriMedia\Connector\Api\Data\OrderlineInterface[] $orderline
     * @return \DistriMedia\Connector\Api\Data\TrackIdInterface
     */
    public function setOrderline($orderline);

    /**
     * @return \DistriMedia\Connector\Api\Data\PackageInterface
     */
    public function getPackage();

    /**
     * @return \DistriMedia\Connector\Api\Data\TrackIdInterface
     */
    public function setPackage($package);
}
