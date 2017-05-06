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

class Shippit_Shippit_Model_System_Config_Source_Order_Status_Processing
{
    public function toOptionArray()
    {
        $optionsArray = array();

        $statuses = Mage::getSingleton('sales/order_config')
            ->getStateStatuses(Mage_Sales_Model_Order::STATE_PROCESSING);

        foreach ($statuses as $statusCode => $statusLabel) {
            $optionsArray[] = array(
                'value' => $statusCode,
                'label' => $statusLabel
            );
        }

        return $optionsArray;
    }
}
