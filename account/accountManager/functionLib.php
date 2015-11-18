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

$expect = ['alarmSingle', 'alarmMem', 'disAlarmSingle', 'disAlarmMem'];

if(empty($_POST['type'])){
	$sendAry = array();
	$sendAry['systemErr'] = 'alert("No Data。");';
	echo json_encode($sendAry);
	exit;
}
if(!function_exists($_POST['type']) && !in_array($_POST['type'], $expect)){
	$sendAry = array();
	$sendAry['systemErr'] = 'alert("No Data(type)。");';
	echo json_encode($sendAry);
	exit;
}
//同一頁面呼叫
if(in_array($_POST['type'], ['alarmSingle', 'alarmMem', 'disAlarmSingle', 'disAlarmMem'])){
	confirm();
}else{
	$_POST['type']();
}
exit;

/**
 * 驗證Form
 */
function validate(){
	global $langAry;
	foreach ($_POST as $key => $value) {
		switch ($key) {
			case 'MemberAccount':
				$db = new PDO_DB(DB_HOST, DB_USER, DB_PWD, DB_NAME);
				//有id必須忽略此id暱稱
				$sql = '';
				if(!empty($_POST['id'])){
					$sql = " AND MemberID != '".$_POST['id']."'";
				}
				//是否有重複暱稱問題
				$db->selectTB('A_Member');
				$db->getData("MemberID", "WHERE MemberAccount = '".$value."'".$sql);
				$db->execute();
				if($db->total_row > 0){
					unset($db);
					echo '"'.$langAry['ERRAccountRepeat'].'"';
					exit;
				}
				unset($db);
				echo 'true';
				exit;
			case 'NickName':
				$db = new PDO_DB(DB_HOST, DB_USER, DB_PWD, DB_NAME);
				//有id必須忽略此id暱稱
				$sql = '';
				if(!empty($_POST['id'])){
					$sql = " AND MemberID != '".$_POST['id']."'";
				}
				//是否有重複暱稱問題
				$db->selectTB('A_Member');
				$db->getData("MemberID", "WHERE NickName = '".$value."'".$sql);
				$db->execute();
				if($db->total_row > 0){
					unset($db);
					echo '"'.$langAry['ERRNickNameRepeat'].'"';
					exit;
				}
				unset($db);
				echo 'true';
				exit;
			case 'admin_acc':
				$db = new PDO_DB(DB_HOST, DB_USER, DB_PWD, DB_NAME);
				//是否有重複帳號問題
				$db->selectTB('Admin');
				$db->getData("admin_id", "WHERE admin_acc = '".$value."'");
				$db->execute();
				if($db->total_row > 0){
					unset($db);
					echo '"'.$langAry['ERRAccountRepeat'].'"';
					exit;
				}
				unset($db);
				echo 'true';
				exit;
		}
	}
	echo 'true';
	exit;
}

/**
*  取得用户IP位置
*/
function getUserIp(){
	if(!empty($_SERVER["HTTP_CLIENT_IP"])){
		$cip = $_SERVER["HTTP_CLIENT_IP"];
	}else if(!empty($_SERVER["HTTP_X_FORWARDED_FOR"])){
		$cip = $_SERVER["HTTP_X_FORWARDED_FOR"];
	}else if(!empty($_SERVER["REMOTE_ADDR"])){
		$cip = $_SERVER["REMOTE_ADDR"];
	}else{
		$cip = "error!";
	}
	return $cip;
}

/**
 * 檢查警報帳號
 */
function chkAlarmAccount(){
	global $langAry;
	global $nowLoginData;

	$sendAry = [];
	if(!in_array($nowLoginData['ag_id'], ['1', '2'])){
		$_SESSION['err'] = '3';
		header('location: index.php');
		exit;
	}
	if(empty($_POST['alarmAccount'])){
		$sendAry['systemErr'] = $langAry['ERRNoAccount'];
		echo json_encode($sendAry);
		exit;
	}
	$alarmAccount = $_POST['alarmAccount'];

	$db = new PDO_DB(DB_HOST, DB_USER, DB_PWD, DB_NAME);
	$db->selectTB('Admin');
	$db->getData("*","WHERE admin_acc = '".$alarmAccount."'");
	$db->execute();
	if($db->total_row <= 0){
		$sendAry['systemErr'] = $langAry['ERRNoAccount'];
		echo json_encode($sendAry);
		exit;
	}
	$alarmAccData = $db->row;
	unset($db);

	$sendAry['result'] = true;
	$sendAry['alarmType'] = $alarmAccData['alarmType'];
	echo json_encode($sendAry);
	exit;
}

