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

class Shippit_Shippit_Block_Adminhtml_Sales_Order_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('shippit_order_grid');
        $this->setDefaultSort('sync_id');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
    }

    protected function _prepareCollection()
    {
        $collection = Mage::getResourceModel('shippit/sync_order_collection')
            ->addFieldToSelect(
                array(
                    'sync_id',
                    'shippit_shipping_method' => 'shipping_method',
                    'track_number',
                    'synced_at',
                    'sync_status' => 'status'
                )
            )
            ->join(
                array(
                    'order' => 'sales/order'
                ),
                'main_table.order_id = order.entity_id',
                array(
                    'increment_id'         => 'increment_id',
                    'grand_total'          => 'grand_total',
                    'order_state'          => 'state',
                    'order_status'         => 'status',
                    'created_at'           => 'created_at',
                )
            )
            ->addFilterToMap(
                'sync_status',
                'main_table.status'
            )
            ->addFilterToMap(
                'shippit_shipping_method',
                'main_table.shipping_method'
            )
            ->addFilterToMap(
                'order_state',
                'order.state'
            )
            ->addFilterToMap(
                'order_status',
                'order.status'
            );

        $this->setCollection($collection);
        parent::_prepareCollection();

        return $this;
    }

    protected function _prepareColumns()
    {
        $helper = Mage::helper('shippit');
        $currency = (string) Mage::getStoreConfig(Mage_Directory_Model_Currency::XML_PATH_CURRENCY_BASE);

        $this->addColumn('sync_id', array(
            'header' => $helper->__('ID'),
            'index'  => 'sync_id',
            'column_css_class' => 'no-display',
            'header_css_class' => 'no-display'
        ));

        $this->addColumn('increment_id', array(
            'header' => $helper->__('Order #'),
            'index'  => 'increment_id',
        ));

        $this->addColumn('purchased_on', array(
            'header' => $helper->__('Purchased On'),
            'type'   => 'datetime',
            'index'  => 'created_at'
        ));

        $this->addColumn('items', array(
            'filter' => false,
            'header' => $helper->__('Items'),
            'getter' => 'getItems',
            'renderer' => 'shippit/adminhtml_sales_order_items'
        ));

        $this->addColumn('grand_total', array(
            'header'        => $helper->__('Grand Total'),
            'index'         => 'grand_total',
            'type'          => 'currency',
            'currency_code' => $currency
        ));

        $this->addColumn('shippit_shipping_method', array(
            'header' => $helper->__('Shipping Method'),
            'index'  => 'shippit_shipping_method',
            'frame_callback' => array($this, 'decorateServiceClass'),
            'type'   => 'options',
            'options' => Mage::getSingleton('shippit/system_config_source_shippit_shipping_methods')->toArray()
        ));

        $this->addColumn('order_state', array(
            'header'  => $helper->__('State'),
            'index'   => 'order_state',
            'type'    => 'options',
            'options' => Mage::getSingleton('sales/order_config')->getStates(),
        ));

        $this->addColumn('order_status', array(
            'header'  => $helper->__('Status'),
            'index'   => 'order_status',
            'type'    => 'options',
            'options' => Mage::getSingleton('sales/order_config')->getStatuses(),
        ));

        $this->addColumn('track_number', array(
            'header' => $helper->__('Shippit Reference'),
            'index'  => 'track_number',
            'frame_callback' => array($this, 'decorateTrackNumber'),
        ));

        $this->addColumn('synced_at', array(
            'header' => $helper->__('Synced At'),
            'type'   => 'datetime',
            'index'  => 'synced_at'
        ));

        $this->addColumn('sync_status', array(
            'header'  => $helper->__('Sync Status'),
            'index'   => 'sync_status',
            'type'    => 'options',
            'options' => Mage::getSingleton('shippit/sync_order_config')->getStatus(),
            'frame_callback' => array($this, 'decorateStatus'),
        ));

        $this->addColumn('actions', array(
            'header' => Mage::helper('shippit')->__('Action'),
            'width' => '150px',
            'type' => 'action',
            'getter' => 'getId',
            'actions' => array(
                array(
                    'caption' => Mage::helper('shippit')->__('Sync Now'),
                    'url' => array('base'=>'*/*/sync'),
                    'field' => 'id'
                ),
                array('caption' => Mage::helper('shippit')->__('Schedule Sync'),
                    'url' => array('base'=>'*/*/schedule'),
                    'field' => 'id'
                ),
                array('caption' => Mage::helper('shippit')->__('Remove'),
                    'url' => array('base'=>'*/*/remove'),
                    'field' => 'id'
                ),
                'filter' => false,
                'sortable' => false
            )
        ));

        return parent::_prepareColumns();
    }

    public function decorateServiceClass($value)
    {
        return ucfirst($value);
    }

    public function decorateTrackNumber($value)
    {
        if (empty($value)) {
            return $value;
        }

        $cell = sprintf(
            '<a href="https://www.shippit.com/track/%s" title="Track Order" target="_blank">%s</a>',
            $value,
            $value
        );

        return $cell;
    }

    public function decorateStatus($value)
    {
        $cell = sprintf(
            '<span class="grid-severity-%s"><span>%s</span></span>',
            $this->getGridSeverity($value),
            $value
        );
        return $cell;
    }

    public function getGridSeverity($value)
    {
        $gridSeverity = 'critical';

        switch ($value) {
            case Shippit_Shippit_Model_Sync_Order::STATUS_PENDING_TEXT:
                $gridSeverity = 'minor';
                break;
            case Shippit_Shippit_Model_Sync_Order::STATUS_FAILED_TEXT:
                $gridSeverity = 'critical';
                break;
            case Shippit_Shippit_Model_Sync_Order::STATUS_SYNCED_TEXT:
                $gridSeverity = 'notice';
                break;
        }

        return $gridSeverity;
    }

    protected function _prepareMassaction(){
        $this->setMassactionIdField('sync_id');
        $this->getMassactionBlock()->setFormFieldName('sync_ids');

        $this->getMassactionBlock()->addItem(
            'Sync Now',
            array(
                'label' => Mage::helper('shippit')->__('Sync Now'),
                'url' => $this->getUrl('*/*/massSync')
            )
        );

        $this->getMassactionBlock()->addItem(
            'Schedule Sync',
            array(
                'label' => Mage::helper('shippit')->__('Schedule Sync'),
                'url' => $this->getUrl('*/*/massSchedule')
            )
        );

        $this->getMassactionBlock()->addItem(
            'Remove',
            array(
                'label' => Mage::helper('shippit')->__('Remove'),
                'url' => $this->getUrl('*/*/massRemove')
            )
        );
    }

    public function getRowUrl($row)
    {

    }

    public function getGridUrl()
    {
        return $this->getUrl('*/*/grid', array('_current'=>true));
    }
}
