<?php

class Triggmine_IntegrationModule_Model_Observer
{
    public function send_page_init(Varien_Event_Observer $observer)
    {   
        if (Mage::helper('integrationmodule/data')->isEnabled())
        {
            $data = Mage::helper('integrationmodule/data')->PageInit($observer);
            if ($data) {
                Mage::helper('integrationmodule/data')->onPageInit($data);
            }
        }
    }    
    
    public function diagnostic_information_updated(Varien_Event_Observer $observer)
    {   
        $data = Mage::helper('integrationmodule/data')->SoftChek($observer);
        $res = Mage::helper('integrationmodule/data')->onDiagnosticInformationUpdated($data);
        
        if ($res["status"] === 503)
        {   
            Mage::throwException(Mage::helper('adminhtml')->__('Invalid API URL'));
        }
        else if ($res["status"] === 401)
        {   
            Mage::throwException(Mage::helper('adminhtml')->__('Invalid API KEY'));
        }
    }
    
    public function export_order_history(Varien_Event_Observer $observer)
    {
        if (Mage::helper('integrationmodule/data')->isEnabled() &&
            Mage::helper('integrationmodule/data')->exportOrderEnabled())
        {
            $data = Mage::helper('integrationmodule/data')->getOrderHistory($observer);
            Mage::helper('integrationmodule/data')->exportOrderHistory($data);
        }
    }
    
    public function export_customer_history(Varien_Event_Observer $observer)
    {
        if (Mage::helper('integrationmodule/data')->isEnabled() &&
            Mage::helper('integrationmodule/data')->exportCustomerEnabled())
        {
            $data = Mage::helper('integrationmodule/data')->getCustomerHistory($observer);
            Mage::helper('integrationmodule/data')->exportCustomerHistory($data);
        }
    }

    public function SalesOrderPlaceAfter(Varien_Event_Observer $observer)
    {
        if (Mage::helper('integrationmodule/data')->isEnabled())
        {
            $data = Mage::helper('integrationmodule/data')->getOrderData($observer);
            Mage::helper('integrationmodule/data')->onConvertCartToOrder($data);
        }
    }

    public function CheckoutCartSaveAfter(Varien_Event_Observer $observer)
    {
        if (Mage::helper('integrationmodule/data')->isEnabled())
        {
            $data = Mage::helper('integrationmodule/data')->getCartData();
            Mage::helper('integrationmodule/data')->sendCart($data);
        }
    }

    public function CustomerRegisterSuccess(Varien_Event_Observer $observer)
    {
        if (Mage::helper('integrationmodule/data')->isEnabled())
        {
            $event = $observer->getEvent();
            
            $data = Mage::helper('integrationmodule/data')->getCustomerRegisterData($event);
            Mage::helper('integrationmodule/data')->sendRegisterData($data);
        }
    }

    public function CustomerLogin(Varien_Event_Observer $observer)
    {
        if (Mage::helper('integrationmodule/data')->isEnabled())
        {
            $data = Mage::helper('integrationmodule/data')->getCustomerLoginData();
            Mage::helper('integrationmodule/data')->sendLoginData($data);
        }
    }

    public function CustomerLogout(Varien_Event_Observer $observer)
    {
        if (Mage::helper('integrationmodule/data')->isEnabled())
        {
            $data = Mage::helper('integrationmodule/data')->getCustomerLoginData();
            Mage::helper('integrationmodule/data')->sendLogoutData($data);
        }
    }
    
    public function CatalogProductSaveAfter(Varien_Event_Observer $observer)
    {
        if (Mage::helper('integrationmodule/data')->isEnabledByProduct($observer))
        {
            $websites = Mage::helper('integrationmodule/data')->isEnabledByProduct($observer);
            foreach ($websites as $websiteId)
            {
                $url   = Mage::app()->getWebsite($websiteId)->getConfig('triggmine/settings/url_api');
                $token = Mage::app()->getWebsite($websiteId)->getConfig('triggmine/settings/token');

                $data = Mage::helper('integrationmodule/data')->getProductEditData($observer);
                Mage::helper('integrationmodule/data')->exportProductData($data, $url, $token);
            }
        }
    }
}