/**
 * 檢查上層帳號
 */
function chkUpAccount()
{
	global $langAry;
	global $startDate;
	global $endDate;
	global $nowLoginData;

	$sendAry = [];
	if($nowLoginData['ag_id'] != '1'){
		$_SESSION['err'] = '3';
		header('location: index.php');
		exit;
	}
	if(empty($_POST['upAccount'])){
		$sendAry['systemErr'] = $langAry['ERRUpAccount'];
		echo json_encode($sendAry);
		exit;
	}
	$upAccount = $_POST['upAccount'];

	$db = new PDO_DB(DB_HOST, DB_USER, DB_PWD, DB_NAME);
	$db->selectTB('Admin');
	$db->getData("*","WHERE admin_acc = '".$upAccount."'");
	$db->execute();
	if($db->total_row <= 0){
		$sendAry['systemErr'] = $langAry['ERRNoUpAccount'];
		echo json_encode($sendAry);
		exit;
	}
	$upAdminData = $db->row;

	$commissionRate = 0;
	$ReturnRate     = 0;

	if(in_array($upAdminData['ag_id'], ['1'])){
		$sendAry['systemErr'] = $langAry['ERRUpAccountAgain'];
		echo json_encode($sendAry);
		exit;
	}
	$Points = new Points();
	$upAdminData = $Points->getAdminRate($upAdminData, $startDate, $endDate);//自己返水占成率
	unset($db, $Points);

	$sendAry['result'] = true;
	$sendAry['upAdminData'] = [
		'commission' => $upAdminData['commissionRate'],
		'return'     => $upAdminData['ReturnRate'],
		'point'      => number_format($upAdminData['points'])
	];
	echo json_encode($sendAry);
	exit;
}

/**
 * 警報處理-可輸入帳號頁面
 */
function alarm(){
	$html = new Template(); 
	$subTemp = $html->getFile($_SERVER['DOCUMENT_ROOT']."/tpl/account/accountManager/alarmAgent.html");
	$html->publicTemp = $subTemp;
	
	//重新组合页面
	$main = $html->compiler();
	
	$sendAry = array();
	$sendAry['result'] = $main;
	
	echo json_encode($sendAry);
	unset($html, $db);
	exit;
}

/**
 * 警報處理-單一代理
 * 解除警報處理-單一代理
 * 警報處理-單一會員
 * 解除警報處理-單一會員
 */
function confirm(){
	$html = new Template(); 
	$subTemp = $html->getFile($_SERVER['DOCUMENT_ROOT']."/tpl/account/accountManager/confirmTemp.html");
	$html->publicTemp = $subTemp;
	
	//重新组合页面
	$main = $html->compiler();
	
	$sendAry = array();
	$sendAry['result'] = $main;
	
	echo json_encode($sendAry);
	unset($html, $db);
	exit;
}

/**
 * 會員刪除
 */
function delMemData()
{
	$sendAry = [];
	if(empty($_POST['id'])){
		$sendAry['systemErr'] = 'window.location = "/index.php?err=3&msg=noid";';
		echo json_encode($sendAry);
		exit;
	}
	$id   = $_POST['id'];
	$html = new Template(); 
	$db   = new PDO_DB(DB_HOST, DB_USER, DB_PWD, DB_NAME);
	$db->selectTB('A_Member');
	$db->getData("*","WHERE MemberID = '".$id."'");
	$db->execute();
	if($db->total_row <= 0){
		$sendAry['systemErr'] = 'window.location = "/index.php?err=3&msg=norow";';
		echo json_encode($sendAry);
		exit;
	}
	$subTemp  = $html->getFile($_SERVER['DOCUMENT_ROOT']."/tpl/account/accountManager/delAccount.html");
	$mergeAry = $db->row;

	//取得样版中的样版资料，作为次样版
	$subTemp = $html->regexReplace($mergeAry, $subTemp, '<!--__', '__-->');
	$html->publicTemp = $subTemp;
	
	//重新组合页面
	$main = $html->compiler();
	
	$sendAry = array();
	$sendAry['result'] = $main;
	
	echo json_encode($sendAry);
	unset($html, $db);
	exit;
}

