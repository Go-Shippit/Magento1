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

class Shippit_Shippit_Model_Sync_Shipment extends Mage_Core_Model_Abstract
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
        $this->_init('shippit/sync_shipment');
    }

    /**
     * Get the Order Object attached to the Sync Shipment Entry
     */
    public function getOrder()
    {
        $orderIncrementId = $this->getOrderIncrement();

        return Mage::getModel('sales/order')->loadByIncrementId($orderIncrementId);
    }

    /**
     * Retrieve sync shipment items collection
     *
     * @param   bool $useCache
     * @return  Mage_Eav_Model_Entity_Collection_Abstract
     */
    public function getItemsCollection($useCache = true)
    {
        if (is_null($this->_itemsCollection) || !$useCache) {
            $this->_itemsCollection = Mage::getResourceModel('shippit/sync_shipment_item_collection');
            $this->_itemsCollection->addSyncShipmentFilter($this);
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
     * Add a new item to the sync shipment request
     *
     * @param Shippit_Shippit_Model_Sync_Shipment_Item $item
     */
    public function addItem(Shippit_Shippit_Model_Sync_Shipment_Item $item)
    {
        if (!$item->getSyncItemId()) {
            $this->getItemsCollection()->addItem($item);
            $this->_items[] = $item;
        }

        return $this;
    }

    /**
     * Add a new item to the sync shipment request
     *
     * @param Array $item
     */
    public function addItems($items)
    {
        foreach ($items as $item) {
            $itemObject = Mage::getModel('shippit/sync_shipment_item')->addItem($item);
            $this->addItem($itemObject);
        }

        return $this;
    }
}
