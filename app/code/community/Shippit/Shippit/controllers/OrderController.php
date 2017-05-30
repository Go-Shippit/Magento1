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
    const ERROR_ORDER_STATUS = 'The order id requested has an status that is not available for shipping';
    const NOTICE_SHIPMENT_STATUS = 'Ignoring the order status update, as we only respond to ready_for_pickup, in_transit state';
    const NOTICE_SHIPMENT_STATUS_INTRANSIT_PARTIAL = 'Ignoring the order status update, as we only respond to in_transit when the order has not yet had any shipments';
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

        // attempt to retrieve request data values for the shipment
        $order = $this->_getOrder($request);

        if (!$this->_checkRequestOrderInTransit($request, $order)) {
            return;
        }

        $products = $this->_getProducts($request);
        $courierName = $this->_getCourierName($request);
        $trackingNumber = $this->_getTrackingNumber($request);

        if (!$this->_checkOrder($order)) {
            return;
        }

        try {
            $shipmentRequest = Mage::getModel('shippit/request_api_shipment')
                ->setOrder($order)
                ->processItems($products);

            // create the shipment
            $response = $this->_createShipment(
                $shipmentRequest->getOrder(),
                $shipmentRequest->getItems(),
                $courierName,
                $trackingNumber
            );

            return $this->getResponse()->setBody($response);
        }
        catch (Exception $e)
        {
            $response = $this->_prepareResponse(false, $e->getMessage());
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

        if (!isset($request['current_state'])
            || empty($request['current_state'])
            || (
                $request['current_state'] != 'ready_for_pickup'
                && $request['current_state'] != 'in_transit'
            )
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

    protected function _checkRequestOrderInTransit($request, $order)
    {
        // Don't allow requests that are "in_transit"
        // to be accepted when an order has 1 or more shipments
        if ($request['current_state'] == 'in_transit'
            && $order->hasShipments()) {
            $response = $this->_prepareResponse(
                true,
                self::NOTICE_SHIPMENT_STATUS_INTRANSIT_PARTIAL
            );

            $this->getResponse()->setBody($response);

            return false;
        }

        return true;
    }

    protected function _checkOrder($order)
    {
        if (!$order->getId()) {
            $response = $this->_prepareResponse(
                false,
                self::ERROR_ORDER_MISSING
            );

            $this->getResponse()->setBody($response);

            return false;
        }

        if (!$order->canShip()) {
            $response = $this->_prepareResponse(
                false,
                self::ERROR_ORDER_STATUS
            );

            $this->getResponse()->setBody($response);

            return false;
        }

        return true;
    }

    protected function _getOrder($request = array())
    {
        if (!isset($request['retailer_order_number'])) {
            return false;
        }

        $orderIncrementId = $request['retailer_order_number'];

        return Mage::getModel('sales/order')->load($orderIncrementId, 'increment_id');
    }

    protected function _getProducts($request = array())
    {
        if (isset($request['products'])) {
            return $request['products'];
        }

        return array();
    }

    protected function _getCourierName($request = array())
    {
        if (isset($request['courier_name'])) {
            return 'Shippit - ' . $request['courier_name'];
        }
        else {
            return 'Shippit';
        }
    }

    protected function _getTrackingNumber($request = array())
    {
        if (isset($request['tracking_number'])) {
            return $request['tracking_number'];
        }
        else {
            return 'N/A';
        }
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

    protected function _createShipment($order, $items, $courierName, $trackingNumber)
    {
        $shipment = $order->prepareShipment($items);

        $shipment = Mage::getModel('sales/service_order', $order)
            ->prepareShipment($items);

        if ($shipment) {
            $comment = 'Your order has been shipped - your tracking number is ' . $trackingNumber;

            $track = Mage::getModel('sales/order_shipment_track')
                ->setNumber($trackingNumber)
                ->setCarrierCode(Shippit_Shippit_Helper_Data::CARRIER_CODE)
                ->setTitle($courierName);

            $shipment->addTrack($track)
                ->register()
                ->addComment($comment, true)
                ->setEmailSent(true);

            $shipment->getOrder()->setIsInProcess(true);

            try {
                $transactionSave = Mage::getModel('core/resource_transaction')
                    ->addObject($shipment)
                    ->addObject($shipment->getOrder())
                    ->save();

                $shipment->sendEmail(true, $comment);
            }
            catch (Mage_Core_Exception $e) {
                return $this->_prepareResponse(
                    false,
                    self::ERROR_SHIPMENT_FAILED
                );
            }

            return $this->_prepareResponse(
                true,
                self::SUCCESS_SHIPMENT_CREATED
            );
        }

        return $this->_prepareResponse(
            false,
            self::ERROR_SHIPMENT_FAILED
        );
    }
}
