<?php
/**
 * Copyright © 2017 webshipr.com
 * @autor eduedeleon
 * Includes "webshipr_droppoint_info" in Order table
 */

namespace Webshipr\Shipping\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

/**
 * @codeCoverageIgnore
 */
class InstallSchema implements InstallSchemaInterface
{
    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;

        /**
         * Prepare database for install
         */
        $installer->startSetup();

        //Adding webshipr_droppoint_info column to sales_order table
        $installer->getConnection()->addColumn(
            $installer->getTable('sales_order'),
            'webshipr_droppoint_info',
            [
                'type'      => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'comment'   => 'Webshipr Droppoint Info',
            ]
        );

        //Adding webshipr_tracking_url column to sales_shipment_track table
        $installer->getConnection()->addColumn(
            $installer->getTable('sales_shipment_track'),
            'webshipr_tracking_url',
            [
                'type'      => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'comment'   => 'Webshipr Tracking Url',
                'after'     => 'track_number',
            ]
        );

        $setup->endSetup();
    }
}