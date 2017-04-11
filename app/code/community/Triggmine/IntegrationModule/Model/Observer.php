<?php

class Triggmine_IntegrationModule_Model_Observer
{
    public function send_page_init(Varien_Event_Observer $observer)
    {   
        if (Mage::helper('integrationmodule/data')->isEnabled())
        {
            $data = Mage::helper('integrationmodule/data')->PageInit($observer);
            $res = Mage::helper('integrationmodule/data')->onPageInit($data);
            Mage::log(json_encode($data), null, 'log1.log');
            Mage::log(json_encode($res), null, 'log1.log');
        }
    }    
    
    public function diagnostic_information_updated(Varien_Event_Observer $observer)
    {   
        $data = Mage::helper('integrationmodule/data')->SoftChek($observer);
        $res = Mage::helper('integrationmodule/data')->onDiagnosticInformationUpdated($data);
        Mage::log(json_encode($data), null, 'log2.log');
        
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
            Mage::log(json_encode($data), null, 'log3.log');
        }
    }
    
    public function export_customer_history(Varien_Event_Observer $observer)
    {
        if (Mage::helper('integrationmodule/data')->isEnabled() &&
            Mage::helper('integrationmodule/data')->exportCustomerEnabled())
        {
            $data = Mage::helper('integrationmodule/data')->getCustomerHistory($observer);
            Mage::log(json_encode($data), null, 'export.log');
            //Mage::helper('integrationmodule/data')->exportCustomerHistory($data);
        }
    }

    public function SalesOrderPlaceAfter(Varien_Event_Observer $observer)
    {
        if (Mage::helper('integrationmodule/data')->isEnabled())
        {
            $data = Mage::helper('integrationmodule/data')->getOrderData($observer);
            Mage::helper('integrationmodule/data')->onConvertCartToOrder($data);
            Mage::log(json_encode($data), null, 'log4.log');
        }
    }

    public function CheckoutCartSaveAfter(Varien_Event_Observer $observer)
    {
        if (Mage::helper('integrationmodule/data')->isEnabled())
        {
            $data = Mage::helper('integrationmodule/data')->getCartData();
            Mage::helper('integrationmodule/data')->sendCart($data);
            Mage::log(json_encode($data), null, 'log5.log');
        }
    }

    public function CustomerRegisterSuccess(Varien_Event_Observer $observer)
    {
        if (Mage::helper('integrationmodule/data')->isEnabled())
        {
            $event = $observer->getEvent();
            
            $data = Mage::helper('integrationmodule/data')->getCustomerRegisterData($event);
            Mage::helper('integrationmodule/data')->sendRegisterData($data);
            Mage::log(json_encode($data), null, 'log6.log');
        }
    }

    public function CustomerLogin(Varien_Event_Observer $observer)
    {
        if (Mage::helper('integrationmodule/data')->isEnabled())
        {
            $data = Mage::helper('integrationmodule/data')->getCustomerLoginData();
            Mage::helper('integrationmodule/data')->sendLoginData($data);
            Mage::log(json_encode($data), null, 'log7.log');
        }
    }

    public function CustomerLogout(Varien_Event_Observer $observer)
    {
        if (Mage::helper('integrationmodule/data')->isEnabled())
        {
            $data = Mage::helper('integrationmodule/data')->getCustomerLoginData();
            Mage::helper('integrationmodule/data')->sendLogoutData($data);
            Mage::log(json_encode($data), null, 'log8.log');
        }
    }
}