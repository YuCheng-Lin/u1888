<?php
/**
 * 
 * 管理者系统 - 新增帐号 、 修改帐号、删除帐号
 * 
 */


include_once $_SERVER['DOCUMENT_ROOT'].'/class/WebLogin.class.php';


if(!isset($_SESSION['_admin_acc']) || $_SESSION['_admin_acc'] == '' || !isset($_SESSION['_admin_pwd']) || $_SESSION['_admin_pwd'] == '' || !isset($_SESSION['_admin_idls4']) || $_SESSION['_admin_idls4'] == ''){
	header("Location: /index.php?err=1");
	exit;
}


if(!isset($_POST) || count($_POST) <= 0){
	header("Location: /index.php?err=3");
	exit;
}


$login = new WebLogin();
$login->chkLogin($_SESSION['_admin_acc'], $_SESSION['_admin_pwd'], $_SESSION['_admin_idls4']);
$status = $login->getStatus();
if($status != '0'){
	header("Location: /index.php?err=".$status);
	exit;
}

$nowAdmin_agid = $login->ag_id;
unset($login);




/**
 * 
 * 新增帐号
 * 
 */
if(!isset($_POST['ag_id'])){
	
	$referer = $_SERVER['HTTP_REFERER'];
	
	$insertData = array();
	$insertData = $_POST;
	$insertData['ag_power'] = json_encode(array_filter($_POST['admin_power']));
	
	$insert = new Mysql(DB_HOST, DB_USER, DB_PWD, DB_NAME);
	$insert->selectTB('admin_group');	//选择资料表
	$insert->insertData($insertData, $referer);
	unset($insert);
	exit;
	
}


/**
 * 
 * 修改群组权限
 * 
 */
if(isset($_POST['ag_id']) && count($_POST) >= 3){
		
//	if(isset($_SERVER['HTTP_REFERER']) && $_SERVER['HTTP_REFERER'] != ''){
		$referer = $_SERVER['HTTP_REFERER'];
//	}else{
//		$referer = '';
//	}
	
	$ag_id = $_POST['ag_id'];
		
	$chkUser = new Mysql(DB_HOST, DB_USER, DB_PWD, DB_NAME);
	$chkUser->selectTB('admin_group');
	$chkUser->getData("*", 
					  "WHERE ag_id = '". $ag_id ."'");
	if($chkUser->total_row <= 0){
		header("Location: /index.php?err=3");
		unset($chkUser);
		exit;
	}
	unset($chkUser);
	
	$updateData = array();
	$updateData = $_POST;
	$updateData['ag_power'] = json_encode(array_filter($_POST['admin_power']));
	
	$update = new Mysql(DB_HOST, DB_USER, DB_PWD, DB_NAME);
	$update->selectTB('admin_group');	//选择资料表
	$update->updateData($updateData, 
						"WHERE ag_id = '". $ag_id ."'", 
						$referer);
	unset($update);
	exit;
	
}




/**
 * 
 * 删除群组
 * 
 */
if(isset($_POST['ag_id']) && count($_POST) == 1){
	
	$referer = $_SERVER['HTTP_REFERER'];
	
	$chkUser = new Mysql(DB_HOST, DB_USER, DB_PWD, DB_NAME);
	$chkUser->selectTB('admin_group');
	$chkUser->getData("*", 
					  "WHERE ag_id = '". $_POST['ag_id'] ."'");
	if($chkUser->total_row <= 0){
		header("Location: /index.php?err=3");
		unset($chkUser);
		exit;
	}
	unset($chkUser);
	
	$del = new Mysql(DB_HOST, DB_USER, DB_PWD, DB_NAME);
	$del->selectTB('admin_group');	//选择资料表
	$del->deleteData("WHERE ag_id = '". $_POST['ag_id'] ."'",
					 $referer);
	
	unset($del);
	exit;
	
}


//
//
//echo "<pre>";
//print_r($_POST);
//echo json_encode($_POST['admin_power']);
//
//
//
//$aa = array_filter($_POST['admin_power']);
//echo "<br />";
//echo json_encode($aa);




?>