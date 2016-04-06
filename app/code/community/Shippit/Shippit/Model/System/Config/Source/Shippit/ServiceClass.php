<?php
/**
*  Shippit Pty Ltd
*
*  NOTICE OF LICENSE
*
*  This source file is subject to the terms
*  that is available through the world-wide-web at this URL:
*  http://www.shippit.com/terms
*
*  @category   Shippit
*  @copyright  Copyright (c) 2016 by Shippit Pty Ltd (http://www.shippit.com)
*  @author     Matthew Muscat <matthew@mamis.com.au>
*  @license    http://www.shippit.com/terms
*/

class Shippit_Shippit_Model_System_Config_Source_Shippit_ServiceClass
{
    public function toOptionArray()
    {
        $optionsArray = array(
            array(
                'label' => 'Standard',
                'value' => 'standard'
            ),
            array(
                'label' => 'Express',
                'value' => 'express'
            ),
            // Premium is not an available service class mapping,
            // as it requires live quoting to determine service availability
            //
            // array(
            //     'label' => 'Premium',
            //     'value' => 'premium'
            // )
        );
        
        return $optionsArray;
    }
}