<?php

class Shippit_Shippit_Block_Adminhtml_System_Config_Form_Field_Renderer_Shipping_Methods extends Mage_Core_Block_Abstract
{
    protected function _toHtml()
    {
        $column = $this->getColumn();
        $options = Mage::getModel('shippit/system_config_source_shipping_methods')->toOptionArray();

        foreach ($options as $option) {
            $optionsHtml[] = '<option value="' . $option['value'] . '">' . $option['label'] . "</option>";
        }
        
        return '<select class="'.$column['class'].'" style="'.$column['style'].'" name="'.$this->getInputName().'">'.implode('', $optionsHtml).'</select>';
    }
}