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

class Mamis_Shippit_Model_Shipping_Carrier_Shippit extends Mage_Shipping_Model_Carrier_Abstract implements Mage_Shipping_Model_Carrier_Interface
{
    /**
     * Carrier's code
     *
     * @var string
     */
    protected $_code = 'mamis_shippit';

    /**
     * Configuration Helper
     * @var Mamis_Shippit_Helper_Data
     */
    protected $helper;
    protected $api;
    protected $bugsnag;

    /**
     * Attach the helper as a class variable
     */
    public function __construct()
    {
        $this->helper = Mage::helper('mamis_shippit');
        $this->api = Mage::helper('mamis_shippit/api');

        if ($this->helper->isDebugActive()) {
            $this->bugsnag = Mage::helper('mamis_shippit/bugsnag')->init();
        }

        return parent::__construct();
    }

    /**
     * Collect and get rates
     *
     * @abstract
     * @param Mage_Shipping_Model_Rate_Request $request
     * @return Mage_Shipping_Model_Rate_Result|bool|null
     */
    public function collectRates(Mage_Shipping_Model_Rate_Request $request)
    {
        if (!$this->helper->isActive()) {
            return false;
        }

        $rateResult = Mage::getModel('shipping/rate_result');

        // check the products are eligible for shippit shipping
        if (!$this->_canShipProducts($request)) {
            return false;
        }

        $quoteRequest = new Varien_Object;

        // @TODO: Confirm how processing time is factored in
        // for order pickup availability date
        // Suggestion - config value?
        $orderDate = new Zend_Date(Mage::getModel('core/date')->timestamp(), Zend_Date::TIMESTAMP);
        $orderDate->addDay('+2');

        $quoteRequest->setOrderDate($orderDate->toString('YYYY-MM-dd'));

        if ($request->getDestCity()) {
            $quoteRequest->setDropoffSuburb($request->getDestCity());
        }

        if ($request->getDestPostcode()) {
            $quoteRequest->setDropoffPostcode($request->getDestPostcode());
        }

        if ($request->getDestRegionCode()) {
            $quoteRequest->setDropoffState($request->getDestRegionCode());
        }

        $quoteRequest->setParcelAttributes($this->_getParcelAttributes($request));

        try {
            // Call the api and retrieve the quote
            $shippingQuotes = $this->api->getQuote($quoteRequest);
        }
        catch (Exception $e) {
            if ($this->helper->isDebugActive() && $this->bugsnag) {
                $this->bugsnag->notifyError('API - Quote Request', $e->getMessage());
            }

            Mage::log($e->getMessage());
        
            return false;
        }

        $this->_processShippingQuotes($rateResult, $shippingQuotes);

        return $rateResult;
    }

    private function _processShippingQuotes(&$rateResult, $shippingQuotes)
    {
        // Process the response and return available options
        foreach ($shippingQuotes as $shippingQuoteKey => $shippingQuote) {
            if ($shippingQuote->success) {
                if ($shippingQuote->courier_type == 'Bonds') {
                    $this->_addPremiumQuote($rateResult, $shippingQuote);
                }
                else {
                    $this->_addEconomyQuote($rateResult, $shippingQuote);
                }
            }
        }

        return $rateResult;
    }

    private function _addEconomyQuote(&$rateResult, $shippingQuote)
    {
        foreach ($shippingQuote->quotes as $shippingQuoteQuote) {
            $rateResultMethod = Mage::getModel('shipping/rate_result_method');
            $rateResultMethod->setCarrier($this->_code)
                ->setCarrierTitle($shippingQuote->courier_type)
                ->setMethod($shippingQuote->courier_type)
                ->setMethodTitle('Economy')
                ->setCost($shippingQuoteQuote->price)
                ->setPrice($shippingQuoteQuote->price);

            $rateResult->append($rateResultMethod);
        }
    }

