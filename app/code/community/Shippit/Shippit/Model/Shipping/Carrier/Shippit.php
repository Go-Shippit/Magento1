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
 * @copyright  Copyright (c) 2016 by Shippit Pty Ltd (http://www.shippit.com)
 * @author     Matthew Muscat <matthew@mamis.com.au>
 * @license    http://www.shippit.com/terms
 */

class Shippit_Shippit_Model_Shipping_Carrier_Shippit extends Mage_Shipping_Model_Carrier_Abstract implements Mage_Shipping_Model_Carrier_Interface
{
    /**
     * Carrier's code
     *
     * @var string
     */
    protected $_code = 'shippit';

    /**
     * Configuration Helper
     * @var Shippit_Shippit_Helper_Data
     */
    protected $helper;
    protected $api;
    protected $logger;

    /**
     * Attach the helper as a class variable
     */
    public function __construct()
    {
        $this->helper = Mage::helper('shippit/carrier');
        $this->api = Mage::helper('shippit/api');
        $this->logger = Mage::getModel('shippit/logger');

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

        // check if we have any methods allowed before proceeding
        $allowedMethods = $this->helper->getAllowedMethods();
        if (count($allowedMethods) == 0) {
            return false;
        }

        // check the products are eligible for shippit shipping
        if (!$this->_canShipProducts($request)) {
            return false;
        }

        $rateResult = Mage::getModel('shipping/rate_result');
        $quoteRequest = new Varien_Object;

        // Get the first available dates based on the customer's shippit profile settings
        $quoteRequest->setOrderDate('');

        if ($request->getDestCity()) {
            $quoteRequest->setDropoffSuburb($request->getDestCity());
        }

        if ($request->getDestPostcode()) {
            $quoteRequest->setDropoffPostcode($request->getDestPostcode());
        }

        if ($request->getDestRegionCode()) {
            $quoteRequest->setDropoffState($request->getDestRegionCode());
        }
        elseif ($request->getDestPostcode()
            && $regionCodeFallback = $this->helper->getStateFromPostcode($request->getDestPostcode())) {
            $quoteRequest->setDropoffState($regionCodeFallback);
        }

        $quoteRequest->setParcelAttributes($this->_getParcelAttributes($request));

        try {
            // Call the api and retrieve the quote
            $shippingQuotes = $this->api->getQuote($quoteRequest);
        }
        catch (Exception $e) {
            return false;
        }

        $this->_processShippingQuotes($rateResult, $shippingQuotes);

        return $rateResult;
    }

    private function _processShippingQuotes(&$rateResult, $shippingQuotes)
    {
        $allowedMethods = $this->helper->getAllowedMethods();

        $isPriorityAvailable = in_array('priority', $allowedMethods);
        $isExpressAvailable = in_array('express', $allowedMethods);
        $isStandardAvailable = in_array('standard', $allowedMethods);

        // Process the response and return available options
        foreach ($shippingQuotes as $shippingQuoteKey => $shippingQuote) {
            if ($shippingQuote->success) {
                switch ($shippingQuote->service_level) {
                    case 'priority':
                        if ($isPriorityAvailable) {
                            $this->_addPriorityQuote($rateResult, $shippingQuote);
                        }
                        
                        break;
                    case 'express':
                        if ($isExpressAvailable) {
                            $this->_addExpressQuote($rateResult, $shippingQuote);
                        }
                        
                        break;
                    case 'standard':
                        if ($isStandardAvailable) {
                            $this->_addStandardQuote($rateResult, $shippingQuote);
                        }

                        break;
                }
            }
        }

        return $rateResult;
    }

    private function _addStandardQuote(&$rateResult, $shippingQuote)
    {
        foreach ($shippingQuote->quotes as $shippingQuoteQuote) {
            $rateResultMethod = Mage::getModel('shipping/rate_result_method');
            $rateResultMethod->setCarrier($this->_code)
                ->setCarrierTitle($this->helper->getTitle())
                ->setMethod('standard')
                ->setMethodTitle('Standard')
                ->setCost($shippingQuoteQuote->price)
                ->setPrice($shippingQuoteQuote->price);

            $rateResult->append($rateResultMethod);
        }
    }

    private function _addExpressQuote(&$rateResult, $shippingQuote)
    {
        foreach ($shippingQuote->quotes as $shippingQuoteQuote) {
            $rateResultMethod = Mage::getModel('shipping/rate_result_method');
            $rateResultMethod->setCarrier($this->_code)
                ->setCarrierTitle($this->helper->getTitle())
                ->setMethod('express')
                ->setMethodTitle('Express')
                ->setCost($shippingQuoteQuote->price)
                ->setPrice($shippingQuoteQuote->price);

            $rateResult->append($rateResultMethod);
        }
    }

    private function _addPriorityQuote(&$rateResult, $shippingQuote)
    {
        $maxTimeslots = $this->helper->getMaxTimeslots();
        $timeslotCount = 0;

        foreach ($shippingQuote->quotes as $shippingQuoteQuote) {
            if (!empty($maxTimeslots) && $maxTimeslots <= $timeslotCount) {
                break;
            }

            $rateResultMethod = Mage::getModel('shipping/rate_result_method');

            if (property_exists($shippingQuoteQuote, 'delivery_date')
                && property_exists($shippingQuoteQuote, 'delivery_window')
                && property_exists($shippingQuoteQuote, 'delivery_window_desc')) {
                $timeslotCount++;
                $carrierTitle = $this->helper->getTitle();
                $method = 'priority_' . $shippingQuoteQuote->delivery_date . '_' . $shippingQuoteQuote->delivery_window;
                $methodTitle = 'Priority' . ' - Delivered ' . $shippingQuoteQuote->delivery_date. ', Between ' . $shippingQuoteQuote->delivery_window_desc;
            }
            else {
                $carrierTitle = $this->helper->getTitle();
                $method = 'priority';
                $methodTitle = 'Priority';
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

    public function isTrackingAvailable()
    {
        return true;
    }

    public function getTrackingInfo($tracking)
    {
        $track = Mage::getModel('shipping/tracking_result_status');
        $track->setUrl('https://www.shippit.com/track/' . $tracking)
            ->setTracking($tracking)
            ->setCarrierTitle($this->getConfigData('name'));

        return $track;
    }

    public function getAllowedMethods()
    {
        $configAllowedMethods = $this->helper->getAllowedMethods();
        $availableMethods = Mage::getModel('shippit/system_config_source_shippit_methods')->getMethods();

        $allowedMethods = array();

        foreach ($availableMethods as $methodValue => $methodLabel) {
            if (in_array($methodValue, $configAllowedMethods)) {
                $allowedMethods[$methodValue] = $this->helper->getTitle() . ' ' . $methodLabel;
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
            // Skip special product types
            if ($item->getProduct()->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE
                || $item->getProduct()->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_BUNDLE
                || $item->getProduct()->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_GROUPED
                || $item->getProduct()->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_VIRTUAL) {
                continue;
            }

            $productIds[] = $item->getProduct()->getId();
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
            if ($productIds != array_intersect($productIds, $enabledProductIds)) {
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
                ->addAttributeToFilter('entity_id', array('in' => $productIds));

            // When filtering by attribute value, allow for * as a wildcard
            if (strpos($attributeValue, '*') !== FALSE) {
                $attributeValue = str_replace('*', '%', $attributeValue);

                $attributeProductCount = $attributeProductCount->addAttributeToFilter($attributeCode, array('like' => $attributeValue))
                    ->getSize();
            }
            // Otherwise, use the exact match
            else {
                $attributeProductCount = $attributeProductCount->addAttributeToFilter($attributeCode, array('eq' => $attributeValue))
                    ->getSize();
            }

            // If the number of filtered products is not
            // equal to the products in the cart, return false
            if ($attributeProductCount != count($productIds)) {
                return false;
            }
        }

        // All checks have passed, return true
        return true;
    }

    private function _getParcelAttributes($request)
    {
        $parcelAttributes = array(
            array(
                'qty' => $request->getPackageQty(),
                'weight' => ($request->getPackageWeight() / $request->getPackageQty())
            )
        );
        
        return $parcelAttributes;
    }
}