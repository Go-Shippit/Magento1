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

class Shippit_Shippit_Model_Observer_Shipping_Tracking
{
    /**
     * Updates the order shipment email template to include
     * the tracking link in the tracking details
     *
     * @param  Varien_Event_Observer $observer
     */
    public function updateOrderShipmentTrackTemplate(Varien_Event_Observer $observer)
    {
        $block = $observer->getBlock();

        if ($block->getTemplate() == 'email/order/shipment/track.phtml'
            && Mage::helper('shippit/sync_shipping')->isUpdateTemplateActive()) {
            $block->setTemplate('shippit/email/order/shipment/track.phtml');
        }
    }
}
