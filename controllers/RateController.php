<?php
class Cammino_Shippingestimate_RateController extends Mage_Core_Controller_Front_Action{
	
	public function indexAction(){
		$request = Mage::app()->getRequest()->getParams();
		$request = new Varien_Object($request);

		$product = $this->getProduct($request['product_id']);
		$rate = $this->getShippingEstimate($product,  $request);
		Mage::log('RateController:index -> count($rate) ' . count($rate), null, "frete.log");
		
		if (count($rate) == 0){
        	$rate = array('errorShipping' => 'Desculpe, mas no momento não estamos atuando no seu estado.');
      	}

      	echo json_encode($rate);
      	return ;
	}

	protected function getShippingEstimate($product,  $request, $countryId = "BR"){
		Mage::log("RateController:getShippingEstimate " , null, "frete.log");
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

				if($quote->getShippingAddress()->getFreeShipping() === true) {
					$shippingRates = array();
					$shippingRates[] =  array("title" => $rate->getMethodTitle(), "price" => "Frete Grátis");
					break;
				} else {
					$discount = $quote->getShippingAddress()->getDiscountAmount() * -1;
					$price = $rate->getPrice() - $discount;
					$price = Mage::helper('core')->currency($price, true, false);
					$shippingRates[] =  array("title" => $rate->getMethodTitle(), "price" => $price);
				}
      		}

      		Mage::log("RateController:getShippingEstimate -> shippingRates " . $shippingRates, null, "frete.log");
      		Mage::log($shippingRates, null, "frete.log");
      		
		
	    endforeach;
    
    	$this->setCartPostCode($request['cep']);
		return $shippingRates;
	}

	private function setCartPostCode($postcode = ""){
		if (empty($postcode))
			return false;

		$cartQuote = Mage::getSingleton('checkout/cart')->getQuote();

		if ($cartQuote->getItemsCount()){
			$cartQuote->getShippingAddress()
				->setCountryId('BR')
				->setPostcode($postcode)
				->setCollectShippingRates(true);
			$cartQuote->save();
		} else {
			Mage::getSingleton("core/session")->setQuotePostcode($postcode);
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