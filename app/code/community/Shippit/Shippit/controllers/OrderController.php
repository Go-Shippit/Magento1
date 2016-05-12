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

class Shippit_Shippit_OrderController extends Mage_Core_Controller_Front_Action
{
    const ERROR_SYNC_DISABLED = 'Shipping Sync is Disabled';
    const ERROR_API_KEY_MISSING = 'An API Key is required';
    const ERROR_API_KEY_MISMATCH = 'The API Key provided does not match the configured API Key';
    const ERROR_BAD_REQUEST = 'An invalid request was recieved';
    const ERROR_ORDER_MISSING = 'The order id requested was not found';
    const ERROR_ORDER_STATUS = 'The order id requested has an status that is not available for shipping';
    const NOTICE_SHIPMENT_STATUS = 'Ignoring the order status update, as we only respond to ready_for_pickup state';
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
        $request = json_decode(file_get_contents('php://input'), true);

        $metaData = array(
            'api_request' => array(
                'request_body' => $request
            )
        );

        $this->logger->setMetaData($metaData);
        $this->logger->log('Shipment Sync Request Recieved');

        if (!$this->helper->isActive()) {
            $this->logger->log('Shipping Sync is not active');
            $response = $this->_prepareResponse(false, self::ERROR_SYNC_DISABLED);

            return $this->getResponse()->setBody($response);
        }

        $apiKey = $this->getRequest()->getParam('api_key');
        $orderIncrementId = $request['retailer_order_number'];
        $orderShipmentState = $request['current_state'];

        $courierName = $request['courier_name'];
        $trackingNumber = $request['tracking_number'];

        if (isset($request['products'])) {
            $products = $request['products'];
        }
        else {
            $products = array();
        }

        if (empty($apiKey)) {
            $response = $this->_prepareResponse(false, self::ERROR_API_KEY_MISSING);
            $this->logger->log('Shipment Sync Error - ' . self::ERROR_API_KEY_MISSING, Zend_Log::WARN);

            return $this->getResponse()->setBody($response);
        }

        if (!$this->_checkApiKey($apiKey)) {
            $response = $this->_prepareResponse(false, self::ERROR_API_KEY_MISMATCH);
            $this->logger->log('Shipment Sync Error - ' . self::ERROR_API_KEY_MISMATCH, Zend_Log::WARN);

            return $this->getResponse()->setBody($response);
        }

        if (empty($request)) {
            $response = $this->_prepareResponse(false, self::ERROR_BAD_REQUEST);
            $this->logger->log('Shipment Sync Error - ' . self::ERROR_BAD_REQUEST, Zend_Log::WARN);

            return $this->getResponse()->setBody($response);
        }

        if (empty($orderShipmentState) || $orderShipmentState != 'ready_for_pickup') {
            $response = $this->_prepareResponse(true, self::NOTICE_SHIPMENT_STATUS);
            $this->logger->log('Shipment Sync Error - ' . self::NOTICE_SHIPMENT_STATUS);

            return $this->getResponse()->setBody($response);
        }

        // attempt to get the order using the reference provided
        $order = $this->_getOrder($orderIncrementId);

        if (!$order->getId()) {
            $response = $this->_prepareResponse(false, self::ERROR_ORDER_MISSING);

            return $this->getResponse()->setBody($response);
        }

        if (!$order->canShip()) {
            $response = $this->_prepareResponse(false, self::ERROR_ORDER_STATUS);

            return $this->getResponse()->setBody($response);
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

    private function _prepareResponse($success, $message)
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

        $this->logger->setMetaData($metaData);

        return Mage::helper('core')->jsonEncode($response);
    }

    private function _checkApiKey($apiKey)
    {
        $configuredApiKey = Mage::helper('shippit')->getApiKey();
        
        if ($configuredApiKey != $apiKey) {
            return false;
        }
        
        return true;
    }

    private function _getOrder($orderIncrementId)
    {
        return Mage::getModel('sales/order')->load($orderIncrementId, 'increment_id');
    }

    private function _createShipment($order, $items, $courierName, $trackingNumber)
    {
        $shipment = $order->prepareShipment($items);

        $shipment = Mage::getModel('sales/service_order', $order)
            ->prepareShipment($items);

        if ($shipment) {
            $comment = 'Your order has been shipped - your tracking number is ' . $trackingNumber;

            $track = Mage::getModel('sales/order_shipment_track')
                ->setNumber($trackingNumber)
                ->setCarrierCode(Shippit_Shippit_Helper_Data::CARRIER_CODE)
                ->setTitle('Shippit - ' . $courierName);

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
                $this->logger->log('Shipment Sync Error - ' . self::ERROR_SHIPMENT_FAILED);

                return $this->_prepareResponse(false, self::ERROR_SHIPMENT_FAILED);
            }

            $this->logger->log('Shipment Sync Successful - ' . self::SUCCESS_SHIPMENT_CREATED);

            return $this->_prepareResponse(true, self::SUCCESS_SHIPMENT_CREATED);
        }

        $this->logger->log('Shipment Sync Error - ' . self::ERROR_SHIPMENT_FAILED);

        return $this->_prepareResponse(false, self::ERROR_SHIPMENT_FAILED);
    }
}