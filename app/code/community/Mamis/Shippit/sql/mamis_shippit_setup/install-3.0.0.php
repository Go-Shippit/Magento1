<?php
/**
*  Mamis.IT
*
*  NOTICE OF LICENSE
*
*  This source file is subject to the EULA
*  that is available through the world-wide-web at this URL:
*  http://www.mamis.com.au/licencing
*
*  @category   Mamis
*  @copyright  Copyright (c) 2015 by Mamis.IT Pty Ltd (http://www.mamis.com.au)
*  @author     Matthew Muscat <matthew@mamis.com.au>
*  @license    http://www.mamis.com.au/licencing
*/

$installer = $this;
$installer->startSetup();

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

$installer->getConnection()->addColumn(
    $installer->getTable('sales/quote'),
    'shippit_delivery_instructions',
    array(
        'type' => Varien_Db_Ddl_Table::TYPE_TEXT,
        'length' => 256,
        'comment' => 'Shippit - Customer Delivery Instructions'
    )
);


$installer->getConnection()->addColumn(
    $installer->getTable('sales/order'),
    'shippit_delivery_instructions',
    array(
        'type' => Varien_Db_Ddl_Table::TYPE_TEXT,
        'length' => 256,
        'comment' => 'Shippit - Customer Delivery Instructions'
    )
);

$installer->getConnection()->addColumn(
    $installer->getTable('sales/quote'),
    'shippit_authority_to_leave',
    array(
        'type' => Varien_Db_Ddl_Table::TYPE_BOOLEAN,
        'default' => null,
        'comment' => 'Shippit - Customer Authority To Leave'
    )
);

$installer->getConnection()->addColumn(
    $installer->getTable('sales/order'),
    'shippit_authority_to_leave',
    array(
        'type' => Varien_Db_Ddl_Table::TYPE_BOOLEAN,
        'default' => null,
        'comment' => 'Shippit - Customer Authority To Leave'
    )
);

$installer->endSetup();