# ibm-watson-discovery-api
Implementing IBM watson Discovery api in php

# Usage
	require_once('DiscoveryApi.php');

	$alchemy = new DiscoveryApi();
	$data = $alchemy->queryCollection("IBM", "", "1 day");
	echo "<pre>";print_r($data);
