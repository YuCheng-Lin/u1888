<?php
include_once $_SERVER['DOCUMENT_ROOT'].'/class/Template.class.php';

$html = new Template();

//未登入
if($html->WebLogin->getStatus() != '0'){
	$_SESSION['err'] = $html->WebLogin->getStatus();
	header("location: /index.php");
	exit;
}

if(empty($_POST['admin_pwd'])){
	$ary['msg'] = $html->lang['ERRPassword'];
	$_SESSION['_err'] = json_encode($ary);
	header("location: per_info.php");
	exit;
}

$admin_id = $html->WebLogin->admin_id;
$oldData = new PDO_DB(DB_HOST, DB_USER, DB_PWD, DB_NAME);
$oldData->selectTB('Admin');
$oldData->getData("*", "WHERE admin_id = '". $admin_id ."'");
$oldData->execute();
if($oldData->total_row <= 0){
	header("Location: /index.php?err=1");
	exit;
}

if($oldData->row['admin_pwd'] == $_POST['admin_pwd']){
	$ary['msg'] = $html->lang['ERRPassword4'];
	$_SESSION['_err'] = json_encode($ary);
	header("location: per_info.php");
	exit;
}

$ary = array();
$ary['admin_pwd'] = $_POST['admin_pwd'];

$oldData->updateData($_POST, "WHERE admin_id = '". $admin_id ."'");
$oldData->execute();

$html->WebLogin->logout();
$_SESSION['updPwdDone'] = 'done';
header("location: /index.php");
unset($oldData, $html);
exit;
?>