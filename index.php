<?php
include_once $_SERVER['DOCUMENT_ROOT'].'/class/Template.class.php';
//载入基础样版
$html = new Template($_SERVER['DOCUMENT_ROOT']."/tpl/index.html", false);

if($html->WebLogin->getStatus() == '0' && empty($_SESSION['err']) && empty($_GET['err'])){
	header('location: /per_info/');
	exit;
}

$replaceData = array();

if(!empty($_SESSION['err']) || !empty($_GET['err'])){
	if(!empty($_SESSION['err'])){
		$err = $_SESSION['err'];
		unset($_SESSION['err']);
	}else{
		$err = $_GET['err'];
	}
	$errMsg	= 'No Message';
	switch($err){
		case '1':
			//未输入帐密
			$errMsg = $html->__systemConfig['__sendReady__'];
		break;
		
		case '2':
			//帐密错误
			$errMsg = $html->__systemConfig['__errAcc__'];
		break;
		
		case '3':
			//请依正常方式操作。
			$errMsg = $html->__systemConfig['__illegal__'];
		break;
		
		case '4':
			//使用者正在线上。
			$errMsg = $html->__systemConfig['__userOnline__'];
		break;
		
		case '5':
			//帐号停止使用。
			$errMsg = $html->__systemConfig['__noPermission__'];
		break;
		
		case '6':
			//本页无权限
			$errMsg = $html->__systemConfig['__noPagePermission__'];
		break;
		
		case '7':
			//總控台未填寫允許登入ip
			$errMsg = $html->lang['IndexErrorMsg9'];
		break;
		
		case '8':
			//總控台允許登入ip不符
			$errMsg = $html->lang['IndexErrorMsg10'];
		break;
		
		default:
		break;
	}
	$replaceData['__warning__']	= '<div class="alert alert-danger alert-dismissible" role="alert" id="fail">
			<button type="button" class="close" data-dismiss="alert">
				<span aria-hidden="true">&times;</span><span class="sr-only">Close</span>
			</button>'.$errMsg.'</div>';
	$html->WebLogin->logout();
}

if(!empty($_SESSION['logout'])){
	unset($_SESSION['logout']);
	//成功登出
	$replaceData['__warning__']	= '<div class="alert alert-success alert-dismissible" role="alert" id="fail">
			<button type="button" class="close" data-dismiss="alert">
				<span aria-hidden="true">&times;</span><span class="sr-only">Close</span>
			</button>'.$html->__systemConfig['__logOutSuccess__'].'</div>';
}

if(!empty($_SESSION['updPwdDone'])){
	unset($_SESSION['updPwdDone']);
	//成功登出
	$replaceData['__warning__']	= '<div class="alert alert-success alert-dismissible" role="alert" id="fail">
			<button type="button" class="close" data-dismiss="alert">
				<span aria-hidden="true">&times;</span><span class="sr-only">Close</span>
			</button>'.$html->__systemConfig['__updateSuccess__'].'</div>';
}

echo $html->compiler($replaceData);
unset($html);
exit;
?>