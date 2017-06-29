<?php

class Triggmine_IntegrationModule_AjaxController extends Mage_Core_Controller_Front_Action
{
    public function exportAction()
    {
        $pageSize   = Mage::app()->getRequest()->getParam('pageSize');
        $page       = Mage::app()->getRequest()->getParam('page');
        $url        = Mage::app()->getRequest()->getParam('apiURL');
        $token      = Mage::app()->getRequest()->getParam('apiToken');
        $pagesTotal = Mage::app()->getRequest()->getParam('pagesTotal');
        $websiteId  = (int) Mage::app()->getRequest()->getParam('website');

        $data = Mage::helper('integrationmodule/data')->getProductHistory($pageSize, $page);
        Mage::helper('integrationmodule/data')->exportProductData($data, $url, $token);
        
        if ($page == $pagesTotal)
        {
            // export finished
            Mage::getConfig()->saveConfig('triggmine/settings/plugin_set_up', '1', 'websites', $websiteId);
        }
    }
}