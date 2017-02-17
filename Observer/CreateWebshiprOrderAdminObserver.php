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
 * Webshipr module observer trigger after order is saved/updated in Admin Panel
 */
class CreateWebshiprOrderAdminObserver implements ObserverInterface
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
        \Magento\Framework\Message\ManagerInterface $messageManager

    ) {
        $this->_storeManager    = $storeManager;
        $this->_webshiprHelper  = $webshiprHelperData;
        $this->_logger          = $logger;
        $this->_orderFactory    = $orderFactory;
        $this->_messageManager  = $messageManager;
    }

    /**
     * Create an order in Webshipr when order status match the statuses in the backend
     *
     * @param EventObserver $observer
     * @return void
     */
    public function execute(EventObserver $observer){

      $updatedOrder = $observer->getEvent()->getOrder();
      if (!($updatedOrder instanceof \Magento\Framework\Model\AbstractModel)) {
        return;          
      }
      $order_status = $updatedOrder->getStatus();

      //Check if module is enabled and shipping method is Webshipr
      if($this->_webshiprHelper->isEnabled()){
      
        $order_id   = $updatedOrder->getEntityId();

        //Loading order details
        $orderModel         = $this->_orderFactory->create();
        $order              = $orderModel->load($order_id);
        $shipping_method    = $order->getShippingMethod();

        //Checking if shipping method is Webshipr (Remove this is if is possible to create orders without shipping_rate_id in Webshipr)
        // if($this->_webshiprHelper->isWebshiprMethod($shipping_method)){


              //Getting auto transfer statuses from backend
              $trigger_statuses           = $this->_webshiprHelper->getStatuses();
              $trigger_statuses_array     = explode(",",$trigger_statuses);

              //Getting droppoint data from order
              $droppoint_info     = $order->getWebshiprDroppointInfo();
              $dropppoint_data    = array();
              if(!empty($droppoint_info)){
                  $dropppoint_data = json_decode($droppoint_info, true);
              }
              
              //Check if order match status selected in the backend (create order in Webshipr if there is a match)
              if(in_array($order_status, $trigger_statuses_array)) {
                  //Check if order already exists in Webshiper (extra validation)
                  $webshiprOrder = $this->_webshiprHelper->getWebshiprOrder($order_id);
                  if(!empty($webshiprOrder)){
                    return;
                  }

                  //Getting auto process flag 
                  $process_order = $this->_webshiprHelper->processOnAutoTransfer();

                  //Create order via API
                  $webshipr_result = $this->_webshiprHelper->createWebshiprOrder($order_id, null, $process_order, $dropppoint_data);

                  //Check if order was created in Webshipr
                  if(empty($webshipr_result['success'])){
                      $error_msg = $webshipr_result['msg']. " (Order #".$order->getIncrementId().")";
                      $this->_logger->info($error_msg);
                      $this->_messageManager->addWarning($error_msg);
                  } else {
                    //Add information about transaction in order history
                    $order->addStatusHistoryComment(__('Order was successfully created in Webshipr on auto transfer mode. Shipping method: '). $order->getShippingDescription())->save();
                  }
              }


        // }


      }
    }
}
