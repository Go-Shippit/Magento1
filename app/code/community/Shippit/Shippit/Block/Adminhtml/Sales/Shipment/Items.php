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

class Shippit_Shippit_Block_Adminhtml_Sales_Shipment_Items extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{

    public function render(Varien_Object $row)
    {
        return $this->decorateItems($row->getItems());
    }

    public function decorateItems($items)
    {
        if (empty($items)) {
            return 'All Items';
        }

        $table = '<table class="data-table shipments-table" cellspacing="0">';
        $table .= '<thead>';
        $table .= '<tr>';
        $table .= '<th>Sku</th>';
        $table .= '<th>Title</th>';
        $table .= '<th>Qty</th>';
        $table .= '</tr>';
        $table .= '</thead>';

        $table .= '<tbody>';

        foreach ($items as $item) {
            $table .= '<tr>';
            $table .= '<td>' . $item->getSku() . '</td>';
            $table .= '<td>' . $item->getTitle() . '</td>';
            $table .= '<td>' . $item->getQty() . '</td>';
            $table .= '</tr>';
        }

        $table .= '</tbody>';
        $table .= '</table>';

        return $table;
    }
}
