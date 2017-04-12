<?php

class Triggmine_IntegrationModule_Helper_Data extends Mage_Core_Helper_Abstract
{
    const XML_PATH_ENABLED               = 'triggmine/settings/is_on';
    const XML_PATH_URL_API               = 'triggmine/settings/url_api';
    const XML_PATH_TOKEN                 = 'triggmine/settings/token';
    const XML_PATH_KEY                   = 'triggmine/settings/key';
    const XML_PATH_SECRET                = 'triggmine/settings/secret';
    const XML_PATH_ORDER_EXPORT          = 'triggmine/triggmine_order_export/export';
    const XML_PATH_ORDER_DATE_FROM       = 'triggmine/triggmine_order_export/my_date_from';
    const XML_PATH_ORDER_DATE_TO         = 'triggmine/triggmine_order_export/my_date_to';
    const XML_PATH_CUSTOMER_EXPORT       = 'triggmine/triggmine_customer_export/export';
    const XML_PATH_CUSTOMER_DATE_FROM    = 'triggmine/triggmine_customer_export/my_date_from';
    const XML_PATH_CUSTOMER_DATE_TO      = 'triggmine/triggmine_customer_export/my_date_to';
    const VERSION_PLUGIN                 = '3.0.14.1';

    protected $_cartItemRepository;
    protected $_customerRepository;
    protected $_customerSession;
    protected $_cookieManager;
    protected $_websiteCode;
    protected $_websiteId;
    protected $_storeId;
    protected $_url;
    protected $_token;
    protected $_pluginOn;
    protected $_enableOrderExport;
    protected $_exportOrderFromDate;
    protected $_exportOrderToDate;
    protected $_enableCustomerExport;
    protected $_exportCustomerFromDate;
    protected $_exportCustomerToDate;

    public function __construct()
    {
        $this->_cookieManager       = Mage::getModel('core/cookie');
        $this->_customerSession     = Mage::getSingleton('customer/session');
        $this->_customerRepository  = Mage::getModel("customer/customer");
        
        $this->_websiteCode         = Mage::getSingleton('adminhtml/config_data')->getWebsite();
        $this->_websiteId           = Mage::getModel('core/website')->load($this->_websiteCode)->getId();
        $this->_storeId             = Mage::app()->getWebsite($this->_websiteId)->getDefaultStore()->getId();
        
        $this->_url                    = Mage::app()->getWebsite($this->_websiteId)->getConfig(self::XML_PATH_URL_API);
        $this->_token                  = Mage::app()->getWebsite($this->_websiteId)->getConfig(self::XML_PATH_TOKEN);
        $this->_pluginOn               = Mage::app()->getWebsite($this->_websiteId)->getConfig(self::XML_PATH_ENABLED);
        $this->_enableOrderExport      = Mage::app()->getWebsite($this->_websiteId)->getConfig(self::XML_PATH_ORDER_EXPORT);
        $this->_exportOrderFromDate    = Mage::app()->getWebsite($this->_websiteId)->getConfig(self::XML_PATH_ORDER_DATE_FROM);
        $this->_exportOrderToDate      = Mage::app()->getWebsite($this->_websiteId)->getConfig(self::XML_PATH_ORDER_DATE_TO);
        $this->_enableCustomerExport   = Mage::app()->getWebsite($this->_websiteId)->getConfig(self::XML_PATH_CUSTOMER_EXPORT);
        $this->_exportCustomerFromDate = Mage::app()->getWebsite($this->_websiteId)->getConfig(self::XML_PATH_CUSTOMER_DATE_FROM);
        $this->_exportCustomerToDate   = Mage::app()->getWebsite($this->_websiteId)->getConfig(self::XML_PATH_CUSTOMER_DATE_TO);
    }
    
    public function apiClient($data, $method)
    {
        
        if ($this->_url == "")
        {
            $res = array(
                "status"    => 0,
                "body"      => ""
            );
        }
        else
        {
            $target = "https://" . $this->_url . "/" . $method;
    
            $data_string = json_encode($data);
            
            $ch = curl_init();
    
            curl_setopt($ch, CURLOPT_URL, $target);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);           
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(                  
                'Content-Type: application/json',
                'ApiKey: ' . $this->_token,
                'Content-Length: ' . strlen($data_string))
            );
            
