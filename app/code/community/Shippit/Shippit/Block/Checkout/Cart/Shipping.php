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

class Shippit_Shippit_Block_Checkout_Cart_Shipping extends Mage_Checkout_Block_Cart_Shipping
{
    /**
     * Show City in Shipping Estimation
     *
     * @return bool
     */
    public function getCityActive()
    {
        return (bool) Mage::helper('shippit/carrier_shippit')->isActive()
            || parent::getCityActive();
    }

    /**
     * Show State in Shipping Estimation
     *
     * @return bool
     */
    public function getStateActive()
    {
        return (bool) Mage::helper('shippit/carrier_shippit')->isActive()
            || parent::getStateActive();
    }
}
