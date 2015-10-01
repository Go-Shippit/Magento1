<?php
/**
*  Mamis.IT
*
*  NOTICE OF LICENSE
*
*  This source file is subject to the EULA
*  that is available through the world-wide-web at this URL:
*  http://www.mamis.com.au/licencing
*
*  @category   Mamis
*  @copyright  Copyright (c) 2015 by Mamis.IT Pty Ltd (http://www.mamis.com.au)
*  @author     Matthew Muscat <matthew@mamis.com.au>
*  @license    http://www.mamis.com.au/licencing
*/

class Mamis_Shippit_Block_Checkout_Cart_Shipping extends Mage_Checkout_Block_Cart_Shipping
{
    /**
     * Show City in Shipping Estimation
     *
     * @return bool
     */
    public function getCityActive()
    {
        return (bool) Mage::helper('mamis_shippit')->isActive()
            || parent::getCityActive();
    }

    /**
     * Show State in Shipping Estimation
     *
     * @return bool
     */
    public function getStateActive()
    {
        return (bool) Mage::helper('mamis_shippit')->isActive()
            || parent::getStateActive();
    }
}