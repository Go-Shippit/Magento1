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

class Shippit_Shippit_Adminhtml_Shippit_OrderController extends Mage_Adminhtml_Controller_Action
{
    public function indexAction()
    {
        $this->_title($this->__('Sales'))->_title($this->__('Shippit Orders'));

        $this->loadLayout();
        $this->_setActiveMenu('sales/shippit_orders');

        $this->_addContent($this->getLayout()->createBlock('shippit/adminhtml_sales_order'));
        $this->renderLayout();
    }
 
    public function gridAction()
    {
        $this->loadLayout(false);
        $this->renderLayout();
    }

    public function massSyncAction()
    {
        $syncIds = $this->getRequest()->getPost('sync_ids', array());
        $this->_syncOrders($syncIds);
    }

    public function syncAction()
    {
        $syncId = $this->getRequest()->getParam('id', null);

        if (empty($syncId)) {
            $this->_getSession()->addError($this->__('You must select at least 1 order to sync'));

            $this->_redirect('*/*/index');

            return;
        }

        $this->_syncOrders(array($syncId));
    }

    private function _syncOrders($syncIds)
    {
        if (empty($syncIds)) {
            $this->_getSession()->addError($this->__('You must select at least 1 order to sync'));

            $this->_redirect('*/*/index');
            
            return;
        }

        // get a list of all pending sync in the sync queue
        $syncOrders = Mage::getModel('shippit/sync_order')
            ->getCollection()
            ->addFieldToFilter('sync_id', array('in', $syncIds));

        if ($syncOrders->getSize() == 0) {
            $this->_getSession()->addError($this->__('No valid orders were found'));

            $this->_redirect('*/*/index');

            return;
        }

        $apiOrder = Mage::getSingleton('shippit/api_order');

        // reset the status of all items and attempts
        foreach ($syncOrders as $syncOrder) {
            $syncOrder->setStatus(Shippit_Shippit_Model_Sync_Order::STATUS_PENDING)
                ->setTrackNumber(null)
                ->setSyncedAt(null)
                ->save();
        }

        foreach ($syncOrders as $syncOrder) {
            $apiOrder->sync($syncOrder, true);
        }

        $this->_redirect('*/*/index');

        return;
    }

    public function removeAction()
    {
        $syncId = $this->getRequest()->getParam('id', null);

        if (empty($syncId)) {
            $this->_getSession()->addError($this->__('You must select at least 1 order to remove'));

            $this->_redirect('*/*/index');

            return;
        }

        $this->_removeItems(array($syncId));
    }

    public function massRemoveAction()
    {
        $syncIds = $this->getRequest()->getPost('sync_ids', null);

        $this->_removeItems($syncIds);
    }

    private function _removeItems($syncIds)
    {
        if (empty($syncIds)) {
            $this->_getSession()->addError($this->__('You must select at least 1 order to remove'));

            $this->_redirect('*/*/index');

            return;
        }

        // get a list of all pending sync in the sync queue
        $syncItems = Mage::getModel('shippit/sync_order')
            ->getCollection()
            ->addFieldToFilter('sync_id', array('in', $syncIds));

        foreach ($syncItems as $syncItem) {
            $syncItem->delete();
        }

        if (count($syncItems) > 1) {
            $this->_getSession()->addSuccess($this->__('The orders have been removed from the sync queue'));
        }
        else {
            $this->_getSession()->addSuccess($this->__('The order has been removed from the sync queue'));
        }

        $this->_redirect('*/*/index');

        return;
    }

    public function scheduleAction()
    {
        $syncId = $this->getRequest()->getParam('id', null);

        if (empty($syncId)) {
            $this->_getSession()->addError($this->__('You must select at least 1 order to schedule'));

            $this->_redirect('*/*/index');

            return;
        }

        $this->_scheduleItems(array($syncId));
    }

    public function massScheduleAction()
    {
        $syncIds = $this->getRequest()->getParam('sync_ids', array());
        $this->_scheduleItems($syncIds);
    }

    private function _scheduleItems($syncIds)
    {
        if (empty($syncIds)) {
            $this->_getSession()->addError($this->__('You must select at least 1 order to schedule'));

            $this->_redirect('*/*/index');

            return;
        }

        // get a list of all pending sync in the sync queue
        $syncItems = Mage::getModel('shippit/sync_order')
            ->getCollection()
            ->addFieldToFilter('sync_id', array('in', $syncIds));

        foreach ($syncItems as $syncItem) {
            $syncItem->setStatus(Shippit_Shippit_Model_Sync_Order::STATUS_PENDING)
                ->setAttemptCount(0)
                ->setTrackNumber(null)
                ->setSyncedAt(null)
                ->save();
        }

        if (count($syncItems) > 1) {
            $this->_getSession()->addSuccess($this->__('The orders have been successfully reset and will sync with Shippit again shortly'));
        }
        else {
            $this->_getSession()->addSuccess($this->__('The order has been successfully reset and will sync with Shippit again shortly'));
        }

        $this->_redirect('*/*/index');

        return;
    }

    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('sales/shippit_orders');
    }
}