<?php
/**
 * Shippit Pty Ltd
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the terms
 * that is available through the world-wide-web at this URL:
 * http://www.shippit.com/terms
 *
 * @category   Shippit
 * @copyright  Copyright (c) Shippit Pty Ltd (http://www.shippit.com)
 * @author     Matthew Muscat <matthew@mamis.com.au>
 * @license    http://www.shippit.com/terms
 */

abstract class Shippit_Shippit_Model_Shipping_Carrier_Abstract extends Mage_Shipping_Model_Carrier_Abstract
{
    public function isTrackingAvailable()
    {
        return true;
    }

    public function getTrackingInfo($tracking)
    {
        $shippitTrackUrl = 'https://www.shippit.com/track/%s';

        if (Mage::helper('shippit')->getEnvironment() == 'staging') {
            $shippitTrackUrl = 'https://staging.shippit.com/track/%s';
        }

        $track = Mage::getModel('shipping/tracking_result_status');
        $track->setUrl(
                sprintf(
                    $shippitTrackUrl,
                    $tracking
                )
            )
            ->setTracking($tracking)
            ->setCarrierTitle($this->getConfigData('name'));

        return $track;
    }
}
