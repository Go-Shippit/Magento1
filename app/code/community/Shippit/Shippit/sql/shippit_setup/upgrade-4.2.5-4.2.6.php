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

// Update the weight column to be casted as a float
$installer->getConnection()->changeColumn(
    $installer->getTable('shippit/sync_order_item'),
    'weight',
    'weight',
    array(
        'type' => Varien_Db_Ddl_Table::TYPE_DECIMAL,
        'length' => '12,4',
        'default' => '0.0000',
        'comment' => 'Item Weight'
    )
);

// Update the item qty column to be casted as a float
$installer->getConnection()->changeColumn(
    $installer->getTable('shippit/sync_order_item'),
    'qty',
    'qty',
    array(
        'type' => Varien_Db_Ddl_Table::TYPE_DECIMAL,
        'length' => '12,4',
        'default' => '0.0000',
        'comment' => 'Item Qty'
    )
);

$installer->endSetup();
