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

// Update the tracking number column to be casted as a varchar
$installer->getConnection()->changeColumn(
    $installer->getTable('shippit/sync_order'),
    'track_number',
    'track_number',
    array(
        'type' => Varien_Db_Ddl_Table::TYPE_TEXT,
        'length' => 255,
        'default' => null,
        'nullable' => true,
        'comment' => 'Tracking Number'
    )
);

// Add an index to the tracking number
$orderTable = $installer->getConnection()->addIndex(
    $installer->getTable('shippit/sync_order'),
    $installer->getIdxName(
        'shippit/sync_order',
        array('track_number')
    ),
    array('track_number')
);

// Create a shipment table
$shipmentTable = $installer->getConnection()
    ->newTable($installer->getTable('shippit/sync_shipment'))
    ->addColumn(
        'sync_id',
        Varien_Db_Ddl_Table::TYPE_INTEGER,
        null,
        array(
            'identity'  => true,
            'unsigned'  => true,
            'nullable'  => false,
            'primary'   => true,
        ),
        'Id'
    )
    ->addColumn(
        'store_id',
        Varien_Db_Ddl_Table::TYPE_SMALLINT,
        null,
        array(
            'unsigned'  => true,
        ),
        'Store Id'
    )
    ->addColumn(
        'order_increment',
        Varien_Db_Ddl_Table::TYPE_INTEGER,
        null,
        array(
            'unsigned'  => true,
            'default'   => null,
            'nullable'  => true,
        ),
        'Order Increment'
    )
    ->addColumn(
        'shipment_increment',
        Varien_Db_Ddl_Table::TYPE_INTEGER,
        null,
        array(
            'unsigned'  => true,
            'default'   => null,
            'nullable'  => true,
        ),
        'Shipment Increment'
    )
    ->addColumn(
        'status',
        Varien_Db_Ddl_Table::TYPE_TINYINT,
        null,
        array(
            'unsigned'  => true,
            'nullable'  => false,
        ),
        'Status'
    )
    ->addColumn(
        'courier_allocation',
        Varien_Db_Ddl_Table::TYPE_VARCHAR,
        255,
        array(),
        'Courier Allocation'
    )
    ->addColumn(
        'track_number',
        Varien_Db_Ddl_Table::TYPE_VARCHAR,
        255,
        array(),
        'Tracking Number'
    )
    ->addColumn(
        'attempt_count',
        Varien_Db_Ddl_Table::TYPE_TINYINT,
        null,
        array(
            'unsigned'  => true,
            'nullable'  => false,
        ),
        'Attempt Count'
    )
    ->addColumn(
        'created_at',
        Varien_Db_Ddl_Table::TYPE_TIMESTAMP,
        null,
        array(),
        'Created At'
    )
    ->addColumn(
        'synced_at',
        Varien_Db_Ddl_Table::TYPE_TIMESTAMP,
        null,
        array(),
        'Synced At'
    )
    ->addIndex(
        $installer->getIdxName(
            'shippit/sync_shipment',
            array('store_id')
        ),
        array('store_id')
    )
    ->addIndex(
        $installer->getIdxName(
            'shippit/sync_shipment',
            array('order_increment')
        ),
        array('order_increment')
    )
    ->addIndex(
        $installer->getIdxName(
            'shippit/sync_shipment',
            array('shipment_increment')
        ),
        array('shipment_increment')
    )
    ->addIndex(
        $installer->getIdxName(
            'shippit/sync_shipment',
            array('track_number')
        ),
        array('track_number')
    )
    ->addForeignKey(
        $installer->getFkName(
            'shippit/sync_shipment',
            'store_id',
            'core/store',
            'store_id'
        ),
        'store_id',
        $installer->getTable('core/store'),
        'store_id',
        Varien_Db_Ddl_Table::ACTION_SET_NULL,
        Varien_Db_Ddl_Table::ACTION_CASCADE
    )
    ->setComment('Shippit Shipment Sync History');

$installer->getConnection()->createTable($shipmentTable);

// Create a shipment item table
$shipmentItemTable = $installer->getConnection()
    ->newTable($installer->getTable('shippit/sync_shipment_item'))
    ->addColumn(
        'sync_item_id',
        Varien_Db_Ddl_Table::TYPE_INTEGER,
        null,
        array(
            'identity'  => true,
            'unsigned'  => true,
            'nullable'  => false,
            'primary'   => true,
        ),
        'Id'
    )
    ->addColumn(
        'sync_id',
        Varien_Db_Ddl_Table::TYPE_INTEGER,
        null,
        array(
            'unsigned'  => true,
            'nullable'  => false,
        ),
        'Shipment Sync Id'
    )
    ->addColumn(
        'sku',
        Varien_Db_Ddl_Table::TYPE_VARCHAR,
        255,
        array(
            'nullable'  => false,
        ),
        'Item SKU'
    )
    ->addColumn(
        'title',
        Varien_Db_Ddl_Table::TYPE_TEXT,
        '64k',
        array(),
        'Item Name'
    )
    ->addColumn(
        'qty',
        Varien_Db_Ddl_Table::TYPE_DECIMAL,
        '12,4',
        array(
            'default' => '0.0000',
        ),
        'Item Qty'
    )
    ->addIndex(
        $installer->getIdxName(
            'shippit/sync_shipment_item',
            array('sync_id')
        ),
        array('sync_id')
    )
    ->addIndex(
        $installer->getIdxName(
            'shippit/sync_shipment_item',
            array('sku')
        ),
        array('sku')
    )
    ->addForeignKey(
        $installer->getFkName(
            'shippit/sync_shipment_item',
            'sync_id',
            'shippit/sync_shipment',
            'sync_id'
        ),
        'sync_id',
        $installer->getTable('shippit/sync_shipment'),
        'sync_id',
        Varien_Db_Ddl_Table::ACTION_CASCADE,
        Varien_Db_Ddl_Table::ACTION_CASCADE
    )
    ->setComment('Shippit Shipment Items Sync History');

$installer->getConnection()->createTable($shipmentItemTable);

$installer->endSetup();
