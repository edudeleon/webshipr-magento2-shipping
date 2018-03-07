<?php
/**
 * @copyright 2018 webshipr.com
 */

namespace Webshipr\Shipping\Model\Order\Item;

use Magento\Framework\Exception\NoSuchEntityException;

class ToWebshiprItem
{
    /**
     * @var \Webshipr\Shipping\Helper\Data
     */
    protected $_webshiprHelper;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $_productRepository;

    /**
     * @var \Webshipr\Shipping\Api\OrderItemExtRefManagementInterface
     */
    protected $_extRefManagement;

    /**
     * @param \Webshipr\Shipping\Helper\Data                            $webshiprHelper
     * @param \Magento\Store\Model\StoreManagerInterface                $storeManager
     * @param \Magento\Catalog\Api\ProductRepositoryInterface           $productRepository
     * @param \Webshipr\Shipping\Api\OrderItemExtRefManagementInterface $extRefManagement
     */
    public function __construct(
        \Webshipr\Shipping\Helper\Data $webshiprHelper,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Webshipr\Shipping\Api\OrderItemExtRefManagementInterface $extRefManagement
    ) {
        $this->_webshiprHelper = $webshiprHelper;
        $this->_storeManager = $storeManager;
        $this->_productRepository = $productRepository;
        $this->_extRefManagement = $extRefManagement;
    }

    /**
     * @param \Magento\Sales\Api\Data\OrderItemInterface $item
     * @return array
     */
    public function convert(\Magento\Sales\Api\Data\OrderItemInterface $item): array
    {
        $tarifNumber = '';
        $originCountryCode = '';

        try {
            //Loading product data (Not the best solution - a better solution is to save/load this data in/from order item detail..)
            $product = $this->_productRepository->get($item->getProductId(), false, $item->getStoreId());
            $tarifNumber = $product->getTarifNumber();
            $originCountryCode = $product->getOriginCountryCode();
        } catch (NoSuchEntityException $e) {
            // Let's just do nothing
        }

        $store = $this->_storeManager->getStore($item->getStoreId());
        return [
            'description'         => $item->getName(),
            'product_no'          => $item->getProductNo() ?? '',
            'sku'                 => $item->getSku(),
            'quantity'            => (int)$item->getQtyOrdered(),
            'item_weight'         => $this->_webshiprHelper->getConvertedWeight($item->getWeight(), $store),
            'location'            => $item->getLocation() ?? '',
            'colli'               => 1,
            'tarif_number'        => $tarifNumber,
            'origin_country_code' => $originCountryCode,
            'ext_ref'             => $this->_extRefManagement->getExtRef($item),
            'unit_price'          => (float)$item->getPrice(),
            'tax_percent'         => (float)$item->getTaxPercent(),
        ];
    }
}
