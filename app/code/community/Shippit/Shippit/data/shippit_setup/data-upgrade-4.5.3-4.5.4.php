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

// Update any shippit sync orders that referenced international to standard
$internationalOrders = Mage::getResourceModel('shippit/sync_order_collection')
    ->addFieldToFilter('shipping_method', array('eq' => 'international'));

// Ensure we have international orders
if ($internationalOrders->count() > 0) {
    $internationalOrders->massUpdate(array('shipping_method' => 'standard'));
}

// Update any mapped shipping methods that reference international to standard
$shippingMethodMappingConfig = Mage::getStoreConfig('shippit/sync_order/shipping_method_mapping');

if ($shippingMethodMappingConfig) {
    $mappedShippingMethods = Zend_Serializer::unserialize($shippingMethodMappingConfig);

    foreach ($mappedShippingMethods as $mappedKey => $mappedValue) {
        if ($mappedValue['shippit_service'] == 'international') {
            $mappedShippingMethods[$mappedKey]['shippit_service'] = 'standard';
        }
    }

    Mage::getConfig()->saveConfig(
        'shippit/sync_order/shipping_method_mapping',
        Zend_Serializer::serialize($mappedShippingMethods),
        'default',
        0
    );
}
