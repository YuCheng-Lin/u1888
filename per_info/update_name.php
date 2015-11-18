<?php
include_once $_SERVER['DOCUMENT_ROOT'].'/class/Template.class.php';

$html = new Template();

//未登入
if($html->WebLogin->getStatus() != '0'){
	$_SESSION['err'] = $html->WebLogin->getStatus();
	header("location: /index.php");
	exit;
}

$referer = $_SERVER['HTTP_REFERER'];
if(empty($_POST['admin_name'])){
	breakoff($html->lang['ERRNickName'], $referer);
	exit;
}

$admin_id = $html->WebLogin->admin_id;
$oldData  = new PDO_DB(DB_HOST, DB_USER, DB_PWD, DB_NAME);
$oldData->selectTB('Admin');
$oldData->getData("*", "WHERE admin_id = '". $admin_id ."'");
$oldData->execute();
if($oldData->total_row <= 0){
	$_SESSION['err'] = '1';
	header("location: /index.php");
	exit;
}

$ary = array();
$ary['admin_name'] = $_POST['admin_name'];

$oldData->updateData($_POST, "WHERE admin_id = '". $admin_id ."'");
$oldData->execute();

breakoff($html->lang['UpdateSuccess'], $referer);
unset($oldData, $html);
exit;

function breakoff($msg, $referer = 'index.php'){
	$ary['msg'] = $msg;
	$_SESSION['_err'] = json_encode($ary);
	header('location: '.$referer);
	exit;
}
?>