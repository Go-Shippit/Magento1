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
 * Migrate settings data from v4.2.3 to v4.2.4
 */

$configOptions = array(
    'shippit/sync_order/all_orders' => 'shippit/sync_order/send_all_orders_active'
);

foreach ($configOptions as $configOptionOldKey => $configOptionNewKey) {
    $configOptionValue = Mage::getStoreConfig($configOptionOldKey);

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

$installer->endSetup();
