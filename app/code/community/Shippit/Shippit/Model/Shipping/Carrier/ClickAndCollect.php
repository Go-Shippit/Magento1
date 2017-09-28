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

class Shippit_Shippit_Model_Shipping_Carrier_ClickAndCollect extends Shippit_Shippit_Model_Shipping_Carrier_Abstract implements Mage_Shipping_Model_Carrier_Interface
{
    /**
     * Carrier's code
     *
     * @var string
     */
    protected $_code = 'shippit_cc';

    protected $helper;

    /**
     * Attach the helper as a class variable
     */
    public function __construct()
    {
        $this->helper = Mage::helper('shippit/carrier_clickAndCollect');
    }

    public function isActive()
    {
        return $this->helper->isActive();
    }

    public function getAllowedMethods()
    {
        $allowedMethods['shippit_cc'] = $this->getConfigData('title');

        return $allowedMethods;
    }

    public function collectRates(Mage_Shipping_Model_Rate_Request $request)
    {
        $result = Mage::getModel('shipping/rate_result');

        $result->append($this->_getClickAndCollectRate());

        return $result;
    }

    protected function _getClickAndCollectRate()
    {
        $rate = Mage::getModel('shipping/rate_result_method');

        $rate->setCarrier($this->_code);
        $rate->setCarrierTitle($this->getConfigData('title'));
        $rate->setMethod($this->_code);
        $rate->setMethodTitle($this->getConfigData('method'));
        $rate->setPrice(0);
        $rate->setCost(0);

        return $rate;
    }
}
