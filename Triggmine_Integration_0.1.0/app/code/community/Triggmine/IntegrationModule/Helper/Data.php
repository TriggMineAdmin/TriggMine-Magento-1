<?php
use Triggmine\Commerce\CommerceClient;

class Triggmine_IntegrationModule_Helper_Data extends Mage_Core_Helper_Abstract
{
    const XML_PATH_ENABLED = 'triggmine/config/is_on';
    const XML_PATH_KEY = 'triggmine/config/key';
    const XML_PATH_SECRET = 'triggmine/config/secret';
    const XML_PATH_TOKEN = 'triggmine/config/token';


    protected $_storeManager;
    protected $_cartItemRepository;
    protected $_commerceClient;
    protected $_customerRepository;
    protected $_customerSession;
    protected $_cookieManager;


    public function __construct()
    {
        $this->_cookieManager = Mage::getModel('core/cookie');
        $this->_storeManager = Mage::app()->getStore()->getId();
        $this->_customerSession = Mage::getSingleton('customer/session');
        $this->_customerRepository = Mage::getModel("customer/customer");


        $this->_commerceClient = new CommerceClient(
            [
                'version'     => 'latest',
  //            'debug'     => true,
                'credentials' => [
                    'key'    => $this->getApiPublicKey(),
                    'secret' => $this->getApiPrivateKey()
                ]
            ]
        );

    }

    /*

     public function isEnabled($store = null)
     {
         return Mage::getStoreConfigFlag(self::XML_PATH_ENABLED, $store);
     }

     public function getAPIKey($store = null)
     {
         $api_key = Mage::getStoreConfig(self::XML_PATH_API_KEY, $store);
         return $api_key ? $api_key : false;
     }
 */
    /*   */

    public function getApiPublicKey()
    {
        return Mage::getStoreConfig(self::XML_PATH_KEY);
    }

    public function getApiPrivateKey()
    {
        return Mage::getStoreConfig(self::XML_PATH_SECRET);
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


    public function getCartData()
    {
        $cart = Mage::getSingleton('checkout/session');
        $products = $cart->getQuote()->getAllVisibleItems();
        $data = array(
            'device_id'   => $this->getDeviceId(),
            'device_id_1' => $this->getDeviceId_1(),
            'price_total' => sprintf('%01.2f', $cart->getQuote()->getGrandTotal()),
            'qty_total'   => (int)Mage::helper('checkout/cart')->getSummaryCount(),
            'products'    => array()
        );

        foreach ($products as $product) {
            if (!$catalogProduct = $product->getProduct()) {
                throw new Exception('No productModel');
            }
            if (!$productId = $catalogProduct->getId()) {
                throw new Exception('No productId');
            }
            if (!$productName = $catalogProduct->getName()) {
                throw new Exception('No productName');
            }
            if (!$productPrice = $catalogProduct->getFinalPrice($product->getQty())) {
                throw new Exception('No productPrice');
            }
            if (!$productTotalVal = $product->getRowTotal()) {
                throw new Exception('No productTotalPriceInclTax');
            }
            if (!$productQty = $product->getQty()) {
                throw new Exception('No productQnt');
            }
            if (!$productImage = Mage::getModel('catalog/product')->load($productId)->getImageUrl()) {
                throw new Exception('No image Url');
            }

            $itemData = array();
            $itemData['product_id'] = (string)$productId;
            $itemData['product_name'] = $this->normalizeName($productName);
            $itemData['product_sku'] = $product->GetData('sku');
            $itemData['product_image'] = $productImage;
            $itemData['product_url'] = $catalogProduct->getProductUrl();
            $itemData['product_qty'] = $product->getQty();
            $itemData['product_price'] = $productPrice;
            $itemData['product_total_val'] = $productTotalVal;
            $data['products'][] = $itemData;
        }

        return $data;
    }

    public function sendCart($cartData)
    {
        return $this->_commerceClient->onFullCartChange($cartData);
    }

    public function onConvertCartToOrder($orderData)
    {
        return $this->_commerceClient->onConvertCartToOrder($orderData);
    }

    public function getOrderData($observer)
    {
        $order = $observer->getEvent()->getOrder();

        $data = array(
            'device_id'   => $this->getDeviceId(),
            'device_id_1' => $this->getDeviceId_1(),
            'customer_id' => $order->getCustomerId(),
            'order_id'    => $order->getId()
        );

        return $data;
    }

    public function getCustomerLoginData($customer = null)
    {
        if (is_null($customer)) {
            $customer = $this->getCustomer();
        }

        $data = array(
            'device_id'           => $this->getDeviceId(),
            'device_id_1'         => $this->getDeviceId_1(),
            'customer_id'         => $customer->getId(),
            'customer_first_name' => $customer->getFirstname(),
            'customer_last_name'  => $customer->getLastname(),
            'customer_email'      => $customer->getEmail()
        );

        return $data;
    }

    public function getCustomer()
    {
        return $this->_customerRepository->load($this->_customerSession->getCustomerId());
    }

    public function sendLoginData($loginData)
    {
        $this->_commerceClient->onLogin($loginData);
    }

    public function sendLogoutData($logoutData)
    {
        $this->_commerceClient->onLogout($logoutData);
    }

    public function sendRegisterData($registerData)
    {
        return $this->_commerceClient->onCustomerRegister($registerData);
    }

}
	 