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

    public function getAllowedMethods()
    {
        return self::getStoreConfig('allowed_methods');
    }

    public function getTitle()
    {
        return self::getStoreConfig('title');
    }

    public function getMaxTimeslots()
    {
        return self::getStoreConfig('max_timeslots');
    }

    public function isSendAllOrdersActive()
    {
        return self::getStoreConfig('send_all_orders_active', true);
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
}