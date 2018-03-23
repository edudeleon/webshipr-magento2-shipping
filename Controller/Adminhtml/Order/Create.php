<?php
/**
 * Copyright © 2017 webshipr.com
 * @autor eduedeleon
 * */
namespace Webshipr\Shipping\Controller\Adminhtml\Order;

class Create extends \Magento\Backend\App\Action
{
    /**
     * @var \Webshipr\Shipping\Model\WebshiprManagement
     */
    protected $_webshiprManagement;

    /**
     * @var \Webshipr\Shipping\Api\OrderManagementInterface
     */
    protected $_orderManagement;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Webshipr\Shipping\Model\WebshiprManagement $webshiprManagement,
        \Webshipr\Shipping\Api\OrderManagementInterface $orderManagement
    ) {
        $this->_orderFactory       = $orderFactory;
        $this->_webshiprManagement = $webshiprManagement;
        $this->_orderManagement    = $orderManagement;
        parent::__construct($context);
    }

    public function execute()
    {

        $request               = $this->getRequest();
        $magento_order_id      = $request->getParam('magento_order_id');
        $shipping_rate_label   = $request->getParam('shipping_rate_label');

        //Check if order has droppoint
        $orderModel         = $this->_orderFactory->create();
        $order              = $orderModel->load($magento_order_id);
        $droppoint_info     = $order->getWebshiprDroppointInfo();
        $droppoint          = [];
        if (!empty($droppoint_info)) {
            $droppoint = json_decode($droppoint_info, true);
        }

        //Check if shipping rate is going to be changed / Set droppoint to empty when true
        $shipping_rate_id   = $this->_orderManagement->getWebshiprShippingRateId($order);
        if ($shipping_rate_id  != $request->getParam('shipping_rate_id')) {
             $order->setShippingDescription("Webshipr - ".$shipping_rate_label);
             $order->setWebshiprDroppointInfo('');
             $order->save();
             $droppoint = [];
        }
        
        //Create order via API
        $result = $this->_webshiprManagement->createWebshiprOrder(
            $magento_order_id,
            $request->getParam('shipping_rate_id'),
            $request->getParam('process_order'),
            $droppoint
        );

        //Validate Success action
        if (!empty($result['success'])) {
            $this->messageManager->addSuccess(__('Order was successfully created in Webshipr'));

            //Add information about transaction in order history
            $order->addStatusHistoryComment(__('Order was successfully created in Webshipr from Admin Panel. Shipping method: '). $order->getShippingDescription())->save();
        }

        $this->getResponse()->clearHeaders()->setHeader('Content-type', 'application/json', true);
        $this->getResponse()->setBody(json_encode($result));
    }
}
