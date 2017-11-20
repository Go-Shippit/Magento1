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

class Shippit_Shippit_OrderController extends Mage_Core_Controller_Front_Action
{
    const ERROR_SYNC_DISABLED = 'Shipping Sync is Disabled';
    const ERROR_API_KEY_MISSING = 'An API Key is required';
    const ERROR_API_KEY_MISMATCH = 'The API Key provided does not match the configured API Key';
    const ERROR_BAD_REQUEST = 'An invalid request was recieved';
    const ERROR_ORDER_MISSING = 'The order id requested was not found';
    const NOTICE_SHIPMENT_STATUS = 'Ignoring the order status update, as we only respond to ready_for_pickup, in_transit state';
    const ERROR_SHIPMENT_FAILED = 'The shipment record was not able to be created at this time, please try again.';
    const SUCCESS_SHIPMENT_CREATED = 'The shipment record was created successfully.';

    protected $helper;
    protected $logger;

    public function _construct()
    {
        $this->helper = Mage::helper('shippit/sync_shipping');
        $this->logger = Mage::getModel('shippit/logger');

        return parent::_construct();
    }

    public function updateAction()
    {
        if (!$this->_checkIsActive()) {
            return;
        }

        if (!$this->_checkApiKey()) {
            return;
        }

        $request = json_decode(file_get_contents('php://input'), true);

        $this->_logRequest($request);

        // Allow in transit requests to make it through, as we
        // complete further checks in "checkRequestInTransit"
        if (!$this->_checkRequest($request)) {
            return;
        }

        try {
            // Retrieve the relevant details for the order
            $orderIncrement = $this->_getOrderIncrement($request);
            $storeId = $this->_getStoreId($request);
            $courierName = $this->_getCourierName($request);
            $trackingNumber = $this->_getTrackingNumber($request);
            $products = $this->_getProducts($request);

            // Create the shipment sync request and save
            $syncShipment = Mage::getModel('shippit/sync_shipment')
                ->setOrderIncrement($request['retailer_order_number'])
                ->setStoreId($storeId)
                ->setCourierAllocation($courierName)
                ->setTrackNumber($trackingNumber)
                ->addItems($products)
                ->save();

            $response = $this->_prepareResponse(
                true,
                self::SUCCESS_SHIPMENT_CREATED
            );

            return $this->getResponse()->setBody($response);
        }
        catch (Exception $e) {
            $response = $this->_prepareResponse(false, self::ERROR_SHIPMENT_FAILED);
            $this->logger->logException($e);

            return $this->getResponse()->setBody($response);
        }
    }

    protected function _checkIsActive()
    {
        if (!$this->helper->isActive()) {
            $response = $this->_prepareResponse(
                false,
                self::ERROR_SYNC_DISABLED
            );

            $this->getResponse()->setBody($response);

            return false;
        }

        return true;
    }

    protected function _checkApiKey()
    {
        $apiKey = $this->getRequest()->getParam('api_key');

        if (empty($apiKey)) {
            $response = $this->_prepareResponse(
                false,
                self::ERROR_API_KEY_MISSING,
                Zend_Log::WARN
            );

            $this->getResponse()->setBody($response);

            return false;
        }

        $configuredApiKey = Mage::helper('shippit')->getApiKey();

        if ($configuredApiKey != $apiKey) {
            $response = $this->_prepareResponse(
                false,
                self::ERROR_API_KEY_MISMATCH,
                Zend_Log::WARN
            );

            $this->getResponse()->setBody($response);

            return false;
        }

        return true;
    }

    protected function _logRequest($request = array())
    {
        $metaData = array(
            'api_request' => array(
                'request_body' => $request
            )
        );

        $this->logger->setMetaData($metaData);
        $this->logger->log('Shipment Sync', 'Shipment Sync Request Recieved');
    }

    protected function _checkRequest($request = array())
    {
        if (empty($request)) {
            $response = $this->_prepareResponse(
                false,
                self::ERROR_BAD_REQUEST,
                Zend_Log::WARN
            );

            $this->getResponse()->setBody($response);

            return false;
        }

        if (empty($request['current_state'])
            || $request['current_state'] != 'ready_for_pickup'
        ) {
            $response = $this->_prepareResponse(
                true,
                self::NOTICE_SHIPMENT_STATUS
            );

            $this->getResponse()->setBody($response);

            return false;
        }

        if (!isset($request['retailer_order_number']) || empty($request['retailer_order_number'])) {
            $response = $this->_prepareResponse(
                false,
                self::ERROR_ORDER_MISSING
            );

            $this->getResponse()->setBody($response);

            return false;
        }

        return true;
    }

    protected function _getOrderIncrement($request = array())
    {
        if (!empty($request['retailer_order_number'])) {
            return $request['retailer_order_number'];
        }
    }

    protected function _getStoreId($request = array())
    {
        return Mage::app()->getStore()->getId();
    }

    protected function _getCourierName($request = array())
    {
        if (!empty($request['courier_name'])) {
            return $request['courier_name'];
        }
    }

    protected function _getTrackingNumber($request = array())
    {
        if (!empty($request['tracking_number'])) {
            return $request['tracking_number'];
        }

        return 'N/A';
    }

    protected function _getProducts($request = array())
    {
        if (!empty($request['products'])) {
            $products = $request['products'];

            return array_map(
                function($product) {
                    return array(
                        'sku' => $product['sku'],
                        'title' => $product['title'],
                        'qty' => $product['quantity']
                    );
                },
                $products
            );
        }

        return array();
    }

    protected function _prepareResponse($success, $message, $logLevel = Zend_Log::DEBUG)
    {
        $response = array(
            'success' => $success,
            'message' => $message,
        );

        $metaData = array(
            'api_request' => array(
                'request_body' => json_decode(file_get_contents('php://input'), true),
                'response_body' => $response
            )
        );

        if ($success) {
            $messageType = 'Shipment Sync Success';
        }
        else {
            $messageType = 'Shipment Sync Error';
        }

        $this->logger->setMetaData($metaData);
        $this->logger->log($messageType, $message, $logLevel);

        return Mage::helper('core')->jsonEncode($response);
    }
}
