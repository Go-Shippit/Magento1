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

class Shippit_Shippit_Model_Observer_Adminhtml_Order_Sync
{
    /**
     * Set the order to be synced with shippit
     * @param Varien_Event_Observer $observer [description]
     */
    public function addOrder(Varien_Event_Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();

        // Get emulation model
        $appEmulation = Mage::getSingleton('core/app_emulation');

        // Start Store Emulation
        $environment = $appEmulation->startEnvironmentEmulation($order->getStoreId());

        Mage::getModel('shippit/observer_order_sync')->addOrder($observer);

        // Stop Store Emulation
        $appEmulation->stopEnvironmentEmulation($environment);

        return $this;
    }
}
