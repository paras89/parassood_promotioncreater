<?php


/**
 * Promotioncreater observer model
 *
 * @category    Parassood
 * @package     Parassood_Promotioncreater
 * @author      Paras Sood
 */
class Parassood_Promotioncreater_Model_Observer
{

    /**
     * Hold all salesRules in memory.
     * @var array
     */
    protected $_salesRules = array();

    /**
     * Hold All conditional Skus in memory.
     * @var array
     */
    protected $_conditionSkus = array();

    /**
     * Array of websites.
     * @var null
     */
    protected $_websites = null;

    /**
     * Array Map for promo_rule to action.
     * @var array
     */
    protected $_salesruleAction = array('02P' => '',
        '03P' => Mage_SalesRule_Model_Rule::CART_FIXED_ACTION,
        '04P' => Mage_SalesRule_Model_Rule::BY_PERCENT_ACTION,
        '05P' => '',);


    protected $_salesruleType = array('03P','04P');


    const catalogrule = '06p';


    /**
     * Cron job to import Promotion Rules.
     */
    public function importPromotions()
    {

        $file = Mage::getBaseDir() . DS . 'var' . DS . 'uploads' . DS . 'promo-rules.csv';
        $csv = new Varien_File_Csv();
        $csvRows = $csv->getData($file);
        foreach ($csvRows as $key => $row) {
            if ($key == 0) {
                continue;
            }
            $websiteIds = $this->_getRuleWebsiteIds($row);
            if(count($websiteIds) < 1){
                continue;
            }
            if (in_array($row[4],$this->_salesruleType)) {
                $discountAmount = $row[9];
                $salesRule = Mage::getModel('salesrule/rule');
                $id = $salesRule->load($row[0],'promo_ref')->getId();
                if(isset($id)){
                    // This promo ref is already imported and is a duplicate. Log promo ref and continue.
                    Mage::log('Trying to import duplicate Promo Reference: ' . $row[0],null,'salesrule_import.log');
                    continue;
                }
                $salesRule->setName($row[1])
                    ->setFromDate($row[5])
                    ->setToDate($row[6])
                    ->setIsActive(1)
                    ->setWebsiteIds($websiteIds)
                    ->setCouponType(Mage_SalesRule_Model_Rule::COUPON_TYPE_NO_COUPON)
                    ->setCustomerGroupIds(array(0, 1, 2, 4, 5))
                    ->setSimpleAction($this->_salesruleAction[$row[4]])
                    ->setDiscountAmount($discountAmount)
                    ->setPromoQty($row[7])
                    ->setPromoRef($row[0])
                    ->setPromotionImage($row[2]);

                $this->_salesRules[$row[0]] = $salesRule;

            } else {

                continue;
                // For now ignore catalog rules.
                $catalogRule = Mage::getModel('catalogrule/rule');
                $catalogRule->setName($row[1])
                    ->setFromDate($row[4])
                    ->setToDate($row[5])
                    ->setIsActive(1)
                    ->setDiscountAmount($row[7])
                    ->setWebsiteIds($websiteIds);

                $this->_salesRules[$row[0]] = $catalogRule;
            }
        }

        $productFile = Mage::getBaseDir() . DS . 'var' . DS . 'uploads' . DS . 'promo-products.csv';
        $csv = new Varien_File_Csv();
        $csvRows = $csv->getData($productFile);
        foreach ($csvRows as $key => $row) {
            if ($key == 0) {
                continue;
            }
            if (!array_key_exists($row[3], $this->_salesRules)) {
                Mage::log('Trying to import Promo Reference: ' . $row[3] . ' not found.',null,'salesrule_import.log');
                continue;
            }

            $promotionRule = $this->_salesRules[$row[3]];
            $conditions = $this->_getSalesruleCondition($promotionRule, $row);
            $actions = $this->_getSalesruleAction($promotionRule,$row);
            $promotionRule->setData("conditions",$conditions);
            $promotionRule->setData("actions",$actions);
            $promotionRule->loadPost($promotionRule->getData());
        }
        $this->_savePromotionRules();
    }


    /*
     * Get website ids for a particular sales rule.
     */
    protected function _getRuleWebsiteIds($row)
    {
        $websites = array();
        if (!isset($this->_websites)) {
            $this->_websites = Mage::app()->getWebsites(true, true);
        }

        if(array_key_exists($row[3],$this->_websites)){
            $website = $this->_websites[$row[3]];
            $websites[] = $website->getId();
        }
        return $websites;
    }


    /**
     * Get conditions for $salesRule salesrule after adding the $row's sku to the condition.
     * @param $salesRule
     * @param $row
     * @return array
     */
    protected function _getSalesruleCondition($salesRule, $row)
    {
        if(!array_key_exists($salesRule->getPromoRef(),$this->_conditionSkus)){
            $this->_conditionSkus[$salesRule->getPromoRef()] =array();

        }
        $this->_conditionSkus[$salesRule->getPromoRef()][]  =  $row[2];
        //Only add unique SKUs to the condition array.
        $this->_conditionSkus[$salesRule->getPromoRef()] = array_unique($this->_conditionSkus[$salesRule->getPromoRef()]);
        $conditions = array(
            "1" => array(
                "type" => "salesrule/rule_condition_combine",
                "aggregator" => "all",
                "value" => "1",
                "new_child" => null
            ),
            "1--1" => array(
                "type" => "salesrule/rule_condition_product_subselect",
                "attribute" => "qty",
                "operator" => ">=",
                "value" => $salesRule->getPromoQty(),
                "aggregator" => "all",
                "new_child" => null

            ),
            "1--1--1" => array(
                "type" => "salesrule/rule_condition_product",
                "attribute" => "sku",
                "operator" => "()",
                "value" => implode(',',$this->_conditionSkus[$salesRule->getPromoRef()])
            ));

        $salesRule->setSkuList(','.implode(',',$this->_conditionSkus[$salesRule->getPromoRef()]) . ',');

        return $conditions;
    }


    /**
     * Get actions for $salesRule salesrule after adding the $row's sku to the condition.
     * @param $salesRule
     * @param $row
     * @return array
     */
    protected function _getSalesruleAction($salesRule,$row)
    {
        if(!array_key_exists($salesRule->getPromoRef(),$this->_conditionSkus)){
            $this->_conditionSkus[$salesRule->getPromoRef()] =array();

        }
        $this->_conditionSkus[$salesRule->getPromoRef()][]  =  $row[2];
        //Only add unique SKUs to the array.
        $this->_conditionSkus[$salesRule->getPromoRef()] = array_unique($this->_conditionSkus[$salesRule->getPromoRef()]);
        $actions = array(
            "1"         => array(
                "type"          => "salesrule/rule_condition_product_combine",
                "aggregator"    => "all",
                "value"         => "1",
                "new_child"     => false
            ),
            "1--1"      => array(
                "type"          => "salesrule/rule_condition_product",
                "attribute"     => "sku",
                "operator"      => "()",
                "value"         => implode(',',$this->_conditionSkus[$salesRule->getPromoRef()])
            ));


        return $actions;
    }


    /**
     * Save All promotion rules.
     */
    protected function _savePromotionRules()
    {
        foreach($this->_salesRules as $rule){
            $rule->save();
        }
    }


}