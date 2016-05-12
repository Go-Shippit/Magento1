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

class Shippit_Shippit_Helper_Order_Items extends Mage_Core_Helper_Abstract
{
    protected $locationAttributeCode = null;

    public function getSkus($items)
    {
        $itemsSkus = array();

        foreach ($items as $item) {
            if (isset($item['sku'])) {
                $itemSkus[] = $item['sku'];
            }
        }

        return $itemSkus;
    }

    public function getIds($items)
    {
        $itemsIds = array();

        foreach ($items as $item) {
            if (isset($item['id'])) {
                $itemsIds[] = $item['id'];
            }
        }

        return $itemsIds;
    }

    public function getQtyToShip($item, $qtyRequested = null)
    {
        $qtyToShip = $item->getQtyToShip();

        // if no quantity is provided, or the qty requested is
        // greater than the pending shipment qty
        // return the pending shipment qty
        if (empty($qtyRequested) || $qtyRequested > $qtyToShip) {
            return $qtyToShip;
        }
        // otherwise, return the qty requested
        else {
            return $qtyRequested;
        }
    }

    public function getItemData($items, $itemKey, $itemValue, $itemDataKey)
    {
        if (PHP_VERSION_ID < 50500) {
            foreach ($items as $key => $value) {
                if (isset($value[$itemKey]) && $value[$itemKey] == $itemValue) {
                    if (isset($value[$itemDataKey])) {
                        return $value[$itemDataKey];
                    }
                    else {
                        return false;
                    }
                }
            }
        }
        else {
            $searchResult = array_search($itemValue, array_column($items, $itemKey));

            if ($searchResult !== false) {
                return $items[$searchResult][$itemDataKey];
            }
        }

        return false;
    }

    public function getLocation($item)
    {
        $attributeCode = $this->getLocationAttributeCode();

        if ($attributeCode) {
            return $this->getAttributeValue($item->getProduct(), $attributeCode);
        }
        else {
            return null;
        }
    }

    public function getLocationAttributeCode()
    {
        if (is_null($this->locationAttributeCode)) {
            $helper = Mage::helper('shippit/sync_order');
            
            if (!$helper->isProductLocationActive()) {
                $this->locationAttributeCode = false;
            }
            else {
                $this->locationAttributeCode = $helper->getProductLocationAttributeCode();
            }
        }

        return $this->locationAttributeCode;
    }

    /**
     * Get the product attribute value, ensuring we get
     * the full text value if it's a select or multiselect attribute
     * 
     * @param  object  $product        The Product Object
     * @param  string  $attributeCode  The Attribute Code
     * @return string                  The Product Attribute Value (full text)
     */
    public function getAttributeValue($product, $attributeCode)
    {
        $attribute = $product->getResource()->getAttribute($attributeCode);

        if ($attribute && $attribute->usesSource()) {
            $attributeValue = $product->getAttributeText($attributeCode);
        }
        else {
            $attributeFunction = $this->getFunctionName($attributeCode);
            $attributeValue = $product->{$attributeFunction}();
        }

        return $attributeValue;
    }

    private function getFunctionName($attributeCode, $prefix = 'get', $capitaliseFirstChar = true)
    {
        if ($capitaliseFirstChar) {
            $attributeCode[0] = strtoupper($attributeCode[0]);
        }

        $function = create_function('$c', 'return strtoupper($c[1]);');
        $functionName = preg_replace_callback('/_([a-z])/', $function, $attributeCode);

        return $prefix . $functionName;
    }
}