/**
 * 代理刪除
 */
function delAgentData()
{
	$sendAry = [];
	if(empty($_POST['id'])){
		$sendAry['systemErr'] = 'window.location = "/index.php?err=3&msg=noid";';
		echo json_encode($sendAry);
		exit;
	}
	$id   = $_POST['id'];
	$html = new Template(); 
	$db   = new PDO_DB(DB_HOST, DB_USER, DB_PWD, DB_NAME);
	$db->selectTB('Admin');
	$db->getData("*","WHERE admin_id = '".$id."'");
	$db->execute();
	if($db->total_row <= 0){
		$sendAry['systemErr'] = 'window.location = "/index.php?err=3&msg=norow";';
		echo json_encode($sendAry);
		exit;
	}
	$point = new Points($html->WebLogin);
	$point->produceLink($db->row);
	if(!$point->allowSearch){
		$sendAry['systemErr'] = 'alert("'.$html->lang['IndexErrorMsg8'].'");';
		echo json_encode($sendAry);
		exit;
	}

	$subTemp  = $html->getFile($_SERVER['DOCUMENT_ROOT']."/tpl/account/accountManager/delAccount.html");
	$mergeAry = $db->row;

	//取得样版中的样版资料，作为次样版
	$subTemp = $html->regexReplace($mergeAry, $subTemp, '<!--__', '__-->');
	$html->publicTemp = $subTemp;
	
	//重新组合页面
	$main = $html->compiler();
	
	$sendAry = array();
	$sendAry['result'] = $main;
	
	echo json_encode($sendAry);
	unset($html, $db);
	exit;
}

/**
 * 會員解除封鎖
 */
function unblockMemData()
{
	$sendAry = [];
	if(empty($_POST['id'])){
		$sendAry['systemErr'] = 'window.location = "/index.php?err=3&msg=noid";';
		echo json_encode($sendAry);
		exit;
	}
	$id   = $_POST['id'];
	$html = new Template(); 
	$db   = new PDO_DB(DB_HOST, DB_USER, DB_PWD, DB_NAME);
	$db->selectTB('A_Member');
	$db->getData("*","WHERE MemberID = '".$id."'");
	$db->execute();
	if($db->total_row <= 0){
		$sendAry['systemErr'] = 'window.location = "/index.php?err=3&msg=norow";';
		echo json_encode($sendAry);
		exit;
	}
	$subTemp  = $html->getFile($_SERVER['DOCUMENT_ROOT']."/tpl/account/accountManager/unblockAccount.html");
	$mergeAry = $db->row;

	//取得样版中的样版资料，作为次样版
	$subTemp = $html->regexReplace($mergeAry, $subTemp, '<!--__', '__-->');
	$html->publicTemp = $subTemp;
	
	//重新组合页面
	$main = $html->compiler();
	
	$sendAry = array();
	$sendAry['result'] = $main;
	
	echo json_encode($sendAry);
	unset($html, $db);
	exit;
}

/**
 * 代理解除封鎖
 */
