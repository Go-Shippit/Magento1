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
 * @copyright  Copyright (c) 2016 by Shippit Pty Ltd (http://www.shippit.com)
 * @author     Matthew Muscat <matthew@mamis.com.au>
 * @license    http://www.shippit.com/terms
 */

class Shippit_Shippit_Adminhtml_Shippit_Order_SyncController extends Mage_Adminhtml_Controller_Action
{
    public function sendAction()
    {
        $orderId = $this->getRequest()->getParam('order_id', null);

        if (empty($orderId)) {
            $this->_getSession()->addError($this->__('The order could not be found'));
            $this->_redirect('adminhtml/sales_order');

            return;
        }

        $order = Mage::getModel('sales/order')->load($orderId);

        if (!$order) {
            $this->_getSession()->addError($this->__('The order could not be found'));
            $this->_redirect('adminhtml/sales_order');

            return;
        }

        try {
            Mage::dispatchEvent(
                'shippit_add_order',
                array(
                    'entity_id' => $orderId,
                    'sync_mode' => 'realtime',
                    'display_notifications' => true,
                    'shipping_method' => $order->getShippingMethod()
                )
            );

            $this->_redirect(
                'adminhtml/sales_order/view',
                array(
                    'order_id' => $orderId
                )
            );

            return;
        }
        catch (Exception $e) {
            $this->_getSession()->addError($this->__('An error occured while send the order to Shippit') . ' - ' . $e->getMessage());

            $this->_redirect(
                'adminhtml/sales_order/view',
                array(
                    'order_id' => $orderId
                )
            );

            return;
        }
    }

    public function massSendAction()
    {
        $orderIds = $this->getRequest()->getParam('order_ids', null);

        if (empty($orderIds)) {
            $this->_getSession()->addError($this->__('The orders could not be found'));
            $this->_redirect('adminhtml/sales_order');

            return;
        }

        $orders = Mage::getModel('sales/order')
            ->getCollection()
            ->addAttributeToSelect('entity_id')
            ->addAttributeToSelect('shipping_method')
            ->addAttributeToFilter('entity_id', array('in' => $orderIds));

        try {
            foreach ($orders as $order) {
                Mage::dispatchEvent(
                    'shippit_add_order',
                    array(
                        'entity_id' => $order->getId(),
                        'shipping_method' => $order->getShippingMethod()
                    )
                );
            }

            $this->_getSession()->addSuccess($this->__('The orders have been scheduled to sync with Shippit'));

            $this->_redirect('adminhtml/sales_order');

            return;
        }
        catch (Exception $e) {
            $this->_getSession()->addError($this->__('An error occured while scheduling the orders to sync with Shippit') . ' - ' . $e->getMessage());

            $this->_redirect('adminhtml/sales_order');

            return;
        }
    }

    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('sales/order/actions/shippit_order_send');
    }
}
