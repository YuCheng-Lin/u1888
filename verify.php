<?php
include_once $_SERVER['DOCUMENT_ROOT']."/class/WebLogin.class.php";
$acc = $_POST['acc'];
$pwd = $_POST['pwd'];

$login = new WebLogin();
$login->chkLogin($acc, $pwd, true);
$status = $login->getStatus();
if($status === '0'){
	//初始頁
	header("location: /per_info/");
	exit;
}else{
	$_SESSION['err'] = $status;
	header("location: /index.php");
	exit;
}
exit;
?>