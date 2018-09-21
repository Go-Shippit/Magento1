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

// Add the tariff_code column
$installer->getConnection()
    ->addColumn(
        $installer->getTable('shippit/sync_order_item'),
        'tariff_code',
        array(
            'type' => Varien_Db_Ddl_Table::TYPE_TEXT,
            'length' => 255,
            'default' => null,
            'nullable' => true,
            'after' => 'location',
            'comment' => 'Item Tariff Code'
        )
    );

$installer->endSetup();