function unblockAgentData()
{
	$sendAry = [];
	if(empty($_POST['id'])){
		$sendAry['systemErr'] = 'window.location = "/index.php?err=3&msg=noid";';
		echo json_encode($sendAry);
		exit;
	}
	$id   = $_POST['id'];
	$html = new Template(); 
	$db   = new PDO_DB(DB_HOST, DB_USER, DB_PWD, DB_NAME);
	$db->selectTB('Admin');
	$db->getData("*","WHERE admin_id = '".$id."'");
	$db->execute();
	if($db->total_row <= 0){
		$sendAry['systemErr'] = 'window.location = "/index.php?err=3&msg=norow";';
		echo json_encode($sendAry);
		exit;
	}
	$point = new Points($html->WebLogin);
	$point->produceLink($db->row);
	if(!$point->allowSearch){
		$sendAry['systemErr'] = 'alert("'.$html->lang['IndexErrorMsg8'].'");';
		echo json_encode($sendAry);
		exit;
	}
	$subTemp  = $html->getFile($_SERVER['DOCUMENT_ROOT']."/tpl/account/accountManager/unblockAccount.html");
	$mergeAry = $db->row;

	//取得样版中的样版资料，作为次样版
	$subTemp = $html->regexReplace($mergeAry, $subTemp, '<!--__', '__-->');
	$html->publicTemp = $subTemp;
	
	//重新组合页面
	$main = $html->compiler();
	
	$sendAry = array();
	$sendAry['result'] = $main;
	
	echo json_encode($sendAry);
	unset($html, $db);
	exit;
}

/**
 * 會員停權
 */
function stopMemData()
{
	$sendAry = [];
	if(empty($_POST['id'])){
		$sendAry['systemErr'] = 'alert("No ID");';
		// $sendAry['systemErr'] = 'window.location = "/index.php?err=3&msg=noid";';
		echo json_encode($sendAry);
		exit;
	}
	$id   = $_POST['id'];
	$html = new Template(); 
	$db   = new PDO_DB(DB_HOST, DB_USER, DB_PWD, DB_NAME);
	$db->selectTB('A_Member');
	$db->getData("*","WHERE MemberID = '".$id."'");
	$db->execute();
	if($db->total_row <= 0){
		$sendAry['systemErr'] = 'window.location = "/index.php?err=3&msg=norow";';
		echo json_encode($sendAry);
		exit;
	}
	$subTemp  = $html->getFile($_SERVER['DOCUMENT_ROOT']."/tpl/account/accountManager/stopAccount.html");
	$mergeAry = $db->row;

	//取得样版中的样版资料，作为次样版
	$subTemp = $html->regexReplace($mergeAry, $subTemp, '<!--__', '__-->');
	$html->publicTemp = $subTemp;
	
	//重新组合页面
	$main = $html->compiler();
	
	$sendAry = array();
	$sendAry['result'] = $main;
	
	echo json_encode($sendAry);
	unset($html, $db);
	exit;
}

/**
 * 代理停權
 */
function stopAgentData()
{
	$sendAry = [];
	if(empty($_POST['id'])){
		$sendAry['systemErr'] = 'window.location = "/index.php?err=3&msg=noid";';
		echo json_encode($sendAry);
		exit;
	}
	$id   = $_POST['id'];
	$html = new Template(); 
	$db   = new PDO_DB(DB_HOST, DB_USER, DB_PWD, DB_NAME);
	$db->selectTB('Admin');
	$db->getData("*","WHERE admin_id = '".$id."'");
	$db->execute();
	if($db->total_row <= 0){
		$sendAry['systemErr'] = 'window.location = "/index.php?err=3&msg=norow";';
		echo json_encode($sendAry);
		exit;
	}
	$point = new Points($html->WebLogin);
	$point->produceLink($db->row);
	if(!$point->allowSearch){
		$sendAry['systemErr'] = 'alert("'.$html->lang['IndexErrorMsg8'].'");';
		echo json_encode($sendAry);
		exit;
	}
	$subTemp  = $html->getFile($_SERVER['DOCUMENT_ROOT']."/tpl/account/accountManager/stopAccount.html");
	$mergeAry = $db->row;

	//取得样版中的样版资料，作为次样版
	$subTemp = $html->regexReplace($mergeAry, $subTemp, '<!--__', '__-->');
	$html->publicTemp = $subTemp;
	
	//重新组合页面
	$main = $html->compiler();
	
	$sendAry = array();
	$sendAry['result'] = $main;
	
	echo json_encode($sendAry);
	unset($html, $db);
	exit;
}