    private function _addPremiumQuote(&$rateResult, $shippingQuote)
    {
        $maxTimeslots = Mage::helper('mamis_shippit')->getMaxTimeslots();
        $timeslotCount = 0;

        foreach ($shippingQuote->quotes as $shippingQuoteQuote) {
            if (!empty($maxTimeslots)&& $maxTimeslots <= $timeslotCount) {
                break;
            }

            $rateResultMethod = Mage::getModel('shipping/rate_result_method');

            if (property_exists($shippingQuoteQuote, 'delivery_date')
                && property_exists($shippingQuoteQuote, 'delivery_window')
                && property_exists($shippingQuoteQuote, 'delivery_window_desc')) {
                $timeslotCount++;
                $carrierTitle = $shippingQuote->courier_type;
                $method = $shippingQuote->courier_type . '_' . $shippingQuoteQuote->delivery_date . '_' . $shippingQuoteQuote->delivery_window;
                $methodTitle = 'Premium' . ' - Delivered ' . $shippingQuoteQuote->delivery_date. ', Between ' . $shippingQuoteQuote->delivery_window_desc;
            }
            else {
                $carrierTitle = $shippingQuote->courier_type;
                $method = $shippingQuote->courier_type;
                $methodTitle = 'Premium';
            }

            $rateResultMethod->setCarrier($this->_code)
                ->setCarrierTitle($carrierTitle)
                ->setMethod($method)
                ->setMethodTitle($methodTitle)
                ->setCost($shippingQuoteQuote->price)
                ->setPrice($shippingQuoteQuote->price);

            $rateResult->append($rateResultMethod);
        }
    }

    public function getAllowedMethods()
    {
        $configAllowedMethods = explode(',', Mage::helper('mamis_shippit')->getAllowedMethods());
        $availableMethods = Mage::getModel('mamis_shippit/shipping_carrier_shippit_methods')->getMethods();

        $allowedMethods = array();

        foreach ($availableMethods as $methodValue => $methodLabel) {
            if (in_array($methodValue, $configAllowedMethods)) {
                $allowedMethods[$methodValue] = $methodLabel;
            }
        }

        return $allowedMethods;
    }

    public function isStateProvinceRequired()
    {
        return true;
    }

    public function isCityRequired()
    {
        return true;
    }

    public function isZipCodeRequired($countryId = null)
    {
        if ($countryId == 'AU') {
            return true;
        }
        else {
            return parent::isZipCodeRequired($countryId);
        }
    }

    /**
     * Checks the request and ensures all products are either enabled, or part of the attributes elidgable
     * 
     * @param  [type] $request The shipment request
     * @return boolean         True or false
     */
    private function _canShipProducts($request)
    {
        $items = $request->getAllItems();
        $productIds = array();

        foreach ($items as $item) {
            $productIds[] = $item->getId();
        }

        $canShipEnabledProducts = $this->_canShipEnabledProducts($productIds);
        $canShipEnabledProductAttributes = $this->_canShipEnabledProductAttributes($productIds);

        if ($canShipEnabledProducts && $canShipEnabledProductAttributes) {
            return true;
        }
        else {
            return false;
        }
    }

    private function _canShipEnabledProducts($productIds)
    {
        if (!$this->helper->isEnabledProductActive()) {
            return true;
        }

        $enabledProductIds = $this->helper->getEnabledProductIds();

        // if we have enabled products, check that all
        // items in the shipping request are enabled
        if (count($enabledProductIds) > 0) {
            if ($productIds == array_intersect($productIds, $enabledProductIds)) {
                return false;
            }
        }

        return true;
    }

    private function _canShipEnabledProductAttributes($productIds)
    {
        if (!$this->helper->isEnabledProductAttributeActive()) {
            return true;
        }

        $attributeCode = $this->helper->getEnabledProductAttributeCode();
        $attributeValue = $this->helper->getEnabledProductAttributeValue();
        
        if (!empty($attributeCode) && !empty($attributeValue)) {
            $attributeProductCount = Mage::getModel('catalog/product')
                ->getCollection()
                ->addAttributeToFilter('entity_id', array('in' => $productIds))
                ->addAttributeToFilter($attributeCode, array('eq' => $attributeValue))
                ->getSize();

            if ($attributeProductCount != count($productIds)) {
                return false;
            }
        }

        return true;
    }

    private function _getParcelAttributes($request)
    {
        $items = $request->getAllItems();
        $parcelAttributes = array();

        foreach ($items as $item) {
            // Skip special product types
            if ($item->getProduct()->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE
                || $item->getProduct()->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_BUNDLE
                || $item->getProduct()->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_GROUPED
                || $item->getProduct()->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_VIRTUAL) {
                continue;
            }

            $parcelAttributes[] = array(
                'qty' => $item->getQty(),
                'weight' => $item->getWeight()
            );
        }

        return $parcelAttributes;
    }
}