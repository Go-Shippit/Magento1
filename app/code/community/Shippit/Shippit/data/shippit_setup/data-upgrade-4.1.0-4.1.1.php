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

$installer = $this;
$installer->startSetup();

/**
 * Migrate settings data from v4.1.0 to v4.1.1
 */
$configKeys = array(
    'carriers/shippit',
    'shippit'
);

$configOptions = array(
    'active' => array(
        'shippit/general/active',
        'shippit/sync_order/active',
        'shippit/checkout/authority_to_leave_active',
        'shippit/checkout/delivery_instructions_active',
        'shippit/sync_shipping/active',
        'shippit/sync_shipping/update_template_active'
    ),
    'api_key' => 'shippit/general/api_key',
    'environment' => 'shippit/general/environment',
    'debug_active' => 'shippit/general/debug_active',
    'sync_mode' => 'shippit/sync_order/mode',
    'send_all_orders_active' => 'shippit/sync_order/send_all_orders_active',
    'product_location_active' => 'shippit/sync_order/product_location_active',
    'product_location_attribute_code' => 'shippit/sync_order/product_location_attribute_code'
);

foreach ($configOptions as $configOptionOldKey => $configOptionNewKey) {
    $configOptionValue = Mage::getStoreConfig('carriers/shippit/' . $configOptionOldKey);

    if (!is_null($configOptionValue)) {
        if (is_array($configOptionNewKey)) {
            foreach ($configOptionNewKey as $configOptionNewKeyItem) {
                Mage::getConfig()->saveConfig(
                    $configOptionNewKeyItem,
                    $configOptionValue,
                    'default',
                    0
                );
            }
        }
        else {
            Mage::getConfig()->saveConfig(
                $configOptionNewKey,
                $configOptionValue,
                'default',
                0
            );
        }
    }
}

/**
 * Update sync order records to include the shipping method class
 */
$syncOrders = Mage::getModel('shippit/sync_order')->getCollection()
    ->addFieldToFilter('shipping_method', array('null' => true));

foreach ($syncOrders as $syncOrder) {
    $order = $syncOrder->getOrder();

    if (!$order->getId()) {
        continue;
    }

    $shippingMethod = $order->getShippingMethod();

    // If the shipping method is a shippit method,
    // processing using the selected shipping options
    if (strpos($shippingMethod, 'shippit') !== FALSE) {
        $shippingOptions = str_replace('shippit' . '_', '', $shippingMethod);
        $shippingOptions = explode('_', $shippingOptions);
        $courierData = array();

        if (isset($shippingOptions[0])) {
            if ($shippingOptions[0] == 'Bonds') {
                $shippitShippingMethod = 'premium';
            }
            elseif ($shippingOptions[0] == 'eparcelexpress') {
                $shippitShippingMethod = 'express';
            }
            elseif ($shippingOptions[0] == 'CouriersPlease'
                || $shippingOptions[0] == 'Fastway') {
                $shippitShippingMethod = 'standard';
            }
            else {
                $shippitShippingMethod = 'standard';
            }
        }
        else {
            $shippitShippingMethod = 'standard';
        }
    }
    else {
        $shippitShippingMethod = 'standard';
    }

    $syncOrder->setShippingMethod($shippitShippingMethod)
        ->save();
}

$installer->endSetup();
