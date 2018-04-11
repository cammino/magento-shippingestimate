<?php
class Cammino_Shippingestimate_Model_Observer extends Varien_Object {

	public function setPostcode(Varien_Event_Observer $observer) {
		$postcode = Mage::getSingleton("core/session")->getQuotePostcode();
		Mage::log("----------MÓDULO SHIPPING ESTIMATE; FUNÇÃO setPostcode---------", null, "frete.log"); 
		Mage::log("setPostcode -> postcode " . $postcode, null, "frete.log");
		if (!empty($postcode)) {
			$quote = Mage::getSingleton('checkout/cart')->getQuote();
			$quote->getShippingAddress()
				->setCountryId('BR')
				->setPostcode($postcode)
				->setCollectShippingRates(true);

			$quote->save();
			Mage::getSingleton('checkout/session')->unsQuotePostcode();
		}
	}

}