<?php
/**
 * @copyright 2018 webshipr.com
 */

namespace Webshipr\Shipping\Model;

class WebshiprManagement
{
    /**
     * @var \Webshipr\Shipping\Helper\Data
     */
    protected $_webshiprHelper;

    /**
     * @var \Webshipr\Shipping\Model\Api\Webshiprapi
     */
    protected $_webshiprApi;

    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    protected $_orderRepository;

    /**
     * @var \Webshipr\Shipping\Model\Order\ToWebshiprOrder
     */
    protected $_toWebshiprOrder;

    public function __construct(
        \Webshipr\Shipping\Helper\Data $webshiprHelper,
        \Webshipr\Shipping\Model\Api\Webshiprapi $webshiprApi,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Webshipr\Shipping\Model\Order\Item\ToWebshiprItem $toWebshiprItem,
        \Webshipr\Shipping\Model\Order\ToWebshiprOrder $toWebshiprOrder
    ) {
        $this->_webshiprHelper  = $webshiprHelper;
        $this->_webshiprApi     = $webshiprApi;
        $this->_orderRepository = $orderRepository;
        $this->_toWebshiprOrder = $toWebshiprOrder;
    }

    /**
     * Update Webshipr Order
     * Order updated with current Magento order data
     *
     * @param $magentoOrderId
     * @param null $shippingRateId
     * @param bool $processOrder
     * @param array $dropPoint
     * @return array|mixed|string
     */
    public function updateWebshiprOrder(
        $magentoOrderId,
        $shippingRateId = null,
        $processOrder = false,
        $dropPoint = []
    ) {
        /** @var \Magento\Sales\Model\Order $magentoOrder */
        $magentoOrder = $this->_orderRepository->get($magentoOrderId);

        //Format Magento order into Webshipper Order
        $webshiprOrderData = $this->_toWebshiprOrder->convert(
            $magentoOrder,
            $shippingRateId,
            $dropPoint,
            $this->normalizeProcessOrderFlag($processOrder)
        );

        try {
            $result  = $this->_webshiprApi->updateOrder($magentoOrderId, $webshiprOrderData);

            //Checking for Errors
            $errorMsg = $this->_getErrorMessages($result);
            if (empty($errorMsg)) {
                $webshiprResult = [
                    'success'   => true,
                    'site_id'   => $result['order_id']
                ];

                return $webshiprResult;
            } else {
                return [
                    'success' => false,
                    'msg'     => $errorMsg
                ];
            }
        } catch (\Throwable $e) {
            return [
                'success'   => false,
                'msg'       => $e->getMessage(),
            ];
        }
    }


    /**
     * Create a Magento Order in Webshipr
     *
     * @param int $magentoOrderId
     * @param null $shippingRateId
     * @param bool $processOrder
     * @param array $dropPoint
     * @param null $orderObject
     * @return array
     */
    public function createWebshiprOrder(
        $magentoOrderId,
        $shippingRateId = null,
        $processOrder = false,
        $dropPoint = [],
        $orderObject = null
    ) {
        /** @var \Magento\Sales\Model\Order $magentoOrder */
        $magentoOrder = $orderObject ?? $this->_orderRepository->get($magentoOrderId);

        //Format Magento order into Webshipper Order
        $webshiprOrderData = $this->_toWebshiprOrder->convert(
            $magentoOrder,
            $shippingRateId,
            $dropPoint,
            $this->normalizeProcessOrderFlag($processOrder)
        );

        try {
            $result  = $this->_webshiprApi->createOrder($webshiprOrderData);

            //Checking for Errors
            $errorMsg = $this->_getErrorMessages($result);
            if (empty($errorMsg)) {
                $webshipr_result = [
                    'success'   => true,
                    'site_id'   => $result['order_id']
                ];

                return $webshipr_result;
            } else {
                return [
                    'success' => false,
                    'msg'     => $errorMsg
                ];
            }
        } catch (\Throwable $e) {
            return [
                'success'   => false,
                'msg'       => $e->getMessage(),
            ];
        }
    }

    /**
     * @param string|bool $processOrder
     * @return bool
     */
    private function normalizeProcessOrderFlag($processOrder): bool
    {
        return ($processOrder && ($processOrder !== "false")) ? true : false;
    }


    /**
     * Get error messages
     * @param array $result
     * @return string
     */
    private function _getErrorMessages($result)
    {
        //Checking for Errors
        $error_msg = '';
        if (empty($result['success'])) {
            if (!empty($result['error'])) {
                $error      = is_array($result['error']) ? json_encode($result['error']) : $result['error'];
                $error_msg  = "[WEBSHIPR MSG] ". $error;
            } else {
                $error_msg  = "[WEBSHIPR MSG] ".json_encode($result);
            }
        }
        return $error_msg;
    }
}
