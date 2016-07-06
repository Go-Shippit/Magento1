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

// Validates the request to create a sync order object
// Ensuring the order details, items and qtys requested
// to be synced are valid

class Shippit_Shippit_Model_Request_Sync_Order extends Varien_Object
{
    protected $helper;
    protected $itemsHelper;
    protected $items;

    /**
     * Constants for keys of data array. Identical to the name of the getter in snake case
     */
    const ORDER_ID        = 'order_id';
    const ITEMS           = 'items';
    const SHIPPING_METHOD = 'shipping_method';

    // const ERROR_INVALID_SHIPPING_METHOD = 'An invalid shipping method was requested, valid options include "standard" or "express"';
    const ERROR_NO_ITEMS_AVAILABLE_FOR_SHIPPING = 'No items could be added to the sync order request, please ensure the items are available for shipping';

    public function __construct() {
        $this->helper = Mage::helper('shippit/sync_order');
        $this->itemsHelper = Mage::helper('shippit/order_items');
    }

    /**
     * Set the order to be sent to the api request
     *
     * @param object $order The Order Request
     */
    public function setOrderId($orderId)
    {
        return $this->setData(self::ORDER_ID, $orderId);
    }

    public function setOrder(Mage_Sales_Model_Order $order)
    {
        return $this->setOrderId($order->getId());
    }

    /**
     * Add items from the order to the parcel details
     *
     * @param object $items   The items to be included in the request
     */
    public function setItems($items = array())
    {
        $itemsCollection = Mage::getResourceModel('sales/order_item_collection')
            ->addFieldToFilter('order_id', $this->getOrderId());

        // if specific items have been passed,
        // ensure that these are the only items in the request
        if (!empty($items)) {
            $itemsSkus = $this->itemsHelper->getSkus($items);

            if (!empty($itemsSkus)) {
                $itemsCollection = $itemsCollection->addFieldToFilter('sku', array('in' => $itemsSkus));
            }
        }

        $itemsAdded = 0;

        foreach ($itemsCollection as $item) {
            if ($item->getHasChildren()) {
                continue;
            }

            $requestedQty = $this->itemsHelper->getItemData($items, 'sku', $item->getSku(), 'qty');

            /**
             * Magento marks a shipment only for the parent item in the order
             * get the parent item to determine the correct qty to ship
             */
            $rootItem = $this->_getRootItem($item);
            
            $itemQty = $this->itemsHelper->getQtyToShip($rootItem, $requestedQty);
            $itemWeight = $item->getWeight();

            $itemLocation = $this->itemsHelper->getLocation($item);

            if ($itemQty > 0) {
                $this->addItem(
                    $item->getSku(),
                    $item->getName(),
                    $itemQty,
                    $rootItem->getBasePrice(),
                    $itemWeight,
                    $itemLocation
                );

                $itemsAdded++;
            }
        }

        if ($itemsAdded == 0) {
            throw new Exception(self::ERROR_NO_ITEMS_AVAILABLE_FOR_SHIPPING);
        }

        return $this;
    }

    private function _getRootItem($item)
    {
        if ($item->getParentItem()) {
            return $item->getParentItem();
        }
        else {
            return $item;
        }
    }

    public function setShippingMethod($shippingMethod)
    {
        // Standard, express and priority options are available
        // Priority services requires the use of live quoting to determine
        // booking availability
        $validShippingMethods = array(
            'standard',
            'express',
            'international',
            'priority'
        );

        // if the shipping method passed is not a standard shippit service class, attempt to get a service class based on the configured mapping
        if (!in_array($shippingMethod, $validShippingMethods)) {
            $shippingMethod = $this->helper->getShippitShippingMethod($shippingMethod);
        }

        if (in_array($shippingMethod, $validShippingMethods)) {
            return $this->setData(self::SHIPPING_METHOD, $shippingMethod);
        }
        else {
            return $this->setData(self::SHIPPING_METHOD, 'standard');
        }
    }

    public function reset()
    {
        // reset the request data
        $this->setOrderId(null)
            ->setItems(null);

        return $this->setData(self::SHIPPING_METHOD, null);
    }

    /**
     * Add a parcel with attributes
     *
     */
    public function addItem($sku, $title, $qty, $price, $weight = 0, $location = null)
    {
        $items = $this->getItems();

        if (empty($items)) {
            $items = array();
        }

        $newItem = array(
            'sku' => $sku,
            'title' => $title,
            'qty' => $qty,
            'price' => $price,
            'weight' => $weight,
            'location' => $location
        );

        $items[] = $newItem;

        return $this->setData(self::ITEMS, $items);
    }
}