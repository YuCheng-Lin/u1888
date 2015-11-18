<?php
include_once $_SERVER['DOCUMENT_ROOT'].'/class/Template.class.php';
include_once $_SERVER['DOCUMENT_ROOT'].'/class/WebLogin.class.php';
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

function updRecord()
{
	$sendAry = array();
	if(empty($_POST['id'])){
		$sendAry['systemErr'] = 'alert("'.$html->lang['IndexErrorMsg7'].'");';
		echo json_encode($sendAry);
		exit;
	}
	$id   = $_POST['id'];
	$html = new Template($_SERVER['DOCUMENT_ROOT']."/tpl/reportData/pointsTrans/updRecord.html"); 

	$db = new PDO_DB(DB_HOST, DB_USER, DB_PWD, DB_NAME);
	$db->selectTB('AdminWinLose');
	$db->getData("*", "WHERE id = '".$id."'");
	$db->execute();

	//重新组合页面
	$html->publicTemp = $html->regexReplace($db->row, $html->publicTemp, '<!--__', '__-->');
	$main = $html->compiler();
	
	$sendAry = array();
	$sendAry['result'] = $main;
	
	echo json_encode($sendAry);
	unset($html, $db);
	exit;
}

function updMemRecord()
{
	$sendAry = array();
	if(empty($_POST['id'])){
		$sendAry['systemErr'] = 'alert("'.$html->lang['IndexErrorMsg7'].'");';
		echo json_encode($sendAry);
		exit;
	}
	$id   = $_POST['id'];
	$html = new Template($_SERVER['DOCUMENT_ROOT']."/tpl/reportData/pointsTrans/updRecord.html"); 

	$db = new PDO_DB(DB_HOST, DB_USER, DB_PWD, DB_NAME);
	$db->selectTB('MemWinLose');
	$db->getData("*", "WHERE id = '".$id."'");
	$db->execute();

	//重新组合页面
	$html->publicTemp = $html->regexReplace($db->row, $html->publicTemp, '<!--__', '__-->');
	$main = $html->compiler();
	
	$sendAry = array();
	$sendAry['result'] = $main;
	
	echo json_encode($sendAry);
	unset($html, $db);
	exit;
}
?>