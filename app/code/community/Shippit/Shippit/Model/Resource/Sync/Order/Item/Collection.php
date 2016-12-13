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
 * @copyright  Copyright (c) 2016 by Shippit Pty Ltd (http://www.shippit.com)
 * @author     Matthew Muscat <matthew@mamis.com.au>
 * @license    http://www.shippit.com/terms
 */

class Shippit_Shippit_Model_Resource_Sync_Order_Item_Collection extends Mage_Core_Model_Resource_Db_Collection_Abstract
{
    protected $syncOrder;

    protected function _construct()
    {
        $this->_init('shippit/sync_order_item');
    }

    /**
     * Filter the items collection by the SyncOrder
     * @param Shippit_Shippit_Model_Sync_Order $syncOrder The Sync Order Object
     */
    public function addSyncOrderFilter(Mage_Core_Model_Abstract $syncOrder)
    {
        $syncOrderId = $syncOrder->getSyncId();

        if ($syncOrderId) {
            $this->addFieldToFilter('sync_id', $syncOrderId);
        }
        else {
            $this->addFieldToFilter('sync_id', null);
        }

        return $this;
    }
}
