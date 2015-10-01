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
    public $api;

    public function __construct()
    {
        $this->api = Mage::helper('mamis_shippit/api');
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
    
        // Mage::log($orderData->toArray());
        $this->api->sendOrder($orderData);
    }

    private function _setRetailerInvoice(&$orderData, &$order)
    {
        $orderData->setRetailerInvoice(
            $order->getIncrementId()
        );
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
    }

    private function _setDeliveryInstructions(&$orderData, &$order)
    {
        $orderData->setDeliveryInstructions(
            $order->getShippitDeliveryInstructions()
        );
    }

    private function _setCourierType(&$orderData, &$order)
    {
        $shippingMethod = $order->getShippingDescription();
        $shippingMethodSegments = explode(' - ', $shippingMethod);
        $courierData = array();
        
        if (isset($shippingMethodSegments[0])) {
            if ($shippingMethodSegments[0] == 'Bonds') {
                // convert the delivery date to the format required by the order api
                $deliveryDate = new Zend_Date(
                    strtotime($shippingMethodSegments[1])
                );

                $orderData->setCourierType( $shippingMethodSegments[0] )
                    ->setDeliveryDate( $deliveryDate->toString('dd/MM/YYYY') )
                    ->setDeliveryWindow( $shippingMethodSegments[2] );
            }
            else {
                $orderData->setCourierType( $shippingMethodSegments[0] );
            }
        }
    }

    private function _setReceiver(&$orderData, &$order)
    {
        $shippingAddress = $order->getShippingAddress();

        $orderData->setReceiverName( $shippingAddress->getName() )
            ->setReceiverContactNumber( $shippingAddress->getTelephone() );
    }

    private function _setDeliveryAddress(&$orderData, &$order)
    {
        $shippingAddress = $order->getShippingAddress();

        $orderData->setDeliveryAddress( $shippingAddress->getStreetFull() )
            ->setDeliverySuburb( $shippingAddress->getCity() )
            ->setDeliveryPostcode( $shippingAddress->getPostcode() )
            ->setDeliveryState( $shippingAddress->getRegionCode() );
    }

    private function _setUserAttributes(&$orderData, &$order)
    {
        $userAttributes = array(
            'email' => $order->getCustomerEmail(),
            'first_name' => $order->getCustomerFirstname(),
            'last_name' => $order->getCustomerLastname(),
        );

        $orderData->setUserAttributes($userAttributes);
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
    }
}