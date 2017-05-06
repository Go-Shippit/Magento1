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

class Shippit_Shippit_Block_Checkout_Shipping_AuthorityToLeave extends Mage_Core_Block_Template
{
    protected $helper;

    public function __construct()
    {
        $this->helper = Mage::helper('shippit/checkout');

        parent::__construct();
    }

    /**
     * Get label text for the authority to leave field
     *
     * @return string
     */
    public function getLabelText()
    {
        return Mage::helper('shippit')->__('Authority to Leave without Signature');
    }

    /**
     * Get field id
     *
     * @return string
     */
    public function getFieldId()
    {
        return Mage::helper('shippit')->getAuthorityToLeaveId();
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

    /**
     * Render Authority to Leave Block
     *
     * @return string
     */
    protected function _toHtml()
    {
        if (!$this->helper->isAuthorityToLeaveActive()) {
            return '';
        }

        return parent::_toHtml();
    }
}
