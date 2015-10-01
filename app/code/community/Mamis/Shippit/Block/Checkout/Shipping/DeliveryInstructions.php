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

class Mamis_Shippit_Block_Checkout_Shipping_DeliveryInstructions extends Mage_Core_Block_Template
{
    /**
     * Get label text for the field
     *
     * @return string
     */
    public function getLabelText()
    {
        return Mage::helper('mamis_shippit')->__('Delivery Instructions');
    }

    /**
     * Get field id
     *
     * @return string
     */
    public function getFieldId()
    {
        return Mage::helper('mamis_shippit')->getDeliveryInstructionsId();
    }

    /**
     * Get the quote object from the session
     * 
     * @return [Mage_Sales_Model_Quote] The Quote object
     */
    private function _getQuote()
    {
        return Mage::getSingleton('checkout/session')->getQuote();
    }

    /**
     * Get the value of the field
     * 
     * @return [string] The field value
     */
    public function getValue()
    {
        $quote = $this->_getQuote();

        return $quote->getShippitDeliveryInstructions();
    }
}