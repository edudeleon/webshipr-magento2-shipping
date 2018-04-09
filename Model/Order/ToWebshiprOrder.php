<?php
/**
 * @copyright 2018 webshipr.com
 */

namespace Webshipr\Shipping\Model\Order;

class ToWebshiprOrder
{
    /**
     * @var \Webshipr\Shipping\Api\OrderManagementInterface
     */
    protected $_orderManagement;

    /**
     * @var \Webshipr\Shipping\Api\OrderExtRefManagementInterface
     */
    protected $_orderExtRefManagement;

    /**
     * @var \Webshipr\Shipping\Model\Order\Item\ToWebshiprItem
     */
    protected $_toWebshiprItem;

    /**
     * @var \Webshipr\Shipping\Model\Order\ToWebshiprDiscount
     */
    protected $_toWebshiprDiscount;

    /**
     * @var \Webshipr\Shipping\Model\Order\Address\ToWebshiprAddress
     */
    protected $_toWebshiprAddress;

    /**
     * @var \Webshipr\Shipping\Model\Order\ToWebshiprShippingFinancial
     */
    protected $_toWebshiprShippingFinancial;

    public function __construct(
        \Webshipr\Shipping\Api\OrderManagementInterface $orderManagement,
        \Webshipr\Shipping\Api\OrderExtRefManagementInterface $orderExtRefManagement,
        \Webshipr\Shipping\Model\Order\Item\ToWebshiprItem $toWebshiprItem,
        \Webshipr\Shipping\Model\Order\ToWebshiprDiscount $toWebshiprDiscount,
        \Webshipr\Shipping\Model\Order\Address\ToWebshiprAddress $toWebshiprAddress,
        \Webshipr\Shipping\Model\Order\ToWebshiprShippingFinancial $toWebshiprShippingFinancial
    ) {
        $this->_orderManagement = $orderManagement;
        $this->_orderExtRefManagement = $orderExtRefManagement;
        $this->_toWebshiprItem  = $toWebshiprItem;
        $this->_toWebshiprDiscount = $toWebshiprDiscount;
        $this->_toWebshiprAddress = $toWebshiprAddress;
        $this->_toWebshiprShippingFinancial = $toWebshiprShippingFinancial;
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @param int|null                   $shippingRateId
     * @param array|null                 $dropPoint
     * @param bool                       $processOrder
     *
     * @return array
     */
    public function convert(
        \Magento\Sales\Model\Order $order,
        int $shippingRateId = null,
        array $dropPoint = null,
        bool $processOrder = false
    ): array {

        // Check if shipping rate ID is NOT included (Observer case)
        if ($shippingRateId === null) {
            // Get Shipping Id from order shipping method code)
            $shippingRateId = $this->_orderManagement->getWebshiprShippingRateId($order);
        }

        // Prepare Order Items
        $orderItems = [];
        foreach ($this->getItemsForWebshiprShipment($order) as $item) {
            $orderItems[] = $this->_toWebshiprItem->convert($item);
        }

        $webshiprOrderFormat =  [
            'ext_ref'            => $this->_orderExtRefManagement->getExtRef($order),
            'visible_ref'        => $order->getIncrementId(),
            'shipping_rate_id'   => $shippingRateId,
            'currency'           => $order->getOrderCurrencyCode(),
            'shipping_financial' => $this->_toWebshiprShippingFinancial->convert($order),
            'discounts'          => $this->_toWebshiprDiscount->convert($order),
            'process'            => $processOrder,
            'delivery_address'   => $this->_toWebshiprAddress->convert($order->getShippingAddress()),
            'billing_address'    => $this->_toWebshiprAddress->convert($order->getBillingAddress()),
            'items'              => $orderItems,
        ];

        // Insert drop point data
        if ($dropPoint && $this->_orderManagement->usesWebshiprDropPoint($order)) {
            $webshiprOrderFormat['droppoint'] = $this->convertDropPoint($dropPoint);
        }

        return $webshiprOrderFormat;
    }

    /**
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @return \Magento\Sales\Api\Data\OrderItemInterface[]
     */
    private function getItemsForWebshiprShipment(\Magento\Sales\Api\Data\OrderInterface $order): array
    {
        $shipmentItems = [];
        foreach ($order->getItems() as $item) {
            if ($item instanceof \Magento\Sales\Model\Order\Item) {
                // Test if this is a child item
                if ($item->getParentItem()) {
                    $addItem = $item->isChildrenCalculated();
                } else { // This is a single or parent item
                    $addItem = !$item->isChildrenCalculated();
                }
            } else {
                $addItem = true;
            }

            if ($addItem) {
                $shipmentItems[] = $item;
            }
        }

        return $shipmentItems;
    }

    /**
     * @param array $dropPoint
     * @return array
     */
    private function convertDropPoint(array $dropPoint): array
    {
        return [
            'id'           => $dropPoint['id'],
            'address_1'    => $dropPoint['street'] ?? '',
            'address_2'    => '',
            'company_name' => $dropPoint['name'] ?? '',
            'city'         => $dropPoint['city'] ?? '',
            'zip'          => $dropPoint['zip'] ?? '',
            'country_code' => $dropPoint['country'] ?? '',
            'state'        => $dropPoint['state'] ?? '',
        ];
    }
}