/**
 * 修改會員資料
 */
function updMemData(){
	$sendAry = array();
	if(empty($_POST['id'])){
		$sendAry['systemErr'] = 'window.location = "/index.php?err=3&msg=noid";';
		echo json_encode($sendAry);
		exit;
	}
	$id   = $_POST['id'];
	$html = new Template(); 
	$db   = new PDO_DB(DB_HOST, DB_USER, DB_PWD, DB_NAME);
	$db->selectTB('A_Member');
	$db->getData("A_Member.*, Currency.title","LEFT JOIN Currency ON (A_Member.MemberCurrency = Currency.code) WHERE MemberID = '".$id."'");
	$db->execute();
	if($db->total_row <= 0){
		$sendAry['systemErr'] = 'window.location = "/index.php?err=3&msg=norow";';
		echo json_encode($sendAry);
		exit;
	}
	$mergeAry                 = $db->row;
	$mergeAry['upReturnRate'] = 0;
	$mergeAry['ReturnRate']   = 0;
	//下期占成設定
	// global $startDate;
	// global $endDate;
	// global $startDateTime;
	// global $endDateTime;
	// $mergeAry['date'] = $startDateTime .' ～ '.$endDateTime;

	$mainTemp = $html->getFile($_SERVER['DOCUMENT_ROOT']."/tpl/account/accountManager/updMemData.html");

	// //自己返水占成率
	// $Points   = new Points();
	// $mergeAry = $Points->getAdminRate($mergeAry, $startDate, $endDate, TRUE);

	// //查詢上層資料
	// $db->selectTB('Admin');
	// $db->getData("*","WHERE admin_id = '".$mergeAry['UpAdmin_id']."'");
	// $db->execute();
	// if($db->total_row <= 0){
	// 	$sendAry['systemErr'] = 'window.location = "/index.php?err=3&msg=nouprow";';
	// 	echo json_encode($sendAry);
	// 	unset($db);
	// 	exit;
	// }
	// $upAdminData = $db->row;
	// $upAdminData = $Points->getAdminRate($upAdminData, $startDate, $endDate);
	// $mergeAry['upReturnRate'] = $upAdminData['ReturnRate'];//上層返水率

	//語系
	foreach ($html->lang as $key => $value) {
		$mergeAry['Lang_'.$key] = $value;
	}

	// if($html->RateSettingSwitch == 'y'){
	// 	$mergeAry['ReturnRateTemp'] = $html->regexMatch($mainTemp, '<!--__ReturnRateStart', 'ReturnRateEnd__-->');
	// }else{
	// 	$mergeAry['ReturnRateTemp'] = $html->regexMatch($mainTemp, '<!--__OnlyReturnRateStart', 'OnlyReturnRateEnd__-->');
	// }
	// $mergeAry['ReturnRateTemp'] = $html->regexReplace($mergeAry, $mergeAry['ReturnRateTemp']);

	//現在金額
	$Points = 0;
	$db->selectTB('MemberFinance2');
	$db->getData("*","WHERE MemberId = '".$id."' AND PointType = 'Game'");
	$db->execute();
	if($db->total_row > 0){
		$Points = $db->row['Points']/$html->MoneyPointRate;
	}
	$mergeAry['Points'] = $Points;

	//取得样版中的样版资料，作为次样版
	$mainTemp = $html->regexReplace($mergeAry, $mainTemp, '<!--__', '__-->');
	$html->publicTemp = $mainTemp;
	//重新组合页面
	$main = $html->compiler();

	$sendAry = array();
	$sendAry['result'] = $main;
	
	echo json_encode($sendAry);
	unset($html, $db, $Points);
	exit;
}

/**
 * 修改管理員代理總控台資料
 */
