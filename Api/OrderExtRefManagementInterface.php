<?php

namespace Webshipr\Shipping\Api;

/**
 * Interface OrderExtRefManagementInterface
 * @copyright 2018 webshipr.com
 * @api
 */
interface OrderExtRefManagementInterface
{
    /**
     * @param string $extRef
     * @return \Magento\Sales\Api\Data\OrderInterface
     * @throws \Magento\Framework\Exception\InputException        if empty external reference argument was provided
     * @throws \Magento\Framework\Exception\NoSuchEntityException if order could not be found
     */
    public function getEntity(string $extRef): \Magento\Sales\Api\Data\OrderInterface;

    /**
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @return string
     */
    public function getExtRef(\Magento\Sales\Api\Data\OrderInterface $order): string;

    /**
     * Test if provided order match the provided external reference
     *
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @param string                                 $extRef
     * @return bool
     */
    public function equality(\Magento\Sales\Api\Data\OrderInterface $order, string $extRef): bool;
}
