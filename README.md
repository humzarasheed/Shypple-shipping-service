# Shypple-Shipping-Service
Techozone Assesment

To use the ShippingService, you need to follow these steps:

* Include the ShippingService.php file in your Laravel project.
* Instantiate the service by passing the JSON data obtained from MapReduce

`$jsonData = /* api call to MapReduce */;
$shippingService = new ShippingService($jsonData);`

Finally you can call any of required methods in following way.
Eg for getting cheapest direct sailing

`$shipping->getCheapestDirectSailing($originPort, $destinationPort);`
