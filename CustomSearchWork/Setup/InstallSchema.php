<?php

namespace Ced\CustomSearchWork\Setup;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

/**
 * Class InstallSchema
 * @package Ced\CustomSearchWork\Setup
 */
class InstallSchema implements InstallSchemaInterface
{

    /**
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();
        $vendorTable = $installer->getTable('catalog_product_entity');
        if ($installer->getConnection()->isTableExists($vendorTable) == true) {
            $columns = [
                'vendor_public_name' => [
                    'type' => Table::TYPE_TEXT,
                    255,
                    'nullable' => false,
                    'comment' => 'Vendor Public Name',
                ],
                'vendor_shop_url_key' => [
                    'type' => Table::TYPE_TEXT,
                    255,
                    'nullable' => false,
                    'comment' => 'Vendor Shop Url Key',
                ],
            ];
            $connection = $installer->getConnection();
            foreach ($columns as $name => $definition) {
                $connection->addColumn($vendorTable, $name, $definition);
            }
        }
        $installer->endSetup();
    }
}
