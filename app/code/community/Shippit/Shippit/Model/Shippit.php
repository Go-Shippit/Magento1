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

class Shippit_Shippit_Model_Shippit extends Mage_Core_Model_Abstract
{
    protected $helper;

    public function __construct() {
        $this->helper = Mage::helper('shippit');

        return parent::__construct();
    }

    /**
     * Adds the order to the request queue,
     * if the mode is requested as realtime,
     * attempts to sync the record immediately.
     *
     * @param integer $entityId             The order entity_id
     * @param array   $items                An array of the items to be included
     * @param string  $shippingMethod       The shipping method service class to be used (standard, express)
     * @param string  $apiKey               The API Key to be used in the request
     * @param string  $syncMode             The sync mode ot be used for the request
     * @param boolean $displayNotifications Flag to indiciate if notifications should be shown to the user
     */
    public function addOrder($entityId, $items = array(), $shippingMethod = null, $apiKey = null, $syncMode = null, $displayNotifications = false)
    {
        // Ensure the module is active
        if (!$this->helper->isActive()) {
            return $this;
        }

        $request = Mage::getModel('shippit/request_sync_order')
            ->setOrderId($entityId)
            ->setItems($items)
            ->setApiKey($apiKey)
            ->setShippingMethod($shippingMethod);

        // Create a new sync order record
        $syncOrder = Mage::getModel('shippit/sync_order')->addRequest($request)
            ->save();

        // sync immediately if sync mode is realtime,
        if ($syncMode == Shippit_Shippit_Helper_Data::SYNC_MODE_REALTIME) {
            // return the result of the sync
            return Mage::getModel('shippit/api_order')->sync($syncOrder, $displayNotifications);
        }

        // return the sync order object
        return $syncOrder;
    }
}
