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
    const XML_PATH_PRODUCT_EXPORT        = 'triggmine/triggmine_product_export/export';
    const XML_PATH_PLUGIN_SET_UP         = 'triggmine/settings/plugin_set_up'; // 0 if it's the first time user installs the plugin, 1 if it's set up and product export is already performed
    const VERSION_PLUGIN                 = '3.0.23.1';

    protected $_cartItemRepository;
    protected $_customerRepository;
    protected $_triggmineDiagnosticURL;
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
    protected $_enableProductExport;
    protected $_pluginSetUp;

    public function __construct()
    {
        $this->_cookieManager       = Mage::getModel('core/cookie');
        $this->_customerSession     = Mage::getSingleton('customer/session');
        $this->_customerRepository  = Mage::getModel("customer/customer");
        
        $this->_triggmineDiagnosticURL = 'plugindiagnostic.triggmine.com';
        
        $this->_websiteCode         = Mage::getSingleton('adminhtml/config_data')->getWebsite();
        $this->_websiteId           = Mage::getModel('core/website')->load($this->_websiteCode)->getId();
        $this->_storeId             = Mage::app()->getWebsite($this->_websiteId)->getDefaultStore()->getId();
        
        $this->_url                    = Mage::app()->getWebsite($this->_websiteId)->getConfig(self::XML_PATH_URL_API);
        $this->_token                  = Mage::app()->getWebsite($this->_websiteId)->getConfig(self::XML_PATH_TOKEN);
        $this->_pluginOn               = Mage::app()->getWebsite($this->_websiteId)->getConfig(self::XML_PATH_ENABLED);
        $this->_enableOrderExport      = Mage::getStoreConfig(self::XML_PATH_ORDER_EXPORT, $this->_storeId);
        $this->_exportOrderFromDate    = Mage::getStoreConfig(self::XML_PATH_ORDER_DATE_FROM, $this->_storeId);
        $this->_exportOrderToDate      = Mage::getStoreConfig(self::XML_PATH_ORDER_DATE_TO, $this->_storeId);
        $this->_enableCustomerExport   = Mage::getStoreConfig(self::XML_PATH_CUSTOMER_EXPORT, $this->_storeId);
        $this->_exportCustomerFromDate = Mage::getStoreConfig(self::XML_PATH_CUSTOMER_DATE_FROM, $this->_storeId);
        $this->_exportCustomerToDate   = Mage::getStoreConfig(self::XML_PATH_CUSTOMER_DATE_TO, $this->_storeId);
        $this->_enableProductExport    = Mage::getStoreConfig(self::XML_PATH_PRODUCT_EXPORT, $this->_storeId);
        $this->_pluginSetUp            = Mage::app()->getWebsite($this->_websiteId)->getConfig(self::XML_PATH_PLUGIN_SET_UP);
    }
    
    public function apiClient($data, $method, $url = null, $token = null)
    {
        
        $url   = $url ? $url : $this->_url;
        $token = $token ? $token : $this->_token;

        if ($url == "")
        {
            $res = array(
                "status"    => 0,
                "body"      => ""
            );
        }
        else
        {
            $target = "https://" . $url . "/" . $method;
    
            $data_string = json_encode($data);
            
            $ch = curl_init();
    
            curl_setopt($ch, CURLOPT_URL, $target);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);           
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(                  
                'Content-Type: application/json',
                'ApiKey: ' . $token,
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
    
    public function isEnabledByProduct($observer)
    {
        // to check if pugin enabled in product add/edit page
        // if TriggMine is found, returns website ids where it's properly installed
        
        $res = false;

        if (Mage::helper('core')->isModuleEnabled('Triggmine_IntegrationModule'))
        {
            $product    = $observer->getEvent()->getProduct();
            $websiteIds = $product->getWebsiteIds();

            foreach ($websiteIds as $websiteId)
            {
                $pluginOn = Mage::app()->getWebsite($websiteId)->getConfig(self::XML_PATH_ENABLED);
                $token    = Mage::app()->getWebsite($websiteId)->getConfig(self::XML_PATH_URL_API);
                if ($pluginOn && !empty($token))
                {
                    $res[] = $websiteId;
                }
            }
        }
        else
        {
            $res = false;
        }

        return $res;
    }
    
    public function getProductGroupPrices($product, $priceType = 'group')
    {   
        // also can be used to get tier prices
        
        $resource = Mage::getSingleton('core/resource');
        $readConnection = $resource->getConnection('core_read');
        $query = 'SELECT * FROM ' . $resource->getTableName('catalog_product_entity_' . $priceType . '_price') . 
                    ' WHERE entity_id=' . $product->getId();
        $groupPrices = $readConnection->fetchAll($query);
        
        return $groupPrices;
    }
    
    public function buildPriceItem($product, $priceObj = null)
    {
        $priceId = isset($priceObj['value_id']) ? $priceObj['value_id'] : "";
        $priceValue = $priceObj ? $priceObj['value'] : $product->getSpecialPrice();
        $priceActiveFrom = $priceObj ? null : $product->getSpecialFromDate();
        $priceActiveTo = $priceObj ? null : $product->getSpecialToDate();
        $priceCustomerGroup = isset($priceObj['customer_group_id']) ? $priceObj['customer_group_id'] : null;
        $priceQty = isset($priceObj['qty']) ? (int) $priceObj['qty'] : null;

        $productPrice = array(
                    'price_id'             => $priceId,
                    'price_value'          => $priceValue,
                    'price_priority'       => null,
                    'price_active_from'    => $priceActiveFrom,
                    'price_active_to'      => $priceActiveTo,
                    'price_customer_group' => $priceCustomerGroup,
                    'price_quantity'       => $priceQty
                );
        
        return $productPrice;
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
    
    public function isBot()
	{
	   preg_match('/bot|curl|spider|google|facebook|yandex|bing|aol|duckduckgo|teoma|yahoo|twitter^$/i', $_SERVER['HTTP_USER_AGENT'], $matches);
	
	   return (empty($matches)) ? false : true;
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
    
    public function getProdUrl($product, $storeId = null)
    {
        $url = "";

        if (!$storeId)
        {
            $store = Mage::app()->getStore();
            $storeId = $store->getStoreId();
        }
        $product->setStoreId($storeId);
        $url = $product->getProductUrl();
        
        return $url;
    }

    public function getCartData()
    {   
        $cart           = Mage::getSingleton('checkout/session');
        $customer       = Mage::getSingleton('customer/session');
        $products       = $cart->getQuote()->getAllItems();
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
            'order_id'    => null,
            'price_total' => $products ? sprintf('%01.2f', $cart->getQuote()->getGrandTotal()) : 0,
            'qty_total'   => $products ? Mage::helper('checkout/cart')->getItemsCount() : 0,
            'products'    => array()
        );
        
        foreach ($products as $product)
        {   
            // to prevent duplicate entries for configurable product - consider only child simple products
            if($product->getProductType() !== "configurable")
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
            if($item->getProductType() !== "configurable")
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
    
    public function getDiagnosticInfo( $diagnosticTtype = 'InstallPlugin' ) 
    {
        $versionMage    = Mage::getVersion();
        $versionPlugin  = self::VERSION_PLUGIN;
        $datetime       = Mage::getModel('core/date')->date('Y-m-d\TH:i:s');
        
        $data = array(
            'DateCreated'                   => $datetime,
            'DiagnosticType'                => $diagnosticTtype,
            'Description'                   => 'Magento ' . $versionMage . ' Plugin ' . $versionPlugin,
            'Remarks'                       => 'LoLoLo',
            'Host'                          => Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB),
            'EmailAdmin'                    => Mage::getStoreConfig('trans_email/ident_general/email', $this->_storeId),
            'StatusEnableTriggmine'         => $this->_pluginOn,
            'StatusEnableOrderExport'       => $this->_enableOrderExport,
            'StatusEnableCustomerExport'    => $this->_enableCustomerExport,
            'ApiUrl'                        => $this->_url,
            'ApiKey'                        => $this->_token,
            'OrderExportDateFrom'           => $this->_exportOrderFromDate,
            'OrderExportDateTo'             => $this->_exportOrderToDate,
            'CustomerExportDateFrom'        => $this->_exportCustomerFromDate,
            'CustomerExportDateTo'          => $this->_exportCustomerToDate
        );
    
        return $data;
    }
    
    public function sendExtendedDiagnostic($data)
    {
        return $this->apiClient($data, 'api/diagnostic', $this->_triggmineDiagnosticURL);
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
                if($item->getProductType() !== "configurable")
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
    
    public function getAllProductPrices($product)
    {
        $res = array();
        
        // special price
        if ($product->getSpecialPrice())
        {
            $productPrice = $this->buildPriceItem($product);
            $res[] = $productPrice;
        }
        
        //group price
        $groupPrices = $this->getProductGroupPrices($product);
        
        if ($groupPrices)
        {
            foreach ($groupPrices as $groupPrice)
            {
                $productPrice = $this->buildPriceItem($product, $groupPrice);
                $res[] = $productPrice;
            }
        }
        
        // tier price
        $tierPrices = $this->getProductGroupPrices($product, 'tier');
        
        if ($tierPrices)
        {
            foreach ($tierPrices as $tierPrice)
            {
                $productPrice = $this->buildPriceItem($product, $tierPrice);
                $res[] = $productPrice;
            }
        }

        return $res;
    }
    
    public function getProductRelationsJson($product)
    {
        $res = array();
        
        if ($product->getRelatedProducts())
        {
            $relatedProducts = $product->getRelatedProducts();
            
            foreach ($relatedProducts as $relatedProduct)
            {
                $relatedProductData = $relatedProduct->getData();
                
                $res[] = array(
                        'relation_product_id' => $relatedProductData['entity_id'],
                        'relation_type'       => $relatedProductData['entity_type_id'],
                        'relation_priority'   => $relatedProductData['position']
                    );
            }
        }
                
        return $res;
    }
    
    public function getProductHistory($pageSize = 20, $page = 1, $websiteId = 1, $storeId = 1)
    {   
        $dataExport = array(
                'products' => array()
            );
        
        $products = Mage::getModel('catalog/product')
            ->getCollection()
            ->addStoreFilter($storeId)
            ->addAttributeToSelect('*')
            ->joinField('qty',
                 'cataloginventory/stock_item',
                 'qty',
                 'product_id=entity_id',
                 '{{table}}.stock_id=1',
                 'left')
            ->setPageSize($pageSize)
            ->setCurPage($page);

        $store = Mage::app()->getWebsite($websiteId)->getDefaultStore();

        foreach ($products as $productItem)
        {
            // prepare prices array
            $productPrice = $this->getAllProductPrices($productItem);
            
            // prepare categories array
            $productCategory = array();
            $categories = $productItem->getCategoryIds();
            
            foreach ($categories as $categoryId)
            {
                $category     = Mage::getModel('catalog/category')->load($categoryId);
                $categoryName = $category->getName();
                
                // this structure is needed on the backend
                $productCategory[] = array(
                    'product_category_type' => array(
                        'category_id'   => $categoryId ? $categoryId : "",
                        'category_name' => $categoryName
                    )
                );
            }
            
            // prepare relations array
            $productRelation = $this->getProductRelationsJson($productItem);
            
            $productStatus = true; 
            if ((int) $productItem->getStatus() !== 1)
            {
                $productStatus = false;
            }
            
            // complete product array
            $product = array (
                    'product_id'               => $productItem->getId(),
                    'parent_id'                => $productItem->getId(),
                    'product_name'             => $productItem->getName() ? $productItem->getName() : "",
                    'product_desc'             => $productItem->getDescription(),
                    'product_create_date'      => $productItem->getCreatedAt(),
                    'product_sku'              => $productItem->getSku(),
                    'product_image'            => $this->getProdImg($productItem),
                    'product_url'              => $this->getProdUrl($productItem, $storeId),
                    'product_qty'              => (int) $productItem->getQty(),
                    'product_default_price'    => $productItem->getPrice(),
                    'product_prices'           => $productPrice,
                    'product_categories'       => $productCategory,
                    'product_relations'        => $productRelation,
                    'product_is_removed'       => null,
                    'product_is_active'        => $productStatus,
                    'product_active_from'      => $productItem->getCustomDesignFrom(),
                    'product_active_to'        => $productItem->getCustomDesignTo(),
                    'product_show_as_new_from' => $productItem->getNewsFromDate(),
                    'product_show_as_new_to'   => $productItem->getNewsToDate()
                );
            
            $dataExport['products'][] = $product;
        }
            
        return $dataExport;
    }
    
    public function getProductEditData($observer)
    {
        $productItem = $observer->getEvent()->getProduct();
        
        // prepare prices array
        $productPrice = $this->getAllProductPrices($productItem);
        
        // prepare categories array
        $productCategory = array();
        $categories = $productItem->getCategoryIds();
        
        foreach ($categories as $categoryId)
        {
            $category     = Mage::getModel('catalog/category')->load($categoryId);
            $categoryName = $category->getName();
                
            // this structure is needed on the backend
            $productCategory[] = array(
                'product_category_type' => array(
                    'category_id'   => $categoryId ? $categoryId : "",
                    'category_name' => $categoryName
                )
            );
        }
        
        // prepare relations array
        $productRelation = $this->getProductRelationsJson($productItem);
        
        $productStatus = true;
        if ((int) $productItem->getStatus() == 0)
        {
            $productStatus = false;
        }
            
        // complete product array
        $product = array (
                'product_id'               => $productItem->getId(),
                'parent_id'                => $productItem->getParentItem() ? $productItem->getParentItem()->getId() : null,
                'product_name'             => $productItem->getName() ? $productItem->getName() : "",
                'product_desc'             => $productItem->getDescription(),
                'product_create_date'      => $productItem->getCreatedAt(),
                'product_sku'              => $productItem->getSku(),
                'product_image'            => $this->getProdImg($productItem),
                'product_url'              => $this->getProdUrl($productItem),
                'product_qty'              => (int) $productItem->getQty(),
                'product_default_price'    => $productItem->getPrice(),
                'product_prices'           => $productPrice,
                'product_categories'       => $productCategory,
                'product_relations'        => $productRelation,
                'product_is_removed'       => null,
                'product_is_active'        => $productStatus,
                'product_active_from'      => $productItem->getCustomDesignFrom(),
                'product_active_to'        => $productItem->getCustomDesignTo(),
                'product_show_as_new_from' => $productItem->getNewsFromDate(),
                'product_show_as_new_to'   => $productItem->getNewsToDate()
            );
        
        $dataExport['products'][] = $product;
        
        return $dataExport;
    }
    
    public function exportProductData($data, $url, $token)
    {
        return $this->apiClient($data, 'api/products/import', $url, $token);
    }
}