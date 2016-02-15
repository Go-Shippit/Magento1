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

class Shippit_Shippit_Model_Sync_Order extends Mage_Core_Model_Abstract
{
    const CARRIER_CODE = 'shippit';

    protected $api;
    protected $helper;
    protected $bugsnag;

    public function __construct()
    {
        $this->helper = Mage::helper('shippit');
        $this->api = Mage::helper('shippit/api');

        if ($this->helper->isDebugActive()) {
            $this->bugsnag = Mage::helper('shippit/bugsnag')->init();
        }
    }

    public function run()
    {
        $orders = $this->getOrders();

        foreach ($orders as $order) {
            $this->syncOrder($order);
        }
    }

    public function getSyncItems()
    {
        return Mage::getModel('shippit/order_sync')
            ->getCollection()
            ->addFieldToFilter('status', Shippit_Shippit_Model_Order_Sync::STATUS_PENDING)
            ->addFieldToFilter('attempt_count', array('lt' => Shippit_Shippit_Model_Order_Sync::SYNC_MAX_ATTEMPTS));
    }

    public function getOrders()
    {
        // get a list of all pending sync in the sync queue
        $syncQueueOrderIds = $this->getSyncItems()
            ->getAllOrderIds();

        if (empty($syncQueueOrderIds)) {
            return array();
        }

        $orders = Mage::getModel('sales/order')
            ->getCollection()
            ->addFieldToFilter('state', Mage_Sales_Model_Order::STATE_PROCESSING)
            ->addAttributeToFilter('entity_id', array('in' => $syncQueueOrderIds));

        return $orders;
    }
    
    public function syncOrder($order, $displayNotifications = false)
    {
        $orderData = new Varien_Object;

        // Load the sync item from the queue
        $syncItem = Mage::getModel('shippit/order_sync')
            ->load($order->getEntityId(), 'order_id');
        // increase the sync item attempt count
        $syncItem->setAttemptCount( $syncItem->getAttemptCount() + 1 );

        $this->_setRetailerInvoice($orderData, $order);
        $this->_setAuthorityToLeave($orderData, $order);
        $this->_setDeliveryInstructions($orderData, $order);
        $this->_setUserAttributes($orderData, $order);
        $this->_setCourierType($orderData, $order);
        $this->_setReceiver($orderData, $order);
        $this->_setDeliveryAddress($orderData, $order);
        $this->_setParcelsAttributes($orderData, $order);

        try {
            $apiResponse = $this->api->sendOrder($orderData);

            // Update the order to be marked as synced
            $syncItem->setStatus(Shippit_Shippit_Model_Order_Sync::STATUS_SYNCED)
                ->setTrackNumber($apiResponse->tracking_number)
                ->setSyncedAt(Varien_Date::now())
                ->save();

            if ($displayNotifications) {
                Mage::getSingleton('adminhtml/session')
                    ->addSuccess(
                        $this->helper->__('Order ' . $order->getIncrementId() . ' Synced with Shippit - ' . $apiResponse->tracking_number)
                    );
            }

            // Add the order tracking details
            $comment = $this->helper->__('Order Synced with Shippit - ' . $apiResponse->tracking_number);
            $order->addStatusHistoryComment($comment)
                ->setIsVisibleOnFront(false);
                
            $order->save();
        }
        catch (Exception $e) {
            if ($this->helper->isDebugActive() && $this->bugsnag) {
                $this->bugsnag->notifyError('API - Order Sync Request', $e->getMessage());
            }

            // Fail the sync item if it's breached the max attempts
            if ($syncItem->getAttemptCount() > Shippit_Shippit_Model_Order_Sync::SYNC_MAX_ATTEMPTS) {
                $syncItem->setStatus(Shippit_Shippit_Model_Order_Sync::STATUS_FAILED);
            }

            // save the sync item attempt count
            $syncItem->save();

            if ($displayNotifications) {
                Mage::getSingleton('adminhtml/session')
                    ->addError(
                        $this->helper->__('Order ' . $order->getIncrementId() . ' was not Synced with Shippit - ' . $e->getMessage())
                    );
            }

            Mage::log($e->getMessage(), null, 'shippit.log');
        
            return false;
        }

        return true;
    }

    private function _setRetailerInvoice(&$orderData, &$order)
    {
        $orderData->setRetailerInvoice(
            $order->getIncrementId()
        );

        return $orderData;
    }

    private function _setAuthorityToLeave(&$orderData, &$order)
    {
        if ($order->getShippitAuthorityToLeave()) {
            $authorityToLeave = 'Yes';
        }
        else {
            $authorityToLeave = 'No';
        }

        $orderData->setAuthorityToLeave($authorityToLeave);

        return $orderData;
    }

    private function _setDeliveryInstructions(&$orderData, &$order)
    {
        $orderData->setDeliveryInstructions(
            $order->getShippitDeliveryInstructions()
        );

        return $orderData;
    }

    private function _setCourierType(&$orderData, &$order)
    {
        $shippingMethod = $order->getShippingMethod();

        // If the shipping method is a shippit method,
        // processing using the selected shipping options
        if (strpos($shippingMethod, $this::CARRIER_CODE) !== FALSE) {
            $shippingOptions = str_replace($this::CARRIER_CODE . '_', '', $shippingMethod);
            $shippingOptions = explode('_', $shippingOptions);
            $courierData = array();
            
            if (isset($shippingOptions[0])) {
                if ($shippingOptions[0] == 'Bonds') {
                    $orderData->setCourierType( $shippingOptions[0] )
                        ->setDeliveryDate( $shippingOptions[1] )
                        ->setDeliveryWindow( $shippingOptions[2] );
                }
                else {
                    $orderData->setCourierType( $shippingOptions[0] );
                }
            }
        }
        // Otherwise, use the default "CouriersPlease" courier type
        else {
            $orderData->setCourierType('CouriersPlease');
        }

        return $orderData;
    }

    private function _setReceiver(&$orderData, &$order)
    {
        $shippingAddress = $order->getShippingAddress();

        $orderData->setReceiverName( $shippingAddress->getName() )
            ->setReceiverContactNumber( $shippingAddress->getTelephone() );

        return $orderData;
    }

    private function _setDeliveryAddress(&$orderData, &$order)
    {
        $shippingAddress = $order->getShippingAddress();

        $orderData->setDeliveryAddress( $shippingAddress->getStreetFull() )
            ->setDeliverySuburb( $shippingAddress->getCity() )
            ->setDeliveryPostcode( $shippingAddress->getPostcode() )
            ->setDeliveryState( $shippingAddress->getRegionCode() );

        $regionCode = $shippingAddress->getRegionCode();

        if (empty($regionCode)) {
            // attempt to use fallback mechanism
            $regionCodeFallback = $this->helper->getRegionCodeFromPostcode($shippingAddress->getPostcode());

            if ($regionCodeFallback) {
                $orderData->setDeliveryState($regionCodeFallback);
            }
        }

        return $orderData;
    }

    private function _setUserAttributes(&$orderData, &$order)
    {
        $userAttributes = array(
            'email' => $order->getCustomerEmail(),
            'first_name' => $order->getCustomerFirstname(),
            'last_name' => $order->getCustomerLastname(),
        );

        $orderData->setUserAttributes($userAttributes);

        return $orderData;
    }

    private function _setParcelsAttributes(&$orderData, &$order)
    {
        $items = $order->getAllItems();
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
                'qty' => $item->getQtyOrdered(),
                'weight' => $item->getWeight()
            );
        }

        $orderData->setParcelAttributes($parcelAttributes);

        return $orderData;
    }
}