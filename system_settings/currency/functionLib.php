<?php
include_once $_SERVER['DOCUMENT_ROOT'].'/class/Template.class.php';
include_once $_SERVER['DOCUMENT_ROOT'].'/class/WebLogin.class.php';

if(empty($_POST)){
	header('location: /index.php?err=3');
	exit;
}

//载入基础样版
$html = new Template();

//未登入
$status = $html->WebLogin->getStatus();
if($status != '0'){
	// $sendAry = array();
	// $sendAry['systemErr'] = 'window.location = "/index.php?err='. $status .'";';
	// echo json_encode($sendAry);
	header("Location: /index.php?err=".$status);
	exit;
}

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
unset($html);
exit;

/**
 * 新增幣別
 */
function add(){
	global $html;

	$html->publicTemp = $html->getFile($_SERVER['DOCUMENT_ROOT'].'/tpl/system_settings/currency/add.html');

	//重新组合页面
	$main = $html->compiler();

	$sendAry = [];
	$sendAry['result'] = $main;
	
	echo json_encode($sendAry);
	exit;
}

/**
 * 編輯幣別
 */
function update(){
	global $html;

	if(empty($_POST['id'])){
		$sendAry = array();
		$sendAry['systemErr'] = 'alert("No id。");';
		echo json_encode($sendAry);
		exit;
	}
	$id = $_POST['id'];

	$html->publicTemp = $html->getFile($_SERVER['DOCUMENT_ROOT'].'/tpl/system_settings/currency/update.html');

	$db = new PDO_DB(DB_HOST, DB_USER, DB_PWD, DB_NAME);
	$db->selectTB('Currency');
	$db->getData("*", "WHERE id = '".$id."'");
	$db->execute();
	if($db->total_row <= 0){
		$sendAry = array();
		$sendAry['systemErr'] = 'alert("Not found。");';
		echo json_encode($sendAry);
		exit;
	}
	$data = $db->row;
	$data['checked'] = $db->row['enabled'] == 'y' ? 'checked="checked"' : '';

	$html->publicTemp = $html->regexReplace($data, $html->publicTemp, '<!--__', '__-->');

	//重新组合页面
	$main = $html->compiler();

	$sendAry = [];
	$sendAry['result'] = $main;
	
	echo json_encode($sendAry);
	exit;
}

/**
 * 刪除幣別
 */
function delete(){
	global $html;

	if(empty($_POST['id'])){
		$sendAry = array();
		$sendAry['systemErr'] = 'alert("No id。");';
		echo json_encode($sendAry);
		exit;
	}
	$id = $_POST['id'];

	$html->publicTemp = $html->getFile($_SERVER['DOCUMENT_ROOT'].'/tpl/system_settings/currency/delete.html');

	$db = new PDO_DB(DB_HOST, DB_USER, DB_PWD, DB_NAME);
	$db->selectTB('Currency');
	$db->getData("*", "WHERE id = '".$id."'");
	$db->execute();
	if($db->total_row <= 0){
		$sendAry = array();
		$sendAry['systemErr'] = 'alert("Not found。");';
		echo json_encode($sendAry);
		exit;
	}
	$data = $db->row;

	$html->publicTemp = $html->regexReplace($data, $html->publicTemp, '<!--__', '__-->');

	//重新组合页面
	$main = $html->compiler();

	$sendAry = [];
	$sendAry['result'] = $main;
	
	echo json_encode($sendAry);
	exit;
}

?>