<?php
class Cammino_Shippingestimate_RateController extends Mage_Core_Controller_Front_Action{
	
	public function indexAction(){
		$request = Mage::app()->getRequest()->getParams();
		$request = new Varien_Object($request);

		$product = $this->getProduct($request['product_id']);
		$rate = $this->getShippingEstimate($product,  $request);

		if (count($rate) == 0){
        	$rate = array('errorShipping' => 'Desculpe, mas no momento não estamos atuando no seu estado.');
      	}

      	echo json_encode($rate);
      	return ;
	}

	protected function getShippingEstimate($product,  $request, $countryId = "BR"){
		$quote = Mage::getModel('sales/quote')->setStoreId(1);
		$product->getStockItem()->setUseConfigManageStock(false);
    	$product->getStockItem()->setManageStock(false);

	    $quote->addProduct($product, $request);
	    $quote->getShippingAddress()->setCountryId($countryId)->setPostcode($request['cep']); 
	    $quote->getShippingAddress()->setCollectShippingRates(true);
	    $quote->getShippingAddress()->collectShippingRates();

	    $quote->setCustomerGroupId(0);
	    $quote->setCouponCode('');

	    $rates = $quote->getShippingAddress()->getShippingRatesCollection();
	    $shippingRates = array();
    
	    foreach ($rates as $rate):
      		if ($rate->getMethodTitle() != "") {
				$quote->getShippingAddress()->setShippingMethod($rate->getCode())->setCollectShippingRates(true);
				$quote->collectTotals();
				$discount = $quote->getShippingAddress()->getDiscountAmount() * -1;
				$price = $rate->getPrice() - $discount;
				$price = Mage::helper('core')->currency($price, true, false);
				$shippingRates[] =  array("title" => $rate->getMethodTitle(), "price" => $price);
      		}
	    endforeach;
    
    	$this->setCartPostCode($cep);
		return $shippingRates;
	}

	private function setCartPostCode($cep = ""){
		if (empty($cep))
			return false;

		$cartQuote = Mage::getSingleton('checkout/cart')->getQuote();

		if ($cartQuote->getItemsCount()){
			$cartQuote->getShippingAddress()
				->setCountryId('BR')
				->setPostcode($cep)
				->setCollectShippingRates(true);
			$cartQuote->save();
		}else{
			Mage::getSingleton("core/session")->setQuotePostcode($cep);
		}   
	}

	protected function getProduct($productId){
    	$product = Mage::getModel('catalog/product')->load($productId);

    	if ($this->getRequest()->getParam('super_attribute')) {
    		$superAttribute = $this->getRequest()->getParam('super_attribute');
      		$childProduct = Mage::getModel('catalog/product_type_configurable')->getProductByAttributes($superAttribute, $product);
			return Mage::getModel('catalog/product')->load($childProduct->getId());
    	} else if ($product->getTypeID() == "configurable") {
    		$conf = Mage::getModel('catalog/product_type_configurable')->setProduct($product);
		    $simple_collection = $conf->getUsedProductCollection()->addAttributeToSelect('*')->addFilterByRequiredOptions();
		    foreach($simple_collection as $simple_product){
		        return $simple_product;
		    }
    	} else{
    		return $product;
    	}
    }

}