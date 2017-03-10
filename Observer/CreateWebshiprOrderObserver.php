<?php
/**
 * Copyright © 2017 webshipr.com
 * @autor eduedeleon
 * 
 * */
namespace Webshipr\Shipping\Observer;

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;

/**
 * Webshipr module observer trigger after order is saved in checkout or in Admin Panel
 *
 */
class CreateWebshiprOrderObserver implements ObserverInterface
{
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Webshipr\Shipping\Helper\Data $webshiprHelperData,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Checkout\Model\Session $checkoutSession
    ) {
        $this->_storeManager    = $storeManager;
        $this->_webshiprHelper  = $webshiprHelperData;
        $this->_logger          = $logger;
        $this->_orderFactory    = $orderFactory;
        $this->_messageManager  = $messageManager;
        $this->_checkoutSession = $checkoutSession;
    }

    /**
     * Create an order in Webshipr when order status matches the status in the backend
     *
     * @param EventObserver $observer
     * @return void
     */
    public function execute(EventObserver $observer){
        $order = $observer->getEvent()->getOrder();
        if (!($order instanceof \Magento\Framework\Model\AbstractModel)) {
            return;          
        }

        //Getting checkout flag
        $checkout_flag = $this->_checkoutSession->getWebshiprCheckout();
        // $this->_checkoutSession->setWebshiprCheckout(null);

        // $this->_logger->info("[Webshipr] After saving order #".$order->getIncrementId(). " CheckoutFlag: ".$checkout_flag);

        //Check if module is enabled
        if($this->_webshiprHelper->isEnabled()){

            //Loading order details
            $order_id           = $order->getEntityId();
            $orderModel         = $this->_orderFactory->create();
            $shipping_method    = $order->getShippingMethod();
            $order_status       = $order->getStatus();

            //Getting auto transfer status from backend
            $trigger_statuses           = $this->_webshiprHelper->getStatuses();
            $trigger_statuses_array     = explode(",",$trigger_statuses);
            
            //Getting droppoint data from order
            $droppoint_info     = $order->getWebshiprDroppointInfo();
            $dropppoint_data    = array();
            if(!empty($droppoint_info)){
                $dropppoint_data = json_decode($droppoint_info, true);
            }
                
            //Check if order matches status selected in the backend (create order in Webshipr if there is a match)
            if(in_array($order_status, $trigger_statuses_array)) {

                //Check if order already exists in Webshiper (extra validation - Admin Panel)
                if(!$checkout_flag){
                    $webshiprOrder = $this->_webshiprHelper->getWebshiprOrder($order_id);
                    if(!empty($webshiprOrder)){
                        return;
                    }
                }

                //Getting auto process flag 
                $process_order = $this->_webshiprHelper->processOnAutoTransfer();
                if($checkout_flag){
                    //Only create order in webshipr if shipping method is Webshipr (During checkout)
                    if($this->_webshiprHelper->isWebshiprMethod($shipping_method)){
                    
                        //Create order via API
                        $webshipr_result = $this->_webshiprHelper->createWebshiprOrder($order_id, null, $process_order, $dropppoint_data, $order);

                        //Log error if order was not created in Webshipr
                        if(empty($webshipr_result['success'])){

                            $error_msg = $webshipr_result['msg']. " (Order #".$order->getIncrementId().")";
                            $this->_logger->info("[Webshipr] ".$error_msg);

                        } else {
                            //Add information about transaction in order history
                            $order->addStatusHistoryComment(__('Order was successfully created in Webshipr during checkout process. Shipping method: '). $order->getShippingDescription());
                        }
                    }
                } else {
                    //Create order via API (Always create order in webshipr)
                    $webshipr_result = $this->_webshiprHelper->createWebshiprOrder($order_id, null, $process_order, $dropppoint_data);

                    //Log error if order was not created in Webshipr
                    if(empty($webshipr_result['success'])){

                        $error_msg = $webshipr_result['msg']. " (Order #".$order->getIncrementId().")";
                        $this->_logger->info("[Webshipr] ".$error_msg);
                        $this->_messageManager->addWarning($error_msg);
                    } else {
                        $order->addStatusHistoryComment(__('Order was successfully created in Webshipr on auto transfer mode. Shipping method: '). $order->getShippingDescription());
                    }
                }
            }
        }
    }
}
