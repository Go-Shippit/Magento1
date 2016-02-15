<?php
 
class Shippit_Shippit_Block_Adminhtml_Sales_Order extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    public function __construct()
    {
        $this->_blockGroup = 'shippit';
        $this->_controller = 'adminhtml_sales_order';
        $this->_headerText = Mage::helper('shippit')->__('Shippit Orders');
 
        parent::__construct();
        $this->_removeButton('add');
    }
}