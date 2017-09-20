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

class Shippit_Shippit_Model_System_Config_Source_Shipping_Methods
{
    public function toOptionArray($excludeShippit = false)
    {
        $optionsArray = array();
        $carriers = Mage::getSingleton('shipping/config')->getAllCarriers();

        foreach ($carriers as $carrierCode => $carrier) {
            // if the carrier is shippit, exclude it from the
            // returned results
            if ($excludeShippit && ($carrierCode == 'shippit' || $carrierCode == 'shippit_cc')) {
                continue;
            }

            if ($methods = $carrier->getAllowedMethods()) {
                if (!$methodTitle = Mage::getStoreConfig("carriers/$carrierCode/title")) {
                    $methodTitle = $carrierCode;
                }

                foreach ($methods as $methodCode => $method) {
                    $carrierMethodCode = $carrierCode . '_' . $methodCode;
                    $optionsArray[] = array(
                        'label' => $methodTitle . ' (' . $methodCode . ')',
                        'value' => $carrierMethodCode
                    );
                }
            }
        }

        return $optionsArray;
    }
}
