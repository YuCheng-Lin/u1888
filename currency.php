<?php
include_once $_SERVER['DOCUMENT_ROOT'].'/class/PDO_DB.class.php';
$db = new PDO_DB(DB_HOST, DB_USER, DB_PWD, DB_NAME);
$db->selectTB('Currency');
$db->getData("*");
$db->execute();
$converterAry = [];
$currencyAry  = [];
if($db->total_row > 0){
	do{
		$converterAry[] = $db->row['code'];
	}while($db->row = $db->fetch_assoc());
}

//api.fixer.io
// $response = fixerConverter($converterAry);
//Google
$response = googleConverter($converterAry);
// echo '<pre>';
// print_r($response);
// exit;

if(count($response['rates']) > 0){
	foreach ($response['rates'] as $key => $value) {
		$db->updateData([
			'rate'       => $value,
			'updated_at' => date('Y-m-d H:i:s')
		], "WHERE code = '".$key."'");
		$db->execute();
	}
}
echo json_encode(['result'=>'ok']);
exit;




//fixer
function fixerConverter($converterAry, $base=CURRENCY){
	$response = file_get_contents('http://api.fixer.io/latest?base='.$base.'&symbols='.join(",",$converterAry));
	return json_decode($response, true);
}


// Google
function googleConverter($converterAry, $base=CURRENCY, $money='1'){
	$response['rates'] = [];
	foreach ($converterAry as $key => $value) {
		$contents          = file_get_contents('http://www.google.com/finance/converter?a='.$money.'&from='.$value.'&to='.$base);
		$regularExpression = '#\<span class=bld\>(.+?)\<\/span\>#s';
		preg_match($regularExpression, $contents, $contents);
		if(count($contents) > 0){
			$response['rates'][$value] = explode(" ", $contents[1])[0];
		}
	}
	return $response;
}


?>