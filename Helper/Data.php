<?php


namespace Fandi\PaymentReminder\Helper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const PR_CONFIG_PATH = 'payment_reminder/default/';

    public function getConfig($configNode)
    {
        return $this->scopeConfig->getValue(
            self::PR_CONFIG_PATH . $configNode,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function isEnable(){
        return $this->getConfig('enable') == 1 ? true : false;
    }

    public function getDaysReminder(){
        return $this->getConfig('allowable_days_for_credit') ?: 0;
    }
}
