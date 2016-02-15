<?php

class Shippit_Shippit_Model_Order_Sync extends Mage_Core_Model_Abstract
{
    const STATUS_PENDING = 0;
    const STATUS_PENDING_TEXT = 'Pending';
    const STATUS_SYNCED = 1;
    const STATUS_SYNCED_TEXT = 'Synced';
    const STATUS_FAILED = 2;
    const STATUS_FAILED_TEXT = 'Failed';

    const SYNC_MAX_ATTEMPTS = 5;

    protected function _construct()
    {
        $this->_init('shippit/order_sync');
    }

    public function addOrder($order)
    {
        $sync = array(
            'store_id' => $order->getStoreId(),
            'order_id' => $order->getEntityId(),
            'status' => self::STATUS_PENDING,
        );

        return $this->setData($sync)
            ->save();
    }

    public function getOrder()
    {
        $orderId = $this->getOrderId();

        return Mage::getModel('sales/order')->load($orderId);
    }
}