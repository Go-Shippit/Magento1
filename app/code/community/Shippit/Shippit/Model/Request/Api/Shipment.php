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

// Read the Shippit webhook request and provides
// a summary of the available item actions in Magento

class Shippit_Shippit_Model_Request_Api_Shipment extends Varien_Object
{
    protected $itemHelper;

    const ORDER = 'order';
    const ITEMS = 'items';

    const ERROR_ORDER_MISSING = 'The order id requested was not found';
    const ERROR_ORDER_STATUS = 'The order id requested has an status that is not available for shipping';

    public function __construct()
    {
        $this->itemHelper = Mage::helper('shippit/sync_item');

        return $this;
    }

    public function getOrderId()
    {
        return $this->getOrder()->getId();
    }

    public function setOrderByIncrementId($orderIncrementId)
    {
        $order = Mage::getModel('sales/order')->load($orderIncrementId, 'increment_id');

        return $this->setOrder($order);
    }

    public function setOrder($order)
    {
        if (!$order->getId()) {
            throw new Exception(self::ERROR_ORDER_MISSING);
        }

        if (!$order->canShip()) {
            throw new Exception(self::ERROR_ORDER_STATUS);
        }

        return $this->setData(self::ORDER, $order);
    }

    /**
     * Process items in the shipment request,
     * - ensures only items contained in the order are present
     * - ensures only qtys available for shipping are used in the shipment
     *
     * @param object $items   The items to be included in the request
     */
    public function processItems($items = array())
    {
        $itemsCollection = Mage::getResourceModel('sales/order_item_collection')
            ->addFieldToFilter('order_id', $this->getOrderId());

        // for the specific items that have been passed, ensure they are valid
        // items for the item
        if (!empty($items)) {
            $itemsSkus = $this->itemHelper->getSkus($items);

            if (!empty($itemsSkus)) {
                $itemsCollection->addFieldToFilter('sku', array('in' => $itemsSkus));
            }
        }

        // For all valid items, process the qty to be marked as shipped
        foreach ($itemsCollection as $item) {
            $requestedQty = $this->itemHelper->getItemData($items, 'sku', $item->getSku(), 'qty');

            /**
             * Magento marks a shipment only for the parent item in the order
             * get the parent item to determine the correct qty to ship
             */
            $rootItem = $this->_getRootItem($item);

            $itemQty = $this->itemHelper->getQtyToShip($rootItem, $requestedQty);

            if ($itemQty <= 0) {
                continue;
            }

            $this->addItem($item->getId(), $itemQty);
        }

        return $this;
    }

    private function _getRootItem($item)
    {
        if ($item->hasParentItem()) {
            return $item->getParentItem();
        }
        else {
            return $item;
        }
    }

    public function getItems()
    {
        $items = $this->getData(self::ITEMS);

        // if no items have been added, assume all items are to be marked as shipped
        if (empty($items)) {
            return array();
        }
        // otherwise, only mark the items and qtys specified as shipped
        else {
            return $items;
        }
    }

    /**
     * Add a parcel with attributes
     *
     */
    public function addItem($itemId, $qty)
    {
        $items = $this->getItems();

        $items[$itemId] = $qty;

        return $this->setItems($items);
    }
}
