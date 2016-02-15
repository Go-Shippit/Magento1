<?php

class Shippit_Shippit_Helper_Bugsnag extends Mage_Core_Helper_Abstract
{
    private $apiKey = 'b2873ea2ae95a3c9f2cb63ca1557abb5';
    private $severites = 'fatal,error';
    private $client = false;

    public function init()
    {
        if (!$this->client) {
            if (file_exists(Mage::getBaseDir('lib') . '/shippit-bugsnag/Autoload.php')) {
                require_once(Mage::getBaseDir('lib') . '/shippit-bugsnag/Autoload.php');
            }
            else {
                Mage::log('Shippit Bugsnag Error: Couldn\'t activate Bugsnag Error Monitoring due to missing Bugsnag PHP library!', null, 'shippit.log');
                
                return;
            }

            $this->notifySeverities = Mage::getStoreConfig("dev/Bugsnag_Notifier/severites");
            $this->filterFields = Mage::getStoreConfig("dev/Bugsnag_Notifier/filterFiels");

            if (!empty($this->apiKey)) {
                $this->client = new Bugsnag_Client($this->apiKey);
                $this->client->setReleaseStage($this->getReleaseStage())
                     ->setErrorReportingLevel($this->getErrorReportingLevel())
                     ->setMetaData($this->getMetaData());
                
                $this->client->setNotifier($this->getNotiferData());
                
                set_error_handler(
                    array($this->client, "errorHandler")
                );
                set_exception_handler(
                    array($this->client, "exceptionHandler")
                );
            }
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
            'edition' => Mage::getEdition(),
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

        foreach($severities as $severity) {
            $level |= Bugsnag_ErrorTypes::getLevelsForSeverity($severity);
        }

        return $level;
    }
}