<?php

class Triggmine_IntegrationModule_Block_Adminhtml_System_Config_Info extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $version    = (string) Mage::getConfig()->getNode('modules/Triggmine_IntegrationModule/version');
        $website    = (string) Mage::getSingleton('adminhtml/config_data')->getWebsite();
        $siteName   = (string) Mage::getModel('core/website')->load($website)->getName();
        
        $html = "";
        
        $html .= "<div style=\"padding: 1em; margin: 1em 0; border: 0 solid #eee; border-left-width: 5px; border-radius: 0; background-color: #fafafa; border-left-color: #eb5e00; color: #eb5e00;\">";
        $html .= "<h4 style=\"font-size: 18px; line-height: 18px; margin: 0 0 4px; color: #6f8992;\">TriggMine v" . $version . " for " . $siteName . "</h4>";
        $html .= "<p style=\"margin: 0;\">Visit the <b><a href=\"https://triggmine.freshdesk.com/solution/articles/22000037925-setting-the-module-parameters\" target=\"_blank\">Magento getting started guide</a></b> for instructions on configuring TriggMine. Or contact <a href=\"mailto:support@triggmine.com\" target=\"_blank\">support@triggmine.com</a></p>";
        $html .= "</div>";

        return $html;
    }
}