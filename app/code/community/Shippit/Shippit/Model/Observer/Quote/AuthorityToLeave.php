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

class Shippit_Shippit_Model_Observer_Quote_AuthorityToLeave
{
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
}
