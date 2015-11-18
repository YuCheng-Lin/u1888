<?php
include_once $_SERVER['DOCUMENT_ROOT']."/class/WebLogin.class.php";

$logout = new WebLogin();

// if($logout->chkLogin($_SESSION['_admin_acc'], $_SESSION['_admin_pwd'], $_SESSION['_admin_idls4'])){
// 	//回报AP，使用者登出
// 	$command = "110,Logout,". $logout->admin_id .",". $logout->s_id; 
// 	$logout->javaConnnection($command, SERVER_AP_HOST, SERVER_AP_PORT);
// }

$logout->logout();	
unset($logout);
$_SESSION['logout'] = '1';
header("Location: /index.php");
exit;
?>