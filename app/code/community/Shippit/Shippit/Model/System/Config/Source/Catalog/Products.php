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

class Shippit_Shippit_Model_System_Config_Source_Catalog_Products
{
    /**
     * Returns id, sku pairs of all products
     *
     * @return array
     */
    public function toOptionArray()
    {
        $resource = Mage::getSingleton('core/resource');
        $readConnection = $resource->getConnection('core_read');
        $table = $resource->getTableName('catalog/product');

        // We utilise a direct SQL query fetch here to avoid
        // loading a model for every product returned
        $products = $readConnection->fetchAll(
            'SELECT `sku` AS `label`, `entity_id` AS `value` FROM ' . $table
        );

        return $products;
    }
}