function updAgentData(){
	$sendAry = array();
	if(empty($_POST['id'])){
		$sendAry['systemErr'] = 'window.location = "/index.php?err=3&msg=noid";';
		echo json_encode($sendAry);
		exit;
	}
	$id   = $_POST['id'];
	$html = new Template(); 
	$db   = new PDO_DB(DB_HOST, DB_USER, DB_PWD, DB_NAME);
	$db->selectTB('Admin');
	$db->getData("*","WHERE admin_id = '".$id."'");
	$db->execute();
	if($db->total_row <= 0){
		$sendAry['systemErr'] = 'window.location = "/index.php?err=3&msg=norow";';
		echo json_encode($sendAry);
		exit;
	}
	$mergeAry                   = $db->row;
	$mergeAry['commissionRate'] = 0;
	$mergeAry['ReturnRate']     = 0;
	$maxCommissionRate          = '0';
	$maxReturnRate              = '0';
	//下期占成設定
	global $startDate;
	global $endDate;
	global $startDateTime;
	global $endDateTime;
	$mergeAry['date'] = $startDateTime .' ～ '.$endDateTime;

	//自己返水占成率
	$Points   = new Points();
	$mergeAry = $Points->getAdminRate($mergeAry, $startDate, $endDate);

	$subTemp = $html->getFile($_SERVER['DOCUMENT_ROOT']."/tpl/account/accountManager/updAgentData.html");

	// 20150526-拿掉分紅顯示
	//20150701-開啟分紅設定
	//如果有上層帳號則檢查上層佔成數upCommissionRate
	if($mergeAry['upAdmin_id'] != NULL){
		$db->selectTB('Admin');
		$db->getData("*","WHERE admin_id = '".$mergeAry['upAdmin_id']."'");
		$db->execute();
		if($db->total_row <= 0){
			$sendAry['systemErr'] = 'alert("'.$html->lang['ERRNoUpAccount'].'");';
			echo json_encode($sendAry);
			exit;
		}
		$upAdminData = $db->row;
		$upAdminData = $Points->getAdminRate($mergeAry, $startDate, $endDate);
		$upAdminData['upAdmin_acc']      = $upAdminData['admin_acc'];
		$upAdminData['upCommissionRate'] = $db->row['commissionRate'];
		$upAdminData['upReturnRate']     = $db->row['ReturnRate'];

		$upAgentTemp = $html->regexMatch($subTemp, '<!--__upAdminDataStart', 'upAdminDataEnd__-->');
		$mergeAry['upAdminData'] = $html->regexReplace(($upAdminData+$html->lang), $upAgentTemp);
	}

	//下層最高佔成數
	$mergeAry = $Points->getDownMaxAdminRate($mergeAry, $startDate, $endDate);
	$maxCommissionRate = $mergeAry['maxCommissionRate'];
	$maxReturnRate     = $mergeAry['maxReturnRate'];

	//下層最高返水數-會員
	$mergeAry = $Points->getDownMaxMemRate($mergeAry, $startDate, $endDate);
	$maxReturnRate    = $mergeAry['maxReturnRate'];

	//語系
	foreach ($html->lang as $key => $value) {
		$mergeAry['Lang_'.$key] = $value;
	}

	//20150525-拿掉分紅設定
	//20150701-開啟分紅設定
	//總代跟管理員不能修改自己佔成
	if(!in_array($mergeAry['ag_id'], array("1", "2"))){
		if($html->RateSettingSwitch == 'y'){
			$mergeAry['commission'] = $html->regexMatch($subTemp, '<!--__commissionStart', 'commissionEnd__-->');
		}else{
			$mergeAry['commission'] = $html->regexMatch($subTemp, '<!--__OnlycommissionStart', 'OnlycommissionEnd__-->');
		}
		$mergeAry['commission'] = $html->regexReplace($mergeAry, $mergeAry['commission']);
	}

	//代理才顯示開放新增代理選項但只開放管理員及總控台變更
	// 20150701-開放無限層不需控管
	// if($mergeAry['ag_id'] == '3' && in_array($html->WebLogin->ag_id, ['1', '2', '3'])){
	// 	$ary = $mergeAry;
	// 	$ary['checked'] = $mergeAry['canAddAgent'] == 'y' ? 'checked="checked"' : '';
	// 	$mergeAry['canAddAgentTemp'] = $html->regexMatch($subTemp, '<!--__canAddAgentStart', 'canAddAgentEnd__-->');
	// 	$mergeAry['canAddAgentTemp'] = $html->regexReplace($ary, $mergeAry['canAddAgentTemp']);
	// }

	//開放管理員更改區域-ip
	if($html->WebLogin->ag_id == '1' && $mergeAry['ag_id'] == '2'){
		$ary = [];
		$ary['allowLoginIp'] = join("\n", explode(",", $mergeAry['allowLoginIp']));
		$mergeAry['allowLoginIpTemp'] = $html->regexMatch($subTemp, '<!--__allowIpStart', 'allowIpEnd__-->');
		$mergeAry['allowLoginIpTemp'] = $html->regexReplace($ary, $mergeAry['allowLoginIpTemp']);
	}

	//取得样版中的样版资料，作为次样版
	$subTemp = $html->regexReplace($mergeAry, $subTemp, '<!--__', '__-->');
	$html->publicTemp = $subTemp;
	
	//重新组合页面
	$main = $html->compiler();
	
	$sendAry = array();
	$sendAry['result'] = $main;
	
	echo json_encode($sendAry);
	unset($html, $db, $Points);
	exit;
}

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
	$subTemp = $html->getFile($_SERVER['DOCUMENT_ROOT']."/tpl/account/accountManager/minusMemPoints.html");

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
	$subTemp = $html->getFile($_SERVER['DOCUMENT_ROOT']."/tpl/account/accountManager/minusAgentPoints.html");
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
	$subTemp = $html->getFile($_SERVER['DOCUMENT_ROOT']."/tpl/account/accountManager/plusMemPoints.html");
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
	$subTemp   = $html->getFile($_SERVER['DOCUMENT_ROOT']."/tpl/account/accountManager/plusAgentPoints.html");
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

