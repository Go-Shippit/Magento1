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
 * Migrate settings data from v4.1.6 to v4.1.7
 */

$allowedMethods = Mage::getStoreConfig('carriers/shippit/allowed_methods');

$allowedMethods = strtolower($allowedMethods);
$allowedMethods = str_replace('premium', 'priority', $allowedMethods);

Mage::getConfig()->saveConfig(
    'carriers/shippit/allowed_methods',
    $allowedMethods,
    'default',
    0
);

/**
 * Migrate sync orders data from v4.1.6 to v4.1.7
 */

$syncOrders = Mage::getModel('shippit/sync_order')->getCollection()
    ->addFieldToFilter('shipping_method', array('eq' => 'premium'));

foreach ($syncOrders as $syncOrder) {
    $syncOrder->setShippingMethod('priority')
        ->save();
}

$installer->endSetup();
