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
if(!isset($_POST['admin_id'])){
	
	$referer = $_SERVER['HTTP_REFERER'];
	
	$insertData = array();
	$insertData = $_POST;
	$insertData['admin_power'] = json_encode(array_filter($_POST['admin_power']));
	$insertData['admin_pwd'] = md5(trim($_POST['admin_pwd']));
	if(!isset($insertData['ag_id']) || $insertData['ag_id'] == ''){
		$insertData['ag_id'] = $nowAdmin_agid;
	}
	
	if(!empty($insertData['game_power_all'])){
		$insertData['game_power'] = 'all';
	}
	if(!empty($insertData['game_power'])){
		$insertData['game_power'] = json_encode($insertData['game_power']);
	}
	
	if(empty($insertData['game_power_all']) && empty($insertData['game_power'])){
		$insertData['game_power'] = '[]';
	}
	
	$insert = new Mysql(DB_HOST, DB_USER, DB_PWD, DB_NAME);
	$insert->selectTB('admin');	//选择资料表
	$insert->insertData($insertData, $referer);
	unset($insert);
	exit;
	
}




/**
 * 
 * 修改帐号
 * 
 */
if(isset($_POST['admin_id']) && count($_POST) > 3){
	
//	if(isset($_SERVER['HTTP_REFERER']) && $_SERVER['HTTP_REFERER'] != ''){
		$referer = $_SERVER['HTTP_REFERER'];
//	}else{
//		$referer = '';
//	}
	
	$chkUser = new Mysql(DB_HOST, DB_USER, DB_PWD, DB_NAME);
	$chkUser->selectTB('admin');
	$chkUser->getData("*", 
					  "WHERE admin_id = '". $_POST['admin_id'] ."'");
	if($chkUser->total_row <= 0){
		header("Location: /index.php?err=3");
		unset($chkUser);
		exit;
	}
	unset($chkUser);
	
	$updateData = array();
	$updateData = $_POST;
	$updateData['admin_power'] = json_encode(array_filter($_POST['admin_power']));

	$updatePwd	= false;
	if(isset($_POST['admin_pwd']) && $_POST['admin_pwd'] != ''){
		$updateData['admin_pwd'] = md5(trim($_POST['admin_pwd']));
		$updatePwd	= true;
	}
	
	if(!empty($updateData['game_power'])){
		$updateData['game_power'] = json_encode($updateData['game_power']);
	}
	if(!empty($updateData['game_power_all'])){
		$updateData['game_power'] = 'all';
	}
	
	if(empty($updateData['game_power_all']) && empty($updateData['game_power'])){
		$updateData['game_power'] = '[]';
	}

	$update = new Mysql(DB_HOST, DB_USER, DB_PWD, DB_NAME);
	$update->selectTB('admin');	//选择资料表
	
	if($updatePwd){
		$data	= array();
		$data['s_id']	= NULL;
		$update->updateData($data, "WHERE admin_id = '". $_POST['admin_id'] ."'");
	}
	
	$update->updateData($updateData, 
						"WHERE admin_id = '". $_POST['admin_id'] ."'", 
						$referer);
	unset($update);
	exit;
	
}




/**
 * 
 * 删除帐号
 * 
 */
if(isset($_POST['admin_id']) && count($_POST) == 1){
	
	$referer = $_SERVER['HTTP_REFERER'];
	
	$chkUser = new Mysql(DB_HOST, DB_USER, DB_PWD, DB_NAME);
	$chkUser->selectTB('admin');
	$chkUser->getData("*", 
					  "WHERE admin_id = '". $_POST['admin_id'] ."'");
	if($chkUser->total_row <= 0){
		header("Location: /index.php?err=3");
		unset($chkUser);
		exit;
	}
	unset($chkUser);
	
	$del = new Mysql(DB_HOST, DB_USER, DB_PWD, DB_NAME);
	$del->selectTB('admin');	//选择资料表
	$del->deleteData("WHERE admin_id = '". $_POST['admin_id'] ."'",
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