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

class Shippit_Shippit_Helper_Sync_Item extends Shippit_Shippit_Helper_Data
{
    const UNIT_WEIGHT_KILOGRAMS = 'kilograms';
    const UNIT_WEIGHT_GRAMS = 'grams';

    const UNIT_DIMENSION_MILLIMETRES = 'millimetres';
    const UNIT_DIMENSION_CENTIMETRES = 'centimetres';
    const UNIT_DIMENSION_METRES = 'metres';

    const DEFAULT_WEIGHT = 0.2;

    protected $locationAttributeCode = null;

    /**
     * Path to module sync order config options
     */
    const XML_PATH_SETTINGS = 'shippit/sync_item/';

    /**
     * Return store config value for key
     *
     * @param   string $key
     * @return  string
     */
    public function getStoreConfig($key, $flag = false)
    {
        $path = self::XML_PATH_SETTINGS . $key;

        if ($flag) {
            return Mage::getStoreConfigFlag($path);
        }
        else {
            return Mage::getStoreConfig($path);
        }
    }

    // BEGIN: Configuration Helpers

    public function getProductUnitWeight()
    {
        return self::getStoreConfig('product_unit_weight');
    }

    public function isProductDimensionActive()
    {
        return self::getStoreConfig('product_dimension_active', true);
    }

    public function getProductUnitDimension()
    {
        return self::getStoreConfig('product_unit_dimension');
    }

    public function getProductDimensionLengthAttributeCode()
    {
        return self::getStoreConfig('product_dimension_length_attribute_code');
    }

    public function getProductDimensionWidthAttributeCode()
    {
        return self::getStoreConfig('product_dimension_width_attribute_code');
    }

    public function getProductDimensionDepthAttributeCode()
    {
        return self::getStoreConfig('product_dimension_depth_attribute_code');
    }

    public function isProductLocationActive()
    {
        return self::getStoreConfig('product_location_active', true);
    }

    public function getProductLocationAttributeCode()
    {
        return self::getStoreConfig('product_location_attribute_code');
    }

    // END: Configuration Helpers

    // BEGIN: Logic Helpers

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

    public function getWeight($weight)
    {
        if ($this->getProductUnitWeight() == self::UNIT_WEIGHT_GRAMS
            && $weight != 0) {
            return ($weight / 1000);
        }

        return $weight;
    }

    public function getDefaultWeight()
    {
        return self::DEFAULT_WEIGHT;
    }

    public function getDimension($dimension)
    {
        // ensure the dimension is present and not empty
        if (empty($dimension)) {
            return null;
        }

        switch ($this->getProductUnitDimension()) {
            case self::UNIT_DIMENSION_MILLIMETRES:
                $dimension = ($dimension / 1000);
                break;
            case self::UNIT_DIMENSION_CENTIMETRES:
                $dimension = ($dimension / 100);
                break;
            case self::UNIT_DIMENSION_METRES:
                $dimension = $dimension;
                break;
        }

        return (float) $dimension;
    }

    public function getWidth($item)
    {
        $attributeCode = $this->getProductDimensionWidthAttributeCode();

        if ($attributeCode) {
            $attributeValue = $this->getAttributeValue($item->getProduct(), $attributeCode);
        }
        else {
            $attributeValue = null;
        }

        return $this->getDimension($attributeValue);
    }

    public function getLength($item)
    {
        $attributeCode = $this->getProductDimensionLengthAttributeCode();

        if ($attributeCode) {
            $attributeValue = $this->getAttributeValue($item->getProduct(), $attributeCode);
        }
        else {
            $attributeValue = null;
        }

        return $this->getDimension($attributeValue);
    }

    public function getDepth($item)
    {
        $attributeCode = $this->getProductDimensionDepthAttributeCode();

        if ($attributeCode) {
            $attributeValue = $this->getAttributeValue($item->getProduct(), $attributeCode);
        }
        else {
            $attributeValue = null;
        }

        return $this->getDimension($attributeValue);
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
            $helper = Mage::helper('shippit/sync_item');

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

    // END: Logic Helpers
}
