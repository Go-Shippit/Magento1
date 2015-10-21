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

class Mamis_Shippit_Model_Sales_Order_Sync extends Mage_Core_Model_Abstract
{
    const CARRIER_CODE = 'mamis_shippit';

    protected $api;
    protected $helper;
    protected $bugsnag;

    public function __construct()
    {
        $this->helper = Mage::helper('mamis_shippit');
        $this->api = Mage::helper('mamis_shippit/api');

        if ($this->helper->isDebugActive()) {
            $this->bugsnag = Mage::helper('mamis_shippit/bugsnag')->init();
        }
    }

    public function run()
    {
        $orders = $this->getOrders();

        foreach ($orders as $order) {
            $this->syncOrder($order);
        }
    }

    public function getOrders()
    {
        $orders = Mage::getModel('sales/order')
            ->getCollection()
            ->addFieldToFilter('state', Mage_Sales_Model_Order::STATE_PROCESSING)
            ->addAttributeToFilter('shippit_sync', array('eq' => false));

        return $orders;
    }
    
    public function syncOrder($order)
    {
        $orderData = new Varien_Object;

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
            $order->setShippitSync(true);

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

            Mage::log($e->getMessage());
        
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
        $parcelsAttributes = array();

        foreach ($items as $item) {
            // Skip special product types
            if ($item->getProduct()->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE
                || $item->getProduct()->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_BUNDLE
                || $item->getProduct()->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_GROUPED
                || $item->getProduct()->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_VIRTUAL) {
                continue;
            }

            $parcelsAttributes[] = array(
                'qty' => $item->getQtyOrdered(),
                'weight' => $item->getWeight()
            );
        }

        $orderData->setParcelAttributes($parcelsAttributes);

        return $orderData;
    }
}