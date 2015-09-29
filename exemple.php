<?php

error_reporting(E_ERROR | E_WARNING | E_PARSE);
ini_set('display_errors', '1');

date_default_timezone_set("America/Sao_Paulo");
header('Content-Type: text/html; charset=utf-8');
header('Access-Control-Allow-Origin: *'); 
ini_set("memory_limit", "256M");

require "Cielo.class.php";

$cielo = new Cielo("12345678");
$cielo->setMerchantId("00000000-0000-0000-0000-0000000000000");
$cielo->setSoftDescriptor("DESCRIPTION");
$cielo->addItem("Product 1", "Product 1 description", "100", 1, CIELO_TYPE_ASSET, "SKU1234", 200);
$cielo->addItem("Product 2", "Product 2 description", "200", 1, CIELO_TYPE_ASSET, "SKU4321", 200);
$cielo->setDiscount(CIELO_DISCOUNT_TYPE_PERCENTAGE, 10);
$cielo->setShippingInfo(CIELO_SHIPPING_TYPE_CORREIOS, "0000000");
$cielo->setShippingAddress("00000000", "Street name", 123, "", "District", "City", "UF");
$cielo->addShipping("Sedex", 1990, 1);
$cielo->addShipping("PAC", 1350, 3);
$cielo->setDiscountDebit(10);
$cielo->setCustomer("CPF WITHOUT DOTS", "CUSTOMER NAME", "customer@email.com", "11999999999");
$cielo->toggleAntiFraud(false);
$data = $cielo->register();
var_dump($data);

?>