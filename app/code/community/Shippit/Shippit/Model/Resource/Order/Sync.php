<?php

class Shippit_Shippit_Model_Resource_Order_Sync extends Mage_Core_Model_Resource_Db_Abstract
{
    protected function _construct()
    {
        $this->_init('shippit/order_sync', 'sync_id');
    }
}