<?php
/**
 * @copyright 2018 webshipr.com
 */

namespace Webshipr\Shipping\Model\Order\Address;

class ToWebshiprAddress
{
    /**
     * @param \Magento\Sales\Api\Data\OrderAddressInterface $address
     * @return array
     */
    public function convert(\Magento\Sales\Api\Data\OrderAddressInterface $address): array
    {
        if ($address instanceof \Magento\Sales\Model\Order\Address) {
            $name = $address->getName();
        } else {
            $name = $address->getFirstname() . ' ' . $address->getLastname();
        }

        return [
            'address_1'    => $address->getStreet()[0] ?? '',
            'address_2'    => $address->getStreet()[1] ?? '',
            'contact_name' => $name,
            'company_name' => $address->getCompany() ?? '',
            'city'         => $address->getCity(),
            'zip'          => $address->getPostcode(),
            'country_code' => $address->getCountryId(),
            'email'        => $address->getEmail(),
            'phone'        => $address->getTelephone() ?? '',
            'phone_area'   => '',
            'state'        => $address->getRegion() ?? '',
        ];
    }
}