/**
 * 新增总代
 */
function addMember(){
	$html = new Template(); 
	
	//取得样版中的样版资料，作为次样版
	$subTemp   = $html->getFile($_SERVER['DOCUMENT_ROOT']."/tpl/account/accountManager/addMember.html");
	//欲取代的内容
	$compilerAry = array();
	
	//重新组合页面
	$main = $html->compiler($compilerAry);
	
	$sendAry = array();
	$sendAry['result'] = $main;
	
	echo json_encode($sendAry);
	unset($html);
	exit;
}

/**
 * 新增代理
 */
function addSubAgent(){
	$html = new Template(); 

	//取得样版中的样版资料，作为次样版
	$subTemp   = $html->getFile($_SERVER['DOCUMENT_ROOT']."/tpl/account/accountManager/addSubAgent.html");

	//欲取代的内容
	$compilerAry = array();
	$compilerAry['__admin_acc__']      = $html->WebLogin->admin_acc;//自定义取代的内容

	//重新组合页面
	$main = $html->compiler($compilerAry);
	
	$sendAry = array();
	$sendAry['result'] = $main;
	
	echo json_encode($sendAry);
	unset($html, $db);
	exit;
}

/**
 * 新增代理
 */
function addAgent(){
	$html = new Template(); 

	//取得样版中的样版资料，作为次样版
	$subTemp   = $html->getFile($_SERVER['DOCUMENT_ROOT']."/tpl/account/accountManager/addAgent.html");
	
	//下期占成設定
	global $startDate;
	global $endDate;
	global $startDateTime;
	global $endDateTime;

	$ag_id          = $html->WebLogin->ag_id;
	$commissionRate = 0;
	$ReturnRate     = 0;
	$nowAdminData   = (array)$html->WebLogin;

	//自己返水占成率
	$Points = new Points();
	$nowAdminData   = $Points->getAdminRate($nowAdminData, $startDate, $endDate);
	$commissionRate = $nowAdminData['commissionRate'].' %';//上層分紅率
	$ReturnRate     = $nowAdminData['ReturnRate'];//上層返水率
	$point          = number_format($nowAdminData['points']);

	//只有管理員跟總控台可編輯新增下級代理
	// 20150701-開放代理可無限新增
	$canAddAgentTemp = '';
	// if(in_array($ag_id, ['1', '2', '3'])){
	// 	$canAddAgentTemp = $html->regexMatch($subTemp, '<!--__canAddAgentStart', 'canAddAgentEnd__-->');
	// 	$canAddAgentTemp = $html->regexReplace($html->lang, $canAddAgentTemp, '__Lang_', '__');
	// }

	//只有管理員可使用填入上層帳號功能
	$upAccountTemp = $html->WebLogin->admin_acc;
	if(in_array($ag_id, ['1'])){
		$upAccountTemp  = $html->regexMatch($subTemp, '<!--__upAccountStart', 'upAccountEnd__-->');
		$upAccountTemp  = $html->regexReplace($html->lang, $upAccountTemp, '__Lang_', '__');
		$commissionRate = $html->lang['ERRUpAccount'];
		$point          = $html->lang['ERRUpAccount'];
		$ReturnRate     = 0;
	}
	//欲取代的内容
	$compilerAry = array();
	$compilerAry['__points__']          = $point;
	$compilerAry['__commissionRate__']  = $commissionRate;
	$compilerAry['__ReturnRate__']      = $ReturnRate;
	$compilerAry['__date__']            = $startDateTime .' ～ '.$endDateTime;
	$compilerAry['__upAccount__']       = $upAccountTemp;
	$compilerAry['__canAddAgentTemp__'] = $canAddAgentTemp;
	
	//重新组合页面
	$main = $html->compiler($compilerAry);
	
	$sendAry = array();
	$sendAry['result'] = $main;
	
	echo json_encode($sendAry);
	unset($html, $db, $Points);
	exit;
}

