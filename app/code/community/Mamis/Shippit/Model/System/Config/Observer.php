<?php
/**
*  Mamis.IT
*
*  NOTICE OF LICENSE
*
*  This source file is subject to the EULA
*  that is available through the world-wide-web at this URL:
*  http://www.mamis.com.au/licencing
*
*  @category   Mamis
*  @copyright  Copyright (c) 2015 by Mamis.IT Pty Ltd (http://www.mamis.com.au)
*  @author     Matthew Muscat <matthew@mamis.com.au>
*  @license    http://www.mamis.com.au/licencing
*/

class Mamis_Shippit_Model_System_Config_Observer
{
    public function checkApiKey(Varien_Event_Observer $observer)
    {
        if (Mage::app()->getRequest()->getParam('section') != 'carriers') {
            return;
        }

        try {
            $merchant = Mage::helper('mamis_shippit/api')->getMerchant();

            Mage::log($merchant);

            if ($merchant->error == 'invalid_merchant_account') {
                Mage::getSingleton('adminhtml/session')->addError(
                    Mage::helper('mamis_shippit')->__('Shippit configuration error: Please check the API Key')
                );
            }
        }
        catch (Exception $e) {
            Mage::getSingleton('adminhtml/session')->addError('Shippit API error: An error occured while communicating with the Shippit API');
        }

        return;
    }
}