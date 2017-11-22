<?php
/**
 * Mamis.IT Pty Ltd
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is available through the world-wide-web at this URL:
 * http://www.mamis.com.au/licencing
 *
 * @copyright  Copyright (c) by Mamis.IT Pty Ltd (http://www.mamis.com.au)
 * @author     Matthew Muscat <matthew@mamis.com.au>
 * @license    http://www.mamis.com.au/licencing
 */

class Shippit_Shippit_Block_Adminhtml_Sales_Order_View_Info extends Mage_Core_Block_Template
{
    public function getOrder()
    {
        return Mage::registry('sales_order');
    }

    public function getShippitAuthorityToLeave()
    {
        $shippitAuthorityToLeave = $this->getOrder()->getShippitAuthorityToLeave();

        if ($shippitAuthorityToLeave == 1) {
            return 'Yes';
        }
        elseif ($shippitAuthorityToLeave == 0)  {
            return 'No';
        }

        return 'N/A';
    }

    public function getShippitDeliveryInstructions()
    {
        $shippitDeliveryInstructions = $this->getOrder()->getShippitDeliveryInstructions();

        if (!empty($shippitDeliveryInstructions)) {
            return $shippitDeliveryInstructions;
        }

        return 'N/A';
    }
}
