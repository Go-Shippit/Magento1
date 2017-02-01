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
    protected $itemHelper;
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
        $this->itemHelper = Mage::helper('shippit/sync_item');
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
            $itemsSkus = $this->itemHelper->getSkus($items);

            if (!empty($itemsSkus)) {
                $itemsCollection = $itemsCollection->addFieldToFilter('sku', array('in' => $itemsSkus));
            }
        }

        $itemsAdded = 0;

        foreach ($itemsCollection as $item) {
            // Skip the item if...
            // - it does not need to be shipped individually
            // - it is a virtual item
            if ($item->isDummy(true) || $item->getIsVirtual()) {
                continue;
            }

            $requestedQty = $this->itemHelper->getItemData($items, 'sku', $item->getSku(), 'qty');
            $itemQty = $this->itemHelper->getQtyToShip($item, $requestedQty);
            $itemPrice = $this->_getItemPrice($item);
            $itemWeight = $item->getWeight();

            $childItem = $this->_getChildItem($item);
            $itemName = $childItem->getName();

            if ($this->itemHelper->isProductDimensionActive()) {
                $itemLength = $this->itemHelper->getLength($childItem);
                $itemWidth = $this->itemHelper->getWidth($childItem);
                $itemDepth = $this->itemHelper->getDepth($childItem);
            }
            else {
                $itemLength = null;
                $itemWidth = null;
                $itemDepth = null;
            }

            $itemLocation = $this->itemHelper->getLocation($childItem);

            if ($itemQty > 0) {
                $this->addItem(
                    $item->getSku(),
                    $itemName,
                    $itemQty,
                    $itemPrice,
                    $itemWeight,
                    $itemLength,
                    $itemWidth,
                    $itemDepth,
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

    private function _getItemPrice($item)
    {
        $rootItem = $this->_getRootItem($item);

        // Get the item price
        // - If the root item is a bundle, use the item price
        //   Otherwise, use the root item price
        if ($rootItem->getProductType() == 'bundle') {
            // if we are sending the bundle together
            if ($rootItem->getId() == $item->getId()) {
                return $rootItem->getBasePriceInclTax();
            }
            // if we are sending individually
            else {
                return $item->getBasePriceInclTax();
            }
        }
        else {
            return $rootItem->getBasePriceInclTax();
        }
    }

    /**
     * Returns the parent item of the item passed
     *
     * @param  Mage_Sales_Model_Order_Item $item
     * @return Mage_Sales_Model_Order_Item
     */
    private function _getRootItem($item)
    {
        if ($item->getParentItem()) {
            return $item->getParentItem();
        }
        else {
            return $item;
        }
    }

    /**
     * Returns the first child item of the item passed
     * - If the item is a bundle and is being shipped together
     *   we return the bundle item, as it's the "shipped" product
     *
     * @param  Mage_Sales_Model_Order_Item $item
     * @return Mage_Sales_Model_Order_Item
     */
    private function _getChildItem($item)
    {
        if ($item->getHasChildren()) {
            $rootItem = $this->_getRootItem($item);

            // Get the first child item
            // - If the root item is a bundle, use the item
            //   Otherwise, use the root item
            if ($rootItem->getProductType() == 'bundle') {
                // if we are sending the bundle together
                if ($rootItem->getId() == $item->getId()) {
                    return $rootItem;
                }
                else {
                    $items = $item->getChildrenItems();

                    return reset($items);
                }
            }
            else {
                $items = $item->getChildrenItems();

                return reset($items);
            }
        }
        else {
            return $item;
        }
    }

    public function setShippingMethod($shippingMethod)
    {
        // Standard, express and priority options are available
        $validShippingMethods = array(
            'standard',
            'express',
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
    public function addItem($sku, $title, $qty, $price, $weight = 0, $length = null, $width = null, $depth = null, $location = null)
    {
        $items = $this->getItems();

        if (empty($items)) {
            $items = array();
        }

        $newItem = array(
            'sku' => $sku,
            'title' => $title,
            'qty' => (float) $qty,
            'price' => (float) $price,
            'weight' => (float) $this->itemHelper->getWeight($weight),
            'location' => $location
        );

        // for dimensions, ensure the item has values for all dimensions
        if (!empty((float) $length) && !empty((float) $width) && !empty((float) $depth)) {
            $newItem = array_merge(
                $newItem,
                array(
                    'length' => (float) $length,
                    'width' => (float) $width,
                    'depth' => (float) $depth
                )
            );
        }

        $items[] = $newItem;

        return $this->setData(self::ITEMS, $items);
    }
}
