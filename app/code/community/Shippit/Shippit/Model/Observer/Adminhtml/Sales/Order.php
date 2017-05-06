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

class Shippit_Shippit_Model_Observer_Adminhtml_Sales_Order
{
    public function addShippitButton(Varien_Event_Observer $observer)
    {
        $block = $observer->getEvent()->getBlock();

        if ($block instanceof Mage_Adminhtml_Block_Sales_Order_View
            && $this->_isAllowed()
            && Mage::helper('shippit/sync_order')->isManualSyncActive()) {
            $block->addButton(
                'shippit_send_order',
                array(
                    'label' => Mage::helper('shippit/sync_order')->__('Send to Shippit'),
                    'onclick' => "setLocation('{$block->getUrl('*/shippit_order_sync/send')}')",
                    'class' => 'go'
                )
            );
        }
    }

    public function addShippitMassAction(Varien_Event_Observer $observer)
    {
        $block = $observer->getEvent()->getBlock();

        if ($block->getRequest()->getControllerName() == 'sales_order'
            && $block instanceof Mage_Adminhtml_Block_Widget_Grid_Massaction
            && $this->_isAllowed()
            && Mage::helper('shippit/sync_order')->isManualSyncActive()) {
            $block->addItem(
                'shippit_send_orders',
                array(
                    'label' => Mage::helper('shippit/sync_order')->__('Send to Shippit'),
                    'url' => $block->getUrl('*/shippit_order_sync/massSend'),
                )
            );
        }
    }

    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('sales/order/actions/shippit_order_send');
    }
}
