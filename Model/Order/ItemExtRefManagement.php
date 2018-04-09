<?php
/**
 * Updating class to use "Quote Item ID" instead of "Item ID" because sometimes Item ID is not available at checkout_cart_product_add_after.
 * Modified by edudeleon on April 8, 2018
 * */

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
                ->addFilter(OrderItemInterface::QUOTE_ITEM_ID, $extRef)
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
        if ($orderItem->getQuoteItemId() === null) {
            throw new InputException(__('Given order item do not have a Quote Item ID'));
        }

        return (string)$orderItem->getQuoteItemId();
    }

    /**
     * {@inheritdoc}
     */
    public function equality(OrderItemInterface $orderItem, string $extRef): bool
    {
        return $orderItem->getQuoteItemId() !== null && $extRef === (string)$orderItem->getQuoteItemId();
    }
}
