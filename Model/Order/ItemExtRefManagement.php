<?php

namespace Webshipr\Shipping\Model\Order;

use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;

class ItemExtRefManagement implements \Webshipr\Shipping\Api\OrderItemExtRefManagementInterface
{
    /**
     * @var \Magento\Sales\Api\OrderItemRepositoryInterface
     */
    protected $_orderItemRepository;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $_searchCriteriaBuilder;

    public function __construct(
        \Magento\Sales\Api\OrderItemRepositoryInterface $orderItemRepository,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
    )
    {
        $this->_orderItemRepository = $orderItemRepository;
        $this->_searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * {@inheritdoc}
     */
    public function getEntity(string $extRef): OrderItemInterface
    {
        if ($extRef === '') {
            throw new InputException(__('external reference is required'));
        }

        $records = $this->_orderItemRepository->getList(
            $this->_searchCriteriaBuilder
                ->addFilter(OrderItemInterface::ITEM_ID, $extRef)
                ->create()
        );

        if ($records->getTotalCount() < 1) {
            throw new NoSuchEntityException(__("Requested order item entity doesn't exist"));
        }

        $items = $records->getItems();
        return reset($items);
    }

    /**
     * @param \Magento\Sales\Api\Data\OrderItemInterface $orderItem
     * @return string
     * @throws \Magento\Framework\Exception\InputException
     */
    public function getExtRef(OrderItemInterface $orderItem): string
    {
        if ($orderItem->getItemId() === null) {
            throw new InputException(__('Given order item do not have a item ID'));
        }

        return (string)$orderItem->getItemId();
    }

    /**
     * {@inheritdoc}
     */
    public function equality(OrderItemInterface $orderItem, string $extRef): bool
    {
        return $orderItem->getItemId() !== null && $extRef === (string)$orderItem->getItemId();
    }
}
