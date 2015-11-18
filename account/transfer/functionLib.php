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

$langAry      = $html->lang;
$userIp       = $html->WebLogin->getUserIp();
$nowLoginData = (array)$html->WebLogin;

//下期占成設定
$dateAry       = $html->dateArray();
$count         = count($dateAry['start']);
$startDate     = $dateAry['start'][$count-2];//預設查詢最新
$endDate       = $dateAry['end'][$count-1];
$startDateTime = $startDate.' '.$html->StartTime;
$endDateTime   = $endDate.' '.$html->EndTime;

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
/**
 * 更新會員點數
 */
function refreshPoints(){
	if(empty($_POST['id'])){
		$sendAry = array();
		$sendAry['systemErr'] = 'window.location = "/index.php?err=3&msg=noid";';
		echo json_encode($sendAry);
		exit;
	}
	$id = $_POST['id'];
	include_once $_SERVER['DOCUMENT_ROOT'].'/class/Bank.class.php';
	include_once $_SERVER['DOCUMENT_ROOT'].'/class/PDO_DB.class.php';

	$bank = new Bank();
	$db   = new PDO_DB(DB_HOST, DB_USER, DB_PWD, DB_NAME);
	$db->selectTB('A_Member');
	$db->getData("*",
	"LEFT JOIN MemberFinance2 ON (MemberFinance2.MemberId = A_Member.MemberID)
	 WHERE A_Member.MemberID = '".$id."' AND MemberFinance2.PointType = '".$bank->MainPointType."'");
	$db->execute();
	if($db->total_row < 0){
		$sendAry = array();
		$sendAry['systemErr'] = 'alert("No Data");';
		echo json_encode($sendAry);
		exit;
	}
	$points = ($db->row['Points']/$bank->MoneyPointRate);
	$sendAry = array();
	$sendAry['result'] = $points;
	echo json_encode($sendAry);
	unset($bank, $db);
	exit;
}


/**
 * 对會員扣点
 */
function minusMemPoints(){
	$html   = new Template(); 
	$points = '';
	$acc    = '';
	if(empty($_POST['id'])){
		$sendAry['systemErr'] = 'window.location = "/index.php?err=3&msg=noid";';
		echo json_encode($sendAry);
		exit;
	}
	//取得样版中的样版资料，作为次样版
	$subTemp = $html->getFile($_SERVER['DOCUMENT_ROOT']."/tpl/account/transfer/minusMemPoints.html");

	$db = new PDO_DB(DB_HOST, DB_USER, DB_PWD, DB_NAME);
	$db->selectTB('A_Member');
	$db->getData("*",
	"LEFT JOIN MemberFinance2 ON(A_Member.MemberID = MemberFinance2.MemberId)
	 LEFT JOIN Currency ON (A_Member.MemberCurrency = Currency.code)
	 WHERE A_Member.MemberID = '".$_POST['id']."' AND MemberFinance2.PointType = 'Game'");
	$db->execute();
	if($db->total_row > 0){
		$db->row['Points'] = $db->row['Points']/$html->MoneyPointRate;
		$html->publicTemp  = $html->regexReplace($db->row, $html->publicTemp, '<!--__', '__-->');
	}

	//欲取代的内容
	$compilerAry = array();
	$compilerAry['__points__'] = $html->WebLogin->points;//自定义取代的内容
	
	//重新组合页面
	$main = $html->compiler($compilerAry);
	
	$sendAry = array();
	$sendAry['result'] = $main;
	
	echo json_encode($sendAry);
	unset($html, $db);
	exit;
}

/**
 * 管理员对代理扣点
 */
function minusAgentPoints(){
	$html = new Template(); 
	$points = '';
	$acc    = '';
	if(!empty($_POST['id'])){
		$db = new PDO_DB(DB_HOST, DB_USER, DB_PWD, DB_NAME);
		$db->selectTB('Admin');
		$db->getData("*","WHERE admin_id = '".$_POST['id']."'");
		$db->execute();
		if($db->total_row > 0){
			$points = $db->row['points'];
			$points = number_format($points);
			$acc    = $db->row['admin_acc'];
		}
	}

	//取得样版中的样版资料，作为次样版
	$subTemp = $html->getFile($_SERVER['DOCUMENT_ROOT']."/tpl/account/transfer/minusAgentPoints.html");
	//欲取代的内容
	$compilerAry = array();
	$compilerAry['__admin_acc__'] = $acc;//自定义取代的内容
	$compilerAry['__points__']    = $points;//自定义取代的内容
	
	//重新组合页面
	$main = $html->compiler($compilerAry);
	
	$sendAry = array();
	$sendAry['result'] = $main;
	
	echo json_encode($sendAry);
	unset($html, $db);
	exit;
}

/**
 * 替会员补点
 */
function plusMemPoints(){
	$html   = new Template(); 
	$points = '';
	$acc    = '';
	if(empty($_POST['id'])){
		$sendAry['systemErr'] = 'window.location = "/index.php?err=3&msg=noid";';
		echo json_encode($sendAry);
		exit;
	}
	//取得样版中的样版资料，作为次样版
	$subTemp = $html->getFile($_SERVER['DOCUMENT_ROOT']."/tpl/account/transfer/plusMemPoints.html");
	$db = new PDO_DB(DB_HOST, DB_USER, DB_PWD, DB_NAME);
	$db->selectTB('A_Member');
	$db->getData("*",
	"LEFT JOIN MemberFinance2 ON(A_Member.MemberID = MemberFinance2.MemberId)
	 LEFT JOIN Currency ON (A_Member.MemberCurrency = Currency.code)
	 WHERE A_Member.MemberID = '".$_POST['id']."' AND MemberFinance2.PointType = 'Game'");
	$db->execute();
	if($db->total_row > 0){
		$db->row['Points'] = $db->row['Points']/$html->MoneyPointRate;
		$html->publicTemp  = $html->regexReplace($db->row, $html->publicTemp, '<!--__', '__-->');
	}
	//欲取代的内容
	$compilerAry = array();
	$compilerAry['__points__']      = $html->WebLogin->points;//自定义取代的内容
	$compilerAry['__MAXMEMMONEY__'] = MAXMEMMONEY;//20150712-新增遊戲平台幣上限
	
	//重新组合页面
	$main = $html->compiler($compilerAry);
	
	$sendAry = array();
	$sendAry['result'] = $main;
	
	echo json_encode($sendAry);
	unset($html);
	exit;
}

/**
 * 代理商补点
 */
function plusAgentPoints(){
	$html = new Template(); 
	$points = '';
	$acc    = '';
	if(!empty($_POST['id'])){
		$db = new PDO_DB(DB_HOST, DB_USER, DB_PWD, DB_NAME);
		$db->selectTB('Admin');
		$db->getData("*","WHERE admin_id = '".$_POST['id']."'");
		$db->execute();
		if($db->total_row > 0){
			$points = $db->row['points'];
			$points = number_format($points);
			$acc    = $db->row['admin_acc'];
		}
	}
	
	//取得样版中的样版资料，作为次样版
	$subTemp   = $html->getFile($_SERVER['DOCUMENT_ROOT']."/tpl/account/transfer/plusAgentPoints.html");
	//欲取代的内容
	$compilerAry = array();
	$compilerAry['__admin_acc__'] = $acc;//自定义取代的内容
	$compilerAry['__points__']    = $points;//自定义取代的内容
	
	//重新组合页面
	$main = $html->compiler($compilerAry);
	
	$sendAry = array();
	$sendAry['result'] = $main;
	
	echo json_encode($sendAry);
	unset($html);
	exit;
}
?>