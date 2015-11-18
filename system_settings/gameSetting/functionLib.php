<?php
include_once $_SERVER['DOCUMENT_ROOT'].'/class/Template.class.php';


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
 * 編輯描述
 */
function updDetail(){
	$sendAry = array();
	$html = new Template(); 
	$html->publicTemp = $html->getFile($_SERVER['DOCUMENT_ROOT'].'/tpl/system_settings/gameSetting/updDetail.html');

	if(empty($_POST['id'])){
		$sendAry['systemErr'] = 'alert("'.$html->lang['IndexErrorMsg7'].'");';
		echo json_encode($sendAry);
		exit;
	}
	$id = $_POST['id'];

	$db = new PDO_DB(DB_HOST, DB_USER, DB_PWD, DB_NAME);
	$db->selectTB('Game');
	$db->getData("*", "WHERE GameID = '".$id."'");
	$db->execute();
	if($db->total_row <= 0){
		$sendAry['systemErr'] = 'alert("'.$html->lang['IndexErrorMsg7'].'");';
		echo json_encode($sendAry);
		exit;
	}

	$db->row['GameDescription'] = trim($db->row['GameDescription']);

	//重新组合页面
	$html->publicTemp = $html->regexReplace($db->row, $html->publicTemp, '<!--__', '__-->');
	$main = $html->compiler();
	
	$sendAry['result'] = $main;
	
	echo json_encode($sendAry);
	unset($html, $db);
	exit;
}

/**
 * 詳細內容
 */
function viewDetail(){
	$sendAry = array();
	$html = new Template(); 
	$html->publicTemp = $html->getFile($_SERVER['DOCUMENT_ROOT'].'/tpl/system_settings/gameSetting/viewDetail.html');

	if(empty($_POST['id'])){
		$sendAry['systemErr'] = 'alert("'.$html->lang['IndexErrorMsg7'].'");';
		echo json_encode($sendAry);
		exit;
	}
	$id = $_POST['id'];

	$db = new PDO_DB(DB_HOST, DB_USER, DB_PWD, DB_NAME);
	$db->selectTB('Game');
	$db->getData("*", "WHERE GameID = '".$id."'");
	$db->execute();
	if($db->total_row <= 0){
		$sendAry['systemErr'] = 'alert("'.$html->lang['IndexErrorMsg7'].'");';
		echo json_encode($sendAry);
		exit;
	}

	$db->row['GameDescription'] = trim($db->row['GameDescription']);

	//重新组合页面
	$html->publicTemp = $html->regexReplace($db->row, $html->publicTemp, '<!--__', '__-->');
	$main = $html->compiler();
	
	$sendAry['result'] = $main;
	
	echo json_encode($sendAry);
	unset($html, $db);
	exit;
}
?>