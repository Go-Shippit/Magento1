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

class Mamis_Shippit_Model_Sales_Order_Observer
{
    const CARRIER_CODE = 'mamis_shippit';
    
    /**
     * Set the order to be synced with shippit
     * @param Varien_Event_Observer $observer [description]
     */
    public function addOrderToSync(Varien_Event_Observer $observer)
    {
        $helper = Mage::helper('mamis_shippit');

        // Ensure the module is active
        if (!$helper->isActive()) {
            return $this;
        }

        $order = $observer->getEvent()->getOrder();

        // Ensure we have an order
        if (!$order) {
            return $this;
        }

        $shippingMethod = $order->getShippingMethod();
        $shippingCountry = $order->getShippingAddress()->getCountryId();

        // If send all orders + au delivery, or shippit method is selected
        if (($helper->isSendAllOrdersActive() && $shippingCountry == 'AU')
            || strpos($shippingMethod, $this::CARRIER_CODE) !== FALSE) {
            // trigger the order to be synced on the next cron run
            $order->setShippitSync(false);
            $order->save();
        }

        return $this;
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
            Mage::helper('mamis_shippit')->getDeliveryInstructionsId()
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

        $quote->setShippitDeliveryInstructions($deliveryInstructions)
            ->save();
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
            Mage::helper('mamis_shippit')->getAuthorityToLeaveId()
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
        $quote->setShippitAuthorityToLeave($authorityToLeave)
            ->save();
    }
}