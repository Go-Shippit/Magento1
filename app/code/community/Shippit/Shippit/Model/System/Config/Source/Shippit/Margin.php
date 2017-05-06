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

class Shippit_Shippit_Model_System_Config_Source_Shippit_Margin
{
    const NONE = '';
    const PERCENTAGE = 'percentage';
    const FIXED = 'fixed';

    public function toOptionArray()
    {
        $optionsArray = array(
            array(
                'label' => 'No',
                'value' => self::NONE
            ),
            array(
                'label' => 'Yes - Percentage',
                'value' => self::PERCENTAGE
            ),
            array(
                'label' => 'Yes - Fixed Amount',
                'value' => self::FIXED
            )
        );

        return $optionsArray;
    }
}