<?php
// Basic constants created for the integration
define("CIELO_TYPE_ASSET", "Asset");
define("CIELO_TYPE_DIGITAL", "Digital");
define("CIELO_TYPE_SERVICE", "Service");
define("CIELO_TYPE_PAYMENT", "Payment");

define("CIELO_DISCOUNT_TYPE_AMOUNT", "Amount");
define("CIELO_DISCOUNT_TYPE_PERCENTAGE", "Percent");

define("CIELO_SHIPPING_TYPE_CORREIOS", "Correios");
define("CIELO_SHIPPING_TYPE_FIXED", "FixedAmount");
define("CIELO_SHIPPING_TYPE_FREE", "Free");
define("CIELO_SHIPPING_TYPE_PICKUP", "WithoutShippingPickUp");
define("CIELO_SHIPPING_TYPE_WITHOUT", "WithoutShipping");

class Cielo {
	static $merchantId;
	static $orderNumber;
	static $softDescriptor;

	static $items = array();
	static $shipping = array();
	static $payment = array();
	static $customer = array();
	static $discount = NULL;
	static $discountBoleto = 0;
	static $discountDebit = 0;
	static $antiFraud = false;

	function _construct($orderNumber = false) {
		if ($orderNumber === false) {
			return false;
		}

		self::$orderNumber = $orderNumber;
	}

	public function setMerchantId($merchantId = false) {
		if ($merchantId === false) {
			return false;
		}

		self::$merchantId = $merchantId;
		return true;
	}

	public function setSoftDescriptor($softDescriptor = false) {
		if ($softDescriptor === false) {
			return false;
		}

		self::$softDescriptor = $softDescriptor;
		return true;
	}

	public function addItem($name = NULL, $description, $unitPrice = NULL, $quantity = 1, $type = NULL, $sku, $weight = NULL) {
		if ($name == "" || $name === NULL || $unitPrice == "" || $unitPrice === NULL || $quantity == "" || $quantity <= 0 || $type == "" || $type === NULL) {
			return false;
		}

		if ($type == CIELO_TYPE_ASSET && ($weight === NULL || $weight <= 0)) {
			return false;
		}

		self::$items[] = array(
			'Name' => $name,
			'Description' => $description,
			'UnitPrice' => $unitPrice,
			'Quantity' => $quantity,
			'Type' => $type,
			'Sku' => $sku,
			'Weight' => $weight
		);

		return true;
	}

	public function setDiscount($type = NULL, $value = NULL) {
		if ($type === NULL || $value == NULL || $value <= 0) {
			return false;
		}

		self::$discount = array(
			'Type' => $type,
			"Value" => $value
		);

		return true;
	}

	public function setShippingInfo($type = CIELO_SHIPPING_TYPE_WITHOUT, $sourceZipCode = NULL) {
		if (($type != CIELO_SHIPPING_TYPE_WITHOUT && $type != CIELO_SHIPPING_TYPE_PICKUP) && ($sourceZipCode === NULL || $sourceZipCode == "")) {
			return false;
		}

		self::$shipping["Type"] = $type;

		if ($sourceZipCode !== NULL && $sourceZipCode != "") {
			self::$shipping["SourceZipCode"] = $sourceZipCode;
		}

		return true;
	}

	public function setShippingAddress($targetZipCode = NULL, $street = NULL, $number = NULL, $complement = NULL, $district = NULL, $city = NULL, $state = NULL) {
		if ($targetZipCode === NULL || $targetZipCode == "" || $street === NULL || $street == "" || $number === NULL || $number == "" || $complement === NULL || $complement == "" || $district === NULL || $district == "" || $city === NULL || $city == "" || $state === NULL || $state == "") {
			return false;
		}

		if (self::$shipping['Type'] == CIELO_SHIPPING_TYPE_WITHOUT || self::$shipping['Type'] == CIELO_SHIPPING_TYPE_PICKUP) {
			return false;
		}

		self::$shipping['TargetZipCode'] = $targetZipCode;
		self::$shipping['Address'] = array(
			'Street' => $street,
			'Number' => $number,
			'Complement' => $complement,
			'District' => $district,
			'City' => $city,
			'State' => $state
		);

		return true;
	}

	public function addShipping($name = NULL, $price = NULL, $deadline = NULL) {
		if ($name === NULL || $name == "" || $price === NULL || $price <= 0 || $deadline === NULL || $deadline <= 0) {
			return false;
		}

		if (!isset(self::$shipping['Services'])) {
			self::$shipping['Services'] = array();
		}

		self::$shipping["Services"][] = array(
			"Name" => $name,
			"Price" => $price,
			"DeadLine" => $deadline
		);

		return true;
	}

	public function setDiscountDebit($discount = 0) {
		self::$discountDebit = $discount;
	}

	public function setDiscountBoleto($discount = 0) {
		self::$discountBoleto = $discount;
	}

	public function setCustomer($identity = NULL, $name = NULL, $email = NULL, $phone = NULL) {
		if ($identity === NULL || $identity == "" || $name === NULL || $name == "" || $email === NULL || $email == "" || $phone === NULL || $phone == "") {
			return false;
		}

		self::$customer = array(
			"Identity" => $identity,
			"FullName" => $name,
			"Email" => $email,
			"Phone" => $phone
		);

		return true;
	}

	public function toggleAntiFraud($fixed = NULL) {
		if ($fixed !== NULL) {
			self::$antiFraud = $fixed;
		} else {
			self::$antiFraud = (self::$antiFraud === false ? true : false);
		}
		return true;
	}

	public function register() {
		$order = array(
			"OrderNumber" => self::$orderNumber,
			"SoftDescriptor" => self::$softDescriptor,
			"Cart" => array()
		);

		if (self::$discount !== NULL) {
			$order["Cart"]['Discount'] = self::$discount;
		}

		$order["Cart"]['Items'] = self::$items;
		$order["Shipping"] = self::$shipping;
		$order["Payment"] = array(
			"BoletoDiscount" => self::$discountBoleto,
			"DebitDiscount" => self::$discountDebit
		);
		$order["Customer"] = self::$customer;
		$order["Options"] = array(
			"AntifraudEnabled" => (int)self::$antiFraud
		);

		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, 'https://cieloecommerce.cielo.com.br/api/public/v1/orders');
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($order));
		curl_setopt($curl, CURLOPT_HTTPHEADER, array(
		    'MerchantId: ' . self::$merchantId,
		    'Content-Type: application/json'
		));

		$response = curl_exec($curl);

		curl_close($curl);

		return json_decode($response);
	}
}
?>