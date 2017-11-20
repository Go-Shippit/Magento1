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

class Shippit_Shippit_Model_Resource_Sync_Shipment_Item_Collection extends Mage_Core_Model_Resource_Db_Collection_Abstract
{
    protected function _construct()
    {
        $this->_init('shippit/sync_shipment_item');
    }

    /**
     * Filter the items collection by the Sync Shipment
     *
     * @param Shippit_Shippit_Model_Sync_Shipment $syncShipment The Sync Shipment Object
     */
    public function addSyncShipmentFilter(Shippit_Shippit_Model_Sync_Shipment $syncShipment)
    {
        $syncShipmentId = $syncShipment->getSyncId();

        if ($syncShipmentId) {
            $this->addFieldToFilter('sync_id', $syncShipmentId);
        }
        else {
            $this->addFieldToFilter('sync_id', null);
        }

        return $this;
    }
}
