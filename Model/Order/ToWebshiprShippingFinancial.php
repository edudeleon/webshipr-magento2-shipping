<?php
/**
 * @copyright 2018 webshipr.com
 */

namespace Webshipr\Shipping\Model\Order;

class ToWebshiprShippingFinancial
{
    /**
     * @param \Magento\Sales\Api\Data\OrderAddressInterface $address
     * @return array
     */
    public function convert(\Magento\Sales\Api\Data\OrderInterface $order): array
    {
        $data = [];
        $shippingPriceExTax = $order->getShippingInclTax() - $order->getShippingTaxAmount();
        if ($shippingPriceExTax > 0) {
            $data[] = [
                'price_ex_tax' => (float)$shippingPriceExTax,
                'name'         => $order->getShippingDescription(),
                'tax_percent'  => 0.0,    //pending to define
            ];
        }

        return $data;
    }
}
