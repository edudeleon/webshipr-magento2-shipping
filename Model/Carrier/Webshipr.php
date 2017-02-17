<?php
/**
 * Copyright © 2017 webshipr.com
 * @autor eduedeleon
 * */

namespace Webshipr\Shipping\Model\Carrier;
 
use Magento\Quote\Model\Quote\Address\RateResult\Error;
use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Shipping\Model\Carrier\AbstractCarrierOnline;
use Magento\Shipping\Model\Carrier\CarrierInterface;
use Magento\Shipping\Model\Rate\Result;
use Magento\Shipping\Model\Simplexml\Element;
use Magento\Ups\Helper\Config;
use Magento\Framework\Xml\Security;
 
 
class Webshipr extends AbstractCarrierOnline implements CarrierInterface
{
    protected $_code = \Webshipr\Shipping\Model\Config::SHIPPING_METHOD_CODE;
    protected $_request;
    protected $_result;
    protected $_baseCurrencyRate;
    protected $_localeFormat;
    protected $_logger;
    protected $configHelper;
    protected $_errors = [];
     
    /**
     * [__construct description]
     * @param  \Magento\Framework\App\Config\ScopeConfigInterface          $scopeConfig
     * @param  \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory  $rateErrorFactory
     * @param  \Psr\Log\LoggerInterface                                    $logger
     * @param  Security                                                    $xmlSecurity
     * @param  \Magento\Shipping\Model\Simplexml\ElementFactory            $xmlElFactory
     * @param  \Magento\Shipping\Model\Rate\ResultFactory                  $rateFactory
     * @param  \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory $rateMethodFactory
     * @param  \Magento\Shipping\Model\Tracking\ResultFactory              $trackFactory
     * @param  \Magento\Shipping\Model\Tracking\Result\ErrorFactory        $trackErrorFactory
     * @param  \Magento\Shipping\Model\Tracking\Result\StatusFactory       $trackStatusFactory
     * @param  \Magento\Directory\Model\RegionFactory                      $regionFactory
     * @param  \Magento\Directory\Model\CountryFactory                     $countryFactory
     * @param  \Magento\Directory\Model\CurrencyFactory                    $currencyFactory
     * @param  \Magento\Directory\Helper\Data                              $directoryData
     * @param  \Magento\CatalogInventory\Api\StockRegistryInterface        $stockRegistry
     * @param  \Magento\Framework\Locale\FormatInterface                   $localeFormat
     * @param  \Webshipr\Shipping\Helper\Data                              $webshiprHelperData
     * @param  \Magento\Shipping\Helper\Data                               $shippingData
     * @param  \Magento\Sales\Model\OrderFactory                           $orderFactory
     * @param  \Magento\Sales\Api\ShipmentRepositoryInterface              $shipmentRepository
     * @param  \Magento\Shipping\Model\Order\TrackFactory                  $orderTrackFactory
     * @param  Config                                                      $configHelper
     * @param  array                                                       $data
     * @author edudeleon
     * @date   2017-01-30
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory $rateErrorFactory,
        \Psr\Log\LoggerInterface $logger,
        Security $xmlSecurity,
        \Magento\Shipping\Model\Simplexml\ElementFactory $xmlElFactory,
        \Magento\Shipping\Model\Rate\ResultFactory $rateFactory,
        \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory $rateMethodFactory,
        \Magento\Shipping\Model\Tracking\ResultFactory $trackFactory,
        \Magento\Shipping\Model\Tracking\Result\ErrorFactory $trackErrorFactory,
        \Magento\Shipping\Model\Tracking\Result\StatusFactory $trackStatusFactory,
        \Magento\Directory\Model\RegionFactory $regionFactory,
        \Magento\Directory\Model\CountryFactory $countryFactory,
        \Magento\Directory\Model\CurrencyFactory $currencyFactory,
        \Magento\Directory\Helper\Data $directoryData,
        \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry,
        \Magento\Framework\Locale\FormatInterface $localeFormat,
        \Webshipr\Shipping\Helper\Data $webshiprHelperData,
        \Magento\Shipping\Helper\Data $shippingData,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Sales\Api\ShipmentRepositoryInterface $shipmentRepository,
        \Magento\Shipping\Model\Order\TrackFactory $orderTrackFactory,
        Config $configHelper,
        array $data = []
    ) {
        $this->_localeFormat        = $localeFormat;
        $this->configHelper         = $configHelper;
        $this->_webshiprHelper      = $webshiprHelperData;
        $this->_shippingData        = $shippingData;
        $this->_orderFactory        = $orderFactory;
        $this->_shipmentRepository  = $shipmentRepository;
        $this->_orderTrackFactory   = $orderTrackFactory;
        parent::__construct(
            $scopeConfig,
            $rateErrorFactory,
            $logger,
            $xmlSecurity,
            $xmlElFactory,
            $rateFactory,
            $rateMethodFactory,
            $trackFactory,
            $trackErrorFactory,
            $trackStatusFactory,
            $regionFactory,
            $countryFactory,
            $currencyFactory,
            $directoryData,
            $stockRegistry,
            $data
        );
    }

    protected function _doShipmentRequest(\Magento\Framework\DataObject $request) {}

    public function getAllowedMethods(){}
     
    /**
     * Main method used quote shipping rates
     * @param  RateRequest $request
     * @return [type]
     * @author edudeleon
     * @date   2017-01-30
     */
    public function collectRates(RateRequest $request)
    {   
        if (!$this->getConfigFlag('active')) {
            return false;
        }

        /** @var \Magento\Shipping\Model\Rate\Result $result */
        $result = $this->_rateFactory->create();

        $order_subtotal     = $request->getPackageValue();
        $weight             = $request->getPackageWeight();

        //Getting destination address / null for logged in customers (magento bug)
        $street_address     = $request->getDestStreet();

        //Preparing address line 1 and address line 2
        $address_line       = explode(PHP_EOL, $street_address);
        $address_line1      = !empty($address_line[0]) ? $address_line[0] : $street_address;
        $address_line2      = !empty($address_line[1]) ? $address_line[1] : '';
        
        $recipientData = array(
            'address_1'       => $address_line1,
            'address_2'       => $address_line2,                                              
            'zip'             => $request->getDestPostcode(),
            'city'            => $request->getDestCity(),       //null for logged in customers (magento bug)
            'country_code'    => $request->getDestCountryId(),
            'state'           => $request->getDestRegionCode(), //null for logged in customers (magento bug)
        );
        
        //Getting shipping options by method
        $shippingOptions =  $this->_webshiprHelper->getShippingRatesQuote($order_subtotal, $weight, $recipientData);
        foreach ($shippingOptions as $value) {
            /** @var \Magento\Quote\Model\Quote\Address\RateResult\Method $method */
            $method = $this->_rateMethodFactory->create();
     
            $method->setCarrier($this->_code);
            $method->setCarrierTitle($this->getConfigData('title'));
     
            $method->setMethod($value['method_code']);
            $method->setMethodTitle($value['name']);     
        
            $amount =  $value['price'];
            $method->setPrice($amount);
            $method->setCost($amount);
     
            $result->append($method);
        }    
 
        return $result;
    }

