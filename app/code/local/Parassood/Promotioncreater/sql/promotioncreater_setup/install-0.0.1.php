<?php

/* @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;

$salesruleTable = $installer->getTable('salesrule/rule');
$installer->getConnection()
    ->addColumn($salesruleTable, 'promotion_image', array(
        'type'      => Varien_Db_Ddl_Table::TYPE_TEXT,
        'length'    => 255,
        'comment'   => 'Sales Rule Promotion Image.'
    ));



$installer->endSetup();
