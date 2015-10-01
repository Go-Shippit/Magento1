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
    const MAX_TIMESLOTS_MIN = 1;
    const MAX_TIMESLOTS_MAX = 20;
    /**
     * Returns code => code pairs of attributes for all product attributes
     *
     * @return array
     */
    public function toOptionArray()
    {
        $timeslots = range(self::MAX_TIMESLOTS_MIN, self::MAX_TIMESLOTS_MAX);

        $timeslotsArray = array();

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