    /**
     * Get tracking
     *
     * @param string|string[] $trackings
     * @return Result|null
     */
    public function getTracking($trackings)
    {
        if (!is_array($trackings)) {
            $trackings = [$trackings];
        }

        //Prepare webshipr tracking code result
        $this->_getWebshiprTracking($trackings);
       
        return $this->_result;
    }

    /**
     * Prepare data for Tracking Pop Up
     * @param  [type]     $trackings
     * @param  [type]     $order_id
     * @return [type]
     * @author edudeleon
     * @date   2017-01-30
     */
    private function _getWebshiprTracking($trackings){
        $result = $this->_trackFactory->create();
        foreach ($trackings as $tracking) {

            //Getting tracking URL
            $tracking_data = $this->_orderTrackFactory->create()->getCollection()
                            ->addFieldToFilter('track_number', $tracking)
                            ->addFieldToFilter('carrier_code', $this->_code)
                            ->getLastItem(); 

            $tracking_url = $tracking_data->getWebshiprTrackingUrl();

            //Preparing tracking object for Pop Up..
            $status = $this->_trackStatusFactory->create();
            $status->setCarrier($this->_code);
            $status->setCarrierTitle($this->getConfigData('title'));
            $status->setTracking($tracking);
            $status->setPopup(1);
            $status->setUrl($tracking_url);
            $result->append($status);
        }

        $this->_result = $result;

        return $result;
    }
    
    /**
     * [proccessAdditionalValidation description]
     * @param  \Magento\Framework\DataObject $request
     * @return [type]
     * @author edudeleon
     * @date   2017-01-30
     */
    public function proccessAdditionalValidation(\Magento\Framework\DataObject $request) {
        return true;
    }
}