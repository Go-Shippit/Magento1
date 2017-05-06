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
    ->addColumn(
        $installer->getTable('shippit/sync_order_item'),
        'depth',
        array(
            'type' => Varien_Db_Ddl_Table::TYPE_DECIMAL,
            'scale' => 2,
            'precision' => 12,
            'nullable' => true,
            'default' => null,
            'after' => 'weight',
            'comment' => 'Item Depth'
        )
    );

$installer->getConnection()
    ->addColumn(
        $installer->getTable('shippit/sync_order_item'),
        'width',
        array(
            'type' => Varien_Db_Ddl_Table::TYPE_DECIMAL,
            'scale' => 2,
            'precision' => 12,
            'nullable' => true,
            'default' => null,
            'after' => 'weight',
            'comment' => 'Item Width'
        )
    );

$installer->getConnection()
    ->addColumn(
        $installer->getTable('shippit/sync_order_item'),
        'length',
        array(
            'type' => Varien_Db_Ddl_Table::TYPE_DECIMAL,
            'scale' => 2,
            'precision' => 12,
            'nullable' => true,
            'default' => null,
            'after' => 'weight',
            'comment' => 'Item Length'
        )
    );

$installer->endSetup();
