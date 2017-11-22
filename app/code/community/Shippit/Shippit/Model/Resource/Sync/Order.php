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

class Shippit_Shippit_Model_Resource_Sync_Order extends Mage_Core_Model_Resource_Db_Abstract
{
    protected function _construct()
    {
        $this->_init('shippit/sync_order', 'sync_id');
    }

    /**
     * Perform operations before object save
     *
     * @param Mage_Core_Model_Abstract $object
     * @return Shippit_Shippit_Model_Resource_Sync_Order
     */
    protected function _beforeSave(Mage_Core_Model_Abstract $object)
    {
        if (!$object->getId()) {
            $object->setCreatedAt(Mage::getSingleton('core/date')->gmtDate());
        }

        return $this;
    }

    /**
     * Save related items to the Sync Order
     *
     * @param Mage_Core_Model_Abstract $customer
     * @return Shippit_Shippit_Model_Sync_Order
     */
    protected function _afterSave(Mage_Core_Model_Abstract $syncOrder)
    {
        $this->_saveItems($syncOrder);

        return parent::_afterSave($syncOrder);
    }

    /**
     * Save the items attached to the sync order
     *
     * @param  Shippit_Shippit_Model_Sync_Order $syncOrder The Sync Order Object
     * @return Shippit_Shippit_Model_Sync_Order The Sync Order Object
     */
    protected function _saveItems(Shippit_Shippit_Model_Sync_Order $syncOrder)
    {
        foreach ($syncOrder->getItems() as $item) {
            $item->setSyncId($syncOrder->getId())
                ->save();
        }

        return $this;
    }
}
