<?php
/**
 * Copyright © 2017 webshipr.com
 * @autor eduedeleon
 * */

namespace Webshipr\Shipping\Model\Config\Source;

class Orderstatuses implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Get array of custom order statuses
     *
     * @return array
     */
    public function toOptionArray()
    {	
    	//Get Object Manager Instance
		$objectManager 	= \Magento\Framework\App\ObjectManager::getInstance();
		$order_statuses = $objectManager->create('Magento\Sales\Model\Order\Status')->getCollection();

    	$statuses[] = array(
            'value' => 'autotransfer_disabled',
            'label' => 'Disable auto transfer',
        );

    	foreach ($order_statuses as $status) {
    		$statuses[] = array(
    			'value' => $status->getStatus(),
                'label' => $status->getLabel(),
    		);
    	}

        return $statuses;
    }
}