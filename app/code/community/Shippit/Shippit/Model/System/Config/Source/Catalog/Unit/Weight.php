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

class Shippit_Shippit_Model_System_Config_Source_Catalog_Unit_Weight
{
    public function toOptionArray()
    {
        $optionsArray = array(
            array(
                'label' => 'Kilograms',
                'value' => Shippit_Shippit_Helper_Sync_Item::UNIT_WEIGHT_KILOGRAMS
            ),
            array(
                'label' => 'Grams',
                'value' => Shippit_Shippit_Helper_Sync_Item::UNIT_WEIGHT_GRAMS
            )
        );

        return $optionsArray;
    }
}
