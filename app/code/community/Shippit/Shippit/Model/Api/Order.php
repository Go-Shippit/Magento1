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

// Core Class responsible for Managing the process of syncing orders
// with the Shippit Platform. Handles both cron job processing and immediate
// request processing, ensuring it transitions orders into the failed state
// when exceeding the maximum number of attempts.

class Shippit_Shippit_Model_Api_Order extends Mage_Core_Model_Abstract
{
    protected $helper;
    protected $itemHelper;
    protected $api;
    protected $logger;

    public function __construct()
    {
        $this->helper = Mage::helper('shippit/sync_order');
        $this->itemHelper = Mage::helper('shippit/sync_item');
        $this->api = Mage::helper('shippit/api');
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

            $syncOrders = $this->getSyncOrders($store);

            foreach ($syncOrders as $syncOrder) {
                $this->sync($syncOrder);
            }

            // Stop Store Emulation
            $appEmulation->stopEnvironmentEmulation($environment);
        }
    }

    /**
     * Get a list of sync orders pending sync
     * @return [type] [description]
     */
    public function getSyncOrders($store)
    {
        $storeId = $this->getStoreId($store);

        $syncOrders = Mage::getModel('shippit/sync_order')
            ->getCollection()
            ->join(
                array('order' => 'sales/order'),
                'order.entity_id = main_table.order_id',
                array(),
                null,
                'left'
            )
            ->addFieldToFilter('main_table.status', Shippit_Shippit_Model_Sync_Order::STATUS_PENDING)
            ->addFieldToFilter('main_table.attempt_count', array('lteq' => Shippit_Shippit_Model_Sync_Order::SYNC_MAX_ATTEMPTS))
            ->addFieldToFilter('order.state', array('eq' => Mage_Sales_Model_Order::STATE_PROCESSING))
            ->addFieldToFilter('order.store_id', array('eq' => $storeId));

        // Check if order status filtering is active
        if ($this->helper->isFilterOrderStatusActive()) {
            $filterStatus = $this->helper->getFilterOrderStatus();

            // ensure there is a filtering value present
            if (!empty($filterStatus)) {
                $syncOrders->addFieldToFilter(
                    'order.status',
                    array(
                        'in' => $filterStatus
                    )
                );
            }
        }

        return $syncOrders;
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

    public function sync($syncOrder, $displayNotifications = false)
    {
        try {
            $order = $syncOrder->getOrder();

            // Add attempt
            $syncOrder->setAttemptCount($syncOrder->getAttemptCount() + 1);

            // Build the order request
            $orderRequest = Mage::getModel('shippit/request_api_order')
                ->processSyncOrder($syncOrder);

            $apiResponse = $this->api->sendOrder($orderRequest, $syncOrder->getApiKey());

            // Add the order tracking details to
            // the order comments and save
            $comment = $this->helper->__('Order Synced with Shippit - ' . $apiResponse->tracking_number);
            $order->addStatusHistoryComment($comment)
                ->setIsVisibleOnFront(false)
                ->save();

            // Update the order to be marked as synced
            $syncOrder->setStatus(Shippit_Shippit_Model_Sync_Order::STATUS_SYNCED)
                ->setTrackNumber($apiResponse->tracking_number)
                ->setSyncedAt(Varien_Date::now())
                ->save();

            if ($displayNotifications) {
                Mage::getSingleton('adminhtml/session')
                    ->addSuccess(
                        $this->helper->__(
                            'Order %s Synced with Shippit - Shippit Tracking Number %s',
                            $order->getIncrementId(),
                            $apiResponse->tracking_number
                        )
                    );
            }
        }
        catch (Exception $e) {
            $this->logger->log('API - Order Sync Request Failed', $e->getMessage(), Zend_Log::ERR);
            $this->logger->logException($e);

            // Update the sync status to failed if it's breached the max attempts
            if ($syncOrder->getAttemptCount() > Shippit_Shippit_Model_Sync_Order::SYNC_MAX_ATTEMPTS) {
                $syncOrder->setStatus(Shippit_Shippit_Model_Sync_Order::STATUS_FAILED);
            }

            // save the sync item attempt count
            $syncOrder->save();

            if ($displayNotifications) {
                Mage::getSingleton('adminhtml/session')
                    ->addError(
                        $this->helper->__(
                            'Order %s was not Synced with Shippit - %s',
                            $syncOrder->getOrder()->getIncrementId(),
                            $e->getMessage()
                        )
                    );
            }

            return false;
        }

        return true;
    }
}
