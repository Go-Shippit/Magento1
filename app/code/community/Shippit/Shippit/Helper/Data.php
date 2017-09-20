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

class Shippit_Shippit_Helper_Data extends Mage_Core_Helper_Abstract
{
    /**
     * Path to module general config options
     */
    const XML_PATH_SETTINGS = 'shippit/general/';

    const AUTHORITY_TO_LEAVE_ID = 'shippit_authority_to_leave';
    const DELIVERY_INSTRUCTIONS_ID = 'shippit_delivery_instructions';

    const SYNC_MODE_REALTIME = 'realtime';
    const SYNC_MODE_CRON = 'cron';
    const SYNC_MODE_CUSTOM = 'custom';

    const CARRIER_CODE = 'shippit';
    const CARRIER_CODE_CC = 'shippit_cc';

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

    public function getEnvironment()
    {
        return self::getStoreConfig('environment');
    }

    public function isDebugActive()
    {
        return self::getStoreConfig('debug_active', true);
    }

    /**
     * Begin Helpers not related to configuration values retrieval
     */

    public function getCarrierCode()
    {
        return self::CARRIER_CODE;
    }

    public function getModuleVersion()
    {
        $version = (string) Mage::getConfig()
            ->getNode('modules/Shippit_Shippit/version');

        return $version;
    }

    // Helper methods for authority to leave and delivery instructions fields

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
    public function getStateFromPostcode($postcode)
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
