<?php

class Triggmine_IntegrationModule_Model_Observer
{

    public function controllerFrontInitBefore(Varien_Event_Observer $observer)
    {
        self::init();
    }

    /**
     * Add in auto loader for IntegrationModule components
     */
    static private function init()
    {
        // Add our vendor folder to our include path
        set_include_path(get_include_path() . PATH_SEPARATOR . Mage::getBaseDir('lib') . DS . 'Triggmine' . DS . 'vendor');

        // Include the autoloader for composer
        require_once(Mage::getBaseDir('lib') . DS . 'Triggmine' . DS . 'vendor' . DS . 'autoload.php');
    }


    public function SalesOrderPlaceAfter(Varien_Event_Observer $observer)
    {
        try {
            $data = Mage::helper('integrationmodule/data')->getOrderData($observer);
            Mage::log(json_encode($data), null, "triggmine-order.log");
            $result = Mage::helper('integrationmodule/data')->onConvertCartToOrder($data);
            Mage::log($result->toArray(), null, "triggmine-order.log");
        } catch (Exception $e) {
            Mage::log($e, 1, "triggmine-exception-order.log");
        }

    }


    public function CheckoutCartSaveAfter(Varien_Event_Observer $observer)
    {

        try {
            $data = Mage::helper('integrationmodule/data')->getCartData();
            Mage::log(json_encode($data), null, "triggmine-cart.log");
            $result = Mage::helper('integrationmodule/data')->sendCart($data);
            Mage::log($result->toArray(), null, "triggmine-cart.log");
        } catch (Exception $e) {
            Mage::log($e, 1, "triggmine-exception-cart.log");
        }

    }


    public function CustomerRegisterSuccess(Varien_Event_Observer $observer)
    {
        $event = $observer->getEvent();
        $customer = $event->getCustomer();
        $email = $customer->getEmail();
        $customerId = $customer->getId();
        $customerFirstName = $customer->getFirstname();
        $customerLastName = $customer->getLastname();

        $data = array(
            'device_id'           => Mage::helper('integrationmodule/data')->getDeviceId(),
            'device_id_1'         => Mage::helper('integrationmodule/data')->getDeviceId_1(),
            'customer_id'         => $customerId,
            'customer_first_name' => $customerFirstName,
            'customer_last_name'  => $customerLastName,
            'customer_email'      => $email,
        );
        try {
            Mage::log($data, null, "triggmine-customer.log");
            Mage::helper('integrationmodule/data')->sendRegisterData($data);
        } catch (Exception $e) {
            Mage::log($e, 1, "triggmine-exception-customer.log");
        }

    }


    public function CustomerLogin(Varien_Event_Observer $observer)
    {
        try {
            $loginData = Mage::helper('integrationmodule/data')->getCustomerLoginData();
            Mage::log(json_encode($loginData), null, "triggmine-customer.log");
            Mage::helper('integrationmodule/data')->sendLoginData($loginData);
        } catch (Exception $e) {
            Mage::log($e, 1, "triggmine-exception-customer.log");
        }

    }


    public function CustomerLogout(Varien_Event_Observer $observer)
    {

        try {
            $loginData = Mage::helper('integrationmodule/data')->getCustomerLoginData();
            Mage::log(json_encode($loginData), null, "triggmine-customer.log");
            Mage::helper('integrationmodule/data')->sendLogoutData($loginData);
        } catch (Exception $e) {
            Mage::log($e, 1, "triggmine-exception-customer.log");
        }

    }

}
