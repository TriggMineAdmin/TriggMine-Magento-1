<?php
use \GuzzleHttp;

class Triggmine_IntegrationModule_Model_Api2_Sales_Order_Rest_Guest_V1 extends Triggmine_IntegrationModule_Model_Api2_Sales_Order
{
    /**
     * @return string
     */
    protected function _retrieve()
    {
        $orderId = $this->getRequest()->getParam('id');
        if (!$orderId) {
            throw new Exception('Id required');
        }
        try {
            $order = Mage::getModel('sales/order')->load($orderId);
        } catch (Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
        if (empty($orderId) === true || empty($order->getIncrementId()) === true) {
            return null;
        }
        $data = array();
        $data['price_total'] = sprintf('%01.2f', $order->getGrandTotal());
        $data['qty_total'] = (int)$order->getTotalQtyOrdered();
        foreach ($order->getAllItems() as $item) {
            $itemData = array();
            $itemData['product_id'] = $item->getProductId();
            $itemData['product_name'] = trim(preg_replace('/\s+/', ' ', $item->getName()));
            $itemData['product_sku'] = $item->getSku();
            $itemData['product_qty'] = $item->getQtyToInvoice();
            $itemData['product_price'] = sprintf('%01.2f', $item->getPriceInclTax());
            $data['products'] [] = $itemData;
        }

        return json_encode($data);

    }


    protected function _create($shedulerData)
    {
        return json_encode($shedulerData);
    }

    protected function _retrieveCollection()
    {
        return json_encode(array('method' => '_retrieveCollection'));
    }

}