            $res_json = curl_exec ($ch);
            
            $res = array(
                "status"    => curl_getinfo ($ch, CURLINFO_HTTP_CODE),
                "body"      => $res_json ? json_decode ($res_json, true) : curl_error ($ch)
            );
            
            curl_close ($ch);
        }
        
        return $res;
    }
    
    public function isEnabled()
    {
        return ($this->_pluginOn && !empty($this->_token)) ? true : false;
    }
    
    public function exportOrderEnabled()
    {
        return ($this->_enableOrderExport) ? true : false;
    }
    
    public function exportCustomerEnabled()
    {
        return ($this->_enableCustomerExport) ? true : false;
    }

    public function getDeviceId()
    {
        return $this->_cookieManager->get('device_id');
    }

    public function getDeviceId_1()
    {
        return $this->_cookieManager->get('device_id_1');
    }

    public function normalizeName($name)
    {
        return trim(preg_replace('/\s+/', ' ', $name));
    }
    
    public function getProdImg($product)
    {
        $url = "";
        if ($image = $product->getImage())
        {
            $http = (isset($_SERVER['HTTPS']) || isset($_SERVER['HTTPS']) && isset($_SERVER['HTTPS']) == "on" || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
            $url = $http . $_SERVER['SERVER_NAME'] . '/media/catalog/product' . $image;
        }
        return $url;
    }
    
    public function getProdUrl($product)
    {
        $url = "";
        if ($path = $product->getUrlPath())
        {
            $http = (isset($_SERVER['HTTPS']) || isset($_SERVER['HTTPS']) && isset($_SERVER['HTTPS']) == "on" || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
            $url = $http . $_SERVER['SERVER_NAME'] . '/index.php/' . $path;
        }
        return $url;
    }

    public function getCartData()
    {   
        $cart           = Mage::getSingleton('checkout/session');
        $customer       = Mage::getSingleton('customer/session');
        $products       = $cart->getQuote()->getItemsCollection();
        $customerId     = $customer->getCustomer()->getId();
        $customerData   = Mage::getModel('customer/customer')->load($customerId);
        $dateCreated    = $customerId ? date('Y/m/d h:m:s', $customerData->getCreatedAtTimestamp()) : null;
                
        $customer = array(
            'device_id'             => $this->getDeviceId(),
            'device_id_1'           => $this->getDeviceId_1(),
            'customer_id'           => $customerId,
            'customer_first_name'   => $customerData->getFirstname(),
            'customer_last_name'    => $customerData->getLastname(),
            'customer_email'        => $customerData->getEmail(),
            'customer_date_created' => $dateCreated
        );
        
        $data = array(
            'customer'    => $customer,
            'order_id'    => $cart->getQuoteId(), // cart id
            'price_total' => sprintf('%01.2f', $cart->getQuote()->getGrandTotal()),
            'qty_total'   => Mage::helper('checkout/cart')->getItemsCount(),
            'products'    => array()
        );
        
        foreach ($products as $product)
        {   
            // to prevent duplicate entries for configurable product - consider only child simple products
            if($product->getProductType() == "simple")
            {
                $catalogProduct     = $product->getProduct();
                $productId          = $catalogProduct->getId();
                $productName        = $catalogProduct->getName();
                $productPull        = Mage::getModel('catalog/product')->load($productId);
                $productDesc        = $productPull->getDescription();
                
                if ($product->getParentItem())
                {
                    $productPrice       = $product->getParentItem()->getPrice();
                    $productTotalVal    = $product->getParentItem()->getRowTotal();
                    $parentIds          = Mage::getModel('catalog/product_type_configurable')->getParentIdsByChild($productId);
                    $parentItem         = Mage::getModel('catalog/product')->load($parentIds[0]);
                    $productImage       = $this->getProdImg($parentItem);
                    $productUrl         = $this->getProdUrl($parentItem);
                    $categories         = $parentItem->getCategoryIds();
                    $productQty         = $product->getParentItem()->getQty();
                }
                else
                {
                    $productPrice       = $catalogProduct->getFinalPrice($product->getQty());
                    $productTotalVal    = $product->getRowTotal();
                    $categories         = Mage::getModel('catalog/product')->load($productId)->getCategoryIds();
                    $productImage       = $this->getProdImg($productPull);
                    $productUrl         = $this->getProdUrl($catalogProduct);
                    $productQty         = $product->getQty();
                }
                
                $itemData = array();
                $itemData['product_id']         = (string)$productId;
                $itemData['product_name']       = $this->normalizeName($productName);
                $itemData['product_desc']       = $productDesc;
                $itemData['product_sku']        = $product->GetData('sku');
                $itemData['product_image']      = $productImage;
                $itemData['product_url']        = $productUrl;
                $itemData['product_qty']        = $productQty;
                $itemData['product_price']      = intval($productPrice); 
                $itemData['product_total_val']  = intval($productTotalVal);
    
                $itemData['product_categories'] = array();
                
                foreach ($categories as $categoryId)
                {
                    $category = Mage::getModel('catalog/category')->load($categoryId);
                    $itemData['product_categories'][] = $category->getName();
                }
                
                $data['products'][] = $itemData;
            }
            else
            {
                continue;
            }
        }

        return $data;
    }

    public function sendCart($data)
    {
        return $this->apiClient($data, 'api/events/cart');
    }

    public function onConvertCartToOrder($data)
    {
        return $this->apiClient($data, 'api/events/order');
    }

    public function getOrderData($observer)
    {
        $orderId        = $observer->getEvent()->getOrder();
        $id             = $orderId->getId();
        $idInc          = $orderId->getIncrementId();
        $collection     = Mage::getModel('sales/order')->getCollection()->addAttributeToFilter('increment_id', $idInc);
        $order          = $collection->getFirstItem();
        $customerId     = $order->getCustomerId();
        $customerData   = Mage::getModel('customer/customer')->load($customerId);
        $dateCreated    = $customerId ? date('Y/m/d h:m:s', $customerData->getCreatedAtTimestamp()) : null;
                
        $customer = array(
            'device_id'             => $this->getDeviceId(),
            'device_id_1'           => $this->getDeviceId_1(),
            'customer_id'           => $customerId,
            'customer_first_name'   => $order->getBillingAddress()->getFirstname(),
            'customer_last_name'    => $order->getBillingAddress()->getLastname(),
            'customer_email'        => $order->getCustomerEmail(),
            'customer_date_created' => $dateCreated
        );
        
        $data = array(
            'customer'    => $customer,
            'order_id'    => $order->getId(),
            'status'      => $order->getStatus() ? $order->getStatus() : 'pending',
            'price_total' => number_format ($order->getGrandTotal(), 2, '.' , $thousands_sep = ''),
            'qty_total'   => intval($order->getTotalItemCount()),
            'products'    => array()
        );
        
        $orderItems = $order->getItemsCollection()
            ->addAttributeToSelect('*')
            ->addAttributeToFilter('product_type', array('eq'=>'simple'))
            ->load();

        foreach($orderItems as $item)
        {
            // to prevent duplicate entries for configurable product - consider only child simple products
            if($item->getProductType() == "simple")
            {
            
                $catalogProduct         = $item->getProduct();
                $productId              = $catalogProduct->getId();
                $productName            = $catalogProduct->getName();
                $productQty             = $item->getQtyOrdered();
                
                if ($item->getParentItem())
                {
                    $productPrice       = $item->getParentItem()->getPrice();
                    $productTotalVal    = $item->getParentItem()->getRowTotal();
                    $parentIds          = Mage::getModel('catalog/product_type_configurable')->getParentIdsByChild($productId);
                    $parentItem         = Mage::getModel('catalog/product')->load($parentIds[0]);
                    $productImage       = $this->getProdImg($parentItem);
                    $productUrl         = $this->getProdUrl($parentItem);
                    $categories         = $parentItem->getCategoryIds();
                }
                else
                {
                    $productPrice       = $catalogProduct->getFinalPrice($item->getQtyOrdered());
                    $productTotalVal    = $item->getRowTotal();
                    $productImage       = $this->getProdImg($catalogProduct);
                    $productUrl         = $this->getProdUrl($catalogProduct);
                    $categories         = Mage::getModel('catalog/product')->load($productId)->getCategoryIds();
                }
                
                
                $itemData = array();
                $itemData['product_id']         = (string)$productId;
                $itemData['product_name']       = $this->normalizeName($productName);
                $itemData['product_desc']       = $catalogProduct->getDescription();
                $itemData['product_sku']        = $item->GetData('sku');
                $itemData['product_image']      = $productImage;
                $itemData['product_url']        = $productUrl;
                $itemData['product_qty']        = round($productQty);
                $itemData['product_price']      = intval($productPrice);
                $itemData['product_total_val']  = intval($productTotalVal);
                $itemData['product_categories'] = array();
                
                foreach ($categories as $categoryId)
                {
                    $category = Mage::getModel('catalog/category')->load($categoryId);
                    $itemData['product_categories'][] = $category->getName();
                }
                
                $data['products'][] = $itemData;
            }
            else
            {
                continue;
            }
        }
        return $data;
    }
    
    public function getCustomerLoginData($customer = null)
    {
        if (is_null($customer))
        {
            $customer = $this->getCustomer();
        }

        $data = array(
            'device_id'             => $this->getDeviceId(),
            'device_id_1'           => $this->getDeviceId_1(),
            'customer_id'           => $customer->getId(),
            'customer_first_name'   => $customer->getFirstname(),
            'customer_last_name'    => $customer->getLastname(),
            'customer_email'        => $customer->getEmail(),
            'customer_date_created' => date('Y/m/d h:m:s', $customer->getCreatedAtTimestamp())
        );
        
        return $data;
    }
    
    public function getCustomerRegisterData($observer)
    {
        $customer = $observer->getCustomer();

        $data = array(
            'device_id'             => $this->getDeviceId(),
            'device_id_1'           => $this->getDeviceId_1(),
            'customer_id'           => $customer->getId(),
            'customer_first_name'   => $customer->getFirstname(),
            'customer_last_name'    => $customer->getLastname(),
            'customer_email'        => $customer->getEmail(),
            'customer_date_created' => date('Y/m/d h:m:s', $customer->getCreatedAtTimestamp())
        );
        
        return $data;
    }

    public function getCustomer()
    {
        return $this->_customerRepository->load($this->_customerSession->getCustomerId());
    }

    public function sendLoginData($data)
    {
        return $this->apiClient($data, 'api/events/prospect/login');
    }

    public function sendLogoutData($data)
    {
        return $this->apiClient($data, 'api/events/prospect/logout');
    }

    public function sendRegisterData($data)
    {
        return $this->apiClient($data, 'api/events/prospect/registration');
    }

    public function SoftChek($observer)
    {
        $versionMage    = Mage::getVersion();
        $versionPlugin  = self::VERSION_PLUGIN;
        $datetime       = Mage::getModel('core/date')->date('Y-m-d\TH:i:s');
        $status         = ($this->_pluginOn && !empty($this->_token)) ? "1" : "0";
        
        $data = array(
            'dateCreated'       => $datetime,
            'diagnosticType'    => "InstallPlugin",
            'description'       => "Magento " . $versionMage . " Plugin " . $versionPlugin,
            'status'            => $status
        );

        return $data;
    }
    
    public function onDiagnosticInformationUpdated($data)
    {
        return $this->apiClient($data, 'control/api/plugin/onDiagnosticInformationUpdated');
    }
    
    public function PageInit($observer)
    {
        $http       = Mage::helper('core/http');
        $url        = Mage::helper('core/url');
        $customer   = Mage::getSingleton('customer/session');
        $admin      = Mage::getSingleton('admin/session');
        
        $customerId = $customer->getCustomer()->getId();
        $isAdmin    = $admin->isLoggedIn();
        
        $product    = array();

        if (Mage::registry('current_product')) {
            
            $id         = Mage::registry('current_product')->getId();
            $item       = Mage::getModel('catalog/product')->load($id);
            $categories = $item->getCategoryIds();
            
            $product = array (
                "product_id"            => $id,
                "product_name"          => $item->getName(),
                "product_desc"          => $item->getDescription(),
                "product_sku"           => $item->GetData('sku'),
                "product_image"         => $this->getProdImg($item),
                "product_url"           => $this->getProdUrl($item),
                "product_qty"           => 1,
                "product_price"         => $item->getFinalPrice(),
                "product_total_val"     => $item->getPrice(),
                "product_categories"    => array()
            );
            
            foreach ($categories as $categoryId) {
                
                $category = Mage::getModel('catalog/category')->load($categoryId);
                $product['product_categories'][] = $category->getName();
            }
        }
        
        if ($this->getDeviceId() && $this->getDeviceId_1() && !$isAdmin) {
            
            $customerData   = Mage::getModel('customer/customer')->load($customerId);
            $firstName      = $customerData->getFirstname();
            $lastName       = $customerData->getLastname();
            $email          = $customerData->getEmail();
            $dateCreated    = $customerId ? date('Y/m/d h:m:s', $customerData->getCreatedAtTimestamp()) : null;
            
            $customer = array(
                "device_id"             => $this->getDeviceId(),
                "device_id_1"           => $this->getDeviceId_1(),
                "customer_id"           => $customerId,
                "customer_first_name"   => $firstName,
                "customer_last_name"    => $lastName,
                "customer_email"        => $email,
                "customer_date_created" => $dateCreated
            );
            
            $products  = array($product);
            
            $data = array(
              "user_agent"      => $http->getHttpUserAgent(),
              "customer"        => $customer,
              "products"        => $products
            );
        }
        else {
            
            $data = false;
        }   

        return $data;
    }
    
    public function onPageInit($data)
    {
        return $this->apiClient($data, 'api/events/navigation');
    }
    
    public function getOrderHistory($observer)
    {
        $dataExport = false;
        
        /* Format our dates */
        $fromDate   = date('Y-m-d H:i:s', strtotime($this->_exportOrderFromDate));
        $toDate     = date('Y-m-d H:i:s', strtotime($this->_exportOrderToDate));
        
        $dataExport = array();
        
        /* Get the collection */
        $orders = Mage::getModel('sales/order')->getCollection()
            ->addFieldToFilter('store_id', $this->_storeId)
            ->addAttributeToFilter('created_at', array('from'=>$fromDate, 'to'=>$toDate));
                
        foreach ($orders as $order) {
            
            $customerId     = $order->getCustomerId();
            $customerData   = Mage::getModel('customer/customer')->load($customerId);
            $dateCreated    = $customerId ? date('Y/m/d h:m:s', $customerData->getCreatedAtTimestamp()) : null;
            
            $customer = array(
                'customer_id'           => $customerId,
                'customer_first_name'   => $order->getBillingAddress()->getFirstname(),
                'customer_last_name'    => $order->getBillingAddress()->getLastname(),
                'customer_email'        => $order->getCustomerEmail(),
                'customer_date_created' => $dateCreated
            );
            
            $ordersExport = array(
                'customer'      => $customer,
                'order_id'      => $order->getId(),
                'date_created'  => $order->getCreatedAtStoreDate()->toString(Varien_Date::DATETIME_INTERNAL_FORMAT),
                'status'        => $order->getStatus() ? $order->getStatus() : 'pending',
                'price_total'   => number_format ($order->getGrandTotal(), 2, '.' , $thousands_sep = ''),
                'qty_total'     => intval($order->getTotalItemCount()),
                'products'      => array()
            );
            
            $orderItems = $order->getItemsCollection()
                ->addAttributeToSelect('*')
                ->addFieldToFilter('store_id', $this->_storeId)
                ->addAttributeToFilter('product_type', array('eq'=>'simple'))
                ->load();
                
            foreach($orderItems as $item) {
                
                // to prevent duplicate entries for configurable product - consider only child simple products
                if($item->getProductType() == "simple")
                {
                
                    $catalogProduct     = $item->getProduct();
                    $productId          = $catalogProduct->getId();
                    $productName        = $catalogProduct->getName();
                    $productQty         = $item->getQtyOrdered();
                    
                    if ($item->getParentItem())
                    {
                        $productPrice       = $item->getParentItem()->getPrice();
                        $productTotalVal    = $item->getParentItem()->getRowTotal();
                        $parentIds          = Mage::getModel('catalog/product_type_configurable')->getParentIdsByChild($productId);
                        $parentItem         = Mage::getModel('catalog/product')->load($parentIds[0]);
                        $productImage       = $this->getProdImg($parentItem);
                        $productUrl         = $this->getProdUrl($parentItem);
                        $categories         = $parentItem->getCategoryIds();
                    }
                    else
                    {
                        $productPrice       = $catalogProduct->getFinalPrice($item->getQtyOrdered());
                        $productTotalVal    = $item->getRowTotal();
                        $productImage       = $this->getProdImg($catalogProduct);
                        $productUrl         = $this->getProdUrl($catalogProduct);
                        $categories         = Mage::getModel('catalog/product')->load($productId)->getCategoryIds();
                    }
                    
                    
                    $itemData = array();
                    $itemData['product_id'] = $productId;
                    $itemData['product_name'] = $productName; //$this->normalizeName($productName);
                    $itemData['product_desc'] = $catalogProduct->getDescription();
                    $itemData['product_sku'] = $item->GetData('sku');
                    $itemData['product_image'] = $productImage;
                    $itemData['product_url'] = $productUrl;
                    $itemData['product_qty'] = round($productQty);
                    $itemData['product_price'] = intval($productPrice);
                    $itemData['product_total_val'] = intval($productTotalVal);
        
                    $itemData['product_categories'] = array();
                    
                    foreach ($categories as $categoryId) {
                        
                        $category = Mage::getModel('catalog/category')->load($categoryId);
                        $itemData['product_categories'][] = $category->getName();
                    }
                    
                    $ordersExport['products'][] = $itemData;
                
                }
                else
                {
                    continue;
                }
            }
            
            $dataExport['orders'][] = $ordersExport;
        }
        
        return $dataExport;
    }
    
    public function exportOrderHistory($data)
    {
        return $this->apiClient($data, 'api/events/history');
    }
    
    public function getCustomerHistory($observer)
    {
        /* Format our dates */
        $fromDate   = date('Y-m-d H:i:s', strtotime($this->_exportCustomerFromDate));
        $toDate     = date('Y-m-d H:i:s', strtotime($this->_exportCustomerToDate));
        
        $dataExport = array();
        
        /* Get the collection */
        $customers = Mage::getModel('customer/customer')->getCollection()
            ->addFieldToFilter('store_id', $this->_storeId)
            ->addAttributeToFilter('created_at', array('from'=>$fromDate, 'to'=>$toDate));
        
        foreach ($customers as $customerItem)
        {
            $customer       = $customerItem->getData();
            $customerId     = $customer['entity_id'];
            $customerInfo   = Mage::getModel('customer/customer')->load($customerId);
            
            $customerData = array(
                'customer_id'              => $customerId,
                'customer_first_name'      => $customerInfo->getFirstname(),
                'customer_last_name'       => $customerInfo->getLastname(),
                'customer_email'           => $customer['email'],
                'customer_date_created'    => $customer['created_at'],
                'customer_last_login_date' => $customer['updated_at']
              );
            
            $dataExport['prospects'][] = $customerData;
        }
            
        return $dataExport;
    }
    
    public function exportCustomerHistory($data)
    {
        return $this->apiClient($data, 'api/events/history/prospects');
    }
}