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

// Validates the request to create a sync order object
// Ensuring the order details, items and qtys requested
// to be synced are valid

class Shippit_Shippit_Model_Request_Sync_Order extends Varien_Object
{
    protected $helper;
    protected $itemHelper;
    protected $items;
    protected $serviceLevels;
    protected $couriers;

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
        $this->serviceLevels = Shippit_Shippit_Model_System_Config_Source_Shippit_Shipping_Methods::$serviceLevels;
        $this->couriers = Shippit_Shippit_Model_System_Config_Source_Shippit_Shipping_Methods::$couriers;
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

            $itemQty = $this->getItemQty($items, $item);

            // If the item qty is 0, skip this item from being sent to Shippit
            if ($itemQty <= 0) {
                continue;
            }

            $this->addItem(
                $this->getItemSku($item),
                $this->getItemName($item),
                $itemQty,
                $this->getItemPrice($item),
                $this->getItemWeight($item),
                $this->getItemLength($item),
                $this->getItemWidth($item),
                $this->getItemDepth($item),
                $this->getItemLocation($item)
            );

            $itemsAdded++;
        }

        if ($itemsAdded == 0) {
            throw new Exception(self::ERROR_NO_ITEMS_AVAILABLE_FOR_SHIPPING);
        }

        return $this;
    }

    protected function isProductDimensionActive()
    {
        return $this->itemHelper->isProductDimensionActive();
    }

    protected function getItemSku($item)
    {
        return $item->getSku();
    }

    protected function getItemName($item)
    {
        $childItem = $this->_getChildItem($item);

        return $childItem->getName();
    }

    protected function getItemQty($items, $item)
    {
        $requestedQty = $this->getRequestedQuantity($items, 'sku', $item->getSku(), 'qty');

        return $this->itemHelper->getQtyToShip($item, $requestedQty);
    }

    protected function getRequestedQuantity($items, $itemKey, $itemSku, $itemDataKey)
    {
        return $this->itemHelper->getItemData($items, $itemKey, $itemSku, $itemDataKey);
    }

    protected function getItemWeight($item)
    {
        return $item->getWeight();
    }

    protected function getItemLength($item)
    {
        $childItem = $this->_getChildItem($item);
        $isProductDimensionActive = $this->isProductDimensionActive();

        if (!$this->isProductDimensionActive()) {
            return;
        }

        return $this->itemHelper->getLength($childItem);
    }

    protected function getItemWidth($item)
    {
        $childItem = $this->_getChildItem($item);
        $isProductDimensionActive = $this->isProductDimensionActive();

        if (!$this->isProductDimensionActive()) {
            return;
        }

        return $this->itemHelper->getWidth($childItem);
    }

    protected function getItemDepth($item)
    {
        $childItem = $this->_getChildItem($item);
        $isProductDimensionActive = $this->isProductDimensionActive();

        if (!$this->isProductDimensionActive()) {
            return;
        }

        return $this->itemHelper->getDepth($childItem);
    }

    protected function getItemLocation($item)
    {
        $childItem = $this->_getChildItem($item);

        return $this->itemHelper->getLocation($childItem);
    }

    protected function getItemPrice($item)
    {
        $rootItem = $this->_getRootItem($item);

        // Get the item price
        // - If the root item is a bundle, use the item price
        //   Otherwise, use the root item price
        if ($rootItem->getProductType() == 'bundle') {
            return $this->getBundleItemPrice($item);
        }
        else {
            return $this->getBasicItemPrice($item);
        }
    }

    protected function getBundleItemPrice($item)
    {
        $rootItem = $this->_getRootItem($item);

        // if we are sending the bundle together
        if ($rootItem->getId() == $item->getId()) {
            $childItems = $rootItem->getChildrenItems();
            $itemPrice = 0;

            foreach ($childItems as $childItem) {
                // Get the number of items in the bundle per bundle package purchased
                $childItemQty = ($childItem->getQtyOrdered() / $rootItem->getQtyOrdered());
                $rowTotalAfterDiscounts = $childItem->getBaseRowTotal() - $childItem->getBaseDiscountAmount();
                $rowUnitPrice = $rowTotalAfterDiscounts / $childItem->getQtyOrdered();
                $bundleItemUnitPrice = $rowUnitPrice * $childItemQty;

                $itemPrice += $bundleItemUnitPrice;
            }

            return round($itemPrice, 2);
        }
        // if we are sending the bundle individually
        else {
            return $this->getBasicItemPrice($item);
        }
    }

    protected function getBasicItemPrice($item)
    {
        $rowTotalAfterDiscounts = $item->getBaseRowTotal() - $item->getBaseDiscountAmount();
        $itemPrice = $rowTotalAfterDiscounts / $item->getQtyOrdered();

        return round($itemPrice, 2);
    }

    /**
     * Returns the parent item of the item passed
     *
     * @param  Mage_Sales_Model_Order_Item $item
     * @return Mage_Sales_Model_Order_Item
     */
    protected function _getRootItem($item)
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
    protected function _getChildItem($item)
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
        // Retrieve the shipping method options available from shippit
        $validShippingMethods = Mage::getModel('shippit/system_config_source_shippit_shipping_methods')->getMethods();

        // if the shipping method passed is not a standard shippit service class,
        // attempt to get a service class based on the configured mapping
        if (!array_key_exists($shippingMethod, $this->serviceLevels)
            && !array_key_exists($shippingMethod, $this->couriers)) {
            $shippingMethod = $this->helper->getShippitShippingMethod($shippingMethod);
        }

        if (array_key_exists($shippingMethod, $this->serviceLevels)
            || array_key_exists($shippingMethod, $this->couriers)) {
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
        if (!empty($length) && !empty($width) && !empty($depth)) {
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
