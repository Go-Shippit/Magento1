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
 * @copyright  Copyright (c) 2016 by Shippit Pty Ltd (http://www.shippit.com)
 * @author     Matthew Muscat <matthew@mamis.com.au>
 * @license    http://www.shippit.com/terms
 */

class Shippit_Shippit_Helper_Bugsnag extends Mage_Core_Helper_Abstract
{
    private $severites = 'fatal,error';
    private $client = false;
    private $shippitBugsnagApiKey = 'b2873ea2ae95a3c9f2cb63ca1557abb5';

    public function init()
    {
        if (!$this->client) {
            // Allow override of bugsnag key
            // this can be your own bugsnag api key, or an empty string
            // to disable bugsnag logging if required
            $apiKey = Mage::getStoreConfig('shippit/bugsnag/api_key');

            // If no api key is provided, use the shippit bugsnag api key
            if (is_null($apiKey)) {
                $apiKey = $this->shippitBugsnagApiKey;
            }
            // Otherwise, if the api key is an empty value,
            // don't run bugsnag and return early
            elseif (empty($apiKey)) {
                return $this->client;
            }

            if (file_exists(Mage::getBaseDir('lib') . '/shippit-bugsnag/Autoload.php')) {
                require_once(Mage::getBaseDir('lib') . '/shippit-bugsnag/Autoload.php');
            }
            else {
                Mage::log('Shippit Bugsnag Error', 'Couldn\'t activate Bugsnag Error Monitoring due to missing Bugsnag PHP library!', null, 'shippit.log');

                return false;
            }

            $this->client = new Bugsnag_Client($apiKey);
            $this->client->setReleaseStage($this->getReleaseStage())
                 ->setErrorReportingLevel($this->getErrorReportingLevel())
                 ->setMetaData($this->getMetaData());

            $this->client->setNotifier($this->getNotiferData());
        }

        return $this->client;
    }

    public function getReleaseStage()
    {
        return Mage::getIsDeveloperMode() ? "development" : "production";
    }

    public function getMetaData()
    {
        $metaData = array();

        $metaData['magento'] = array(
            'edition' => $this->getEdition(),
            'version' => Mage::getVersion(),
        );
        $metaData['module'] = $this->getModuleInfo();
        $metaData['store'] = array(
            'url' => Mage::getBaseUrl(),
            'store' => Mage::getStoreConfig('general/store_information/name'),
            'contact_number' => Mage::getStoreConfig('general/store_information/phone'),
        );

        return $metaData;
    }

    public function getModuleInfo()
    {
        return array(
            'name' => 'Shippit_Shippit',
            'version' => Mage::helper('shippit')->getModuleVersion(),
        );
    }

    public function getNotiferData()
    {
        return $this->getModuleInfo();
    }

    private function getErrorReportingLevel()
    {
        if (empty($this->severites)) {
            $severites = "fatal,error";
        }
        else {
            $severites = $this->severites;
        }

        $level = 0;
        $severities = explode(",", $severites);

        foreach ($severities as $severity) {
            $level |= Bugsnag_ErrorTypes::getLevelsForSeverity($severity);
        }

        return $level;
    }

    private function getEdition()
    {
        $mage = new Mage;

        if (method_exists($mage, 'getEdition')) {
            return Mage::getEdition();
        }
        else {
            return 'Unknown';
        }
    }
}
