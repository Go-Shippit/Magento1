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

class Shippit_Shippit_Model_System_Config_Source_Shippit_Shipping_Methods
{
    public static $serviceLevels = array(
        'standard' => 'Standard',
        'express' => 'Express',
        'priority' => 'Priority',
        'click_and_collect' => 'Click and Collect',
    );

    public static $couriers = array(
        'eparcel' => 'Auspost eParcel',
        'eparcelexpress' => 'Auspost eParcel Express',
        'eparcelinternationalexpress' => 'Auspost eParcel International Express',
        'eparcelinternational' => 'Auspost eParcel International',
        'couriersplease' => 'Couriers Please',
        'fastway' => 'Fastway',
        'startrack' => 'StarTrack',
        'startrackpremium' => 'StarTrackPremium',
        'tnt' => 'TNT',
        'dhl' => 'DHL Express',
        'dhlexpress' => 'DHL Express Domestic',
        'dhlexpressinternational' => 'DHL Express International',
        'dhlecommerce' => 'DHL eCommerce',
        'plainlabel' => 'Plain Label',
        'plainlabelinternational' => 'Plain Label International',
        'bonds' => 'Bonds Couriers',
    );

    /**
     * Returns code => code pairs of attributes for all product attributes
     *
     * @return array
     */
    public function toOptionArray()
    {
        $methods = $this->getMethods();
        $methodOptions = array();

        foreach ($methods as $method) {
            $methodOptions[] = array(
                'label' => $method['label'],
                'value' => $method['value'],
            );
        }

        return $methodOptions;
    }

    public function getMethods()
    {
        $methods = array(
            array(
                'label' => 'Service Level',
                'value' => self::$serviceLevels,
            ),
            array(
                'label' => 'Couriers',
                'value' => self::$couriers,
            )
        );

        return $methods;
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        return array_merge(
            preg_filter('/^/', 'Service Level: ', self::$serviceLevels),
            preg_filter('/^/', 'Carrier: ', self::$couriers)
        );
    }
}
