<?php
/**
 * @copyright 2018 webshipr.com
 */

namespace Webshipr\Shipping\Model\Order;

class ToWebshiprDiscount
{
    /**
     * @param \Magento\Sales\Api\Data\OrderAddressInterface $address
     * @return array
     */
    public function convert(\Magento\Sales\Api\Data\OrderInterface $order): array
    {
        $data = [];
        $discountAmount = abs($order->getDiscountAmount());
        if ($discountAmount > 0) {
            $data[] = [
                'price'        => (float)$discountAmount,
                'tax_included' => false,
                'tax_percent'  => 0.0,
            ];
        }

        return $data;
    }
}
