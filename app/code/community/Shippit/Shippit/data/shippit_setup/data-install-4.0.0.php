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
 * Add Australian States and Territories if they are not present
 */
$regions = array(
    array(
        'country_id'   => 'AU',
        'code'         => 'VIC',
        'default_name' => 'Victoria'
    ),
    array(
        'country_id'   => 'AU',
        'code'         => 'NSW',
        'default_name' => 'New South Wales'
    ),
    array(
        'country_id'   => 'AU',
        'code'         => 'ACT',
        'default_name' => 'Australian Capital Territory'
    ),
    array(
        'country_id'   => 'AU',
        'code'         => 'QLD',
        'default_name' => 'Queensland'
    ),
    array(
        'country_id'   => 'AU',
        'code'         => 'TAS',
        'default_name' => 'Tasmania'
    ),
    array(
        'country_id'   => 'AU',
        'code'         => 'SA',
        'default_name' => 'South Australia'
    ),
    array(
        'country_id'   => 'AU',
        'code'         => 'NT',
        'default_name' => 'Northern Territory'
    ),
    array(
        'country_id'   => 'AU',
        'code'         => 'WA',
        'default_name' => 'Western Australia'
    ),
);

foreach ($regions as $region) {
    // Attempt to load the region, checking if it already exists
    $hasRegion = Mage::getModel('directory/region')->loadByCode($region['code'], $region['country_id'])
        ->hasData();

    if (!$hasRegion) {
        // Insert the region data
        $installer->getConnection()->insert(
            $installer->getTable('directory/country_region'),
            $region
        );

        // Get the newly created region
        $regionId = $installer->getConnection()->lastInsertId(
            $installer->getTable('directory/country_region')
        );

        // Setup the region name data
        $regionName = array(
            'locale'    => 'en_US',
            'region_id' => $regionId,
            'name'      => $region['default_name'],
        );

        // Add the region name data
        $installer->getConnection()->insert(
            $installer->getTable('directory/country_region_name'),
            $regionName
        );
    }
}

// retrieve all orders with a shippit sync event
$orders = Mage::getModel('sales/order')->getCollection()
    ->addAttributeToFilter('shippit_sync', array('eq' => false));

$orderSyncs = array();

// migrate these orders + sync status to the shippit sync table
foreach ($orders as $order) {
    $orderSyncs[] = array(
        'store_id' => $order->getStoreId(),
        'order_id' => $order->getEntityId(),
        'status' => Shippit_Shippit_Model_Sync_Order::STATUS_PENDING,
    );
}

foreach ($orderSyncs as $orderSync) {
    // Insert the order sync data
    $installer->getConnection()->insert(
        $installer->getTable('shippit/sync_order'),
        $orderSync
    );
}

// Migrate from v3 to v4 (default settings only)
$configKeys = array(
    'mamis_shippit',
    'mindarc_shippit'
);

$configOptions = array(
    'active',
    'api_key',
    'debug_active',
    'sync_mode',
    'send_all_orders_active',
    'title',
    'allowed_methods',
    'max_timeslots',
    'enabled_product_active',
    'enabled_product_ids',
    'enabled_product_attribute_active',
    'enabled_product_attribute_code',
    'enabled_product_attribute_value',
    'sallowspecific',
    'specificcountry',
    'showmethod',
    'sort_order'
);

foreach ($configKeys as $configKey) {
    foreach ($configOptions as $configOption) {
        $configValue = Mage::getStoreConfig('carriers/' . $configKey . '/' . $configOption);

        if (!is_null($configValue)) {
            Mage::getConfig()->saveConfig('carriers/shippit/' . $configOption, $configValue, 'default', 0);
        }
    }
}

$installer->endSetup();
