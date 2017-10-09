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

class Shippit_Shippit_Helper_Carrier_Shippit extends Shippit_Shippit_Helper_Data
{
    /**
     * Path to module carrier options
     */
    const XML_PATH_SETTINGS = 'carriers/shippit/';

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

    /**
     * @return bool
     */
    public function isActive()
    {
        return parent::isActive() && self::getStoreConfig('active', true);
    }

    public function getTitle()
    {
        return self::getStoreConfig('title');
    }

    public function getAllowedMethods()
    {
        return explode(',', self::getStoreConfig('allowed_methods'));
    }

    public function getMargin()
    {
        return self::getStoreConfig('margin');
    }

    public function getMarginAmount()
    {
        return self::getStoreConfig('margin_amount');
    }

    public function getMaxTimeslots()
    {
        return self::getStoreConfig('max_timeslots');
    }

    public function isEnabledProductActive()
    {
        return self::getStoreConfig('enabled_product_active', true);
    }

    public function getEnabledProductIds()
    {
        return explode(',', self::getStoreConfig('enabled_product_ids'));
    }

    public function isEnabledProductAttributeActive()
    {
        return self::getStoreConfig('enabled_product_attribute_active', true);
    }

    public function getEnabledProductAttributeCode()
    {
        return self::getStoreConfig('enabled_product_attribute_code');
    }

    public function getEnabledProductAttributeValue()
    {
        return self::getStoreConfig('enabled_product_attribute_value');
    }
}
