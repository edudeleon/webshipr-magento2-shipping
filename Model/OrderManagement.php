<?php
/**
 * @copyright 2018 webshipr.com
 */

namespace Webshipr\Shipping\Model;

use Webshipr\Shipping\Api\OrderManagementInterface;
use Webshipr\Shipping\Model\Config as WebshiprConfig;

class OrderManagement implements OrderManagementInterface
{
    /**
     * {@inheritDoc}
     */
    public function usesWebshiprDropPoint(\Magento\Sales\Model\Order $order): bool
    {
        $shippingMethod = $order->getShippingMethod();
        return $this->isWebshiprShippingMethod($shippingMethod) && substr_count($shippingMethod, '_') === 3;
    }

    /**
     * {@inheritDoc}
     */
    public function getWebshiprDropPointId(\Magento\Sales\Model\Order $order)
    {
        return $this->orderShippingMethodData($order, 3);
    }

    /**
     * {@inheritDoc}
     */
    public function getWebshiprShippingRateId(\Magento\Sales\Model\Order $order)
    {
        return $this->orderShippingMethodData($order, 2);
    }

    /**
     * {@inheritDoc}
     */
    public function usesWebshiprShippingMethod(\Magento\Sales\Model\Order $order): bool
    {
        $shippingMethod = $order->getShippingMethod();
        return $this->isWebshiprShippingMethod($shippingMethod);
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @param int                        $position
     * @return int|null
     */
    private function orderShippingMethodData(\Magento\Sales\Model\Order $order, int $position)
    {
        $shippingMethod = $order->getShippingMethod();
        if ($this->isWebshiprShippingMethod($shippingMethod)) {
            $data = explode('_', $shippingMethod);
            if (isset($data[$position])) {
                return (int)$data[$position];
            }
        }

        return null;
    }

    /**
     * @param string $shippingMethod e.g. webshipr_123_2131_23
     * @return bool
     */
    private function isWebshiprShippingMethod($shippingMethod)
    {
        return substr($shippingMethod, 0, strlen(WebshiprConfig::SHIPPING_METHOD_CODE)) === WebshiprConfig::SHIPPING_METHOD_CODE;
    }
}
