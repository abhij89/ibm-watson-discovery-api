<?php
require_once('DiscoveryApi.php');

$alchemy = new DiscoveryApi();
$data = $alchemy->queryCollection("IBM", "", "1 day");
echo "<pre>";print_r($data);
?>
