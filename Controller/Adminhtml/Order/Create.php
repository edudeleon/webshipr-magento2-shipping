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

        //Check if shipping rate is going to be changed / Set droppoint to empty when true
        $shipping_method    = $order->getShippingMethod();
        $shipping_rate_id   = $this->_webshiprHelper->getWebshiprShippingRateId($shipping_method);
        if($shipping_rate_id  != $request->getParam('shipping_rate_id')){
             $order->setShippingDescription("Webshipr - ".$shipping_rate_label);
             $order->setWebshiprDroppointInfo('');
             $order->save();
             $droppoint = array();
        }
        
        //Create order via API
        $result = $this->_webshiprHelper->createWebshiprOrder(
            $magento_order_id,
            $request->getParam('shipping_rate_id'),
            $request->getParam('process_order'),
            $droppoint
        );

        //Validate Success action
        if(!empty($result['success'])){
            $this->messageManager->addSuccess( __('Order was successfully created in Webshipr'));

            //Add information about transaction in order history
            $order->addStatusHistoryComment(__('Order was successfully created in Webshipr from Admin Panel. Shipping method: '). $order->getShippingDescription())->save();
        } 

        $this->getResponse()->clearHeaders()->setHeader('Content-type','application/json', true);
        $this->getResponse()->setBody(json_encode($result));
    }   
}