<?php
/**
 * Copyright © 2017 webshipr.com
 * @autor eduedeleon
 * */
namespace Webshipr\Shipping\Block\Adminhtml\Order\View\Info;

class Webshipr extends \Magento\Backend\Block\Template
{
    
    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Webshipr\Shipping\Helper\Data $webshiprHelperData,
        \Magento\Directory\Model\CountryFactory $countryFactory,
        array $data = []
    ) {
        $this->registry 			= $registry;
        $this->_webshiprHelper      = $webshiprHelperData;
        $this->_countryFactory      = $countryFactory;
        parent::__construct($context, $data);
    }

    /**
     * Render block if Webshipr in enabled
     *
     * @return string
     */
    protected function _toHtml()
    {
        return $this->_webshiprHelper->isEnabled() ? parent::_toHtml() : '';
    }

    /**
     * Retrieve current order model instance
     *
     * @return \Magento\Sales\Model\Order
     */
    public function getOrder()
    {
        return $this->registry->registry('current_order');
    }

    /**
     * Get Order Id
     * @return [type]
     * @author edudeleon
     * @date   2017-01-18
     */
    public function getOrderId(){
    	return $this->getOrder()->getId();
    }

    /**
     * Get order Increment ID
     * @return [type]
     * @author edudeleon
     * @date   2017-01-18
     */
    private function _getOrderNumber(){
    	return $this->getOrder()->getIncrementId();
    }

    /**
     * Get order droppoint from Magento order
     * @return [type]
     * @author edudeleon
     * @date   2017-01-20
     */
    public function getOrderDroppoint(){
        $droppoint_info = $this->getOrder()->getWebshiprDroppointInfo();
        if(!empty($droppoint_info)){
            $droppoint = json_decode($droppoint_info, true);

            //Getting country name
            $country_name = $this->_countryFactory->create()->loadByCode($droppoint["country"])->getName();

            return  $droppoint["name"]."</br> ".
                    $droppoint["street"]."</br> ".
                    $droppoint["zip"]." ". $droppoint["city"]."</br> ".
                    $country_name;
        }
        return "";
    }


    /**
     * Get Webshipr Order info
     * @return [type]
     * @author edudeleon
     * @date   2017-01-20
     */
    public function getWebshiprOrder(){
        return $this->_webshiprHelper->getWebshiprOrder($this->getOrderId());
    }

    /**
     * Get Webshipr order fullfillment details
     * Returns an html table
     * @return [type]
     * @author edudeleon
     * @date   2017-01-20
     */
    public function getWebshiprShippingDetails(){
        // Load Webshipr order
        $webshipr_order = $this->getWebshiprOrder();

        if(!empty($webshipr_order)){
            if($webshipr_order['status'] == 'dispatched'){
                $shipping_fulfillments = '';
                foreach ($webshipr_order['fulfillments'] as $value) {
                    $tracking_url   = !empty($value['tracking'][0]['tracking_url']) ? $value['tracking'][0]['tracking_url'] : ''; 
                    $tracking_code  = !empty($value['tracking'][0]['tracking_no']) ? $value['tracking'][0]['tracking_no'] : ''; 
                    $label_link     = !empty($value['label_link']) ? $value['label_link'] : '';
                    
                    $tracking_row = $tracking_url ? '<a href="'. $tracking_url .'" target="_blank">'. $tracking_code .'</a>' : __('No tracking available');
                    $label_row = $label_link ? '<a href="'. $label_link .'" target="_blank">'. __('Print label') .'</a>' : __('No label available');

                    $created_at = '';
                    if(!empty($value['created_at'])){
                        $time       = strtotime($value['created_at']);
                        $created_at = date('m-d-Y H:i',$time);
                    }

                    $shipping_fulfillments .= '
                                    <tr>
                                        <td class="data-row">'.$created_at.'</td>
                                        <td class="data-row">'. $tracking_row .'</td>
                                        <td class="data-row">'. $label_row .'</td>
                                    </tr>';

                }

                $shipping_details = '
                        <div class="admin__field">
                            <label for="webshipr_shipping_details" class="admin__field-label">'.__('Shipping Details').'</label>
                            <div id="webshipr_shipping_details">
                                <table class="data-grid">
                                    <thead>
                                        <tr>    
                                            <th class="data-grid-th" >Created</th>
                                            <th class="data-grid-th">Tracking</th>
                                            <th class="data-grid-th">Label</th>
                                        </tr>
                                    </thead>

                                    <tbody>'.$shipping_fulfillments.'</tbody>
                                </table>
                            </div>
                        </div>';

                return $shipping_details;
            }
        }

        return '';     
    }

    /**
     * Get Webshipr shipping rates 
     * Returns a dropdown options
     * @param  [type]     $shipping_rate_id
     * @return [type]     
     * @author edudeleon
     * @date   2017-01-20
     */
    public function getShippingRatesDropdownOptions($shipping_rate_id = null){
        
        //Get current shipping rate ID
        if(!$shipping_rate_id){

            // Load order from Webshipr
            $webshipr_order     = $this->getWebshiprOrder();

            //If order exists in webshipr, use the current shipping rate in Webshipr
            if(!empty($webshipr_order['shipping_rate_id'])){
                $shipping_rate_id = $webshipr_order['shipping_rate_id'];

            //If not, get shipping rate ID from Magento
            } else {
                $shipping_method    = $this->getOrder()->getShippingMethod();
                $shipping_rate_id   = $this->_webshiprHelper->getWebshiprShippingRateId($shipping_method);
            }
        }

    	// Loading shipping rates
    	$shipping_rates = $this->_webshiprHelper->getShippingRates();
    	$options = '';
    	foreach ($shipping_rates as $value) {
    		$has_droppoints = $value['has_droppoints'] ? 'true' : 'false';
            $selected       = ($shipping_rate_id == $value['id']) ? 'selected="selected"' : '';
    		
            $options .= '<option value="'.$value['id'].'" has_droppoints="'. $has_droppoints .'" ' .$selected.'>'.$value['name'].'</option>';
    	}
    	return $options;
    }
}