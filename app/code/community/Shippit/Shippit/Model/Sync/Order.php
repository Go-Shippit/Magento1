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

class Shippit_Shippit_Model_Sync_Order extends Mage_Core_Model_Abstract
{
    const STATUS_PENDING = 0;
    const STATUS_PENDING_TEXT = 'Pending';
    const STATUS_SYNCED = 1;
    const STATUS_SYNCED_TEXT = 'Synced';
    const STATUS_FAILED = 2;
    const STATUS_FAILED_TEXT = 'Failed';

    const SYNC_MAX_ATTEMPTS = 5;

    protected $_itemsCollection = null;
    protected $_items = null;

    protected function _construct()
    {
        $this->_init('shippit/sync_order');
    }

    public function addRequest($request)
    {
        $this->setOrderId($request->getOrderId())
            ->addItems($request->getItems())
            ->setShippingMethod($request->getShippingMethod())
            ->setApiKey($request->getApiKey());

        return $this;
    }

    /**
     * Add the order details to the sync event
     *
     * @param Mage_Sales_Model_Order|String $order The Magento Order object or Order id
     */
    public function addOrder(Mage_Sales_Model_Order $order)
    {
        $this->setOrderId($order->getEntityId());

        return $this;
    }

    /**
     * Get the Order Object attached to the Sync Order Entry
     *
     * @return [type] [description]
     */
    public function getOrder()
    {
        $orderId = $this->getOrderId();

        return Mage::getModel('sales/order')->load($orderId);
    }

    /**
     * Retrieve sync order items collection
     *
     * @param   bool $useCache
     * @return  Mage_Eav_Model_Entity_Collection_Abstract
     */
    public function getItemsCollection($useCache = true)
    {
        if (is_null($this->_itemsCollection) || !$useCache) {
            $this->_itemsCollection = Mage::getResourceModel('shippit/sync_order_item_collection');
            $this->_itemsCollection->addSyncOrderFilter($this);
        }

        return $this->_itemsCollection;
    }

    /**
     * Retrieve customer address array
     *
     * @return array
     */
    public function getItems()
    {
        $this->_items = $this->getItemsCollection()->getItems();

        return $this->_items;
    }

    /**
     * Add a new item to the sync order request
     *
     * @param Shippit_Shippit_Model_Sync_Order_Item $item
     */
    public function addItem(Shippit_Shippit_Model_Sync_Order_Item $item)
    {
        if (!$item->getSyncItemId()) {
            $this->getItemsCollection()->addItem($item);
            $this->_items[] = $item;
        }

        return $this;
    }

    /**
     * Add a new item to the sync order request
     *
     * @param Array $item
     */
    public function addItems($items)
    {
        foreach ($items as $item) {
            $itemObject = Mage::getModel('shippit/sync_order_item')->addItem($item);
            $this->addItem($itemObject);
        }

        return $this;
    }
}
