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
 * Webshipr module observer trigger after order is placed in checkout
 */
class SetWebshiprCheckoutFlagObserver implements ObserverInterface
{
    /**
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Webshipr\Shipping\Helper\Data $webshiprHelperData
    ) {
        $this->_logger          = $logger;
        $this->_checkoutSession = $checkoutSession;
        $this->_webshiprHelper  = $webshiprHelperData;
    }

    /**
     * Set Webshipr checkout flag
     * Method triggered only when order is created in checkout
     *
     * @param EventObserver $observer
     * @return void
     */
    public function execute(EventObserver $observer){
        $order = $observer->getEvent()->getOrder();
        if (!($order instanceof \Magento\Framework\Model\AbstractModel)) {
            return;          
        }

        // $this->_logger->info("[Webshipr] After placing order #".$order->getIncrementId());

        //Check if module is enabled
        if($this->_webshiprHelper->isEnabled()){

            //Set checkout flag
            $this->_checkoutSession->setWebshiprCheckout('1');

            //Save droppoint information in order
            $shipping_method = $order->getShippingMethod();
            if($this->_webshiprHelper->isWebshiprMethod($shipping_method)){
                
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

                 //Save droppoint to order
                if(!empty($dropppoint_data)) {
                  $order->setWebshiprDroppointInfo(json_encode($dropppoint_data));
                }

                //Log error if droppoint is not saved
                if($this->_webshiprHelper->isDroppoint($shipping_method) && empty($dropppoint_data)) {
                    $this->_logger->info("[Webshipr] Droppoint not saved for Order #".$order->getIncrementId().". Shipping method code: ".$shipping_method);
                }
            }
        }
    }
    
}