<?php

class Shippit_Shippit_Model_Order_Sync_Config extends Mage_Core_Model_Abstract
{
    public function getStatus()
    {
        return array(
            Shippit_Shippit_Model_Order_Sync::STATUS_PENDING => Shippit_Shippit_Model_Order_Sync::STATUS_PENDING_TEXT,
            Shippit_Shippit_Model_Order_Sync::STATUS_SYNCED => Shippit_Shippit_Model_Order_Sync::STATUS_SYNCED_TEXT,
            Shippit_Shippit_Model_Order_Sync::STATUS_FAILED => Shippit_Shippit_Model_Order_Sync::STATUS_FAILED_TEXT,
        );
    }
}