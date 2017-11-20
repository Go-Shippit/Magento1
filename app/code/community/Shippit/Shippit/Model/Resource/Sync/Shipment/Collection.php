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

class Shippit_Shippit_Model_Resource_Sync_Shipment_Collection extends Mage_Core_Model_Resource_Db_Collection_Abstract
{
    protected function _construct()
    {
        $this->_init('shippit/sync_shipment');
    }

    /**
     * Mass update the current collection with data
     *
     * @param $data
     * @return int
     */
    public function massUpdate(array $data)
    {
        $this->getConnection()->update(
            $this->getResource()->getMainTable(),
            $data,
            $this->getResource()->getIdFieldName()
                . ' IN('
                    . implode(',', $this->getAllIds())
                . ')'
        );

        return $this;
    }
}
