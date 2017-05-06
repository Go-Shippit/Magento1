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

$orderTable = $installer->getConnection()
    ->describeTable($installer->getTable('sales/order'));

$quoteTable = $installer->getConnection()
    ->describeTable($installer->getTable('sales/order'));

if (!isset($orderTable['shippit_sync'])) {
    $installer->getConnection()->addColumn(
        $installer->getTable('sales/order'),
        'shippit_sync',
        array(
            'type' => Varien_Db_Ddl_Table::TYPE_BOOLEAN,
            'nullable' => true,
            'default' => null,
            'comment' => 'Shippit - Sync with Shippit'
        )
    );
}

if (!isset($quoteTable['shippit_delivery_instructions'])) {
    $installer->getConnection()->addColumn(
        $installer->getTable('sales/quote'),
        'shippit_delivery_instructions',
        array(
            'type' => Varien_Db_Ddl_Table::TYPE_TEXT,
            'length' => 256,
            'comment' => 'Shippit - Customer Delivery Instructions'
        )
    );
}

if (!isset($orderTable['shippit_delivery_instructions'])) {
    $installer->getConnection()->addColumn(
        $installer->getTable('sales/order'),
        'shippit_delivery_instructions',
        array(
            'type' => Varien_Db_Ddl_Table::TYPE_TEXT,
            'length' => 256,
            'comment' => 'Shippit - Customer Delivery Instructions'
        )
    );
}

if (!isset($quoteTable['shippit_authority_to_leave'])) {
    $installer->getConnection()->addColumn(
        $installer->getTable('sales/quote'),
        'shippit_authority_to_leave',
        array(
            'type' => Varien_Db_Ddl_Table::TYPE_BOOLEAN,
            'default' => null,
            'comment' => 'Shippit - Customer Authority To Leave'
        )
    );
}

if (!isset($orderTable['shippit_authority_to_leave'])) {
    $installer->getConnection()->addColumn(
        $installer->getTable('sales/order'),
        'shippit_authority_to_leave',
        array(
            'type' => Varien_Db_Ddl_Table::TYPE_BOOLEAN,
            'default' => null,
            'comment' => 'Shippit - Customer Authority To Leave'
        )
    );
}

$table = $installer->getConnection()
    ->newTable($installer->getTable('shippit/order_sync'))
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
        'order_id',
        Varien_Db_Ddl_Table::TYPE_INTEGER,
        null,
        array(
            'unsigned'  => true,
            'nullable'  => false,
        ),
        'Order Id'
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
        'attempt_count',
        Varien_Db_Ddl_Table::TYPE_TINYINT,
        null,
        array(
            'unsigned'  => true,
            'nullable'  => false,
        ),
        'Attempt Count'
    )
    ->addColumn('track_number',
        Varien_Db_Ddl_Table::TYPE_TEXT,
        '64k',
        array(),
        'Tracking Number'
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
            'shippit/order_sync',
            array('store_id')
        ),
        array('store_id')
    )
    ->addIndex(
        $installer->getIdxName(
            'shippit/order_sync',
            array('order_id')
        ),
        array('order_id')
    )
    ->addForeignKey(
        $installer->getFkName(
            'shippit/order_sync',
            'order_id',
            'sales/order',
            'entity_id'
        ),
        'order_id',
        $installer->getTable('sales/order'),
        'entity_id',
        Varien_Db_Ddl_Table::ACTION_CASCADE,
        Varien_Db_Ddl_Table::ACTION_CASCADE
    )
    ->addForeignKey(
        $installer->getFkName(
            'shippit/order_sync',
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
    ->setComment('Sales Flat Shipment');

$installer->getConnection()->createTable($table);

$installer->endSetup();
