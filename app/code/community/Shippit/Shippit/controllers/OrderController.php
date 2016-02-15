<?php
/**
*  Shippit Pty Ltd
*
*  NOTICE OF LICENSE
*
*  This source file is subject to the terms
*  that is available through the world-wide-web at this URL:
*  http://www.shippit.com/terms
*
*  @category   Shippit
*  @copyright  Copyright (c) 2016 by Shippit Pty Ltd (http://www.shippit.com)
*  @author     Matthew Muscat <matthew@mamis.com.au>
*  @license    http://www.shippit.com/terms
*/

class Shippit_Shippit_OrderController extends Mage_Core_Controller_Front_Action
{
    const ERROR_API_KEY_MISSING = 'An API Key is required';
    const ERROR_API_KEY_MISMATCH = 'The API Key provided does not match the configured API Key';
    const ERROR_BAD_REQUEST = 'An invalid request was recieved';
    const ERROR_ORDER_MISSING = 'The order id requested was not found';
    const ERROR_ORDER_STATUS = 'The order id requested has an status that is not available for shipping';
    const NOTICE_SHIPMENT_STATUS = 'Ignoring the order status update, as we only respond to ready_for_pickup state';
    const ERROR_SHIPMENT_FAILED = 'The shipment record was not able to be created at this time, please try again.';
    const SUCCESS_SHIPMENT_CREATED = 'The shipment record was created successfully.';

    public function updateAction()
    {
        $post = json_decode(file_get_contents('php://input'));
        $apiKey = $this->getRequest()->getParam('api_key');
        $coreHelper = $coreHelper;
        $orderIncrementId = $post->retailer_order_number;
        $orderShipmentState = $post->current_state;
        $courierName = $post->courier_name;
        $trackingNumber = $post->tracking_number;

        if (empty($apiKey)) {
            $response = $this->_prepareResponse(false, self::ERROR_API_KEY_MISSING);

            return $this->getResponse()->setBody($response);
        }

        if (!$this->_checkApiKey($apiKey)) {
            $response = $this->_prepareResponse(false, self::ERROR_API_KEY_MISMATCH);

            return $this->getResponse()->setBody($response);
        }

        if (empty($post)) {
            $response = $this->_prepareResponse(false, self::ERROR_BAD_REQUEST);

            return $this->getResponse()->setBody($response);
        }

        if (empty($orderShipmentState) || $orderShipmentState != 'ready_for_pickup') {
            $response = $this->_prepareResponse(true, self::NOTICE_SHIPMENT_STATUS);

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

        $response = $this->_createShipment($order, 'Shippit - ' . $courierName, $trackingNumber);

        return $this->getResponse()->setBody($response);
    }

    private function _prepareResponse($success, $message)
    {
        return Mage::helper('core')->jsonEncode(array(
            'success' => false,
            'message' => $message,
        ));
    }

    private function _getOrder($orderIncrementId)
    {
        return Mage::getModel('sales/order')->load($orderIncrementId, 'increment_id');
    }

    private function _checkApiKey($apiKey)
    {
        $configuredApiKey = Mage::helper('shippit')->getApiKey();
        
        if ($configuredApiKey != $apiKey) {
            return false;
        }
        
        return true;
    }

    private function _createShipment($order, $courierName, $trackingNumber)
    {
        $shipment = $order->prepareShipment();

        if ($shipment) {
            $comment = 'Your order has been shipped - your tracking number is ' . $trackingNumber;

            $track = Mage::getModel('sales/order_shipment_track')
                ->setNumber($trackingNumber)
                ->setCarrierCode('shippit')
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
                return $this->_prepareResponse(false, self::ERROR_SHIPMENT_FAILED);
            }

            return $this->_prepareResponse(true, self::SUCCESS_SHIPMENT_CREATED);
        }

        return $this->_prepareResponse(false, self::ERROR_SHIPMENT_FAILED);
    }
}