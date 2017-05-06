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
 * Migrate settings data from v4.4.0 to v4.5.0
 */
$configOptions = array(
	'shippit/sync_order/product_unit_weight' => 'shippit/sync_item/product_unit_weight',
	'shippit/sync_order/product_location_active' => 'shippit/sync_item/product_location_active',
	'shippit/sync_order/product_location_attribute_code' => 'shippit/sync_item/product_location_attribute_code'
);

foreach ($configOptions as $configOptionOldKey => $configOptionNewKey) {
    $configOptionValue = Mage::getStoreConfig($configOptionOldKey);

    if (!is_null($configOptionValue)) {
        Mage::getConfig()->saveConfig(
            $configOptionNewKey,
            $configOptionValue,
            'default',
            0
        );
    }
}

$installer->endSetup();
