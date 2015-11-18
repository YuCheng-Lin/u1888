<?php
include_once $_SERVER['DOCUMENT_ROOT'].'/class/PDO_DB.class.php';
include_once $_SERVER['DOCUMENT_ROOT'].'/class/Language.class.php';
include_once 'MemberApi.class.php';

//測試POST
$_POST['MemberAccount'] = 'chtest2';
$_POST['code']          = 'a';
$_POST['sign']          = md5($_POST['MemberAccount'].'@*&'.$_POST['code']);
$_POST['ip']            = '127.0.0.1';

try {
	$MemberApi = new MemberApi();

	if(empty($_POST)){
		$MemberApi->response(['result'=>'fail']);
	}

	$MemberApi->setBaseData($_POST);//基本參數驗證
	$MemberApi->agentInfo();//檢查Agent Code
	$userInfo       = $MemberApi->userInfo();//返回驗證取得使用者資訊
	$enterPoints    = $userInfo['Points'];
	$upAdminData    = $MemberApi->upAdminInfo();
	//查無此帳號
	if(is_null($MemberApi->MemberData())){
		$MemberApi->register();//註冊此帳號
	}else{
		//有此帳號則必須將點數轉換成聯運商點數
		$MemberApi->AgentPointTrans();
	}
	//進入遊戲
	$MemberApi->play();
	dd($MemberApi->MemberData);
} catch (Exception $e) {
	if(DEBUG){
		die($e->getMessage());
	}elseif(is_object($MemberApi)){
		$MemberApi->response(['result'=>'fail']);
	}else{
		echo 'fail';
		exit;
	}
}

function dd($obj){
	echo '<pre>';
	print_r($obj);
	exit;
}
?>