<?php
/**
 * Copyright © 2017 webshipr.com
 * @autor eduedeleon
 * */
namespace Webshipr\Shipping\Helper;

use Magento\Store\Api\Data\StoreInterface;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var \Webshipr\Shipping\Model\Api\Webshiprapi
     */
    protected $_webshiprApi;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Webshipr\Shipping\Model\Api\Webshiprapi $webshiprApi,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->_webshiprApi  = $webshiprApi;
        $this->_storeManager = $storeManager;
        parent::__construct($context);
    }

    /**
     * Config paths for using throughout the code
     */
    const CONFIG_PATH_ENABLED                       = 'carriers/webshipr/active';
    const CONFIG_PATH_TOKEN                         = 'carriers/webshipr/token';
    const CONFIG_PATH_STATUSES                      = 'carriers/webshipr/statuses';
    const CONFIG_PATH_AUTO_TRANSFER                 = 'carriers/webshipr/auto_transfer';
    const CONFIG_PATH_AUTO_TRANSFER_MONEY_ORDERS    = 'carriers/webshipr/auto_transfer_money_orders';
    const CONFIG_PATH_DROPPOINT_LIMIT               = 'carriers/webshipr/droppoint_limit';
    const CONFIG_PATH_WEIGHT_UNIT                   = 'carriers/webshipr/weight_unit';
    const CONFIG_PATH_ORDER_CLOSING                 = 'carriers/webshipr/order_closing';
    const CONFIG_PATH_CUSTOMER_NOTIFICATIONS        = 'carriers/webshipr/customer_notifications';


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
    public function getToken($store = null)
    {
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
    public function getStatuses()
    {
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
    public function processOnAutoTransfer()
    {
        $config_value = $this->scopeConfig->getValue(
            self::CONFIG_PATH_AUTO_TRANSFER,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        return $config_value ? true : false;
    }

    /**
     * Check if always auto process money orders
     * @return [type]
     * @author edudeleon
     * @date   2017-08-18
     */
    public function autoProcessMoneyOrders()
    {
        $config_value = $this->scopeConfig->getValue(
            self::CONFIG_PATH_AUTO_TRANSFER_MONEY_ORDERS,
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
    public function getDroppointLimit()
    {
         return $this->scopeConfig->getValue(
             self::CONFIG_PATH_DROPPOINT_LIMIT,
             \Magento\Store\Model\ScopeInterface::SCOPE_STORE
         );
    }

    /**
     * Get weight unit from admin panel
     * @param null|StoreInterface $store
     * @return float
     */
    public function getWeightUnit(StoreInterface $store = null)
    {
        $scopeCode = $store ? $store->getCode() : null;
         return (float)$this->scopeConfig->getValue(
             self::CONFIG_PATH_WEIGHT_UNIT,
             \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
             $scopeCode
         );
    }

    /**
     * Check if Webshipr can close Magento Orders via API
     * @return [type]
     * @author edudeleon
     * @date   2017-01-25
     */
    public function orderClosingEnabled()
    {
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
    public function getEnabledNotifications()
    {
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
    public function formatShippingCode($webshiprShippingRate, $webshiprDroppoint = [])
    {
        $shipping_rate_code =  $webshiprShippingRate['carrier_id']."_".$webshiprShippingRate['id'];
        if (!empty($webshiprDroppoint)) {
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
    public function getShippingRates()
    {
        $shipping_rates_array = [];

        try {
            //Get shipping rates for current order
            $shipping_rates = $this->_webshiprApi->getShippingRates();

            //Prepare data
            if (!empty($shipping_rates)) {
                foreach ($shipping_rates as $shipping_rate) {
                    $shipping_rates_array[] = [
                        'id'                => $shipping_rate['id'],
                        'name'              => $shipping_rate['name'],
                        'has_droppoints'    => $shipping_rate['dynamic_pickup'],
                    ];
                }
            }
        } catch (Exception $ex) {
            $error = [
                    'success'   => false,
                    'msg'       => $ex->getMessage(),
            ];
        }

        return $shipping_rates_array;
    }

    /**
     * Get droppoint data by Id and shipping rate ID
     * @param  [type]     $droppoint_id
     * @param  [type]     $shipping_rate_id
     * @param  [type]     $zip_code
     * @param  [type]     $country
     * @param  [type]     $address
     * @return [type]
     * @author edudeleon
     * @date   2017-01-22
     */
    public function getDroppointById($droppoint_id, $shipping_rate_id, $zip_code, $country, $address = null)
    {
        try {
            //Get droppoints from Webshipr
            $droppoints = $this->_webshiprApi->getDroppoints($shipping_rate_id, $zip_code, $country, $address);

            if (!empty($droppoints['status'])) {
                if ($droppoints['status'] == 'success') {
                    //Get droppoint data
                    foreach ($droppoints['data'] as $droppoint) {
                        if ($droppoint['id'] == $droppoint_id) {
                            //Unset other vars opening hours...
                            unset($droppoint['opening_hours']);

                            return [
                                'success'       => true,
                                'droppoint'     => $droppoint,
                            ];
                        }
                    }
                } else {
                    return [
                                'success' => false,
                                'msg'     => !empty($droppoints['message']) ? $droppoints['message'] : "Droppoint not found"
                            ];
                }
            }
        } catch (Exception $e) {
            return [
                        'success' => false,
                        'msg'     => $e->getMessage()
                        ];
        }

        return ['success' => false];
    }


    /**
     * Get Webhshipr shipping rates for current order (Quote)
     * @param  [type]     $order_subtotal   Order subtotal
     * @param  [type]     $weight           Total weight of the current order (sum of items weight)
     * @param  [Array]    $recipientData    Array with recipient information
     * @author edudeleon
     * @date   2017-01-13
     */
    public function getShippingRatesQuote($order_subtotal, $_weight, $recipientData)
    {
        $shipping_options       = [];
        $store                  = $this->_storeManager->getStore();
        $currency_code          = $store->getCurrentCurrencyCode();

        //Convert weight
        $weight = $this->getConvertedWeight($_weight, $store);

        try {
            //Get shipping rates for current order
            $shipping_methods    = $this->_webshiprApi->getShippingRatesQuote($order_subtotal, $currency_code, $weight, $recipientData);

            //Prepare data
            if (!empty($shipping_methods)) {
                foreach ($shipping_methods as $method) {
                    //Check if shipping rate allow droppoints
                    if ($method['dynamic_pickup']) {
                        $droppoints = $this->_webshiprApi->getDroppoints($method['id'], $recipientData['zip'], $recipientData['country_code'], $recipientData['address_1']);
                        if (!empty($droppoints)) {
                            $droppoints_list = $droppoints['data'];
                            $i = 1;
                            foreach ($droppoints_list as $droppoint) {
                                $shipping_option = [
                                    'method_code'   =>  $this->formatShippingCode($method, $droppoint),
                                    'carrier_code'  =>  $method['carrier_code'],
                                    'name'          =>  $method['name'] . ' - ' . $droppoint['name'] . '-' . $droppoint['street'] . ' - '. $droppoint['zip'] . '-' . $droppoint['city'],
                                    'price'         =>  $method['price'],
                                ];

                                 $shipping_options[] = $shipping_option;

                                //Set droppoint limit
                                if ($i >= $this->getDroppointLimit()) {
                                    break;
                                }
                                $i++;
                            }
                        } else {
                             $shipping_option = [
                                'method_code'   =>  $this->formatShippingCode($method),
                                'carrier_code'  =>  $method['carrier_code'],
                                'price'         =>  $method['price'],
                                'carrier_code'  =>  $method['carrier_code'],
                             ];

                             $shipping_options[] = $shipping_option;
                        }
                    } else {
                        $shipping_option =  [
                            'method_code'   =>  $this->formatShippingCode($method),
                            'carrier_code'  =>  $method['carrier_code'],
                            'name'          =>  $method['name'],
                            'price'         =>  $method['price'],
                        ];

                        $shipping_options[] = $shipping_option;
                    }
                }
            }
        } catch (Exception $ex) {
            $error = [
                    'success'   => false,
                    'msg'       => $ex->getMessage(),
            ];
        }

        return $shipping_options;
    }

    /**
     * Get Webshipr order by Magento order ID
     * @param  [type]     $magento_order_id
     * @return [type]
     * @author edudeleon
     * @date   2017-01-18
     */
    public function getWebshiprOrder($magento_order_id)
    {
        try {
            //Get webshipr order
            $webshipr_order    = $this->_webshiprApi->getOrder($magento_order_id);

            //Check if order exists
            if ($webshipr_order['success'] == true) {
                if (!empty($webshipr_order['order'])) {
                    return $webshipr_order['order'];
                }
            }
        } catch (Exception $e) {
            return [];
        }

        return [];
    }

    /**
     * Get Webshipr order status from Order
     * @param  [type]     $magento_order_id
     * @return [type]
     * @author edudeleon
     * @date   2017-01-18
     */
    public function getWebshiprOrderStatus($magento_order_id)
    {
        //Get Webshipr order
        $webshipr_order = $this->getWebshiprOrder($magento_order_id);

        if (!empty($webshipr_order)) {
            return $webshipr_order['status'];
        }

        return false;
    }

    /**
     * Get weight into Webshiper format (grams)
     * @param float               $weight
     * @param null|StoreInterface $store
     * @return float
     * @author edudeleon
     * @date   2017-01-22
     */
    public function getConvertedWeight($weight, StoreInterface $store = null): float
    {
        $weight_unit        = $this->getWeightUnit($store);
        $converted_weight   = $weight_unit * (float)$weight;

        return (float)$converted_weight;
    }
}
