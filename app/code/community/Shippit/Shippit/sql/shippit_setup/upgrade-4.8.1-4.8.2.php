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

// Update the order_increment column to be casted as a varchar
$installer->getConnection()->changeColumn(
    $installer->getTable('shippit/sync_shipment'),
    'order_increment',
    'order_increment',
    array(
        'type' => Varien_Db_Ddl_Table::TYPE_TEXT,
        'length' => 50,
        'default' => null,
        'nullable' => true,
        'comment' => 'Order Increment'
    )
);

// Update the shipment_increment column to be casted as a varchar
$installer->getConnection()->changeColumn(
    $installer->getTable('shippit/sync_shipment'),
    'shipment_increment',
    'shipment_increment',
    array(
        'type' => Varien_Db_Ddl_Table::TYPE_TEXT,
        'length' => 50,
        'default' => null,
        'nullable' => true,
        'comment' => 'Shipment Increment'
    )
);

$installer->endSetup();
