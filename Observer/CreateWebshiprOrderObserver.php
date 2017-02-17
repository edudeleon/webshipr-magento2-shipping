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
 * Webshipr module observer trigger after order is placed succefully in checkout
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
        \Magento\Sales\Model\OrderFactory $orderFactory

    ) {
        $this->_storeManager    = $storeManager;
        $this->_webshiprHelper  = $webshiprHelperData;
        $this->_logger          = $logger;
        $this->_orderFactory    = $orderFactory;
    }

    /**
     * Create an order in Webshipr when order status match the statuses in the backend
     *
     * @param EventObserver $observer
     * @return void
     */
    public function execute(EventObserver $observer){
      //Check if module is enabled and shipping method is Webshipr
      if($this->_webshiprHelper->isEnabled()){
        
        $order_ids  = $observer->getEvent()->getOrderIds();
        $order_id   = $order_ids[0];

        //Loading order details
        $orderModel         = $this->_orderFactory->create();
        $order              = $orderModel->load($order_id);
        $shipping_method    = $order->getShippingMethod();
        $order_status       = $order->getStatus();

        //Checking if shipping method is Webshipr
        if($this->_webshiprHelper->isWebshiprMethod($shipping_method)){
            //Getting auto transfer statuses from backend
            $trigger_statuses           = $this->_webshiprHelper->getStatuses();
            $trigger_statuses_array     = explode(",",$trigger_statuses);

            //Getting droppoint data when available
            $dropppoint_data = array();
            if($this->_webshiprHelper->isDroppoint($shipping_method)){
                //Getting shipping method information
                $droppoint_id     = $this->_webshiprHelper->getWebshiprDroppointId($shipping_method);
                $shipping_rate_id = $this->_webshiprHelper->getWebshiprShippingRateId($shipping_method);
                $zip_code         = $order->getShippingAddress()->getPostcode();
                $country          = $order->getShippingAddress()->getCountryId();

                //Getting droppoint data
                $dropppoint_result = $this->_webshiprHelper->getDroppointById($droppoint_id, $shipping_rate_id, $zip_code, $country);
                if($dropppoint_result['success']){
                  $dropppoint_data = $dropppoint_result['droppoint'];
                }
            }
            
            //Check if order matches status selected in the backend (create order in Webshipr if there is a match)
            if(in_array($order_status, $trigger_statuses_array)) {
                //Getting auto process flag 
                $process_order = $this->_webshiprHelper->processOnAutoTransfer();

                //Create order via API
                $webshipr_result = $this->_webshiprHelper->createWebshiprOrder($order_id, null, $process_order, $dropppoint_data);

                //Log error if order was not created in Webshipr
                if(empty($webshipr_result['success'])){
                    $this->_logger->info($webshipr_result['msg']. " (Order #".$order->getIncrementId().")");
                } else {
                    //Add information about transaction in order history
                    $order->addStatusHistoryComment(__('Order was successfully created in Webshipr during checkout process. Shipping method: '). $order->getShippingDescription())->save();
                }
            }

            //Save droppoint to order
            if(!empty($dropppoint_data)){
              $order->setWebshiprDroppointInfo(json_encode($dropppoint_data));
              $order->save();
            }

            //Log error when droppoint is not saved
            if($this->_webshiprHelper->isDroppoint($shipping_method) && empty($dropppoint_data)){
                $this->_logger->info("Droppoint not saved for Order #".$order->getIncrementId().". Shipping method code: ".$shipping_method);
            }

        }
      }
    }
}