/**
 * 新增会员
 */
function addMem(){
	$html = new Template(); 
	
	//取得样版中的样版资料，作为次样版
	$subTemp   = $html->getFile($_SERVER['DOCUMENT_ROOT']."/tpl/account/accountManager/addMem.html");
	
	//下期占成設定
	global $startDate;
	global $endDate;
	global $startDateTime;
	global $endDateTime;
	$ag_id        = $html->WebLogin->ag_id;
	$nowAdminData = (array)$html->WebLogin;
	$point        = number_format($nowAdminData['points']);
	$ReturnRate   = 0;

	//只有管理員可使用填入上層帳號功能
	$upAccountTemp = $html->WebLogin->admin_acc;
	if(in_array($ag_id, ['1'])){
		$upAccountTemp  = $html->regexMatch($subTemp, '<!--__upAccountStart', 'upAccountEnd__-->');
		$upAccountTemp  = $html->regexReplace($html->lang, $upAccountTemp, '__Lang_', '__');
		$commissionRate = $html->lang['ERRUpAccount'];
		$point          = $html->lang['ERRUpAccount'];
		$ReturnRate     = 0;
	}

	//貨幣選項
	$currencyOptions = '';
	$db = new PDO_DB(DB_HOST, DB_USER, DB_PWD, DB_NAME);
	$db->selectTB('Currency');
	$db->getData("*", "WHERE enabled = 'y'");
	$db->execute();
	if($db->total_row > 0){
		do{
			$currencyOptions .= '<option data-rate="'.$db->row['rate'].'" value="'.$db->row['code'].'"'.($db->row['code'] == CURRENCY ? ' selected="selected"' : '').'>'.$db->row['title'].'('.$db->row['rate'].')</option>';
		}while($db->row = $db->fetch_assoc());
	}


	//欲取代的内容
	$compilerAry = array();
	$compilerAry['__upAccount__']       = $upAccountTemp;
	$compilerAry['__points__']          = $point;
	// $compilerAry['__ReturnRate__']   = $ReturnRate;
	$compilerAry['__date__']            = $startDateTime .' ~ '.$endDateTime;
	$compilerAry['__CurrencyOptions__'] = $currencyOptions;
	$compilerAry['__MAXMEMMONEY__']     = MAXMEMMONEY;//20150712-新增遊戲平台幣上限
	
	//重新组合页面
	$main = $html->compiler($compilerAry);
	
	$sendAry = array();
	$sendAry['result'] = $main;
	
	echo json_encode($sendAry);
	unset($html, $db);
	exit;
}
?>