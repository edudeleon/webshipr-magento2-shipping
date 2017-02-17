<?php
 /**
 * Copyright © 2017 webshipr.com
 * @autor eduedeleon 
 */

namespace Webshipr\Shipping\Controller\Order;
 
use Magento\Framework\App\Action\Context;
 
class Close extends \Magento\Framework\App\Action\Action
{
    protected $_resultPageFactory;
 
    public function __construct(
        Context $context, 
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Sales\Model\Convert\OrderFactory $convertOrderFactory,
        \Magento\Shipping\Model\ShipmentNotifier $shipmentNotifier,
        \Magento\Sales\Model\Service\InvoiceService $invoiceService,
        \Magento\Sales\Model\Order\Email\Sender\InvoiceSender $invoiceSender,
        \Magento\Framework\DB\Transaction $transaction,
        \Webshipr\Shipping\Helper\Data $webshiprHelperData
    ){
        $this->_resultPageFactory       = $resultPageFactory;
        $this->_orderFactory            = $orderFactory;
        $this->_convertOrderFactory     = $convertOrderFactory;
        $this->_shipmentNotifier        = $shipmentNotifier;
        $this->_invoiceService          = $invoiceService;
        $this->_invoiceSender           = $invoiceSender;
        $this->_transaction             = $transaction;
        $this->_webshiprHelper          = $webshiprHelperData;
        parent::__construct($context);
    }

    /**
     * Return endpoint call response 
     * @param  [type]     $msg
     * @param  boolean    $success
     * @return [type]
     * @author edudeleon
     * @date   2017-01-25
     */
    private function _return_http_response($msg, $success=true, $auth=true){

        if($success){
            $result = array(
                'success' => true,
                'msg'     => $msg,
            );

        } else {
            $result = array(
                'success' => false,
                'msg'     => $msg,
            );
        }

        if(!$auth){
            http_response_code(403);
        }

        header('Content-Type: application/json');
        echo json_encode($result);
        exit;
    }
 
    /**
     * Endpoint called by Webshipr to close and order in Magento
     * Method loads order prepare it to have a status completed. 
     * Orders in Magento are considered closed/completed when they have a shipment 
     * and invoice events associated to them.
     * @return [type]
     * @author edudeleon
     * @date   2017-01-21
     */
    public function execute()
    {   
        //Check if module is enabled
        if(!$this->_webshiprHelper->isEnabled()){
            $this->_return_http_response(__('Webshpr extension is not enabled or token has not been configured'), false);   
        }

        //Authenticate request
        $request = new \Zend_Controller_Request_Http();
        $token   = $request->getHeader('Auth-Token');

        //Get data
        $order_detail = $request->getParam('order_detail');
        if(empty($order_detail)){
            $this->_return_http_response(__('"order_detail" not set'), false);
        }

        $data = json_decode($order_detail, true);
        if(empty($data['order_id']) || empty($data['carrier_name']) || empty($data['tracking_code']) || empty($data['tracking_url'])){
            $this->_return_http_response(__('"order_detail" not valid'), false);   
        }

        //Authenticate request (by Token)
        if($this->_webshiprHelper->getToken() != $token){
            $this->_return_http_response(__('Token not valid'), false, false);
        }

        //Check if orders can be closed by Webshipr
        if(!$this->_webshiprHelper->orderClosingEnabled()){
            $this->_return_http_response(__('Orders cannot be closed by Webshipr. Please enable this feature in the extension settings in your Magento Admin Panel.'), false);   
        }

        //Loading order from Magento
        $orderModel = $this->_orderFactory->create();
        $order_id   = $data['order_id'];
        $order      = $orderModel->load($order_id);

        //Get enabled notifications
        $enabled_notifications = $this->_webshiprHelper->getEnabledNotifications();

        if(empty($order)){
            $this->_return_http_response(__('Order not found'), false);
        }
   
        // Check if order can be invoiced and shipped
        if (!$order->canShip() OR !$order->canInvoice()) { 
            $this->_return_http_response(__("Order can't be invoiced or shipped automatically. Please close/complete the order from Magento admin panel."), false); 
        }

        // Create shipment order
        $convertOrder   = $this->_convertOrderFactory->create();
        $shipment       = $convertOrder->toShipment($order);

        // Loop through order items
        foreach ($order->getAllItems() AS $orderItem) {
            // Check if order item has qty to ship or is virtual
            if (! $orderItem->getQtyToShip() || $orderItem->getIsVirtual()) {
                continue;
            }

            $qtyShipped = $orderItem->getQtyToShip();

            // Create shipment item with qty
            $shipmentItem = $convertOrder->itemToShipmentItem($orderItem)->setQty($qtyShipped);

            // Add shipment item to shipment
            $shipment->addItem($shipmentItem);
        }

        // Register shipment
        $shipment->register();
        $shipment->getOrder()->setIsInProcess(true);

        try {

            //Including Tracking code
            $track = $this->_objectManager->create(
                    'Magento\Sales\Model\Order\Shipment\Track')
                ->setTrackNumber($data['tracking_code'])
                ->setWebshiprTrackingUrl($data['tracking_url'])
                ->setCarrierCode(\Webshipr\Shipping\Model\Config::SHIPPING_METHOD_CODE)
                ->setTitle($data['carrier_name']
            );
            $shipment->addTrack($track);

            // Save created shipment and order
            $shipment->save();
            $shipment->getOrder()->save();

            // Notify customer about shipment
            if(in_array('notify_shipment', $enabled_notifications)){
                $this->_shipmentNotifier->notify($shipment);
            }

            $shipment->save();

            // Include shippment comment 
            $order->addStatusHistoryComment(
                __('Shipment order automatically generated by Webshipr. Shipment No. '). $shipment->getIncrementId())
            ->save();

        } catch (Exception $e) {
            $this->_return_http_response(__($e->getMessage()), false);
        }

        //Prepare Order Invoice
        if (!$order->canInvoice()) {
            $this->_return_http_response(__("Order has been processed but invoice needs to be done manually in Magento Admin Panel."), false);
        }

        // Create invoice for this order
        $invoice = $this->_invoiceService->prepareInvoice($order);

         // Make sure there is a qty on the invoice
        if (!$invoice->getTotalQty()) {
            $this->_return_http_response(__("You can't create an invoice without products."), false);
        }

        // Register as invoice item
        $invoice->setRequestedCaptureCase(\Magento\Sales\Model\Order\Invoice::CAPTURE_ONLINE);
        $invoice->register();

        // Save the invoice to the order
        $transaction = $this->_transaction->addObject($invoice)
                                          ->addObject($invoice->getOrder()
        );
        $transaction->save();

        // Notify customer about the invoice
        if(in_array('notify_invoice', $enabled_notifications)){
            $this->_invoiceSender->send($invoice);
        }

        // Include Invoice comment 
        $order->addStatusHistoryComment(
            __('Invoice automatically generated by Webshipr. Invoice No. ').$invoice->getIncrementId())
        // ->setIsCustomerNotified(true) // Notify email about this comment...
        ->save();

        //Order Shipped and Invoiced
        $this->_return_http_response(__("Order was succesfully completed/closed in Magento"));
    }

}