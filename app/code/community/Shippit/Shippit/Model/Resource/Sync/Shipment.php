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

class Shippit_Shippit_Model_Resource_Sync_Shipment extends Mage_Core_Model_Resource_Db_Abstract
{
    protected function _construct()
    {
        $this->_init('shippit/sync_shipment', 'sync_id');
    }

    /**
     * Perform operations before object save
     *
     * @param Mage_Core_Model_Abstract $object
     * @return Shippit_Shippit_Model_Resource_Sync_Shipment
     */
    protected function _beforeSave(Mage_Core_Model_Abstract $object)
    {
        if (!$object->getId()) {
            $object->setCreatedAt(Mage::getSingleton('core/date')->gmtDate());
        }

        return $this;
    }

    /**
     * Save related items to the Sync Shipment
     *
     * @param Mage_Core_Model_Abstract $syncShipment
     * @return Shippit_Shippit_Model_Sync_Shipment
     */
    protected function _afterSave(Mage_Core_Model_Abstract $syncShipment)
    {
        $this->_saveItems($syncShipment);

        return parent::_afterSave($syncShipment);
    }

    /**
     * Save the items attached to the sync shipment
     *
     * @param  Shippit_Shippit_Model_Sync_Shipment $syncShipment The Sync Shipment Object
     * @return Shippit_Shippit_Model_Sync_Shipment The Sync Shipment Object
     */
    protected function _saveItems(Shippit_Shippit_Model_Sync_Shipment $syncShipment)
    {
        foreach ($syncShipment->getItems() as $item) {
            $item->setSyncId($syncShipment->getId())
                ->save();
        }

        return $this;
    }
}
