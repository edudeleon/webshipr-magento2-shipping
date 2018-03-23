<?php

namespace Webshipr\Shipping\Api;

/**
 * Interface OrderManagementInterface
 * @copyright 2018 webshipr.com
 * @api
 */
interface OrderManagementInterface
{
    /**
     * Test if order uses a Webshipr drop point
     *
     * @param \Magento\Sales\Model\Order $order
     * @return bool
     */
    public function usesWebshiprDropPoint(\Magento\Sales\Model\Order $order): bool;

    /**
     * Return Webshipr drop point ID
     *
     * @param \Magento\Sales\Model\Order $order
     * @return int|null
     */
    public function getWebshiprDropPointId(\Magento\Sales\Model\Order $order);

    /**
     * Return Webshipr shipping rate ID
     *
     * @param \Magento\Sales\Model\Order $order
     * @return int|null
     */
    public function getWebshiprShippingRateId(\Magento\Sales\Model\Order $order);

    /**
     * Check if order shipping method is Webshipr type
     * @param \Magento\Sales\Model\Order $order
     * @return bool
     */
    public function usesWebshiprShippingMethod(\Magento\Sales\Model\Order $order): bool;
}
