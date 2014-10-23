<?php

class Parassood_Promotioncreater_Helper_Data extends Mage_Core_Helper_Abstract
{

    protected $_salesRules = array();
    /**
     *
     * Get Promotion Image for product.
     * @param $product
     * @return bool
     */
    public function getPromotionImage($product)
    {
        if ($product instanceof Mage_Catalog_Model_Product) {

            if(array_key_exists($product->getSku(),$this->_salesRules)){
                return $this->_salesRules[$product->getSku()]->getPromotionImage();
            }

            $salesrulesCollection = Mage::getModel('salesrule/rule')->getCollection();
            $time = time();
            $websiteId = Mage::app()->getWebsite()->getId();
            $today = date('Y-m-d', $time);
            $productSku = $product->getSku();
            $salesrulesCollection->addFieldToFilter('sku_list', array('like' => "%,$productSku,%"))
                ->addFieldToFilter('from_date', array('lteq' => $today))
                ->addFieldToFilter('to_date', array('gteq' => $today))
                ->addWebsiteFilter($websiteId)
                ->addIsActiveFilter();

            $salesRule = $salesrulesCollection->getFirstItem();
            $skus = explode(',', $salesRule->getSkuList());
            if (in_array($product->getSku(), $skus)) {
                foreach($skus as $promoSku){
                    // Add All Skus of promo rule to in memory cache.
                     $this->_salesRules[$promoSku] = $salesRule;
                }
                return $salesRule->getPromotionImage();
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

            if(array_key_exists($product->getSku(),$this->_salesRules)){
                return $this->_salesRules[$product->getSku()]->getName();
            }

            $salesrulesCollection = Mage::getModel('salesrule/rule')->getCollection();
            $time = time();
            $websiteId = Mage::app()->getWebsite()->getId();
            $today = date('Y-m-d', $time);
            $productSku = $product->getSku();
            $salesrulesCollection->addFieldToFilter('sku_list', array('like' => "%,$productSku,%"))
                ->addFieldToFilter('from_date', array('lteq' => $today))
                ->addFieldToFilter('to_date', array('gteq' => $today))
                ->addWebsiteFilter($websiteId)
                ->addIsActiveFilter();

            $salesRule = $salesrulesCollection->getFirstItem();
            $skus = explode(',', $salesRule->getSkuList());
            if (in_array($product->getSku(), $skus)) {
                foreach($skus as $promoSku){
                    // Add All Skus of promo rule to in memory cache.
                    $this->_salesRules[$promoSku] = $salesRule;
                }
                return $salesRule->getName();
            }
        }
        return false;
    }
}
