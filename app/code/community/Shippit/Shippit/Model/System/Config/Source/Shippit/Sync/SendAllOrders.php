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
 * @copyright  Copyright (c) 2016 by Shippit Pty Ltd (http://www.shippit.com)
 * @author     Matthew Muscat <matthew@mamis.com.au>
 * @license    http://www.shippit.com/terms
 */

class Shippit_Shippit_Model_System_Config_Source_Shippit_Sync_SendAllOrders
{
    const ALL = 'all';
    const ALL_AU = 'all_au';
    const NO = 'no';

    public function toOptionArray()
    {
        $optionsArray = array(
            array(
                'label' => 'Yes - All Orders',
                'value' => self::ALL
            ),
            array(
                'label' => 'Yes - All Australia Orders',
                'value' => self::ALL_AU
            ),
            array(
                'label' => 'No',
                'value' => self::NO
            )
        );
        
        return $optionsArray;
    }
}