<?php

/**
 * 
 * Ajax 表单验证
 * 
 * 
 */


include_once $_SERVER['DOCUMENT_ROOT'].'/class/WebLogin.class.php';

/* RECEIVE VALUE */
$fieldId		= $_GET['fieldId'];
$fieldValue		= $_GET['fieldValue'];

$fieldId($fieldId, $fieldValue);
exit;



function admin_acc($fieldId, $fieldValue){
	
	/* RETURN VALUE */
	$arrayToJs = array();
	$arrayToJs[0] = $fieldId;
	
	$chkUser = new Mysql(DB_HOST, DB_USER, DB_PWD, DB_NAME);
	$chkUser->selectTB('admin');
	$chkUser->getData("*",
						 "WHERE admin_acc='". $fieldValue ."'");
	if($chkUser->total_row <= 0){
		$arrayToJs[1] = true;
	}else{
		$arrayToJs[1] = false;
	}
	
	echo json_encode($arrayToJs);
	
}



?>