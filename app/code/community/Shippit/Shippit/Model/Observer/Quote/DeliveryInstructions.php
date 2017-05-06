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

class Shippit_Shippit_Model_Observer_Quote_DeliveryInstructions
{
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
}
