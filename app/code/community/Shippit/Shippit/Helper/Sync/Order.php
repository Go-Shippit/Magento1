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

class Shippit_Shippit_Helper_Sync_Order extends Shippit_Shippit_Helper_Data
{
    /**
     * Path to module sync order config options
     */
    const XML_PATH_SETTINGS = 'shippit/sync_order/';

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

    public function isManualSyncActive()
    {
        return parent::isActive() && self::getStoreConfig('manual_sync_active', true);
    }

    public function isFilterOrderStatusActive()
    {
        return self::getStoreConfig('filter_order_status_active');
    }

    public function getFilterOrderStatus()
    {
        return explode(',', self::getStoreConfig('filter_order_status'));
    }

    public function getMode()
    {
        return self::getStoreConfig('mode');
    }

    public function getSendAllOrders()
    {
        return self::getStoreConfig('send_all_orders');
    }

    public function getShippingMethodMapping()
    {
        $values = unserialize( self::getStoreConfig('shipping_method_mapping'));
        $mappings = array();

        if (!empty($values)) {
            foreach ($values as $value) {
                $mappings[$value['shipping_method']] = $value['shippit_service'];
            }
        }

        return $mappings;
    }

    // BEGIN: Logic Helper Methods

    public function getShippitShippingMethod($shippingMethod)
    {
        // If the shipping method is a shippit click and collect
        // method, process as 'click_and_collect'
        if (strpos($shippingMethod, self::CARRIER_CODE_CC) !== FALSE) {
            return 'click_and_collect';
        }

        // If the shipping method is a shippit method,
        // processing using the selected shipping options
        if (strpos($shippingMethod, self::CARRIER_CODE) !== FALSE) {
            $shippingOptions = str_replace(self::CARRIER_CODE . '_', '', $shippingMethod);
            $shippingOptions = explode('_', $shippingOptions);
            $courierData = array();

            if (isset($shippingOptions[0])) {
                $method = strtolower($shippingOptions[0]);

                // allows for legacy capability where
                // "priority" was referred to as "premium"
                if ($method == 'priority' || $method == 'premium') {
                    return 'priority';
                }
                elseif ($method == 'express') {
                    return 'express';
                }
                elseif ($method == 'standard') {
                    return 'standard';
                }
            }
        }

        // Use the mapping values and attempt to get a value
        $shippingMethodMapping = $this->getShippingMethodMapping();

        if (isset($shippingMethodMapping[$shippingMethod])
            && !empty($shippingMethodMapping[$shippingMethod])) {
            return $shippingMethodMapping[$shippingMethod];
        }

        // All options have failed, return false
        return false;
    }

    // END: Login Helper Methods
}
