<?php
 
namespace Webshipr\Shipping\Controller\Index;
 
use Magento\Framework\App\Action\Context;
 
class Index extends \Magento\Framework\App\Action\Action
{
    protected $_resultPageFactory;
 
    public function __construct(
        Context $context, 
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Webshipr\Shipping\Helper\Data $webshiprHelperData,
        \Magento\Catalog\Model\ProductFactory $productFactory
    ){
        $this->_resultPageFactory   = $resultPageFactory;
        $this->_orderFactory        = $orderFactory;
        $this->_webshiprHelper      = $webshiprHelperData;
        $this->_productFactory       = $productFactory;
        parent::__construct($context);
    }
 



    public function execute()
    {
        $street_address = "Line aadreess 1\nLines address 2";
        $address_line       = explode(PHP_EOL, $street_address);
        print_r($address_line);
        echo "<br/><br/><br/>";

        $street_address = "Line aadreess 1";
        $address_line       = explode(PHP_EOL, $street_address);
        print_r($address_line);
        echo "<br/><br/><br/>";

        $street_address = null;
        $address_line       = explode(PHP_EOL, $street_address);
        print_r($address_line);
        echo "<br/><br/><br/>";

        die;

        $weight = '';

        $weight_unit        = $this->_webshiprHelper->getWeightUnit();
        $converted_weight   = ((float)$weight_unit) * ((float)$weight);

        echo (float)$weight; die;
       
        $not = "";

        $a  = explode(",", $not);
        print_r($a );
        die;



        // load order
        $orderModel = $this->_orderFactory->create();
        $order_id = 48;
        $order = $orderModel->load($order_id);

        $shipping_price_ex_tax = $order->getShippingInclTax() - $order->getShippingTaxAmount();
        // echo $shipping_price_ex_tax; die;
        // echo abs($order->getDiscountAmount());die;
        // echo $order->getShippingDescription(); die;

        // print_r($order->getData());
        echo "</br></br>";
        // die;

        // Loop through order items
        foreach ($order->getAllItems() AS $orderItem) {
          
            $_product = $this->_productFactory->create()->load($orderItem->getProductId());



            echo $_product->getOriginCountryCode() ? $_product->getOriginCountryCode() : "No for ".$orderItem->getName();

            
            echo "</br></br>";
        }

        die;

        // Load 
        // 
        $shipping_address   = $order->getShippingAddress();

        print_r($order->getOrderCurrencyCode());
        die;


         $shipping_address_line       = explode(PHP_EOL, "Kastanievej 15\nApt 202");
        print_r($shipping_address_line);
        die;



        $data = "2017-01-23T00:50:18.000+01:00";

        $time = strtotime($data);

        $newformat = date('m-d-Y H:i',$time);

        echo $newformat;die;


        // droppoint data
        $droppoint_data = array(
            "id"            => "234523",
            "address_1"     => "Julsøvej 24",
            "address_2"     => "",
            "company_name"  => "Posthus Dagligbrugsen",
            "city"          => "Silkeborg",
            "zip"           => "8600",
            "country_code"  => "DK",
            "state"         => ""
        );

        
        


        foreach ($order->getAllItems() as $item) {
            // print_r($item->getData());
            $order_items[] = array(
                "description"           => $item->getName(), //<!-- Description of the goods ( not mandatory - but should be sent when available ) -->
                "product_no"            => $item->getProductNo(), //<!-- product no ( not mandatory but should be sent when available ) -->
                "sku"                   =>  $item->getSku(), //<!-- SKU - Stock Keeping Unit ( not mandatory but should be sent when available. It is important for many customers ) -->
                "quantity"              =>  number_format($item->getQtyOrdered(),0), //<!-- Quantity is mandatory -->
                "item_weight"           =>  number_format($item->getWeight(),2), //<!-- weight is mandatory -->
                "location"              =>  $item->getLocation(), //<!-- location code of the goods on the stock. should be sent when possible -->
                "colli"                 => 1, //<!-- colli should always be set to 1 -->
                "origin_country_code"   => $item->getOriginCountryCode(), //<!-- Origin county code of the product. Important. Will not crash if missing, but for all customs shipments this is required to generate customs -->
                "tarif_number"          => $item->getTarifNumber(),//<!-- Tarif number of the product. Important. Will not crash if missing, but for all customs shipments this is required to generate customs -->
                "ext_ref"               => $item->getItemId(),//<!-- to identify this order line. not mandatory -->
                "unit_price"            => number_format($item->getPrice(),2), //<!-- mandatory -->
                "tax_percent"           => number_format($item->getTaxPercent(),2) //<!-- mandatory -->
            );
        }

    

        //Shipping method
        $shipping_method    = $order->getShippingMethod();
        $shipping_postcode  = $order->getShippingAddress()->getPostcode();


        //Prepare shipping address data
        $shipping_address   = $order->getShippingAddress();
        $delivery_address_data = array(
                'address_1'     => $shipping_address['street'],
                'address_2'     => '',
                'contact_name'  => $shipping_address['firstname'] .' '. $shipping_address['lastname'],
                'company_name'  => $shipping_address['company'],
                'city'          => $shipping_address['city'],
                'zip'           => $shipping_address['postcode'],
                'country_code'  => $shipping_address['country_id'],
                'email'         => $shipping_address['email'],
                'phone'         => $shipping_address['phone'],
                'phone_area'    => '',
                'state'         => $shipping_address['region'],
        );


        //Prepare billing address data
        $billing_address = $order->getBillingAddress();
        $billing_address_data = array(
                'address_1'     => $billing_address['street'],
                'address_2'     => '',
                'contact_name'  => $billing_address['firstname'] .' '. $billing_address['lastname'],
                'company_name'  => $billing_address['company'],
                'city'          => $billing_address['city'],
                'zip'           => $billing_address['postcode'],
                'country_code'  => $billing_address['country_id'],
                'email'         => $billing_address['email'],
                'phone'         => $billing_address['phone'],
                'phone_area'    => '',
                'state'         => $billing_address['region'],
        );

        //Prepare dropoint data
        if($this->_webshiprHelper->isDroppoint($shipping_method)){
            $shipping_id = $this->_webshiprHelper->getShippingMethodId($shipping_method, true);
        } else {
            $shipping_id = $this->_webshiprHelper->getShippingMethodId($shipping_method);
        }
        $droppoint_data = array(
            "id"            => $shipping_id,
            "address_1"     => "Julsøvej 24",
            "address_2"     => "",
            "company_name"  => "Posthus Dagligbrugsen",
            "city"          => "Silkeborg",
            "zip"           => $shipping_postcode,
            "country_code"  => "DK",
            "state"         => ""
        );

        


                                
    }
}