<?php
/**
 * Copyright © 2017 webshipr.com
 * @autor eduedeleon
 * */

namespace Webshipr\Shipping\Model\Api;

class Webshiprapi extends \Magento\Framework\Model\AbstractModel
{
    /**
     * Magento Logger
     * @var Psr\Log\LoggerInterface
     */
    protected $_logger;

    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfigInterface
    ) {
        $this->_logger          = $logger;
        $this->_scopeConfig     = $scopeConfigInterface;
    }

    /**
     * Returns the method URL
     * @param  [type]     $path
     * @return [type]
     * @author edudeleon
     * @date   2017-01-02
     */
    protected function _getUrl($path)
    {
        return \Webshipr\Shipping\Model\Config::ENDPOINT_URL.$path;
    }

    /**
     * Get store token
     * @return [type]
     * @author edudeleon
     * @date   2017-01-23
     */
    private function _getToken()
    {
        return $this->_scopeConfig->getValue(
            'carriers/webshipr/token',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Check if log API calls
     * @return [type]
     * @author edudeleon
     * @date   2017-01-23
     */
    private function _logApiCalls()
    {
        return $this->_scopeConfig->getValue(
            'carriers/webshipr/enable_debugging',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Makes the API Call
     * @param  [type]     $endpoint
     * @param  string     $method
     * @param  [type]     $data
     * @return [type]
     * @author edudeleon
     * @date   2017-01-02
     */
    protected function _call($endpoint, $method = 'POST', $data = null)
    {
        $token = $this->_getToken();
        $state =  \Magento\Framework\App\ObjectManager::getInstance()
                        ->get('Magento\Framework\App\State');
                        
        //Get Store Token based on order information (Only when managing order in Admin Panel)
        if($state->getAreaCode() === 'adminhtml') {

            //Check if endpoint is the one used to "get/update" orders
            $get_update_order_endpoint = strchr($endpoint, '/API/V2/orders/');
            if($get_update_order_endpoint) {

                //Getting order data
                $magento_order_id   = str_replace('/API/V2/orders/', '', $endpoint);
                $order              = \Magento\Framework\App\ObjectManager::getInstance()
                                            ->create('\Magento\Sales\Model\Order')->load($magento_order_id);

                //Get Token by Store ID
                $_token = $this->_scopeConfig->getValue(
                    'carriers/webshipr/token',
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                    $order->getStoreId()
                );
                $token = $_token ? $_token : $token;
            
            //Check if endpoint is the one used to "create" orders
            } else if($endpoint == '/API/V2/orders') {
                 
                if(!empty($data['ext_ref'])) {
                    
                    //Getting order data
                    $magento_order_id   = $data['ext_ref'];
                    $order              = \Magento\Framework\App\ObjectManager::getInstance()
                                                ->create('\Magento\Sales\Model\Order')->load($magento_order_id);

                     //Get Token by Store ID
                    $_token = $this->_scopeConfig->getValue(
                        'carriers/webshipr/token',
                        \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                        $order->getStoreId()
                    );
                    $token = $_token ? $_token : $token;
                }   
            
            //Check if Store ID is set in GET request
            } else if(!empty($_GET['storeId'])) {
                $_token = $this->_scopeConfig->getValue(
                    'carriers/webshipr/token',
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                    $_GET['storeId']
                );
                $token = $_token ? $_token : $token;
            }
        }

        //Prepare URL
        $url    =  $this->_getUrl($endpoint);
        $method = strtoupper($method);
        
        //Instantiate the
        $client = new \Zend_Http_Client($url);
        $client->setMethod($method);

        //Preparing headers
        $headers = [
            'Authorization' => 'Token token="'.$token.'"',
            'Content-Type'  => 'application/json'
        ];

        $client->setHeaders($headers);

        if ($method == 'POST' || $method == "PUT" || $method == "PATCH") {
            $client->setRawData(json_encode($data), 'application/json');
        }

        //Log API Request
        if ($this->_logApiCalls()) {
            $this->_logger->debug("[WEBSHIPR API REQUEST] ".
                print_r(
                    [
                       'url'     => $url,
                       'method'  => $method,
                       'headers' => $headers,
                       'data'    => json_encode($data),
                    ],
                    true
                ));
        }

        try {
            $response = $client->request();
        } catch (Zend_Http_Client_Exception $ex) {
            //Logging exceptions
            $this->_logger->debug("[WEBSHIPR] ".'Call to ' . $url . ' resulted in: ' . $ex->getMessage());
            $this->_logger->debug("[WEBSHIPR] ".'--Last Request: ' . $client->getLastRequest());
            $this->_logger->debug("[WEBSHIPR] ".'--Last Response: ' . $client->getLastResponse());

            return $ex->getMessage();
        }

        //Prepare response
        $body = json_decode($response->getBody(), true);

        //Log API Response
        if ($this->_logApiCalls()) {
            $log_msg = var_export($body, true);
            $this->_logger->debug("[WEBSHIPR API RESPONSE] ".$log_msg);
        }

        
        return $body;
    }

    /**
     * Get Shipping Rates
     * @return [type]
     * @author edudeleon
     * @date   2017-01-18
     */
    public function getShippingRates()
    {

        $response = $this->_call('/API/V2/shipping_rates/', 'GET');

        return $response;
    }

    /**
     * Get shipping rates for an order (Quote)
     * @param  [type]     $price            Order subtotal
     * @param  [type]     $currency         Store currency
     * @param  [type]     $weight           Total weight of the current order (sum of items weight)
     * @param  [Array]    $recipientData    Array with recipient information. Array structure:
                                                $recipientData = array(
                                                    'address_1'     => 'Test Address 1',
                                                    'address_2'     => 'Test Address 2',
                                                    'zip'           => '8600',
                                                    'city'          => 'Silkeborg',
                                                    'country_code'  => 'DK',
                                                    'state'
                                                );
     * @author edudeleon
     * @date   2017-01-13
     */
    public function getShippingRatesQuote($price, $currency, $weight, $recipientData)
    {
        $data = [
            'price'     => (float)$price,
            'currency'  => $currency,
            'weight'    => (float)$weight,
            'recipient' => $recipientData
        ];

        $response = $this->_call('/API/V2/shipping_rates/quote', 'POST', $data);

        return $response;
    }

    /**
     * Get droppoints by Zip Code and Shipping Rate ID
     * @param  [type]     $rate_id      Shipping Rate ID
     * @param  [type]     $zip_code     Zip code
     * @param  [type]     $country      Country ISO code
     * @param  [type]     $address      Street address
     * @return [type]
     * @author edudeleon
     * @date   2017-01-13
     */
    public function getDroppoints($rate_id, $zip_code, $country, $address = null)
    {

        $data = [
            'rate_id'   =>  $rate_id,
            'zip'       =>  $zip_code,
            'country'   =>  $country,
        ];

        //If address is present, get droppoint by address and zipcode
        if (!empty($address)) {
            $data['address'] = $address;

            $response = $this->_call('/API/V2/droppoints/by_address', 'POST', $data);
        } else {
            //Get droppoint by zipcode only
            $response = $this->_call('/API/V2/droppoints/by_zip', 'POST', $data);
        }

        return $response;
    }

    /**
     * Create an order in Webshipr
     * @param  [type]     $webshipr_order_data
     * @return [type]
     * @author edudeleon
     * @date   2017-01-23
     */
    public function createOrder($webshipr_order_data)
    {

        $response = $this->_call('/API/V2/orders', 'POST', $webshipr_order_data);

        return $response;
    }


    /**
     * Update an order in Webshipr
     * @param  [type]     $magento_order_id
     * @param  [type]     $webshipr_order_data
     * @return [type]
     * @author edudeleon
     * @date   2017-01-23
     */
    public function updateOrder($magento_order_id, $webshipr_order_data)
    {

        $response = $this->_call('/API/V2/orders/'.$magento_order_id, 'PATCH', $webshipr_order_data);

        return $response;
    }



    /**
     * Get Webshipr order by Magento order ID
     * @param  [type]     $magento_order_id
     * @return [type]
     * @author edudeleon
     * @date   2017-01-18
     */
    public function getOrder($magento_order_id)
    {

        $response = $this->_call('/API/V2/orders/'.$magento_order_id, 'GET');

        return $response;
    }
}
