<?php
/**
 * Copyright © 2017 webshipr.com
 * @autor eduedeleon
 * */

namespace Webshipr\Shipping\Model;

class Config extends \Magento\Framework\Model\AbstractModel
{
   /*
    * API Endpoint
    */
    const ENDPOINT_URL          = 'https://portal.webshipr.com';
    const SANDBOX_URL           = 'https://private-anon-b5ab0d49fe-webshiprmoduleapiversion2.apiary-mock.com';

    const SHIPPING_METHOD_CODE  =   'webshipr';
    const TRACKING_CODE_URL  	=   'https://portal.webshipr.com?tracking=';
}