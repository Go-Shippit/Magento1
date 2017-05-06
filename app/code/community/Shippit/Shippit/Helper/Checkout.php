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

class Shippit_Shippit_Helper_Checkout extends Shippit_Shippit_Helper_Data
{
    /**
     * Path to module sync order config options
     */
    const XML_PATH_SETTINGS = 'shippit/checkout/';

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
    public function isAuthorityToLeaveActive()
    {
        return parent::isActive() && self::getStoreConfig('authority_to_leave_active', true);
    }

    /**
     * @return bool
     */
    public function isDeliveryInstructionsActive()
    {
        return parent::isActive() && self::getStoreConfig('delivery_instructions_active', true);
    }
}
