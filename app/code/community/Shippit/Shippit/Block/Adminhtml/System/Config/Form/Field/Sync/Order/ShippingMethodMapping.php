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

class Shippit_Shippit_Block_Adminhtml_System_Config_Form_Field_Sync_Order_ShippingMethodMapping extends Mage_Adminhtml_Block_System_Config_Form_Field_Array_Abstract
{
    public function __construct()
    {
        $this->addColumn('shipping_method', array(
            'label' => Mage::helper('shippit')->__('Shipping Method'),
            'style' => 'width:200px',
            'renderer' => Mage::app()->getLayout()->createBlock('shippit/adminhtml_system_config_form_field_renderer_shipping_methods'),
        ));
        $this->addColumn('shippit_service', array(
            'label' => Mage::helper('shippit')->__('Shippit Service Class'),
            'style' => 'width:200px',
            'renderer' => Mage::app()->getLayout()->createBlock('shippit/adminhtml_system_config_form_field_renderer_shippit_serviceClass'),
        ));

        $this->_addAfter = false;
        $this->_addButtonLabel = Mage::helper('shippit')->__('Add Mapping');

        parent::__construct();

        // use the array template to auto populate the array of saved data
        $this->setTemplate('shippit/system/config/form/field/array.phtml');
    }
}
