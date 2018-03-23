<?php
/**
 * Copyright © 2017 webshipr.com
 * @autor eduedeleon
 * */

namespace Webshipr\Shipping\Model\Config\Source;

class Customernotification implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Get array of notifications
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => 'notify_disabled',
                'label' => __('Disable notifications'),
            ],
            [
                'value' => 'notify_invoice',
                'label' => __('Notify customer about Invoice'),
            ],
            [
                'value' => 'notify_shipment',
                'label' => __('Notify customer about Shipment'),
            ],
        ];
    }
}
