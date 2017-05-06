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
 * Migrate settings data from v4.3.2 to v4.3.3
 */
$sendAllOrders = Mage::getStoreConfigFlag('shippit/sync_order/send_all_orders_active');

// If send all orders is set to true, update to all_au
if ($sendAllOrders) {
    Mage::getConfig()->saveConfig(
        'shippit/sync_order/send_all_orders',
        'all_au',
        'default',
        0
    );
}

$installer->endSetup();
