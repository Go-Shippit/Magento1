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

class Shippit_Shippit_Model_System_Config_Source_Catalog_Unit_Dimensions
{
    public function toOptionArray()
    {
        $optionsArray = array(
            array(
                'label' => 'Millimetres (mm)',
                'value' => Shippit_Shippit_Helper_Sync_Item::UNIT_DIMENSION_MILLIMETRES
            ),
            array(
                'label' => 'Centimetres (cm)',
                'value' => Shippit_Shippit_Helper_Sync_Item::UNIT_DIMENSION_CENTIMETRES
            ),
            array(
                'label' => 'Metres (m)',
                'value' => Shippit_Shippit_Helper_Sync_Item::UNIT_DIMENSION_METRES
            )
        );

        return $optionsArray;
    }
}
