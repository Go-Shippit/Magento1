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

$installer->getConnection()
    ->dropColumn(
        $installer->getTable('shippit/order_sync'),
        'shippit_sync'
    );

// Rename the order sync table
$installer->getConnection()
    ->renameTable(
        $installer->getTable('shippit/order_sync'),
        $installer->getTable('shippit/sync_order')
    );

// Remove the store_id column
$installer->getConnection()
    ->dropColumn(
        $installer->getTable('shippit/sync_order'),
        'store_id'
    );

$installer->getConnection()
    ->addColumn(
        $installer->getTable('shippit/sync_order'),
        'api_key',
        array(
            'type' => Varien_Db_Ddl_Table::TYPE_TEXT,
            'length' => 255,
            'nullable' => true,
            'default' => null,
            'after' => 'sync_id',
            'comment' => 'Shippit - API Key Override for the request'
        )
    );

$installer->getConnection()
    ->addColumn(
        $installer->getTable('shippit/sync_order'),
        'created_at',
        array(
            'type' => Varien_Db_Ddl_Table::TYPE_TIMESTAMP,
            'default' => null,
            'after' => 'track_number',
            'comment' => 'Shippit - Order Sync Created At Date'
        )
    );

$table = $installer->getConnection()
    ->newTable($installer->getTable('shippit/sync_order_item'))
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
        'Id'
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
        Varien_Db_Ddl_Table::TYPE_INTEGER,
        null,
        array(
            'unsigned'  => true,
            'nullable'  => false,
        ),
        'Item Qty'
    )
    ->addColumn(
        'weight',
        Varien_Db_Ddl_Table::TYPE_INTEGER,
        null,
        array(
            'nullable'  => false,
        ),
        'Item Weight'
    )
    ->addColumn(
        'location',
        Varien_Db_Ddl_Table::TYPE_VARCHAR,
        255,
        array(
            'nullable'  => false,
        ),
        'Item Location'
    )
    ->addIndex(
        $installer->getIdxName(
            'shippit/sync_order_item',
            array('sync_id')
        ),
        array('sync_id')
    )
    ->addIndex(
        $installer->getIdxName(
            'shippit/sync_order_item',
            array('sku')
        ),
        array('sku')
    )
    ->addForeignKey(
        $installer->getFkName(
            'shippit/sync_order_item',
            'sync_id',
            'shippit/sync_order',
            'sync_id'
        ),
        'sync_id',
        $installer->getTable('shippit/sync_order'),
        'sync_id',
        Varien_Db_Ddl_Table::ACTION_CASCADE,
        Varien_Db_Ddl_Table::ACTION_CASCADE
    )
    ->setComment('Shippit Order Sync Items');

$installer->getConnection()->createTable($table);

$installer->endSetup();
