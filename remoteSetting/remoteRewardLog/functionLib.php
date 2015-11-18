<?php
include_once $_SERVER['DOCUMENT_ROOT'].'/class/Template.class.php';
include_once $_SERVER['DOCUMENT_ROOT'].'/class/Points.class.php';

//载入基础样版
$html = new Template();

//未登入
$status = $html->WebLogin->getStatus();
if($status != '0'){
	$sendAry = array();
	$sendAry['systemErr'] = 'window.location = "/index.php?err='. $status .'";';
	echo json_encode($sendAry);
//	header("Location: /index.php?err=".$status);
	exit;
}

$userIp = $html->WebLogin->getUserIp();
unset($html);



if(empty($_POST['type'])){
	$sendAry = array();
	$sendAry['systemErr'] = 'alert("No Data。");';
	echo json_encode($sendAry);
	exit;
}
if(!function_exists($_POST['type'])){
	$sendAry = array();
	$sendAry['systemErr'] = 'alert("No Data(type)。");';
	echo json_encode($sendAry);
	exit;
}
$_POST['type']();
exit;

/**
 * 詳細內容
 */
function addRemote(){
	$sendAry = array();
	$html = new Template($_SERVER['DOCUMENT_ROOT']."/tpl/remoteSetting/remoteRewardLog/addRemote.html"); 
	//欲取代的内容
	$compilerAry = array();

	// $db = new PDO_DB(DB_HOST, DB_USER, DB_PWD, DB_NAME);
	// $db->selectTB('A_Member');
	// $db->getData("*", "WHERE MemberID = '".$id."'");
	// $db->execute();
	// if($db->total_row <= 0){
	// 	$sendAry['systemErr'] = 'alert("'.$html->lang['IndexErrorMsg7'].'");';
	// 	echo json_encode($sendAry);
	// 	exit;
	// }
	// $html->publicTemp = $html->regexReplace(($db->row + $row), $html->publicTemp, '<!--__', '__-->');

	//重新组合页面
	$main = $html->compiler($compilerAry);
	
	$sendAry['result'] = $main;
	
	echo json_encode($sendAry);
	unset($html, $db, $Points);
	exit;
}
?>