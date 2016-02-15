<?php

class Shippit_Shippit_Model_Resource_Order_Sync_Collection extends Mage_Core_Model_Resource_Db_Collection_Abstract
{
    protected function _construct()
    {
        $this->_init('shippit/order_sync');
    }

    public function getAllOrderIds()
    {
        $idsSelect = clone $this->getSelect();
        $idsSelect->reset(Zend_Db_Select::ORDER);
        $idsSelect->reset(Zend_Db_Select::LIMIT_COUNT);
        $idsSelect->reset(Zend_Db_Select::LIMIT_OFFSET);
        $idsSelect->reset(Zend_Db_Select::COLUMNS);

        $idsSelect->columns('order_id', 'main_table');
        
        return $this->getConnection()->fetchCol($idsSelect);
    }
}