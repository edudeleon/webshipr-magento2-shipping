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
        ){
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
    private function _getToken(){
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
    private function _logApiCalls(){
        return false;
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

        //Prepare URL
        $url    =  $this->_getUrl($endpoint);
        $method = strtoupper($method);
        
        //Instantiate the
        $client = new \Zend_Http_Client($url);
        $client->setMethod($method);

        //Preparing headers
        $headers = array(
            'Authorization' => 'Token token="'.$token.'"',
            'Content-Type'  => 'application/json'
        );

        $client->setHeaders($headers);

        if($method == 'POST' || $method == "PUT" || $method == "PATCH") {
            $client->setRawData(json_encode($data), 'application/json');
        }

        //Log API Request
        if($this->_logApiCalls()){
            $this->_logger->info("[WEBSHIPR API REQUEST] ".
                print_r(
                    array(
                       'url'     => $url,
                       'method'  => $method,
                       'headers' => $headers,
                       'data'    => json_encode($data),
                    ),
                    true
                )
            );
        }

        try {

            $response = $client->request();

        } catch ( Zend_Http_Client_Exception $ex ) {   
            //Logging exceptions         
            $this->_logger->info("[WEBSHIPR] ".'Call to ' . $url . ' resulted in: ' . $ex->getMessage());
            $this->_logger->info("[WEBSHIPR] ".'--Last Request: ' . $client->getLastRequest());
            $this->_logger->info("[WEBSHIPR] ".'--Last Response: ' . $client->getLastResponse());

            return $ex->getMessage();
        }

        //Prepare response
        $body = json_decode($response->getBody(), true);

        //Log API Response
        if($this->_logApiCalls()){
            $log_msg = var_export($body, true);
            $this->_logger->info("[WEBSHIPR API RESPONSE] ".$log_msg);
        }

        
        return $body;
    }

    /**
     * Get Shipping Rates
     * @return [type]
     * @author edudeleon
     * @date   2017-01-18
     */
    public function getShippingRates(){

        $response = $this->_call('/API/V2/shipping_rates/','GET');

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
    public function getShippingRatesQuote($price, $currency, $weight, $recipientData){
        $data = array(
            'price'     => (float)$price,
            'currency'  => $currency,
            'weight'    => (float)$weight,
            'recipient' => $recipientData
        );

        $response = $this->_call('/API/V2/shipping_rates/quote','POST', $data);

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
    public function getDroppoints($rate_id, $zip_code, $country, $address=null){

        $data = array(
            'rate_id'   =>  $rate_id,
            'zip'       =>  $zip_code,
            'country'   =>  $country,
        );

        //Remove this when Magento 2 issue "#3789" will be fixed (Fix will come in Magento 2.1.5)
        $address = false;

        //If address is present, get droppoint by address and zipcode
        if(!empty($address)){
            $data['address'] = $address;

            $response = $this->_call('/API/V2/droppoints/by_address','POST', $data);

        } else {
            
            //Get droppoint by zipcode only
            $response = $this->_call('/API/V2/droppoints/by_zip','POST', $data);
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
    public function createOrder($webshipr_order_data){

        $response = $this->_call('/API/V2/orders','POST', $webshipr_order_data);

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
    public function updateOrder($magento_order_id, $webshipr_order_data){

        $response = $this->_call('/API/V2/orders/'.$magento_order_id,'PATCH', $webshipr_order_data);

        return $response;
    }



    /**
     * Get Webshipr order by Magento order ID
     * @param  [type]     $magento_order_id
     * @return [type]
     * @author edudeleon
     * @date   2017-01-18
     */
    public function getOrder($magento_order_id){

        $response = $this->_call('/API/V2/orders/'.$magento_order_id,'GET');

        return $response;
    }

}