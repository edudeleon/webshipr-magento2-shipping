<?php
/**
 * Copyright © 2017 webshipr.com
 * @autor eduedeleon
 * */
namespace Webshipr\Shipping\Controller\Adminhtml\Order;

class Create extends \Magento\Backend\App\Action{

	/**
     * @var \Magento\Framework\Json\Helper\Data
     */
    protected $_webshiprHelper;

    public function __construct(
		\Magento\Backend\App\Action\Context $context,
		\Webshipr\Shipping\Helper\Data $webshiprHelperData,
        \Magento\Sales\Model\OrderFactory $orderFactory
	) {
		$this->_webshiprHelper      = $webshiprHelperData;
        $this->_orderFactory        = $orderFactory;
		parent::__construct($context);
	}

    public function execute(){

    	$request               = $this->getRequest();
        $magento_order_id      = $request->getParam('magento_order_id');
        $shipping_rate_label   = $request->getParam('shipping_rate_label');

        //Check if order has droppoint
        $orderModel         = $this->_orderFactory->create();
        $order              = $orderModel->load($magento_order_id);
        $droppoint_info     = $order->getWebshiprDroppointInfo();
        $droppoint          = array();
        if(!empty($droppoint_info)){
            $droppoint = json_decode($droppoint_info, true);
        }
        
        //Create order via API
        $result = $this->_webshiprHelper->createWebshiprOrder(
            $magento_order_id,
            $request->getParam('shipping_rate_id'),
            $request->getParam('process_order'),
            $droppoint
        );

        //Update shipping description
        $order->setShippingDescription("Webshipr - ".$shipping_rate_label);
        $order->save();

        //Validate Success action
        if(!empty($result['success'])){
            $this->messageManager->addSuccess( __('Order was successfully created in Webshipr'));
        } 

        $this->getResponse()->clearHeaders()->setHeader('Content-type','application/json', true);
        $this->getResponse()->setBody(json_encode($result));
    }   
}