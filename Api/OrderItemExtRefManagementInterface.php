<?php

namespace Webshipr\Shipping\Api;

/**
 * Interface OrderItemExtRefManagementInterface
 * @copyright 2018 webshipr.com
 * @api
 */
interface OrderItemExtRefManagementInterface
{
    /**
     * @param string $extRef
     * @return \Magento\Sales\Api\Data\OrderItemInterface
     * @throws \Magento\Framework\Exception\InputException        if empty external reference argument was provided
     * @throws \Magento\Framework\Exception\NoSuchEntityException if order item could not be found
     */
    public function getEntity(string $extRef): \Magento\Sales\Api\Data\OrderItemInterface;

    /**
     * @param \Magento\Sales\Api\Data\OrderItemInterface $order
     * @return string
     */
    public function getExtRef(\Magento\Sales\Api\Data\OrderItemInterface $order): string;

    /**
     * Test if provided order item match the provided external reference
     *
     * @param \Magento\Sales\Api\Data\OrderItemInterface $orderItem
     * @param string                                     $extRef
     * @return bool
     */
    public function equality(\Magento\Sales\Api\Data\OrderItemInterface $orderItem, string $extRef): bool;
}
