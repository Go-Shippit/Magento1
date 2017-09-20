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

class Shippit_Shippit_Model_System_Config_Source_Shippit_Shipping_QuoteMethods
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
                'value' => $methodValue,
            );
        }

        return $methodOptions;
    }

    public function getMethods()
    {
        $methods = array(
            'standard' => 'Standard',
            'express' => 'Express',
            'priority' => 'Priority',
        );

        return $methods;
    }
}
