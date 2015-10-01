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

class Mamis_Shippit_Block_Checkout_Shipping_AuthorityToLeave extends Mage_Core_Block_Template
{
    /**
     * Get label text for the authority to leave field
     *
     * @return string
     */
    public function getLabelText()
    {
        return Mage::helper('mamis_shippit')->__('Authority to Leave without Signature');
    }

    /**
     * Get field id
     *
     * @return string
     */
    public function getFieldId()
    {
        return Mage::helper('mamis_shippit')->getAuthorityToLeaveId();
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
     * Get the value of the authority to leave
     * 
     * @return [boolean] True or false
     */
    public function getValue()
    {
        $quote = $this->_getQuote();

        if ($quote->getShippitAuthorityToLeave()) {
            return true;
        }
        else {
            return false;
        }
    }
}