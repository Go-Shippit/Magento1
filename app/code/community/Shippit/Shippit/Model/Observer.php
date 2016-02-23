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

class Shippit_Shippit_Model_Observer
{
    // Prevents recursive requests to sync
    private $_hasAttemptedSync = false;

    const CARRIER_CODE = 'shippit';
    
    /**
     * Set the order to be synced with shippit
     * @param Varien_Event_Observer $observer [description]
     */
    public function addOrderToSync(Varien_Event_Observer $observer)
    {
        $helper = Mage::helper('shippit');

        // Ensure the module is active
        if (!$helper->isActive()) {
            return $this;
        }

        $order = $observer->getEvent()->getOrder();

        // Ensure we have an order
        if (!$order || !$order->getId()) {
            return $this;
        }

        $shippingMethod = $order->getShippingMethod();
        $shippingCountry = $order->getShippingAddress()->getCountryId();

        // If send all orders + au delivery, or shippit method is selected
        if (($helper->isSendAllOrdersActive() && $shippingCountry == 'AU')
            || strpos($shippingMethod, $this::CARRIER_CODE) !== FALSE) {
            // trigger the order to be synced on the next cron run
            Mage::getModel('shippit/order_sync')->addOrder($order);

            // If the sync mode is realtime, attempt realtime sync now
            if ($helper->getSyncMode() == $helper::SYNC_MODE_REALTIME) {
                $this->_syncOrder($order);
            }
        }

        return $this;
    }

    private function _syncOrder($order)
    {
        if (!$this->_hasAttemptedSync
            && $order->getState() == Mage_Sales_Model_Order::STATE_PROCESSING) {
            // Check the sync state of the order
            $syncItem = Mage::getModel('shippit/order_sync')
                ->load($order->getEntityId(), 'order_id');

            // Only sync if the syncitem is pending
            if ($syncItem->getStatus() == Shippit_Shippit_Model_Order_Sync::STATUS_PENDING) {
                // Prevents recursive requests to sync
                $this->_hasAttemptedSync = true;

                // Sync the order
                return Mage::getModel('shippit/sync_order')->syncOrder($order);
            }
            else {
                return false;
            }
        }
        else {
            return false;
        }
    }

    /**
     * Check if customer delivery instructions should be added to quote.
     *
     * @param Varien_Event_Observer $observer
     * @return $this
     */
    public function addDeliveryInstructionsToQuote(Varien_Event_Observer $observer)
    {
        /** @var Mage_Core_Controller_Request_Http $request */
        $request = Mage::app()->getRequest();

        /** @var Mage_Sales_Model_Quote $quote */
        $quote = $observer->getEvent()->getQuote();

        $deliveryInstructions = $request->getParam(
            Mage::helper('shippit')->getDeliveryInstructionsId()
        );

        $this->_addDeliveryInstructionsToQuote($quote, $deliveryInstructions);

        return $this;
    }

    /**
     * Add's the order delivery instructions to the quote data.
     *
     * @param Mage_Sales_Model_Quote $quote
     * @param string $promiseDate
     * @return $this
     */
    protected function _addDeliveryInstructionsToQuote($quote, $deliveryInstructions)
    {
        $deliveryInstructions = strip_tags($deliveryInstructions);
        $quote->setShippitDeliveryInstructions($deliveryInstructions);
    }

    /**
     * Check if authority to leave should be added to quote.
     *
     * @param Varien_Event_Observer $observer
     * @return $this
     */
    public function addAuthorityToLeaveToQuote(Varien_Event_Observer $observer)
    {
        /** @var Mage_Core_Controller_Request_Http $request */
        $request = Mage::app()->getRequest();

        /** @var Mage_Sales_Model_Quote $quote */
        $quote = $observer->getEvent()->getQuote();

        $authorityToLeave = $request->getParam(
            Mage::helper('shippit')->getAuthorityToLeaveId()
        );

        if (isset($authorityToLeave)) {
            $this->_addAuthorityToLeaveToQuote($quote, true);
        }
        else {
            $this->_addAuthorityToLeaveToQuote($quote, false);
        }

        return $this;
    }

    /**
     * Add's the authority to leave to the quote data.
     *
     * @param Mage_Sales_Model_Quote $quote
     * @param string $promiseDate
     * @return $this
     */
    protected function _addAuthorityToLeaveToQuote($quote, $authorityToLeave)
    {
        $quote->setShippitAuthorityToLeave($authorityToLeave);
    }

    /**
     * Updates the order shipment email template to include
     * the tracking link in the tracking details
     *
     * @param  Varien_Event_Observer $observer
     */
    public function updateOrderShipmentTrackTemplate(Varien_Event_Observer $observer)
    {
        $block = $observer->getBlock();

        if ($block->getTemplate() == 'email/order/shipment/track.phtml') {
            $block->setTemplate('shippit/email/order/shipment/track.phtml');
        }
    }
}