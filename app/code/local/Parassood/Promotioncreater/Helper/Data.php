<?php

class Parassood_Promotioncreater_Helper_Data extends Mage_Core_Helper_Abstract
{
    /**
     *
     * Get Promotion Image for product.
     * @param $product
     * @return bool
     */
    public function getPromotionImage($product)
    {
        if ($product instanceof Mage_Catalog_Model_Product) {

            $salesrulesCollection = Mage::getModel('salesrule/rule')->getCollection();
            $time = time();
            $websiteId = Mage::app()->getWebsite()->getId();
            $today = date('Y-m-d', $time);
            $salesrulesCollection->addFieldToFilter('sku_list', array('like' => "%,$product->getSku(),%"))
                ->addFieldToFilter('from_date', array('lteq' => $today))
                ->addFieldToFilter('to_date', array('gteq' => $today))
                ->addWebsiteFilter($websiteId)
                ->addIsActiveFilter();

            foreach ($salesrulesCollection as $salesRule) {
                $skus = explode(',', $salesRule->getSkuList());
                if (in_array($product->getSku(), $skus)) {
                    return $salesRule->getPromotionImage();
                }
            }
        }

        return false;
    }


    /**
     * Get Promotion Label for product
     * @param $product
     * @return bool
     */
    public function getPromotionLabel($product)
    {
        if ($product instanceof Mage_Catalog_Model_Product) {

            $salesrulesCollection = Mage::getModel('salesrule/rule')->getCollection();
            $time = time();
            $websiteId = Mage::app()->getWebsite()->getId();
            $today = date('Y-m-d', $time);
            $salesrulesCollection->addFieldToFilter('sku_list', array('like' => "%,$product->getSku(),%"))
                ->addFieldToFilter('from_date', array('lteq' => $today))
                ->addFieldToFilter('to_date', array('gteq' => $today))
                ->addWebsiteFilter($websiteId)
                ->addIsActiveFilter();

            foreach ($salesrulesCollection as $salesRule) {
                $skus = explode(',', $salesRule->getSkuList());
                if (in_array($product->getSku(), $skus)) {
                    return $salesRule->getPromotionImage();
                }
            }
        }
        return false;
    }
}
