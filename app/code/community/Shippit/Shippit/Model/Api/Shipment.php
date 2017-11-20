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

// Core Class responsible for Managing the process of processing shipments
// from Shippit Platform into Magento. Handles cron job processing, as
// well as immediate sync request processing,ensuring it transitions
// shipments into the failed state when exceeding the maximum
// number of attempts.

class Shippit_Shippit_Model_Api_Shipment extends Mage_Core_Model_Abstract
{
    const ERROR_ORDER_MISSING = 'The order increment requested was not found';
    const ERROR_ORDER_STATUS = 'The order increment requested has a status that is not available for shipping';
    const ERROR_SHIPMENT_FAILED = 'The shipment record was not able to be created at this time, please try again.';

    protected $helper;
    protected $logger;

    public function __construct()
    {
        $this->helper = Mage::helper('shippit/sync_shipping');
        $this->logger = Mage::getModel('shippit/logger');
    }

    public function run()
    {
        // get all stores, as we will emulate each storefront for integration run
        $stores = Mage::app()->getStores();

        // get emulation model
        $appEmulation = Mage::getSingleton('core/app_emulation');

        foreach ($stores as $store) {
            // Start Store Emulation
            $environment = $appEmulation->startEnvironmentEmulation($store);

            $syncShipments = $this->getSyncShipments($store);

            foreach ($syncShipments as $syncShipment) {
                $this->sync($syncShipment);
            }

            // Stop Store Emulation
            $appEmulation->stopEnvironmentEmulation($environment);
        }
    }

    /**
     * Get a list of sync orders pending sync
     * @return [type] [description]
     */
    public function getSyncShipments($store)
    {
        $storeId = $this->getStoreId($store);

        $syncShipments = Mage::getModel('shippit/sync_shipment')
            ->getCollection()
            ->addFieldToFilter('status', Shippit_Shippit_Model_Sync_Shipment::STATUS_PENDING)
            ->addFieldToFilter('attempt_count', array('lteq' => Shippit_Shippit_Model_Sync_Shipment::SYNC_MAX_ATTEMPTS))
            ->addFieldToFilter('store_id', array('eq' => $storeId));

        return $syncShipments;
    }

    private function getStoreId($store)
    {
        if ($store instanceof Mage_Core_Model_Store) {
            return $store->getId();
        }
        else {
            return $store;
        }
    }

    public function sync($syncShipment, $displayNotifications = false)
    {
        try {
            // Add to attempt counter
            $syncShipment->setAttemptCount($syncShipment->getAttemptCount() + 1);

            $order = $syncShipment->getOrder();
            $products = $syncShipment->getItemsCollection()->toArray()['items'];

            // Ensure the order exists
            if (!$this->_checkOrderExists($order)) {
                $syncShipment->setStatus(Shippit_Shippit_Model_Sync_Shipment::STATUS_FAILED);

                throw new Exception(self::ERROR_ORDER_MISSING);
            }

            // Ensure the order is in a status that can be shipped
            if (!$this->_checkOrderCanShip($order)) {
                $syncShipment->setStatus(Shippit_Shippit_Model_Sync_Shipment::STATUS_FAILED);

                throw new Exception(self::ERROR_ORDER_STATUS);
            }

            $shipmentRequest = Mage::getModel('shippit/request_api_shipment')
                ->setOrder($order)
                ->processItems($products);

            // Create the shipment
            $shipment = $this->_createShipment(
                $shipmentRequest->getOrder(),
                $shipmentRequest->getItems(),
                $syncShipment->getCourierAllocation(),
                $syncShipment->getTrackNumber()
            );

            // Update the order to be marked as synced
            $syncShipment->setStatus(Shippit_Shippit_Model_Sync_Shipment::STATUS_SYNCED)
                ->setShipmentIncrement($shipment->getIncrementId())
                ->setSyncedAt(Varien_Date::now())
                ->save();

            if ($displayNotifications) {
                Mage::getSingleton('adminhtml/session')
                    ->addSuccess(
                        $this->helper->__(
                            'Shipment %s with tracking number %s for Order %s has been successfully created',
                            $syncShipment->getShipmentIncrement(),
                            $syncShipment->getTrackNumber(),
                            $syncShipment->getOrderIncrement()
                        )
                    );
            }
        }
        catch (Exception $e) {
            $this->logger->log('Shipment Sync Request Processing Failed', $e->getMessage(), Zend_Log::ERR);
            $this->logger->logException($e);

            // Update the sync status to failed if it's breached the max attempts
            if ($syncShipment->getAttemptCount() > Shippit_Shippit_Model_Sync_Shipment::SYNC_MAX_ATTEMPTS) {
                $syncShipment->setStatus(Shippit_Shippit_Model_Sync_Shipment::STATUS_FAILED);
            }

            // save the sync shipment attempt details
            $syncShipment->save();

            if ($displayNotifications) {
                Mage::getSingleton('adminhtml/session')
                    ->addError(
                        $this->helper->__(
                            'Shipment with tracking number %s for Order %s could not be created - %s',
                            $syncShipment->getTrackNumber(),
                            $syncShipment->getOrderIncrement(),
                            $e->getMessage()
                        )
                    );
            }

            return false;
        }

        return true;
    }

    protected function _checkOrderExists($order)
    {
        if (!$order->getId()) {
            return false;
        }

        return true;
    }

    protected function _checkOrderCanShip($order)
    {
        if (!$order->canShip()) {
            return false;
        }

        return true;
    }

    protected function _createShipment($order, $items, $courierName, $trackingNumber)
    {
        $shipment = $order->prepareShipment($items);

        $shipment = Mage::getModel('sales/service_order', $order)
            ->prepareShipment($items);

        if (!$shipment) {
            throw new Exception(self::ERROR_SHIPMENT_FAILED);
        }

        $comment = sprintf(
            'Your order has been shipped - your tracking number is %s',
            $trackingNumber
        );

        $track = Mage::getModel('sales/order_shipment_track')
            ->setNumber($trackingNumber)
            ->setCarrierCode(Shippit_Shippit_Helper_Data::CARRIER_CODE)
            ->setTitle($courierName);

        $shipment->addTrack($track)
            ->register()
            ->addComment($comment, true)
            ->setEmailSent(true);

        $shipment->getOrder()->setIsInProcess(true);

        $transactionSave = Mage::getModel('core/resource_transaction')
            ->addObject($shipment)
            ->addObject($shipment->getOrder())
            ->save();

        $shipment->sendEmail(true, $comment);

        return $shipment;
    }
}
