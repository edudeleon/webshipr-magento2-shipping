/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
/*browser:true*/
/*global define*/
define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/shipping-rates-validator',
        'Magento_Checkout/js/model/shipping-rates-validation-rules',
        'Webshipr_Shipping/js/model/shipping-rates-validator',
        'Webshipr_Shipping/js/model/shipping-rates-validation-rules'
    ],
    function (
        Component,
        defaultShippingRatesValidator,
        defaultShippingRatesValidationRules,
        webshiprShippingRatesValidator,
        webshiprShippingRatesValidationRules
    ) {
        'use strict';

        defaultShippingRatesValidator.registerValidator('webshipr', webshiprShippingRatesValidator);
        defaultShippingRatesValidationRules.registerRules('webshipr', webshiprShippingRatesValidationRules);
        return Component;
    }
);