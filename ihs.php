<?php

include 'functions.php';


$stmt = $connection->prepare("SELECT valasz FROM valaszok WHERE kerdesid = 29 AND helyes =2");
$stmt->execute();
$kepek = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<pre>";
foreach($kepek as $key=>$kep) {
	$fileName = $kep['valasz'];
	$newFileName = $imageFolder."/ihs".str_replace($imageFolder,'',$kep['valasz']);
	copy($fileName,$newFileName);
	//if(array_key_exists('valasz', $kep	) {
		
	//echo str_replace($imageFolder,'',$kep['valasz');
	echo $newFileName."<br/>";
	//}
}

print_r($kepek);

echo "ok";

?>