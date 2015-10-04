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

class Mamis_Shippit_Model_System_Config_Source_Shippit_MaxTimeslots
{
    const TIMESLOTS_MIN = 1;
    const TIMESLOTS_MAX = 20;
    /**
     * Returns code => code pairs of attributes for all product attributes
     *
     * @return array
     */
    public function toOptionArray()
    {
        $timeslots = range(self::TIMESLOTS_MIN, self::TIMESLOTS_MAX);

        $timeslotsArray = array();
        $timeslotsArray[] = array(
            'label' => '-- No Max Timeslots --',
            'value' => ''
        );

        foreach ($timeslots as $timeslot)
        {
            $timeslotsArray[] = array(
                'label' => $timeslot . ' Timeslots',
                'value' => $timeslot
            );
        }
        
        return $timeslotsArray;
    }
}