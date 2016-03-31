<?php
/**
*  Shippit Pty Ltd
*
*  NOTICE OF LICENSE
*
*  This source file is subject to the terms
*  that is available through the world-wide-web at this URL:
*  http://www.shippit.com/terms
*
*  @category   Shippit
*  @copyright  Copyright (c) 2016 by Shippit Pty Ltd (http://www.shippit.com)
*  @author     Matthew Muscat <matthew@mamis.com.au>
*  @license    http://www.shippit.com/terms
*/

class Shippit_Shippit_Model_Observer_System_Config
{
    public function checkApiKey(Varien_Event_Observer $observer)
    {
        if (Mage::app()->getRequest()->getParam('section') != 'carriers') {
            return;
        }

        try {
            $api = Mage::helper('shippit/api');
            $apiKeyValid = false;

            $merchant = $api->getMerchant();

            if (property_exists($merchant, 'error')) {
                if ($merchant->error == 'invalid_merchant_account') {
                    Mage::getSingleton('adminhtml/session')->addError(
                        Mage::helper('shippit')->__('Shippit configuration error: Please check the API Key')
                    );
                }
                else {
                    Mage::getSingleton('adminhtml/session')->addError(
                        Mage::helper('shippit')->__('Shippit API error: ' . $merchant->error)
                    );
                }
            }
            else {
                $apiKeyValid = true;
            }
        }
        catch (Exception $e) {
            Mage::getSingleton('adminhtml/session')->addError('Shippit API error: An error occured while communicating with the Shippit API');
        }

        if ($apiKeyValid) {
            try {
                $apiKey = Mage::helper('shippit')->getApiKey();

                $webhookUrl = Mage::getUrl('shippit/order/update/', array(
                    'api_key' => $apiKey,
                    '_secure' => true,
                ));
                
                $requestData = new Varien_Object;
                $requestData->setWebhookUrl($webhookUrl);
                $merchant = $api->putMerchant($requestData, true);

                if (property_exists($merchant, 'error')) {
                    Mage::getSingleton('adminhtml/session')->addError(
                        Mage::helper('shippit')->__('Shippit Webhook Registration Error: An error occured while registering the webhook with Shippit' . $merchant->error)
                    );
                }
                else {
                    Mage::getSingleton('adminhtml/session')->addSuccess(
                        Mage::helper('shippit')->__('Shippit Webhook Registered: ' . $webhookUrl)
                    );
                }
            }
            catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError('Shippit Webhook Registration Error: An unknown error occured while registering the webhook with Shippit ' . $e->getMessage());
            }
        }

        return;
    }
}