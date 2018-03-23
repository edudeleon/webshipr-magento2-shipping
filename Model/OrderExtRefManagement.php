<?php

namespace Webshipr\Shipping\Model;

use Magento\Sales\Api\Data\OrderInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;

class OrderExtRefManagement implements \Webshipr\Shipping\Api\OrderExtRefManagementInterface
{
    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    protected $_orderRepository;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $_searchCriteriaBuilder;

    public function __construct(
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
    )
    {
        $this->_orderRepository = $orderRepository;
        $this->_searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * {@inheritdoc}
     */
    public function getEntity(string $extRef): OrderInterface
    {
        if ($extRef === '') {
            throw new InputException(__('external reference is required'));
        }

        $records = $this->_orderRepository->getList(
            $this->_searchCriteriaBuilder
                ->addFilter(OrderInterface::ENTITY_ID, $extRef)
                ->create()
        );

        if ($records->getTotalCount() < 1) {
            throw new NoSuchEntityException(__("Requested order entity doesn't exist"));
        }

        $items = $records->getItems();
        return reset($items);
    }

    /**
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @return string
     * @throws \Magento\Framework\Exception\InputException
     */
    public function getExtRef(OrderInterface $order): string
    {
        if ($order->getEntityId() === null) {
            throw new InputException(__('Given order entity do not have a entity ID'));
        }

        return (string)$order->getEntityId();
    }

    /**
     * {@inheritdoc}
     */
    public function equality(OrderInterface $order, string $extRef): bool
    {
        return $order->getEntityId() !== null && $extRef === (string)$order->getEntityId();
    }
}
