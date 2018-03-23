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
     * @var \Webshipr\Shipping\Api\OrderManagementInterface
     */
    protected $_orderManagement;

    /**
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Webshipr\Shipping\Helper\Data $webshiprHelperData,
        \Webshipr\Shipping\Api\OrderManagementInterface $orderManagement
    ) {
        $this->_logger          = $logger;
        $this->_checkoutSession = $checkoutSession;
        $this->_webshiprHelper  = $webshiprHelperData;
        $this->_orderManagement = $orderManagement;
    }

    /**
     * Set Webshipr checkout flag
     * Method triggered only when order is created in checkout
     *
     * @param EventObserver $observer
     * @return void
     */
    public function execute(EventObserver $observer)
    {
        /** @var \Magento\Sales\Model\Order $order */
        $order = $observer->getEvent()->getOrder();
        if (!($order instanceof \Magento\Framework\Model\AbstractModel)) {
            return;
        }

        // $this->_logger->info("[Webshipr] After placing order #".$order->getIncrementId());

        //Check if module is enabled
        if ($this->_webshiprHelper->isEnabled()) {
            //Set checkout flag
            $this->_checkoutSession->setWebshiprCheckout('1');

            //Save droppoint information in order
            $shipping_method = $order->getShippingMethod();
            if ($this->_orderManagement->usesWebshiprShippingMethod($order)) {
                //Getting droppoint data when available
                $dropppoint_data = [];
                if ($this->_orderManagement->usesWebshiprDropPoint($order)) {
                    //Getting shipping method information
                    $droppoint_id     = $this->_orderManagement->getWebshiprDroppointId($order);
                    $shipping_rate_id = $this->_orderManagement->getWebshiprShippingRateId($order);
                    $zip_code         = $order->getShippingAddress()->getPostcode();
                    $country          = $order->getShippingAddress()->getCountryId();
                    $address          = $order->getShippingAddress()->getStreet();

                    $address_line       = explode(PHP_EOL, $address);
                    $address_line1      = !empty($address_line[0]) ? $address_line[0] : $address;
        
                    //Getting droppoint data
                    $dropppoint_result = $this->_webshiprHelper->getDroppointById($droppoint_id, $shipping_rate_id, $zip_code, $country, $address_line1);
                    if ($dropppoint_result['success']) {
                        $dropppoint_data = $dropppoint_result['droppoint'];
                    }
                }

                 //Save droppoint to order
                if (!empty($dropppoint_data)) {
                    $order->setWebshiprDroppointInfo(json_encode($dropppoint_data));
                }

                //Log error if droppoint is not saved
                if ($this->_orderManagement->usesWebshiprDropPoint($order) && empty($dropppoint_data)) {
                    $this->_logger->info("[Webshipr] Droppoint not saved for Order #".$order->getIncrementId().". Shipping method code: ".$shipping_method);
                }
            }
        }
    }
}
