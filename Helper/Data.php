<?php
/**
 * Copyright © 2017 webshipr.com
 * @autor eduedeleon
 * */
namespace Webshipr\Shipping\Helper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Webshipr\Shipping\Model\Api\Webshiprapi $webshiprApi,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Catalog\Model\ProductFactory $productFactory
    ){
        $this->_webshiprApi         = $webshiprApi;
        $this->_storeManager        = $storeManager;
        $this->_orderFactory        = $orderFactory;
        $this->_productFactory      = $productFactory;
        parent::__construct($context);
    }

    /**
     * Config paths for using throughout the code
     */
    const CONFIG_PATH_ENABLED                   = 'carriers/webshipr/active';
    const CONFIG_PATH_TOKEN                     = 'carriers/webshipr/token';
    const CONFIG_PATH_STATUSES                  = 'carriers/webshipr/statuses';
    const CONFIG_PATH_AUTO_TRANSFER             = 'carriers/webshipr/auto_transfer';
    const CONFIG_PATH_DROPPOINT_LIMIT           = 'carriers/webshipr/droppoint_limit';
    const CONFIG_PATH_WEIGHT_UNIT               = 'carriers/webshipr/weight_unit';
    const CONFIG_PATH_ORDER_CLOSING             = 'carriers/webshipr/order_closing';
    const CONFIG_PATH_CUSTOMER_NOTIFICATIONS    = 'carriers/webshipr/customer_notifications';


    /**
     * Verify if extension is enabled
     *
     * @param null|string|bool|int|Store $store
     * @return bool
     */
    public function isEnabled($store = null)
    {
        return $this->getToken() && $this->scopeConfig->isSetFlag(self::CONFIG_PATH_ENABLED, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $store);
    }


    /**
     * Get Token
     * @return [type]
     * @author edudeleon
     * @date   2016-02-10
     */
    public function getToken($store = null){
        return $this->scopeConfig->getValue(
            self::CONFIG_PATH_TOKEN,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Get auto transfer statuses
     * @return [type]
     * @author edudeleon
     * @date   2017-01-19
     */
    public function getStatuses(){
        return $this->scopeConfig->getValue(
            self::CONFIG_PATH_STATUSES,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get Process on auto stranfer flag from extension configuration
     * @return [type]
     * @author edudeleon
     * @date   2017-01-19
     */
    public function processOnAutoTransfer(){
        $config_value = $this->scopeConfig->getValue(
            self::CONFIG_PATH_AUTO_TRANSFER,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        return $config_value ? true : false;
    }

    /**
     * Get limit of droppoint avaiable in checkout 
     * @return [type]
     * @author edudeleon
     * @date   2017-01-23
     */
    public function getDroppointLimit(){
         return $this->scopeConfig->getValue(
            self::CONFIG_PATH_DROPPOINT_LIMIT,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get weight unit from admin panel
     * @return [type]
     * @author edudeleon
     * @date   2017-01-25
     */
    public function getWeightUnit(){
         return (float)$this->scopeConfig->getValue(
            self::CONFIG_PATH_WEIGHT_UNIT,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Check if Webshipr can close Magento Orders via API
     * @return [type]
     * @author edudeleon
     * @date   2017-01-25
     */
     public function orderClosingEnabled(){
         return $this->scopeConfig->getValue(
            self::CONFIG_PATH_ORDER_CLOSING,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Return array of enabled notifications configured in Admin Panel
     * @return [type]
     * @author edudeleon
     * @date   2017-01-25
     */
    public function getEnabledNotifications(){
        $customer_notifications = $this->scopeConfig->getValue(
            self::CONFIG_PATH_CUSTOMER_NOTIFICATIONS,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        $notifcations = explode(",", $customer_notifications);
        return $notifcations;
    }

    /**
     * Format shipping code to store the shipping method in Magento
     * Format: "Webshipr carrrier ID" + "_" + "Webshipr shipping rate ID" (e.g. 3934_9631)
     * If droppoint selected, droppoint ID is added to the end (e.g. 3934_9631_3947)
     * @param  [type]     $webshiprShippingRate
     * @param  [type]     $webshiprDroppoint
     * @return [type]
     * @author edudeleon
     * @date   2017-01-17
     */
    public function formatShippingCode($webshiprShippingRate, $webshiprDroppoint = array()){
        $shipping_rate_code =  $webshiprShippingRate['carrier_id']."_".$webshiprShippingRate['id'];
        if(!empty($webshiprDroppoint)){
            $shipping_rate_code = $shipping_rate_code."_".$webshiprDroppoint['id'];
        }
        return $shipping_rate_code;
    }


    /**
     * Get shipping rates from Webshipr
     * @return [type]
     * @author edudeleon
     * @date   2017-01-18
     */
    public function getShippingRates(){
        $shipping_rates_array = array();

        try {
            //Get shipping rates for current order
            $shipping_rates = $this->_webshiprApi->getShippingRates();

            //Prepare data
            if(!empty($shipping_rates)){
                foreach ($shipping_rates as $shipping_rate) {
                    $shipping_rates_array[] = array(
                        'id'                => $shipping_rate['id'],
                        'name'              => $shipping_rate['name'],
                        'has_droppoints'    => $shipping_rate['dynamic_pickup'],
                    );
                }
            }

        } catch (Exception $ex) {
            $error = array(
                    'success'   => FALSE,
                    'msg'       => $ex->getMessage(),
            );
        }

        return $shipping_rates_array;
    }

    /**
     * Get droppoint data by Id and shipping rate ID
     * @param  [type]     $droppoint_id
     * @param  [type]     $shipping_rate_id
     * @param  [type]     $zip_code
     * @param  [type]     $country
     * @return [type]
     * @author edudeleon
     * @date   2017-01-22
     */
    public function getDroppointById($droppoint_id, $shipping_rate_id, $zip_code, $country){
        try {   
            //Get droppoints from Webshipr
            $droppoints = $this->_webshiprApi->getDroppoints($shipping_rate_id, $zip_code, $country);

            if(!empty($droppoints['status'])){
                if($droppoints['status'] == 'success'){
                    //Get droppoint data
                    foreach ($droppoints['data'] as $droppoint) {
                        if($droppoint['id'] == $droppoint_id){
                            //Unset other vars opening hours...
                            unset($droppoint['opening_hours']);

                            return array(
                                'success'       => TRUE,
                                'droppoint'     => $droppoint,
                            );
                        }
                    }
                    
                } else {
                    return array(
                                'success' => FALSE,
                                'msg'     => !empty($droppoints['message']) ? $droppoints['message'] : "Droppoint not found"
                            );
                }
            }

        } catch (Exception $e) {
            return array(
                        'success' => FALSE,
                        'msg'     => $e->getMessage()
                        );
        }

        return array('success' => FALSE);
    }


    /**
     * Get Webhshipr shipping rates for current order (Quote)
     * @param  [type]     $order_subtotal   Order subtotal
     * @param  [type]     $weight           Total weight of the current order (sum of items weight)
     * @param  [Array]    $recipientData    Array with recipient information
     * @author edudeleon
     * @date   2017-01-13
     */
    public function getShippingRatesQuote($order_subtotal, $_weight, $recipientData){
        $shipping_options       = array();
        $currency_code          = $this->_storeManager->getStore()->getCurrentCurrencyCode();

        //Convert weight
        $weight = $this->getConvertedWeight($_weight);
        
        try {
            //Get shipping rates for current order
            $shipping_methods    = $this->_webshiprApi->getShippingRatesQuote($order_subtotal, $currency_code, $weight, $recipientData);

            //Prepare data
            if(!empty($shipping_methods)){
                foreach ($shipping_methods as $method) {
                    //Check if shipping rate allow droppoints
                    if($method['dynamic_pickup']){
                        $droppoints = $this->_webshiprApi->getDroppoints($method['id'], $recipientData['zip'], $recipientData['country_code'], $recipientData['address_1']);
                        if(!empty($droppoints)){
                            $droppoints_list = $droppoints['data'];
                            $i = 1;
                            foreach ($droppoints_list as $droppoint) {
                                $shipping_option = array(
                                    'method_code'   =>  $this->formatShippingCode($method, $droppoint),
                                    'carrier_code'  =>  $method['carrier_code'],
                                    'name'          =>  $method['name'] . ' - ' . $droppoint['name'] . '-' . $droppoint['street'] . ' - '. $droppoint['zip'] . '-' . $droppoint['city'],
                                    'price'         =>  $method['price'],
                                );

                                 $shipping_options[] = $shipping_option;

                                //Set droppoint limit
                                if($i >= $this->getDroppointLimit()){
                                    break;
                                }
                                $i++;
                            }
                        } else {
                             $shipping_option = array(
                                'method_code'   =>  $this->formatShippingCode($method),
                                'carrier_code'  =>  $method['carrier_code'],
                                'price'         =>  $method['price'],
                                'carrier_code'  =>  $method['carrier_code'],
                            );

                            $shipping_options[] = $shipping_option;
                        }
                    } else {

                        $shipping_option =  array(
                            'method_code'   =>  $this->formatShippingCode($method),
                            'carrier_code'  =>  $method['carrier_code'],
                            'name'          =>  $method['name'],
                            'price'         =>  $method['price'],
                        );

                        $shipping_options[] = $shipping_option;
                    } 
                }
            }

        } catch (Exception $ex) {
            $error = array(
                    'success'   => FALSE,
                    'msg'       => $ex->getMessage(),
            );
        }

        return $shipping_options;
    }


    /**
     * Format Magento Order into Webshipr order
     * @param  [type]     $magento_order_id
     * @param  [type]     $shipping_rate_id
     * @param  [type]     $process_order
     * @param  [type]     $droppoint
     * @return [type]
     * @author edudeleon
     * @date   2017-01-19
     */
    private function _getOrderWebshiprFormat($magento_order_id, $shipping_rate_id=null, $process_order=false, $droppoint=array(), $orderObject=null){
        // Loading Magento order
        if($orderObject){
            $order = $orderObject;
        } else {
            $orderModel = $this->_orderFactory->create();
            $order      = $orderModel->load($magento_order_id);
        }

        //Preparing order data
        $magento_order_number   = $order->getIncrementId();
        $shipping_method        = $order->getShippingMethod();
        $shipping_postcode      = $order->getShippingAddress()->getPostcode();
        $currency_code          = $order->getOrderCurrencyCode();
        $process_flag           = ($process_order && ($process_order !== "false")) ? true : false; //Define Order Status (false = pending, true = dispatch)

        //Init arrays
        $shipping_financial_data = $discounts_data = $droppoint_data = array();

        //Prepare discount data
        $discount_amount = abs($order->getDiscountAmount());
        if($discount_amount > 0){
            $discounts_data[] = array(
                "price"         => (float)$discount_amount,
                "tax_included"  => false,       //pending to define
                "tax_percent"   => (float)0,    //pending to define
            );
        }

        //Prepare shipping financial data
        $shipping_price_ex_tax = $order->getShippingInclTax() - $order->getShippingTaxAmount();
        if($shipping_price_ex_tax > 0){
            $shipping_financial_data[] = array(
                "price_ex_tax"  => (float)$shipping_price_ex_tax,
                "name"          => $order->getShippingDescription(),
                "tax_percent"   => (float)0,    //pending to define
            );
        }

        //Check if shipping rate ID is NOT included (Observer case)
        if(!$shipping_rate_id){
            //Get Shipping Id from order shipping method code)
            $shipping_rate_id = $this->getWebshiprShippingRateId($shipping_method);
        }

        //Prepare shipping address data
        $shipping_address   = $order->getShippingAddress();
        
        $shipping_address_line       = explode(PHP_EOL, $shipping_address['street']);
        $shipping_address_line1      = !empty($shipping_address_line[0]) ? $shipping_address_line[0] : $shipping_address['street'];
        $shipping_address_line2      = !empty($shipping_address_line[1]) ? $shipping_address_line[1] : '';
        
        $delivery_address_data = array(
                'address_1'     => $shipping_address_line1,
                'address_2'     => $shipping_address_line2,
                'contact_name'  => $shipping_address['firstname'] .' '. $shipping_address['lastname'],
                'company_name'  => $shipping_address['company'] ? $shipping_address['company'] : '',
                'city'          => $shipping_address['city'],
                'zip'           => $shipping_address['postcode'],
                'country_code'  => $shipping_address['country_id'],
                'email'         => $shipping_address['email'],
                'phone'         => $shipping_address['telephone'] ? $shipping_address['telephone'] : '',
                'phone_area'    => '',
                'state'         => $shipping_address['region'] ? $shipping_address['region'] : '',
        );

        //Prepare billing address data
        $billing_address = $order->getBillingAddress();

        $billing_address_line       = explode(PHP_EOL, $billing_address['street']);
        $billing_address_line1      = !empty($billing_address_line[0]) ? $billing_address_line[0] : $shipping_address['street'];
        $billing_address_line2      = !empty($billing_address_line[1]) ? $billing_address_line[1] : '';

        $billing_address_data = array(
                'address_1'     => $billing_address_line1,
                'address_2'     => $billing_address_line2,
                'contact_name'  => $billing_address['firstname'] .' '. $billing_address['lastname'],
                'company_name'  => $billing_address['company'] ? $billing_address['company'] : '',
                'city'          => $billing_address['city'],
                'zip'           => $billing_address['postcode'],
                'country_code'  => $billing_address['country_id'],
                'email'         => $billing_address['email'],
                'phone'         => $billing_address['telephone'] ? $billing_address['telephone'] : '',
                'phone_area'    => '',
                'state'         => $billing_address['region'] ? $billing_address['region'] : '',
        );

        //Prepare droppoint data
        if(!empty($droppoint)){
            if($this->isDroppoint($shipping_method)){
                $droppoint_data = array(
                    'id'            => $droppoint['id'],
                    'address_1'     => $droppoint['street'],
                    'address_2'     => '',
                    'company_name'  => $droppoint['name'],
                    'city'          => $droppoint['city'],
                    'zip'           => $droppoint['zip'],
                    'country_code'  => $droppoint['country'],
                    'state'         => $droppoint['state'],
                );
            }
        }
        
        //Prepare Order Items
        foreach ($order->getAllVisibleItems() as $item) {
            
            //Loading product data (Not the best solution - a better solution is to save/load this data in/from order item detail..)
            $product = $this->_productFactory->create()->load($item->getProductId());
            
            $order_items[] = array(
                'description'           => $item->getName(),
                'product_no'            => $item->getProductNo() ? $item->getProductNo() : '',
                'sku'                   => $item->getSku(),
                'quantity'              => (int)$item->getQtyOrdered(),
                'item_weight'           => (float)$this->getConvertedWeight($item->getWeight()),
                'location'              => $item->getLocation() ? $item->getLocation() : '',
                'colli'                 => (int)1,
                'origin_country_code'   => $product->getOriginCountryCode() ? $product->getOriginCountryCode() : '',
                'tarif_number'          => $product->getTarifNumber() ? $product->getTarifNumber() : '',
                'ext_ref'               => $item->getItemId(),
                'unit_price'            => (float)$item->getPrice(),
                'tax_percent'           => (float)$item->getTaxPercent(),
            );
        }

        $webshipr_order_format =  array(
            'ext_ref'               => $magento_order_id,
            'visible_ref'           => $magento_order_number,
            'shipping_rate_id'      => $shipping_rate_id,
            'currency'              => $currency_code,
            'shipping_financial'    => $shipping_financial_data,
            'discounts'             => $discounts_data,
            'process'               => $process_flag,
            'delivery_address'      => $delivery_address_data,
            'billing_address'       => $billing_address_data, 
            'items'                 => $order_items,
        ); 

        if(!empty($droppoint_data)){
            $webshipr_order_format['droppoint'] = $droppoint_data;
        }

        return $webshipr_order_format;
    }


    /**
     * Update Webshipr Order
     * Order updated with current Magento order data
     * @param  [type]     $magento_order_id
     * @param  [type]     $shipping_rate_id
     * @param  [type]     $process_order
     * @param  [type]     $droppoint
     * @author edudeleon
     * @date   2017-01-17
     */
    public function updateWebshiprOrder($magento_order_id, $shipping_rate_id=null, $process_order=false, $droppoint=array()){
        //Format Magento order into Webshipper Order
        $webshipr_order_data = $this->_getOrderWebshiprFormat($magento_order_id, $shipping_rate_id, $process_order, $droppoint);
       
        try {
            
            $result  = $this->_webshiprApi->updateOrder($magento_order_id, $webshipr_order_data);

            //Checking for Errors
            $error_msg = $this->_getErrorMessages($result);
            if(empty($error_msg)){
                $webshipr_result = array(
                    'success'   => TRUE,
                    'site_id'   => $result['order_id']
                );

                return $webshipr_result;

            } else {
                return array(
                    'success' => FALSE,
                    'msg'     => $error_msg
                );
            }

            
        } catch (Exception $e) {
             return array(
                    'success'   => FALSE,
                    'msg'       => $e->getMessage(),
            );

        }

        return $result;
    }


    /**
     * Create a Magento Order in Webshipr
     * @param  [type]     $magento_order_id
     * @param  [type]     $shipping_rate_id
     * @param  [type]     $process_order
     * @param  [type]     $droppoint
     * @author edudeleon
     * @date   2017-01-17
     */
    public function createWebshiprOrder($magento_order_id, $shipping_rate_id=null, $process_order=false, $droppoint=array(), $orderObject=null){
        //Format Magento order into Webshipper Order
        $webshipr_order_data = $this->_getOrderWebshiprFormat($magento_order_id, $shipping_rate_id, $process_order, $droppoint, $orderObject);

        try {

            $result  = $this->_webshiprApi->createOrder($webshipr_order_data);

            //Checking for Errors
            $error_msg = $this->_getErrorMessages($result);
            if(empty($error_msg)){
                $webshipr_result = array(
                    'success'   => TRUE,
                    'site_id'   => $result['order_id']
                );
                
                return $webshipr_result;
                
            } else {
                return array(
                    'success' => FALSE,
                    'msg'     => $error_msg
                );
            }

        } catch (Exception $e) {
            return array(
                    'success'   => FALSE,
                    'msg'       => $e->getMessage(),
            );
        }

        return array('success' => FALSE, 'msg'=> 'Webshipr undefined error.');
    }

    /**
     * Get Webshipr order by Magento order ID
     * @param  [type]     $magento_order_id
     * @return [type]
     * @author edudeleon
     * @date   2017-01-18
     */
    public function getWebshiprOrder($magento_order_id){  
        try {
            //Get webshipr order
            $webshipr_order    = $this->_webshiprApi->getOrder($magento_order_id);

            //Check if order exists
            if($webshipr_order['success'] == true){
                if(!empty($webshipr_order['order'])){
                    return $webshipr_order['order'];
                }
            } 
        } catch (Exception $e) {
            return array();
        }

        return array();
    }

    /**
     * Get Webshipr order status from Order 
     * @param  [type]     $magento_order_id
     * @return [type]
     * @author edudeleon
     * @date   2017-01-18
     */
    public function getWebshiprOrderStatus($magento_order_id){
        //Get Webshipr order
        $webshipr_order = $this->getWebshiprOrder($magento_order_id);

        if(!empty($webshipr_order)){
            return $webshipr_order['status'];
        }

        return false;
    }

    /**
     * Check if order shipping method is Webshipr type
     * @param  [type]     $order_shipping_method (e.g. webshipr_123_2131_23)
     * @return boolean
     * @author edudeleon
     * @date   2017-01-17
     */
    public function isWebshiprMethod($order_shipping_method){
        $webship_index = strpos($order_shipping_method, \Webshipr\Shipping\Model\Config::SHIPPING_METHOD_CODE);
        if($webship_index !== false){
            return true;
        }
        return false;
    }

    /**
     * Return Webshippr shipping rate ID by Magento Order Shipping Method
     * @param  [type]       $order_shipping_method (e.g. webshipr_123_2131_23)
     * @return [type]       WebShipr Shipping Rate ID
     * @author edudeleon
     * @date   2017-01-16
     */
    public function getWebshiprShippingRateId($order_shipping_method){
        if($this->isWebshiprMethod($order_shipping_method)){
            $shipping_rate = explode("_", $order_shipping_method);
            if(!empty($shipping_rate[2])){
                return $shipping_rate[2];   //Shipping rate ID
            }
        }

        return false;
    }

    /**
     * Check if order shipping method is dropppoint type
     * @param  [type]     $order_shipping_method (e.g. webshipr_123_2131_23)
     * @return boolean
     * @author edudeleon
     * @date   2017-01-16
     */
    public function isDroppoint($order_shipping_method){
        $shipping_rate = explode("_", $order_shipping_method);
        if(count($shipping_rate) == 4){ //Check if droppoint id is included in the shipping method
            return true;   
        }

        return false;
    }

     /**
     * Return Webshippr Droppoint ID by Magento Order Shipping Method
     * @param  [type]       $order_shipping_method (e.g. webshipr_123_2131_23)
     * @return [type]       
     * @author edudeleon
     * @date   2017-01-16
     */
    public function getWebshiprDroppointId($order_shipping_method){
        $shipping_rate = explode("_", $order_shipping_method);
        if(!empty($shipping_rate[3])){
            return $shipping_rate[3];   //Droppoint ID
        }

        return false;
    }

    /**
     * Get weight into Webshiper format (grams)
     * @param  [type]     $weight
     * @return [type]
     * @author edudeleon
     * @date   2017-01-22
     */
    public function getConvertedWeight($weight){
        $weight_unit        = $this->getWeightUnit();
        $converted_weight   = $weight_unit * (float)$weight;

        return (float)$converted_weight;
    }

    /**
     * Get error messages
     * @param  [type]     $result
     * @return [type]
     * @author edudeleon
     * @date   2016-02-16
     */
    private function _getErrorMessages($result){
       //Checking for Errors
        $error_msg = '';
        if(empty($result['success'])){
            if(!empty($result['error'])){
                $error      = is_array($result['error']) ? json_encode($result['error']) : $result['error'];
                $error_msg  = "[WEBSHIPR MSG] ". $error;
            } else {
                $error_msg  = "[WEBSHIPR MSG] ".json_encode($result);
            }
        }
        return $error_msg;
    }
}