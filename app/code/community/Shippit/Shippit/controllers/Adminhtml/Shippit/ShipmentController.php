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

class Shippit_Shippit_Adminhtml_Shippit_ShipmentController extends Mage_Adminhtml_Controller_Action
{
    public function indexAction()
    {
        $this->_title($this->__('Sales'))->_title($this->__('Shippit Shipments'));

        $this->loadLayout();
        $this->_setActiveMenu('sales/shippit/shipments');

        $this->_addContent($this->getLayout()->createBlock('shippit/adminhtml_sales_shipment'));
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
        $this->_syncShipments($syncIds);
    }

    public function syncAction()
    {
        $syncId = $this->getRequest()->getParam('id', null);
        $this->_syncShipments(array($syncId));
    }

    private function _syncShipments($syncIds)
    {
        if (empty($syncIds)) {
            $this->_getSession()->addError($this->__('You must select at least 1 shipment to sync'));

            $this->_redirect('*/*/index');

            return;
        }

        // get a list of all pending sync in the sync queue
        $syncShipments = Mage::getModel('shippit/sync_shipment')
            ->getCollection()
            ->addFieldToFilter('sync_id', array('in', $syncIds));

        if ($syncShipments->getSize() == 0) {
            $this->_getSession()->addError($this->__('No valid shipments were found'));

            $this->_redirect('*/*/index');

            return;
        }

        $apiShipment = Mage::getSingleton('shippit/api_shipment');

        // reset the status of all items and attempts
        foreach ($syncShipments as $syncShipment) {
            $syncShipment->setStatus(Shippit_Shippit_Model_Sync_Shipment::STATUS_PENDING)
                ->setAttemptCount(0)
                ->setShipmentIncrement(null)
                ->setTrackingNumber(null)
                ->setSyncedAt(null)
                ->save();
        }

        // get emulation model
        $appEmulation = Mage::getSingleton('core/app_emulation');

        foreach ($syncShipments as $syncShipment) {
            $storeId = $syncShipment->getStoreId();

            // Start Store Emulation
            $environment = $appEmulation->startEnvironmentEmulation($storeId);

            // Sync the order
            $apiShipment->sync($syncShipment, true);

            // Stop Store Emulation
            $appEmulation->stopEnvironmentEmulation($environment);
        }

        $this->_redirect('*/*/index');

        return;
    }

    public function removeAction()
    {
        $syncId = $this->getRequest()->getParam('id', null);

        if (empty($syncId)) {
            $this->_getSession()->addError($this->__('You must select at least 1 shipment to remove'));

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
            $this->_getSession()->addError($this->__('You must select at least 1 shipment to remove'));

            $this->_redirect('*/*/index');

            return;
        }

        // Get the shipment sync items to be removed
        $syncItems = Mage::getModel('shippit/sync_shipment')
            ->getCollection()
            ->addFieldToFilter('sync_id', array('in', $syncIds));

        foreach ($syncItems as $syncItem) {
            $syncItem->delete();
        }

        if (count($syncItems) > 1) {
            $this->_getSession()->addSuccess($this->__('The shipments have been removed from the sync queue'));
        }
        else {
            $this->_getSession()->addSuccess($this->__('The shipment has been removed from the sync queue'));
        }

        $this->_redirect('*/*/index');

        return;
    }

    public function scheduleAction()
    {
        $syncId = $this->getRequest()->getParam('id', null);

        if (empty($syncId)) {
            $this->_getSession()->addError($this->__('You must select at least 1 shipment to schedule'));

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
            $this->_getSession()->addError($this->__('You must select at least 1 shipment to schedule'));

            $this->_redirect('*/*/index');

            return;
        }

        // Get the shipment sync items to reschedule
        $syncItems = Mage::getModel('shippit/sync_shipment')
            ->getCollection()
            ->addFieldToFilter('sync_id', array('in', $syncIds));

        foreach ($syncItems as $syncItem) {
            $syncItem->setStatus(Shippit_Shippit_Model_Sync_Shipment::STATUS_PENDING)
                ->setAttemptCount(0)
                ->setShipmentIncrement(null)
                ->setTrackNumber(null)
                ->setSyncedAt(null)
                ->save();
        }

        if (count($syncItems) > 1) {
            $this->_getSession()->addSuccess($this->__('The shipments have been successfully reset and will be processed again shortly'));
        }
        else {
            $this->_getSession()->addSuccess($this->__('The shipments has been successfully reset and will be processed again shortly'));
        }

        $this->_redirect('*/*/index');

        return;
    }

    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('sales/shippit/shipments');
    }
}
