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

class Shippit_Shippit_Model_System_Config_Source_Shippit_Sync_SendAllOrders
{
    const ALL = 'all';
    const ALL_LABEL = 'Yes - All Orders';

    const ALL_AU = 'all_au';
    const ALL_AU_LABEL = 'Yes - All Australian Orders';

    const NO = 'no';
    const NO_LABEL = 'No';

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        $optionsArray = array(
            array(
                'label' => self::ALL_LABEL,
                'value' => self::ALL
            ),
            array(
                'label' => self::ALL_AU_LABEL,
                'value' => self::ALL_AU
            ),
            array(
                'label' => self::NO_LABEL,
                'value' => self::NO
            )
        );

        return $optionsArray;
    }
}
