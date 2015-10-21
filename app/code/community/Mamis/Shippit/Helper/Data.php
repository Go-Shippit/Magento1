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

class Mamis_Shippit_Helper_Data extends Mage_Core_Helper_Abstract
{
    /**
     * Paths to module config options
     */
    const XML_PATH_SETTINGS = 'carriers/mamis_shippit/';
    const AUTHORITY_TO_LEAVE_ID = 'shippit_authority_to_leave';
    const DELIVERY_INSTRUCTIONS_ID = 'shippit_delivery_instructions';
    
    /**
     * Return store config value for key
     *
     * @param   string $key
     * @return  string
     */
    public function getStoreConfig($key, $flag = false)
    {
        $path = self::XML_PATH_SETTINGS . $key;

        if ($flag) {
            return Mage::getStoreConfigFlag($path);
        }
        else {
            return Mage::getStoreConfig($path);
        }
    }

    public function getModuleVersion()
    {
        $version = (string) Mage::getConfig()
            ->getNode()
            ->modules
            ->Mamis_Shippit
            ->version;

        return $version;
    }

    /**
     * @return bool
     */
    public function isActive()
    {
        return self::getStoreConfig('active', true);
    }

    public function getApiKey()
    {
        return self::getStoreConfig('api_key');
    }

    public function isDebugActive()
    {
        return self::getStoreConfig('debug_active', true);
    }

    public function isSendAllOrdersActive()
    {
        return self::getStoreConfig('send_all_orders_active', true);
    }

    public function getTitle()
    {
        return self::getStoreConfig('title');
    }

    public function getAllowedMethods()
    {
        return explode(',', self::getStoreConfig('allowed_methods'));
    }

    public function getMaxTimeslots()
    {
        return self::getStoreConfig('max_timeslots');
    }

    public function isEnabledProductActive()
    {
        return self::getStoreConfig('enabled_product_active', true);
    }

    public function getEnabledProductIds()
    {
        return explode(',', self::getStoreConfig('enabled_product_ids'));
    }

    public function isEnabledProductAttributeActive()
    {
        return self::getStoreConfig('enabled_product_attribute_active', true);
    }

    public function getEnabledProductAttributeCode()
    {
        return self::getStoreConfig('enabled_product_attribute_code');
    }

    public function getEnabledProductAttributeValue()
    {
        return self::getStoreConfig('enabled_product_attribute_value');
    }

    // Begin helper methods for authority to leave
    //    and delivery instructions fields

    public function getAuthorityToLeaveId()
    {
        return self::AUTHORITY_TO_LEAVE_ID;
    }

    public function getDeliveryInstructionsId()
    {
        return self::DELIVERY_INSTRUCTIONS_ID;
    }

    /**
     * Attempts to get the region code (ie: VIC), using the postcode
     * Used as a fallback mechanism where the address does not contain region data
     * (ie: saved addresses with text based region, or a postcode estimate shipping request)
     *
     * @uses  Postcode ranges from https://en.wikipedia.org/wiki/Postcodes_in_Australia
     *
     * @param  string $postcode The postcode
     * @return mixed            The region code, or false if unable to determine
     */
    public function getRegionCodeFromPostcode($postcode)
    {
        $postcode = (int) $postcode;

        if ($postcode >= 1000 && $postcode <= 2599
            || $postcode >= 2619 && $postcode <= 2899
            || $postcode >= 2921 && $postcode <= 2999) {
            return 'NSW';
        }
        elseif ($postcode >= 200 && $postcode <= 299
            || $postcode >= 2600 && $postcode <= 2618
            || $postcode >= 2900 && $postcode <= 2920) {
            return 'ACT';
        }
        elseif ($postcode >= 3000 && $postcode <= 3999
            || $postcode >= 8000 && $postcode <= 8999) {
            return 'VIC';
        }
        elseif ($postcode >= 4000 && $postcode <= 4999
            || $postcode >= 9000 && $postcode <= 9999) {
            return 'QLD';
        }
        elseif ($postcode >= 5000 && $postcode <= 5799
            || $postcode >= 5800 && $postcode <= 5999) {
            return 'SA';
        }
        elseif ($postcode >= 6000 && $postcode <= 6797
            || $postcode >= 6800 && $postcode <= 6999) {
            return 'WA';
        }
        elseif ($postcode >= 7000 && $postcode <= 7799
            || $postcode >= 7800 && $postcode <= 7999) {
            return 'TAS';
        }
        elseif ($postcode >= 800 && $postcode <= 899
            || $postcode >= 900 && $postcode <= 999) {
            return 'NT';
        }
        else {
            return false;
        }
    }
}