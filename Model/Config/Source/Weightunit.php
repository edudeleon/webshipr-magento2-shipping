<?php
/**
 * Copyright © 2017 webshipr.com
 * @autor eduedeleon
 * */

namespace Webshipr\Shipping\Model\Config\Source;

class Weightunit implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Get array of weight units
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => '1000',
                'label' => 'Gram',
            ],
            [
                'value' => '1',
                'label' => 'KG',
            ],
        ];
    }
}
