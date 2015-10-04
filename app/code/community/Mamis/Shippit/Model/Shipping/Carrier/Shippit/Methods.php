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

class Mamis_Shippit_Model_Shipping_Carrier_Shippit_Methods
{
    /**
     * Returns code => code pairs of attributes for all product attributes
     *
     * @return array
     */
    public function toOptionArray()
    {
        $methods = $this->getMethods();
        $methodOptions = array();

        foreach ($methods as $methodValue => $methodLabel) {
            $methodOptions[] = array(
                'label' => $methodLabel,
                'value' => $methodValue
            );
        }
        
        return $methodOptions;
    }

    public function getMethods()
    {
        $methods = array(
            'CouriersPlease' => 'Couriers Please',
            'eParcel' => 'eParcel (Australia Post)',
            'Bonds' => 'Bonds'
        );

        return $methods;
    }
}