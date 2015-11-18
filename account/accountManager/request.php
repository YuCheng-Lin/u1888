<?php
/**
 * 帐户系统
 */
include_once $_SERVER['DOCUMENT_ROOT'].'/class/Template.class.php';
include_once $_SERVER['DOCUMENT_ROOT'].'/class/WebLogin.class.php';
include_once $_SERVER['DOCUMENT_ROOT'].'/class/Bank.class.php';
include_once $_SERVER['DOCUMENT_ROOT'].'/class/Points.class.php';
include_once $_SERVER['DOCUMENT_ROOT'].'/class/Admin.class.php';

if(empty($_SESSION['_admin_acc']) || empty($_SESSION['_admin_pwd'])){
	$_SESSION['err'] = '1';
	header("Location: /index.php");
	exit;
}

if(empty($_POST) || empty($_POST['action'])){
	$_SESSION['err'] = '3';
	header("Location: /index.php");
	exit;
}

$login = new Template('empty',false);
$login->WebLogin->chkLogin($_SESSION['_admin_acc'], $_SESSION['_admin_pwd']);
$status = $login->WebLogin->getStatus();
if($status != '0'){
	$_SESSION['err'] = $status;
	header("location: /index.php");
	exit;
}
//只有閱讀權限
if($login->WebLogin->nowPagePower == 'r'){
	$_SESSION['err'] = '6';
	header("location: /index.php");
	exit;
}
$nowLoginData           = (array)$login->WebLogin;
$nowAdmin_id            = $login->WebLogin->admin_id;
$nowAdmin_agid          = $login->WebLogin->ag_id;
$nowAdminPoints         = $login->WebLogin->points;
$nowAdminDownCount      = $login->WebLogin->downCount;
$nowAdminReturnRate     = $login->WebLogin->ReturnRate;
$nowAdminDownMemCount   = $login->WebLogin->downMemCount;
$nowAdminCommissionRate = $login->WebLogin->commissionRate;
$RateSettingSwitch      = $login->RateSettingSwitch;
$MaxCommissionRate      = $login->MaxCommissionRate;
$MaxReturnRate          = $login->MaxReturnRate;
$dateAry                = $login->dateArray();
$count                  = count($dateAry['start']);
//下期占成設定
$startDate = $dateAry['start'][$count-2];
$endDate   = $dateAry['end'][$count-1];
unset($login);

try{
	include_once $_SERVER['DOCUMENT_ROOT'].'/class/Language.class.php';
	$language = new Language();
	$langAry  = $language->getLanguage();
}catch(Exception $e){
}

switch ($action = $_POST['action']) {
	//Alarm黑名單-使用帳號名稱
	case $action == 'alarm': 
	//Alarm黑名單-單一帳號編號
	case $action == 'alarmSingle':
	case $action == 'disAlarmSingle':
		$referer = $_SERVER['HTTP_REFERER'];
		if($action == 'alarm'){
			if(empty($_POST['alarmAccount'] || empty($_POST['alarmType']))){
				breakoff($langAry['ERRNoID'], '/index.php?err=3&msg=noid');
				exit;
			}
			$sql = "WHERE admin_acc = '".$_POST['alarmAccount']."'";
			//選擇狀態
			$action = ($_POST['alarmType'] == 'y' ? $action : 'disAlarm');
		}
		if($action == 'alarmSingle' || $action == 'disAlarmSingle'){
			if(empty($_POST['id'])){
				breakoff($langAry['ERRNoID'], '/index.php?err=3&msg=noid');
				exit;
			}
			$sql = "WHERE admin_id = '".$_POST['id']."'";
		}
		$db = new PDO_DB(DB_HOST, DB_USER, DB_PWD, DB_NAME);
		$db->selectTB('Admin');
		$db->getData("*", $sql);
		$db->execute();
		if($db->total_row <= 0){
			breakoff($langAry['ERRNoAccount'], $referer);
			unset($db);
			exit;
		}
		if($db->total_row > 0){
			$result = false;
			$Admin  = new Admin();
			switch ($action) {
				case 'alarm': case 'alarmSingle':
					$result = $Admin->alarmAdmin($db->row['admin_id'], '1', $nowLoginData);
					break;
				case 'disAlarm': case 'disAlarmSingle':
					$result = $Admin->alarmAdmin($db->row['admin_id'], '0', $nowLoginData);
					break;
				default:
					breakoff('No method', $referer);
					break;
			}
			breakoff(($result ? $langAry['UpdateSuccess'] : $langAry['UpdateFail']), $referer);
			unset($Admin);
		}
		unset($db);
		exit;
	//Alarm黑名單-單一會員編號
	case $action == 'alarmMem':
	case $action == 'disAlarmMem':
		$referer = $_SERVER['HTTP_REFERER'];
		if(empty($_POST['id'])){
			breakoff($langAry['ERRNoID'], '/index.php?err=3&msg=noid');
			exit;
		}
		$db = new PDO_DB(DB_HOST, DB_USER, DB_PWD, DB_NAME);
		$db->selectTB('A_Member');
		$db->getData("*", "WHERE MemberID = '".$_POST['id']."'");
		$db->execute();
		if($db->total_row <= 0){
			breakoff($langAry['ERRNoAccount'], $referer);
			unset($db);
			exit;
		}
		if($db->total_row > 0){
			$result = false;
			$Admin  = new Admin();
			switch ($action) {
				case 'alarmMem':
					$result = $Admin->alarmMember($db->row['MemberID'], '1', $nowLoginData);
					break;
				case 'disAlarmMem':
					$result = $Admin->alarmMember($db->row['MemberID'], '0', $nowLoginData);
					break;
				default:
					breakoff('No method', $referer);
					break;
			}
			breakoff(($result ? $langAry['UpdateSuccess'] : $langAry['UpdateFail']), $referer);
			unset($Admin);
		}
		unset($db);
		exit;
	//刪除會員
	case $action == 'delMemData':
		$referer = $_SERVER['HTTP_REFERER'];
		//20150715-新增只有管理員有刪除功能
		if($nowAdmin_agid != '1'){
			breakoff($langAry['IndexErrorMsg6'], '/index.php?err=6');
			exit;
		}
		if(empty($_POST['id'])){
			breakoff($langAry['ERRNoID'], '/index.php?err=3&msg=noid');
			exit;
		}
		$id     = $_POST['id'];
		$note   = empty($_POST['note']) ? '' : $_POST['note'];
		$admin  = new Admin();
		$result = $admin->delMember($id, $note, $nowLoginData);
		if($result){
			breakoff($langAry['DelSuccess'], $referer);
		}else{
			breakoff($langAry['DelFail'], $referer);
		}
		unset($db);
		exit;
	//代理刪除整條代理線
	case $action == 'delAgentData':
		$referer = $_SERVER['HTTP_REFERER'];
		//20150715-新增只有管理員有刪除功能
		if($nowAdmin_agid != '1'){
			breakoff($langAry['IndexErrorMsg6'], '/index.php?err=6');
			exit;
		}
		if(empty($_POST['id'])){
			breakoff($langAry['ERRNoID'], '/index.php?err=3&msg=noid');
			exit;
		}
		$id     = $_POST['id'];
		$note   = empty($_POST['note']) ? '' : $_POST['note'];
		$admin  = new Admin();
		$result = $admin->delAdmin($id, $note, $nowLoginData);
		if($result){
			breakoff($langAry['DelSuccess'].' '.$langAry['Group3'].'：'.$admin->delAdminCount.' '.$langAry['GroupMem'].'：'.$admin->delMemCount, $referer);
		}else{
			breakoff($langAry['DelFail'], $referer);
		}
		unset($db);
		exit;
	//會員解除封鎖
	case $action == 'unblockMemData':
		$referer = $_SERVER['HTTP_REFERER'];
		if(empty($_POST['id'])){
			breakoff($langAry['ERRNoID'], '/index.php?err=3&msg=noid');
			exit;
		}
		$id     = $_POST['id'];
		$note   = empty($_POST['note']) ? '' : $_POST['note'];
		$admin  = new Admin();
		$result = $admin->unblockMember($id, $note, $nowLoginData);
		if($result){
			breakoff($langAry['UpdateSuccess'], $referer);
		}else{
			breakoff($langAry['UpdateFail'], $referer);
		}
		unset($db);
		exit;
	//代理解除封鎖
	case $action == 'unblockAgentData':
		$referer = $_SERVER['HTTP_REFERER'];
		if(empty($_POST['id'])){
			breakoff($langAry['ERRNoID'], '/index.php?err=3&msg=noid');
			exit;
		}
		$id     = $_POST['id'];
		$note   = empty($_POST['note']) ? '' : $_POST['note'];
		$admin  = new Admin();
		$result = $admin->unblockAdmin($id, $note, $nowLoginData);
		if($result){
			breakoff($langAry['UpdateSuccess'], $referer);
		}else{
			breakoff($langAry['UpdateFail'], $referer);
		}
		unset($db);
		exit;
	//會員停權
	case $action == 'stopMemData':
		$referer = $_SERVER['HTTP_REFERER'];
		if(empty($_POST['id'])){
			breakoff($langAry['ERRNoID'], '/index.php?err=3&msg=noid');
			exit;
		}
		$id     = $_POST['id'];
		$note   = empty($_POST['note']) ? '' : $_POST['note'];
		$admin  = new Admin();
		$result = $admin->suspendMember($id, $note, $nowLoginData);
		if($result){
			breakoff($langAry['UpdateSuccess'], $referer);
		}else{
			breakoff($langAry['UpdateFail'], $referer);
		}
		unset($db);
		exit;
	//代理停權
	case $action == 'stopAgentData':
		$referer = $_SERVER['HTTP_REFERER'];
		if(empty($_POST['id'])){
			breakoff($langAry['ERRNoID'], '/index.php?err=3&msg=noid');
			exit;
		}
		$id     = $_POST['id'];
		$note   = empty($_POST['note']) ? '' : $_POST['note'];
		$admin  = new Admin();
		$result = $admin->suspendAdmin($id, $note, $nowLoginData);
		if($result){
			breakoff($langAry['UpdateSuccess'], $referer);
		}else{
			breakoff($langAry['UpdateFail'], $referer);
		}
		unset($db);
		exit;
	//修改會員
	case ($action == 'updMemData'):
		$referer = $_SERVER['HTTP_REFERER'];
		if(empty($_POST['id'])){
			breakoff($langAry['ERRNoID'], '/index.php?err=3&msg=noid');
			exit;
		}
		if(empty($_POST['NickName'])){
			breakoff($langAry['ERRNickName'], $referer);
			exit;
		}
		if(!empty($_POST['MemberPassword'])){
			if(empty($_POST['MemberPassword2'])){
				breakoff($langAry['ERRPassword3'], $referer);
				exit;
			}
			if($_POST['MemberPassword'] != $_POST['MemberPassword2']){
				breakoff($langAry['ERRPassword4'], $referer);
				exit;
			}
		}
		if(empty($_POST['MemberPassword'])){
			unset($_POST['MemberPassword'], $_POST['MemberPassword2']);
		}
		if(isset($_POST['ReturnRate']) && !preg_match('/^([0-9]){1,2}$/', $_POST['ReturnRate'])){
			breakoff($langAry['ERRReturnRate'], $referer);
			exit;
		}

		$updateData = $_POST;
		$id         = $_POST['id'];

		$db = new PDO_DB(DB_HOST, DB_USER, DB_PWD, DB_NAME);
		//是否有重複暱稱問題
		$db->selectTB('A_Member');
		$db->getData("MemberID", "WHERE NickName = '".$updateData['NickName']."' AND MemberID != '".$id."'");
		$db->execute();
		if($db->total_row > 0){
			breakoff($langAry['ERRNickNameRepeat'], $referer);
			exit;
		}
		$db->selectTB('A_Member');
		$db->getData("*", "WHERE MemberID = '".$id."'");
		$db->execute();
		if($db->total_row <= 0){
			breakoff($langAry['ERRNoAccount'], $referer);
			unset($db);
			exit;
		}
		$memData = $db->row;

		$db->selectTB('Admin');
		$db->getData("*","WHERE admin_id = '".$memData['UpAdmin_id']."'");
		$db->execute();
		if($db->total_row <= 0){
			breakoff($langAry['ERRNoUpAccount'], $referer);
			unset($db);
			exit;
		}
		$upAdminData = $db->row;
		$upAdminData['ReturnRate'] = 0;

		$allowManger = false;
		$Points      = new Points($nowLoginData);
		$Points->produceLink($upAdminData);
		$allowManger = $Points->allowSearch;
		unset($Points);
		//該登入者不允許變更
		if(!$allowManger){
			$_SESSION['err'] = '3';
			header('location: /index.php');
			exit;
		}

		//下期占成設定
		// $startDate = date('Y-m-d', strtotime('Monday -1 week '.$setTime));
		// $endDate   = date('Y-m-d', strtotime('Monday this week '.$setTime));
		try{
			//u1888不開放反點
			throw new Exception();

			//尚未開放更改
			if($RateSettingSwitch == 'n'){
				throw new Exception();
			}
			if(!isset($_POST['ReturnRate'])){
				breakoff($langAry['ERRReturnRate'], $referer);
				exit;
			}
			$db->selectTB('AdminRate');
			$db->getData("*",
			"WHERE ag_id = '".$upAdminData['ag_id']."'
			 AND acc_id = '".$upAdminData['admin_id']."'
			 AND startDate >= '".$startDate."'
			 AND endDate <= '".$endDate."'");
			$db->execute();
			if($db->total_row > 0){
				$upAdminData['ReturnRate'] = $db->row['ReturnRate'];//上層返水率
			}
			if($updateData['ReturnRate'] > $upAdminData['ReturnRate']){
				breakoff($langAry['ERRReturnRateOver'], $referer);
				unset($db);
				exit;
			}

			$db->selectTB('AdminRate');
			$db->getData("*",
			"WHERE ag_id = '0' AND acc_id = '".$memData['MemberID']."'
			 AND startDate >= '".$startDate."'
			 AND endDate <= '".$endDate."'");
			$db->execute();
			if($db->total_row <= 0){
				$setData = $updateData;
				$setData['ReturnRate']     = isset($setData['ReturnRate']) ? $setData['ReturnRate'] : 0;
				$setData['acc_id']         = $memData['MemberID'];
				$setData['ag_id']          = '0';
				$setData['startDate']      = $startDate;
				$setData['endDate']        = $endDate;

				$db->insertData($setData);
				$text = [
					'query' => $db->getCurrentQueryString(),
					'value' => $setData
				];
				$result = $db->execute();
				$text['result'] = $result;
				addAdminLogs($text, $nowAdmin_id, '[System]Insert Member Rate');
			}
			if($db->total_row > 0){
				$setData = array();
				$setData['ReturnRate'] = isset($updateData['ReturnRate']) ? $updateData['ReturnRate'] : 0;
				$setData['updtime']    = date('Y-m-d H:i:s');

				$db->updateData($setData, "WHERE ar_id = '".$db->row['ar_id']."'");
				$text = [
					'query' => $db->getCurrentQueryString(),
					'value' => $setData
				];
				$result = $db->execute();
				$text['result'] = $result;
				addAdminLogs($text, $nowAdmin_id, '[System]Update Member Rate');
			}
		}catch (Exception $e){
			if(isset($updateData['commissionRate'])){
				unset($updateData['commissionRate']);
			}
			if(isset($updateData['ReturnRate'])){
				unset($updateData['ReturnRate']);
			}
		}

		$db->selectTB('A_Member');
		$db->updateData($updateData, "WHERE MemberID = '".$id."'");
		$text = [
			'query' => $db->getCurrentQueryString(),
			'value' => $setData
		];
		$result = $db->execute();
		$text['result'] = $result;
		addAdminLogs($text, $nowAdmin_id, '[System]Update Member');
		if(is_string($result)){
			breakoff($langAry['UpdateFail'].'('.$result.')', $referer);
			unset($db);
			exit;
		}
		if(is_bool($result) && $result){
			breakoff($langAry['UpdateMemSucc'], $referer);
			unset($db, $bank);
			exit;
		}
		breakoff($langAry['UpdateFail'], $referer);
		unset($db);
		exit;
	//修改代理
	case ($action == 'updAgentData'):
		$referer = $_SERVER['HTTP_REFERER'];
		if(empty($_POST['id'])){
			breakoff($langAry['ERRNoID'], '/index.php?err=3&msg=noid');
			exit;
		}
		if(empty($_POST['admin_name'])){
			breakoff($langAry['ERRNickName'], $referer);
			exit;
		}
		if(!empty($_POST['admin_pwd'])){
			if(empty($_POST['admin_pwd2'])){
				breakoff($langAry['ERRPassword3'], $referer);
				exit;
			}
			if($_POST['admin_pwd'] != $_POST['admin_pwd2']){
				breakoff($langAry['ERRPassword4'], $referer);
				exit;
			}
		}
		if(empty($_POST['admin_pwd'])){
			unset($_POST['admin_pwd'], $_POST['admin_pwd2']);
		}
		if(isset($_POST['commissionRate']) && !preg_match('/^([0-9]){1,3}$/', $_POST['commissionRate'])){
			breakoff($langAry['ERRCommissionRate'], $referer);
			exit;
		}
		if(isset($_POST['ReturnRate']) && !preg_match('/^([0-9]){1,2}$/', $_POST['ReturnRate'])){
			breakoff($langAry['ERRReturnRate'], $referer);
			exit;
		}
		// 20150701-開放新增無限層代理
		//開放新增代理
		// if(empty($_POST['canAddAgent']) || $_POST['canAddAgent'] != 'y'){
		// 	$_POST['canAddAgent'] = 'n';
		// }

		$updateData = $_POST;
		$id         = $_POST['id'];

		//下期占成設定
		// $startDate = date('Y-m-d', strtotime('Monday -1 week '.$setTime));
		// $endDate   = date('Y-m-d', strtotime('Monday this week '.$setTime));

		$db = new PDO_DB(DB_HOST, DB_USER, DB_PWD, DB_NAME);
		$db->selectTB('Admin');
		$db->getData("*", "WHERE admin_id = '".$id."'");
		$db->execute();
		if($db->total_row <= 0){
			breakoff($langAry['ERRNoAccount'], $referer);
			unset($db);
			exit;
		}
		$nowAdminData = $db->row;

		$allowManger = false;
		$Points      = new Points($nowLoginData);
		$Points->produceLink($nowAdminData);
		$allowManger = $Points->allowSearch;
		//該登入者不允許變更
		if(!$allowManger){
			$_SESSION['err'] = '3';
			header('location: /index.php');
			exit;
		}

		//不是管理員或者總代需要檢查上層帳號
		if(!in_array($nowAdminData['ag_id'], ['1', '2'])){
			try{
				//20150526-拿掉分紅設定
				//20150701-開啟分紅設定
				// throw new Exception();
				
				//尚未開放更改
				if($RateSettingSwitch == 'n'){
					throw new Exception();
				}
				if(!isset($_POST['commissionRate'])){
					breakoff($langAry['ERRCommissionRate'], $referer);
					exit;
				}
				// if(!isset($_POST['ReturnRate'])){
				// 	breakoff($langAry['ERRReturnRate'], $referer);
				// 	exit;
				// }

				//檢查上層%數，輸入%數不得大於上層擁有%數
				$db->selectTB('Admin');
				$db->getData("*","WHERE admin_id = '".$nowAdminData['upAdmin_id']."'");
				$db->execute();
				if($db->total_row <= 0){
					breakoff($langAry['ERRNoUpAccount'], $referer);
					unset($db);
					exit;
				}
				$upAdminData = $db->row;
				$upAdminData['commissionRate'] = $upAdminData['ag_id']=='2' ? $upAdminData['commissionRate'] : 0;
				$upAdminData['ReturnRate']     = 0;
				// $upAdminData['ReturnRate']     = $upAdminData['ag_id']=='2' ? $upAdminData['ReturnRate'] : 0; // u1888無返點

				//如果上層不是管理員或總控台需檢查上層Rate
				if(!in_array($upAdminData['ag_id'], ['1', '2'])){
					$upAdminData = $Points->getAdminRate($upAdminData, $startDate, $endDate);
					// $db->selectTB('AdminRate');
					// $db->getData("*",
					// "WHERE ag_id = '".$upAdminData['ag_id']."'
					//  AND acc_id = '".$upAdminData['admin_id']."'
					//  AND startDate <= '".$startDate."'
					//  AND endDate >= '".$endDate."'");
					// $db->execute();
					// if($db->total_row > 0){
					// 	$upAdminData['commissionRate'] = $db->row['commissionRate'];
					// 	$upAdminData['ReturnRate']     = $db->row['ReturnRate'];
					// }
				}
				if($updateData['commissionRate'] > $upAdminData['commissionRate']){
					breakoff($langAry['ERRCommissRateOver'], $referer);
					unset($db);
					exit;
				}

				//20150701-泰國需求
				//鎖定某帳號只能開設代理分紅90%帳號
				if($upAdminData['admin_id'] == LIMITADMINID && $updateData['commissionRate'] > LIMITRATE){
					breakoff(sprintf($langAry['ERRLimitMaxPointsOver'], LIMITRATE), $referer);
					exit;
				}
				
				if($updateData['ReturnRate'] > $upAdminData['ReturnRate']){
					breakoff($langAry['ERRReturnRateOver'], $referer);
					unset($db);
					exit;
				}
				//檢查下線代理，輸入%數不得小於下線代理最大成數
				$db->selectTB('Admin');
				$db->getData("Max(AdminRate.commissionRate) AS maxRate, admin_acc",
				"INNER JOIN AdminRate ON (Admin.admin_id = AdminRate.acc_id AND Admin.ag_id = AdminRate.ag_id)
				 WHERE upAdmin_id = '".$nowAdminData['admin_id']."'
				 AND startDate = '".$startDate."'
				 AND endDate = '".$endDate."'");
				$db->execute();
				if($db->total_row > 0 && $updateData['commissionRate'] < $db->row['maxRate']){
					breakoff($langAry['ERRCommissRateLower'].'('.$db->row['admin_acc'].'=>'.$db->row['maxRate'].')', $referer);
					unset($db);
					exit;
				}
				//檢查下線代理，輸入返水數不得小於下線代理最大返水數
				$db->getData("MAX(ReturnRate) AS maxReturnRate, admin_acc",
				"WHERE upAdmin_id = '".$nowAdminData['admin_id']."'
				 AND startDate >= '".$startDate."'
				 AND endDate <= '".$endDate."'");
				$db->execute();
				if($db->total_row > 0 && $updateData['ReturnRate'] < $db->row['maxReturnRate']){
					breakoff($langAry['ERRReturnRateLower'].'('.$db->row['admin_acc'].'=>'.$db->row['maxReturnRate'].')', $referer);
					unset($db);
					exit;
				}
				//檢查下線會員，輸入返水數不得小於下線會員最大返水數
				$db->selectTB('A_Member');
				$db->getData("Max(AdminRate.ReturnRate) AS maxReturnRate, MemberAccount",
				"INNER JOIN AdminRate ON (A_Member.MemberID = AdminRate.acc_id AND AdminRate.ag_id = '0')
				 WHERE UpAdmin_id = '".$nowAdminData['admin_id']."'
				 AND startDate >= '".$startDate."'
				 AND endDate <= '".$endDate."'");
				$db->execute();
				if($db->total_row > 0 && $updateData['ReturnRate'] < $db->row['maxReturnRate']){
					breakoff($langAry['ERRReturnRateLower'].'('.$db->row['MemberAccount'].'=>'.$db->row['maxReturnRate'].')', $referer);
					unset($db);
					exit;
				}

				//下期占成如果為新增過則新增否則修改
				$db->selectTB('AdminRate');
				$db->getData("*",
				"WHERE ag_id = '".$nowAdminData['ag_id']."'
				 AND acc_id = '".$nowAdminData['admin_id']."'
				 AND startDate >= '".$startDate."'
				 AND endDate <= '".$endDate."'");
				$db->execute();
				if($db->total_row <= 0){
					$setData = $updateData;
					$setData['commissionRate'] = isset($setData['commissionRate']) ? $setData['commissionRate'] : 0;
					$setData['ReturnRate']     = isset($setData['ReturnRate']) ? $setData['ReturnRate'] : 0;
					$setData['acc_id']         = $nowAdminData['admin_id'];
					$setData['ag_id']          = $nowAdminData['ag_id'];
					$setData['startDate']      = $startDate;
					$setData['endDate']        = $endDate;

					$db->insertData($setData);
					$text = [
						'query' => $db->getCurrentQueryString(),
						'value' => $setData
					];
					$result = $db->execute();
					$text['result'] = $result;
					addAdminLogs($text, $nowAdmin_id, '[System]Insert Agent Rate');
				}
				if($db->total_row > 0){
					$setData = array();
					$setData['commissionRate'] = isset($updateData['commissionRate']) ? $updateData['commissionRate'] : 0;
					$setData['ReturnRate']     = isset($updateData['ReturnRate']) ? $updateData['ReturnRate'] : 0;
					$setData['updtime']        = date('Y-m-d H:i:s');

					$db->updateData($setData, "WHERE ar_id = '".$db->row['ar_id']."'");
					$text = [
						'query' => $db->getCurrentQueryString(),
						'value' => $setData
					];
					$result = $db->execute();
					$text['result'] = $result;
					addAdminLogs($text, $nowAdmin_id, '[System]Update Agent Rate');
				}
			}catch(Exception $e){
				if(isset($updateData['commissionRate'])){
					unset($updateData['commissionRate']);
				}
				if(isset($updateData['ReturnRate'])){
					unset($updateData['ReturnRate']);
				}
			}
		}

		//只有管理員能夠修改總控台的ip
		if(!empty($updateData['ip'])){
			if($nowAdmin_agid == '1' && $nowAdminData['ag_id'] == '2'){
				$allowLoginIp = trim($updateData['ip']);
				$ipAry = [];
				$allowLoginIp = explode("\n", $allowLoginIp);
				foreach ($allowLoginIp as $value) {
					if(count(explode(",", $value)) > 1){
						$ipAry += array_map(function($v){
							return trim($v);
						}, explode(",", $value));
					}else{
						$ipAry[] = trim($value);
					}
				}
				$vaildIpAry = [];
				if(count($ipAry) > 0){
					foreach ($ipAry as $ip) {
						if(filter_var($ip, FILTER_VALIDATE_IP)){
							$vaildIpAry[] = $ip;
						}
					}
				}
				if(count($vaildIpAry) > 0){
					$updateData['allowLoginIp'] = join(",", $vaildIpAry);
				}
			}
			unset($updateData['ip']);
		}

		$db->selectTB('Admin');
		$db->updateData($updateData, "WHERE admin_id = '".$id."'");
		$text = [
			'query' => $db->getCurrentQueryString(),
			'value' => $updateData
		];
		$result = $db->execute();
		$text['result'] = $result;
		addAdminLogs($text, $nowAdmin_id, '[System]Update Agent');

		if(is_string($result)){
			breakoff($langAry['UpdateFail'].'('.$result.')', $referer);
			unset($db);
			exit;
		}
		if(is_bool($result) && $result){
			breakoff($langAry['UpdateAgentSucc'], $referer);
			unset($db, $bank);
			exit;
		}
		breakoff($langAry['UpdateFail'], $referer);
		unset($db);
		exit;
	//新增总代
	case 'addMember':
		if($nowAdmin_agid != '1'){
			$_SESSION['err'] = '3';
			header("location: /index.php");
			exit;
		}
		$referer = $_SERVER['HTTP_REFERER'];

		if(empty($_POST['admin_acc'])){
			breakoff($langAry['ERRAccount'], $referer);
			exit;
		}
		if(empty($_POST['admin_name'])){
			breakoff($langAry['ERRNickName'], $referer);
			exit;
		}
		if(empty($_POST['admin_pwd'])){
			breakoff($langAry['ERRPassword'], $referer);
			exit;
		}
		if(empty($_POST['admin_pwd2'])){
			breakoff($langAry['ERRPassword3'], $referer);
			exit;
		}
		if($_POST['admin_pwd'] != $_POST['admin_pwd2']){
			breakoff($langAry['ERRPassword4'], $referer);
			exit;
		}
		if(!isset($_POST['points']) || !preg_match('/^([0-9]+)$/', $_POST['points']) || $_POST['points'] < 0){
			breakoff($langAry['ERRStartCredit'], $referer);
			exit;
		}	

		$insertData = $_POST;
		$insertData['admin_acc']      = trim($insertData['admin_acc']);
		$insertData['ag_id']          = '2';//1:管理者、2:总代、3:代理
		$insertData['admin_power']    = '{"1":"w"}';
		$insertData['commissionRate'] = $MaxCommissionRate;//佣金率预设100%
		$insertData['ReturnRate']     = $MaxReturnRate;//返水占成預設12
		$insertData['points']         = 0;//起始金额预设0
		$insertData['MaxCredit']      = $_POST['points'];//最大信用額度使用起始金额
		$insertData['canAddAgent']    = 'y';//預設總控台可新增代理

		if(!empty($insertData['ip'])){
			$allowLoginIp = trim($insertData['ip']);
			$ipAry = [];
			$allowLoginIp = explode("\n", $allowLoginIp);
			foreach ($allowLoginIp as $value) {
				if(count(explode(",", $value)) > 1){
					$ipAry += array_map(function($v){
						return trim($v);
					}, explode(",", $value));
				}else{
					$ipAry[] = trim($value);
				}
			}
			$vaildIpAry = [];
			if(count($ipAry) > 0){
				foreach ($ipAry as $ip) {
					if(filter_var($ip, FILTER_VALIDATE_IP)){
						$vaildIpAry[] = $ip;
					}
				}
			}
			if(count($vaildIpAry) > 0){
				$insertData['allowLoginIp'] = join(",", $vaildIpAry);
			}
		}

		$db = new PDO_DB(DB_HOST, DB_USER, DB_PWD, DB_NAME);
		$db->selectTB('AdminGroup');
		$db->getData("*","WHERE ag_id = '".$insertData['ag_id']."'");//取得总代权限
		$db->execute();
		if($db->total_row > 0){
			$insertData['admin_power'] = $db->row['ag_power'];
		}

		$db->selectTB('Admin');
		$db->getData("admin_acc", "WHERE admin_acc = '".$insertData['admin_acc']."'");
		$db->execute();
		if($db->total_row > 0){
			breakoff($langAry['ERRAccountRepeat'], $referer);
			unset($db);
			exit;
		}

		$db->insertData($insertData);
		$text = [
			'query' => $db->getCurrentQueryString(),
			'value' => $insertData
		];
		$result = $db->execute();
		$text['result'] = $result;
		addAdminLogs($text, $nowAdmin_id, '[System]New Master Agent');

		if(is_string($result)){
			breakoff($langAry['AddFail'].'('.$result.')', $referer);
			unset($db);
			exit;
		}
		if(is_bool($result) && $result){
			//管理员建立总代不须扣款直接建立纪录
			$newAdmin_id  = $db->getLastInsertId();
			$newAdmin_acc = $insertData['admin_acc'];
			$event_id     = '21';//管理员补点
			$beforePoints = $nowAdmin_agid == '1' ? $_POST['points'] : $nowAdminPoints;//管理员
			$outgoPoints  = $_POST['points'];
			$note         = '[System]New Account';
			$bank = new Bank();
			$result = $bank->outgoLog($event_id, $nowAdmin_id, $newAdmin_id, $beforePoints, $outgoPoints, $note);
			$errMsg = array();
			if(!$result){
				$errMsg[] = 'OutgoFail';
			}
			//管理员对新帐号第一次补点
			$event_id     = '21';//管理员补点
			$incomePoints = $_POST['points'];
			$beforePoints = 0;//新创帐号预设 0 Points
			$result = $bank->creditFrom($event_id, $newAdmin_id, $nowAdmin_id, $beforePoints, $incomePoints, $note);
			if(!$result){
				$errMsg[] = 'CreditFail';
			}
			if(empty($errMsg)){
				breakoff($langAry['AddMAgentSucc'], $referer);
				unset($db, $bank);
				exit;
			}
			breakoff($langAry['AddMAgentSuccLogFail'].'('.join("-", $errMsg).')', $referer);
			unset($db, $bank);
			exit;
		}
		breakoff($langAry['AddFail'], $referer);
		unset($db);
		exit;
	//新增子賬號
	case ($action == 'addSubAgent'):
		$referer = $_SERVER['HTTP_REFERER'];
		if($nowAdmin_agid == '4'){//除子賬號群組外可以新增
			$_SESSION['err'] = '3';
			header("location: /index.php");
			exit;
		}
		if(empty($_POST['admin_acc'])){
			breakoff($langAry['ERRAccount'], $referer);
			exit;
		}
		if(empty($_POST['admin_name'])){
			breakoff($langAry['ERRNickName'], $referer);
			exit;
		}
		if(empty($_POST['admin_pwd'])){
			breakoff($langAry['ERRPassword'], $referer);
			exit;
		}
		if(empty($_POST['admin_pwd2'])){
			breakoff($langAry['ERRPassword3'], $referer);
			exit;
		}
		if($_POST['admin_pwd'] != $_POST['admin_pwd2']){
			breakoff($langAry['ERRPassword4'], $referer);
			exit;
		}

		$insertData = $_POST;
		$insertData['ag_id']       = '4';//1:管理者、2:总代、3:代理、4：子賬號
		$insertData['admin_power'] = '{"1":"w"}';
		$insertData['subAdmin']    = $nowAdmin_id;
		$insertData['points']      = 0;//起始金额预设0
		$insertData['MaxCredit']   = 0;//最大信用額度使用起始金额

		$db = new PDO_DB(DB_HOST, DB_USER, DB_PWD, DB_NAME);
		$db->selectTB('AdminGroup');
		$db->getData("*","WHERE ag_id = '".$insertData['ag_id']."'");//取得总代权限
		$db->execute();
		if($db->total_row > 0){
			$insertData['admin_power'] = $db->row['ag_power'];
		}

		$db->selectTB('Admin');
		$db->getData("admin_acc", "WHERE admin_acc = '".$insertData['admin_acc']."'");
		$db->execute();
		if($db->total_row > 0){
			breakoff($langAry['ERRAccountRepeat'], $referer);
			unset($db);
			exit;
		}

		$db->selectTB('Admin');
		$db->insertData($insertData);
		$text = [
			'query' => $db->getCurrentQueryString(),
			'value' => $insertData
		];
		$result = $db->execute();
		$text['result'] = $result;
		addAdminLogs($text, $nowAdmin_id, '[System]New Sub Agent');
		if(is_string($result)){
			breakoff($langAry['AddFail'].'('.$result.')', $referer);
			unset($db);
			exit;
		}
		if(is_bool($result) && $result){
			breakoff($langAry['AddSubAgentSucc'], $referer);
			unset($db);
			exit;
		}
		breakoff($langAry['AddFail'], $referer);
		unset($db);
		exit;
	//新增代理
	case ($action == 'addAgent'):
		$referer = $_SERVER['HTTP_REFERER'];
		// 2014-12-6 開放代理可新增代理
		if($nowAdmin_agid == '1'){//管理員必須檢查是否輸入上層帳號
			if(empty($_POST['upAccount'])){
				breakoff($langAry['ERRUpAccount'], $referer);
				exit;
			}
		}
		if(empty($_POST['admin_acc'])){
			breakoff($langAry['ERRAccount'], $referer);
			exit;
		}
		if(empty($_POST['admin_name'])){
			breakoff($langAry['ERRNickName'], $referer);
			exit;
		}
		if(empty($_POST['admin_pwd'])){
			breakoff($langAry['ERRPassword'], $referer);
			exit;
		}
		if(empty($_POST['admin_pwd2'])){
			breakoff($langAry['ERRPassword3'], $referer);
			exit;
		}
		if($_POST['admin_pwd'] != $_POST['admin_pwd2']){
			breakoff($langAry['ERRPassword4'], $referer);
			exit;
		}
		if(!isset($_POST['points']) || !preg_match('/^([0-9]+)$/', $_POST['points']) || $_POST['points'] < 0){
			breakoff($langAry['ERRStartCredit'], $referer);
			exit;
		}	
		//20150526-拿掉分紅設定
		//20150701-開啟分紅設定
		if(!isset($_POST['commissionRate']) || !preg_match('/^([0-9]){1,3}$/', $_POST['commissionRate'])){
			breakoff($langAry['ERRCommissionRate'], $referer);
			exit;
		}
		//開放新增代理
		// 20150701-開放新增無限層代理
		// if(empty($_POST['canAddAgent']) || $_POST['canAddAgent'] != 'y'){
		// 	$_POST['canAddAgent'] = 'n';
		// }
		// //非管理員或總控台不能變更開放新增代理功能
		// if(!in_array($nowAdmin_agid, ['1', '2'])){
		// 	$_POST['canAddAgent'] = 'n';
		// }
		//u1888不開放
		// if(!isset($_POST['ReturnRate']) || !preg_match('/^([0-9]){1,2}$/', $_POST['ReturnRate'])){
		// 	breakoff($langAry['ERRReturnRate'], $referer);
		// 	exit;
		// }
		$enterPoints = $_POST['points'];

		$insertData = $_POST;
		$insertData['admin_acc']   = trim($insertData['admin_acc']);
		$insertData['ag_id']       = '3';//1:管理者、2:总代、3:代理
		$insertData['admin_power'] = '{"1":"w"}';
		$insertData['upAdmin_id']  = $nowAdmin_id;
		$insertData['points']      = 0;//起始金额预设0
		$insertData['MaxCredit']   = $enterPoints;//最大信用額度使用起始金额

		$insertData['canAddAgent'] = 'y';// 20150701-開放新增無限層代理

		$db = new PDO_DB(DB_HOST, DB_USER, DB_PWD, DB_NAME);

		//是否有同帳號
		$db->selectTB('Admin');
		$db->getData("admin_acc", "WHERE admin_acc = '".$insertData['admin_acc']."'");
		$db->execute();
		if($db->total_row > 0){
			breakoff($langAry['ERRAccountRepeat'], $referer);
			unset($db);
			exit;
		}

		//取得該群組權限
		$db->selectTB('AdminGroup');
		$db->getData("*","WHERE ag_id = '".$insertData['ag_id']."'");//取得总代权限
		$db->execute();
		if($db->total_row > 0){
			$insertData['admin_power'] = $db->row['ag_power'];
		}

		//只有管理員能夠輸入上層帳號
		$upAdminData = $nowLoginData;
		if($nowAdmin_agid == '1'){
			$db->selectTB('Admin');
			$db->getData("*", "WHERE admin_acc = '".$insertData['upAccount']."'");
			$db->execute();
			if($db->total_row <= 0){
				breakoff($langAry['ERRNoUpAccount'], $referer);
				unset($db);
				exit;
			}
			$upAdminData = $db->row;
			$insertData['upAdmin_id'] = $upAdminData['admin_id'];
		}

		//如果現在登入者為代理需檢查是否可新增代理
		if($nowAdmin_agid == '3' && !$nowLoginData['canAddAgent']){
			$_SESSION['err'] = '3';
			header('location: /index.php');
			exit;
		}

		//輸入占成不能超過上層占成
		$Points = new Points();
		$upAdminData = $Points->getAdminRate($upAdminData, $startDate, $endDate);
		//20150526-拿掉分紅設定
		//20150701-開啟分紅設定
		if($_POST['commissionRate'] > $upAdminData['commissionRate']){
			breakoff($langAry['ERRCommissRateOver'], $referer);
			exit;
		}

		//20150701-泰國需求
		//鎖定某帳號只能開設代理分紅90%帳號
		if($upAdminData['admin_id'] == LIMITADMINID && $_POST['commissionRate'] > LIMITRATE){
			breakoff(sprintf($langAry['ERRLimitMaxPointsOver'], LIMITRATE), $referer);
			exit;
		}

		//輸入金額不能超過上層餘額
		if($enterPoints > $upAdminData['points']){
			breakoff($langAry['ERRStartCreditOverSelf'], $referer);
			exit;
		}

		//u1888不開放
		// if($_POST['ReturnRate'] > $nowAdminReturnRate){
		// 	breakoff($langAry['ERRReturnRateOver'], $referer);
		// 	exit;
		// }
		$db->selectTB('Admin');
		$db->insertData($insertData);
		$text = [
			'query' => $db->getCurrentQueryString(),
			'value' => $insertData
		];
		$result = $db->execute();
		$text['result'] = $result;
		addAdminLogs($text, $nowAdmin_id, '[System]New Account');

		if(is_string($result)){
			breakoff($langAry['AddFail'].'('.$result.')', $referer);
			unset($db);
			exit;
		}
		if(is_bool($result) && $result){
			//开新帐号成功
			$newAdmin_id  = $db->getLastInsertId();
			$newAdmin_acc = $insertData['admin_acc'];
			$event_id     = '25';//替下线充值
			$beforePoints = $upAdminData['points'];
			$outgoPoints  = $enterPoints;
			$note         = '[System]New Account';
			//修正上级的下线总数
			$updateData = array();
			$updateData['downCount']     = ($upAdminData['downCount'] + 1);
			$updateData['admin_updtime'] = date('Y-m-d H:i:s');

			$db->selectTB('Admin');
			$db->updateData($updateData, "WHERE admin_id = '".$upAdminData['admin_id']."'");
			$text = [
				'query' => $db->getCurrentQueryString(),
				'value' => $updateData
			];
			$result = $db->execute();
			$text['result'] = $result;
			addAdminLogs($text, $nowAdmin_id, '[System]修正上級的下線總數');

			//新帐号支出纪录
			$bank = new Bank();
			$result = $bank->debitTo($event_id, $upAdminData['admin_id'], $newAdmin_id, $beforePoints, $outgoPoints, $note);
			$errMsg = array();
			//扣款成功才能給予下線金額
			if($result){
				//上线代理对下线代理充值
				$event_id     = '2';//上级充值
				$incomePoints = $enterPoints;
				$beforePoints = 0;//新创帐号预设 0 Points
				$result = $bank->creditFrom($event_id, $newAdmin_id, $upAdminData['admin_id'], $beforePoints, $incomePoints, $note);
				if(!$result){
					$errMsg[] = 'CreditFail';
				}
			}else{
				$errMsg[] = 'DebitFail';
			}

			//20150526-拿掉分紅設定
			//20150701-開啟分紅設定
			//新增當期設定占成率
			$RateAry = array();
			$RateAry['ag_id']          = $insertData['ag_id'];
			$RateAry['acc_id']         = $newAdmin_id;
			$RateAry['startDate']      = $startDate;
			$RateAry['endDate']        = $endDate;
			$RateAry['commissionRate'] = isset($insertData['commissionRate']) ? $insertData['commissionRate'] : 0;
			$RateAry['ReturnRate']     = isset($insertData['ReturnRate']) ? $insertData['ReturnRate'] : 0;

			$db->selectTB('AdminRate');
			$db->insertData($RateAry);
			$text = [
				'query' => $db->getCurrentQueryString(),
				'value' => $RateAry
			];
			$result = $db->execute();
			$text['result'] = $result;
			addAdminLogs($text, $nowAdmin_id, '[System]新增當期設定占成率');

			if(is_string($result)){
				$errMsg[] = 'AddRateFail';
			}

			if(empty($errMsg)){
				breakoff($langAry['AddAgentSucc'], $referer);
				unset($db, $bank);
				exit;
			}
			breakoff($langAry['AddAgentSuccLogFail'].'('.join("-", $errMsg).')'.(DEBUG || $nowAdmin_agid == '1' ? '('.$bank->getErrorMsg().')' : ''), $referer);
			unset($db, $bank);
			exit;
		}
		breakoff($langAry['AddFail'].'('.$result.')', $referer);
		unset($db);
		exit;
	//新增会员
	case ($action == 'addMem'):
		$referer = $_SERVER['HTTP_REFERER'];
		if(!in_array($nowAdmin_agid,['1', '3'])){//不是代理或管理員不能新增
			$_SESSION['err'] = '3';
			header("location: /index.php");
			exit;
		}
		if($nowAdmin_agid == '1'){//管理員必須檢查是否輸入上層帳號
			if(empty($_POST['upAccount'])){
				breakoff($langAry['ERRUpAccount'], $referer);
				exit;
			}
		}
		if(empty($_POST['MemberAccount'])){
			breakoff($langAry['ERRAccount'], $referer);
			exit;
		}
		if(empty($_POST['NickName'])){
			breakoff($langAry['ERRNickName'], $referer);
			exit;
		}
		if(empty($_POST['MemberPassword'])){
			breakoff($langAry['ERRPassword'], $referer);
			exit;
		}
		if(empty($_POST['MemberPassword2'])){
			breakoff($langAry['ERRPassword3'], $referer);
			exit;
		}
		if($_POST['MemberPassword'] != $_POST['MemberPassword2']){
			breakoff($langAry['ERRPassword4'], $referer);
			exit;
		}
		if(!isset($_POST['points']) || !preg_match('/^([0-9]+)$/', $_POST['points']) || $_POST['points'] < 0){
			breakoff($langAry['ERRStartCredit'], $referer);
			exit;
		}	
		//u1888不開放
		// if(!isset($_POST['ReturnRate']) || !preg_match('/^([0-9]){1,2}$/', $_POST['ReturnRate'])){
		// 	breakoff($langAry['ERRReturnRate'], $referer);
		// 	exit;
		// }
		//20150706-新增幣別設定
		if(empty($_POST['MemberCurrency'])){
			$_POST['MemberCurrency'] = CURRENCY;
		}
		$enterPoints = $_POST['points'];

		$insertData = $_POST;
		$insertData['MemberAccount'] = trim($insertData['MemberAccount']);
		$insertData['NickName']      = empty($insertData['NickName']) ? $insertData['MemberAccount'] : $insertData['NickName'];
		$insertData['UpAdmin_id']    = $nowAdmin_id;
		$insertData['MaxCredit']     = $enterPoints;//新建帳號的起始金額設定恢復額度最大金額

		$db = new PDO_DB(DB_HOST, DB_USER, DB_PWD, DB_NAME);
		//是否有重複帳號問題
		$db->selectTB('A_Member');
		$db->getData("MemberID", "WHERE MemberAccount = '".$insertData['MemberAccount']."'");
		$db->execute();
		if($db->total_row > 0){
			breakoff($langAry['ERRAccountRepeat'], $referer);
			exit;
		}
		//是否有重複暱稱問題
		$db->selectTB('A_Member');
		$db->getData("MemberID", "WHERE NickName = '".$insertData['NickName']."'");
		$db->execute();
		if($db->total_row > 0){
			breakoff($langAry['ERRNickNameRepeat'], $referer);
			exit;
		}

		//20150706-新增幣別設定
		//是否有此貨幣
		$db->selectTB('Currency');
		$db->getData("*", "WHERE code = '".$insertData['MemberCurrency']."' AND enabled = 'y'");
		$db->execute();
		if($db->total_row <= 0){
			breakoff($langAry['ERRNoCurrency'], $referer);
			exit;
		}
		$CurrencyData = $db->row;
		$calculatePoints = $enterPoints*$CurrencyData['rate'];
		$insertData['MaxCredit'] = $calculatePoints;

		//20150712-新增遊戲平台幣上限
		//貨幣匯率計算後是否大於平台遊戲幣上限
		if($calculatePoints > MAXMEMMONEY){
			breakoff($langAry['ERRPointsOverMax'], $referer);
			exit;
		}

		//只有管理員能夠輸入上層帳號
		$upAdminData = $nowLoginData;
		if($nowAdmin_agid == '1'){
			$db->selectTB('Admin');
			$db->getData("*", "WHERE admin_acc = '".$insertData['upAccount']."'");
			$db->execute();
			if($db->total_row <= 0){
				breakoff($langAry['ERRNoUpAccount'], $referer);
				unset($db);
				exit;
			}
			//20150707-修正上層只能是代理
			if($db->row['ag_id'] != '3'){
				breakoff($langAry['ERRNoUpAccount'], $referer);
				unset($db);
				exit;
			}
			$upAdminData = $db->row;
			$insertData['UpAdmin_id'] = $upAdminData['admin_id'];
		}

		//輸入金額不能超過上層餘額
		if($enterPoints > $upAdminData['points']){
			breakoff($langAry['ERRStartCreditOverSelf'], $referer);
			exit;
		}

		//u1888不開放
		//查詢占成
		// $nowAdminReturnRate     = $nowAdmin_agid=='2' ? $nowAdminReturnRate : 0;
		// if($nowAdmin_agid != '2'){
		// 	$db->selectTB('AdminRate');
		// 	$db->getData("*","WHERE ag_id = '".$nowAdmin_agid."'
		// 	 AND acc_id = '".$nowAdmin_id."'
		// 	 AND startDate >= '".$startDate."'
		// 	 AND endDate <= '".$endDate."'");
		// 	$db->execute();
		// 	if($db->total_row > 0){
		// 		$nowAdminReturnRate = isset($db->row['ReturnRate']) ? $db->row['ReturnRate'] : 0;
		// 	}
		// }
		// if($insertData['ReturnRate'] > $nowAdminReturnRate){
		// 	breakoff($langAry['ERRReturnRateOver'], $referer);
		// 	exit;
		// }

		$db->selectTB('A_Member');
		$db->insertData($insertData);
		$text = [
			'query' => $db->getCurrentQueryString(),
			'value' => $insertData
		];
		$result = $db->execute();
		$text['result'] = $result;
		addAdminLogs($text, $nowAdmin_id, '[System]New Member');

		if(is_string($result)){
			breakoff($langAry['AddFail'].'('.$result.')', $referer);
			unset($db);
			exit;
		}
		if(is_bool($result) && $result){
			//修正上级的下线总数
			$updateData = array();
			$updateData['downMemCount']  = ($upAdminData['downMemCount'] + 1);
			$updateData['admin_updtime'] = date('Y-m-d H:i:s');

			$db->selectTB('Admin');
			$db->updateData($updateData, "WHERE admin_id = '".$upAdminData['admin_id']."'");
			$text = [
				'query' => $db->getCurrentQueryString(),
				'value' => $updateData
			];
			$result = $db->execute();
			$text['result'] = $result;
			addAdminLogs($text, $nowAdmin_id, '[System]修正上級的下線總數');

			//开新帐号成功
			$newMemberID      = $db->getLastInsertId();
			$newMemberAccount = $insertData['MemberAccount'];
			$event_id         = '25';//替下线充值
			$beforePoints     = $upAdminData['points'];
			$outgoPoints      = $enterPoints;
			$note             = '[System]New Member';
			//新帐号支出纪录
			$bank = new Bank();
			$result = $bank->debitTo($event_id, $upAdminData['admin_id'], $newMemberID, $beforePoints, $outgoPoints, $note, True, ['MemberCurrency'=>$CurrencyData['code'], 'CurrencyRate'=>$CurrencyData['rate']]);
			$errMsg = array();
			if($result){
				//上线代理对下线会员充值
				$event_id     = '2';//上级充值
				$incomePoints = $enterPoints;
				$result = $bank->rechargeMem($event_id, $newMemberID, $upAdminData['admin_id'], $incomePoints, $note, ['MemberCurrency'=>$CurrencyData['code'], 'CurrencyRate'=>$CurrencyData['rate']]);

				if(!$result){
					$errMsg[] = 'RechargeMemFail';
				}
			}else{
				$errMsg[] = 'DebitFail';
			}

			//u1888不開放
			//新增當期設定占成率
			// $RateAry = array();
			// $RateAry['acc_id']         = $newMemberID;
			// $RateAry['startDate']      = $startDate;
			// $RateAry['endDate']        = $endDate;
			// $RateAry['ReturnRate']     = isset($insertData['ReturnRate']) ? $insertData['ReturnRate'] : 0;

			// $db->selectTB('AdminRate');
			// $db->insertData($RateAry);
			// $result = $db->execute();
			// if(is_string($result)){
			// 	$errMsg[] = 'AddRateFail';
			// }

			if(empty($errMsg)){
				breakoff($langAry['AddMemSucc'], $referer);
				unset($db, $bank);
				exit;
			}
			breakoff($langAry['AddMemSuccLogFail'].'('.join("-", $errMsg).')'.(DEBUG || $nowAdmin_agid == '1' ? '('.$bank->getErrorMsg().')' : ''), 'accountManager.php?mem='.$newMemberAccount);
			unset($db, $bank);
			exit;
		}
		breakoff($langAry['AddFail'].'('.$result.')', $referer);
		unset($db);
		exit;
	//总代或管理员替代理补点
	// case 'plusAgentPoints':
	// 	$referer  = $_SERVER['HTTP_REFERER'];
	// 	$event_id = '25';//替下线充值
	// 	// 2014-12-6 新增代理可以補代理
	// 	// if($nowAdmin_agid == '3'){//不是管理员或总代不能补代理
	// 	// 	header("location: /index.php?err=3");
	// 	// 	exit;
	// 	// }
	// 	if(empty($_POST['id'])){
	// 		$_SESSION['err'] = '3';
	// 		header("location: /index.php");
	// 		exit;
	// 	}
	// 	if(empty($_POST['points']) || !preg_match('/^([0-9]+)$/', $_POST['points'])){
	// 		breakoff($langAry['ERRPlusPoints'], $referer);
	// 		exit;
	// 	}	
	// 	if($_POST['points'] > $nowAdminPoints){
	// 		if($nowAdmin_agid != '1'){//如果不是管理员
	// 			breakoff($langAry['ERRPlusPointsOverSelf'], $referer);
	// 			exit;
	// 		}
	// 		$nowAdminPoints = $_POST['points'];
	// 		$event_id       = '21';//管理员补点
	// 	}
	// 	if(empty($_POST['note'])){
	// 		unset($_POST['note']);
	// 	}

	// 	//检查给予代理的资料库资料
	// 	$toAdmin_id = $_POST['id'];
	// 	$toPoints   = $_POST['points'];
	// 	$db = new PDO_DB(DB_HOST, DB_USER, DB_PWD, DB_NAME);
	// 	$db->selectTB('Admin');
	// 	$db->getData("*","WHERE admin_id = '".$toAdmin_id."'");
	// 	$db->execute();
	// 	$result = '';
	// 	$errMsg = array();
	// 	if($db->total_row > 0){
	// 		$toAdminPoints = $db->row['points'];
	// 		$toAdmin_acc   = $db->row['admin_acc'];

	// 		$allowManger = false;
	// 		$Points      = new Points($nowLoginData);
	// 		$Points->produceLink($db->row);
	// 		$allowManger = $Points->allowSearch;
	// 		unset($Points);
	// 		//該登入者不允許變更
	// 		if(!$allowManger){
	// 			$_SESSION['err'] = '3';
	// 			header('location: /index.php');
	// 			exit;
	// 		}

	// 		$note = NULL;
	// 		if(!empty($_POST['note'])){$note = $_POST['note'];}
	// 		$bank = new Bank();
	// 		//替下线充值扣点
	// 		$result = $bank->debitTo( $event_id, $nowAdmin_id, $toAdmin_id, $nowAdminPoints, $toPoints, $note);
	// 		if($result){
	// 			//上线补点
	// 			if($nowAdmin_agid != '1'){$event_id = '2';}//上级充值
	// 			$result = $bank->creditFrom( $event_id, $toAdmin_id, $nowAdmin_id, $toAdminPoints, $toPoints, $note);
	// 			if(!$result){
	// 				$errMsg[] = 'CreditFail';
	// 			}
	// 		}else{
	// 			$errMsg[] = 'DebitFail';
	// 		}
	// 		if(empty($errMsg)){
	// 			breakoff($langAry['PlusPointsSuccess'], $referer);
	// 			unset($db, $bank);
	// 			exit;
	// 		}		
	// 	}
	// 	if($db->total_row <= 0){$errMsg[] = $langAry['ERRNoID'];}
	// 	breakoff($langAry['PlusPointsFail'].'('.join("-", $errMsg).')'.(DEBUG || $nowAdmin_agid == '1' ? '('.$bank->getErrorMsg().')' : ''), $referer);
	// 	unset($db, $bank);
	// 	exit;
	// //对代理商扣点
	// case 'minusAgentPoints':
	// 	$referer  = $_SERVER['HTTP_REFERER'];
	// 	$event_id = '20';//管理员扣点
	// 	if($nowAdmin_agid != '1'){//不是管理员或总代不能补代理
	// 		$event_id = '29';//扣点
	// 	}
	// 	if(empty($_POST['id'])){
	// 		$_SESSION['err'] = '3';
	// 		header("location: /index.php");
	// 		exit;
	// 	}
	// 	if(empty($_POST['points']) || !preg_match('/^([0-9]+)$/', $_POST['points'])){
	// 		breakoff($langAry['ERRMinusPoints'], $referer);
	// 		exit;
	// 	}	
	// 	if(empty($_POST['note'])){
	// 		unset($_POST['note']);
	// 	}

	// 	//检查给予代理的资料库资料
	// 	$toAdmin_id = $_POST['id'];
	// 	$toPoints   = $_POST['points'];
	// 	$db = new PDO_DB(DB_HOST, DB_USER, DB_PWD, DB_NAME);
	// 	$db->selectTB('Admin');
	// 	$db->getData("*","WHERE admin_id = '".$toAdmin_id."'");
	// 	$db->execute();
	// 	$result = '';
	// 	$errMsg = array();
	// 	if($db->total_row > 0){
	// 		$toAdminPoints = $db->row['points'];
	// 		$toAdmin_acc   = $db->row['admin_acc'];

	// 		$allowManger = false;
	// 		$Points      = new Points($nowLoginData);
	// 		$Points->produceLink($db->row);
	// 		$allowManger = $Points->allowSearch;
	// 		unset($Points);
	// 		//該登入者不允許變更
	// 		if(!$allowManger){
	// 			$_SESSION['err'] = '3';
	// 			header('location: /index.php');
	// 			exit;
	// 		}

	// 		if($_POST['points'] > $toAdminPoints){
	// 			breakoff($langAry['ERRMinusPointsOver'], $referer);
	// 			exit;
	// 		}
	// 		$note = NULL;
	// 		if(!empty($_POST['note'])){$note = $_POST['note'];}
	// 		$bank = new Bank();
	// 		//对代理扣点
	// 		$result = $bank->debitTo( $event_id, $toAdmin_id, $nowAdmin_id, $toAdminPoints, $toPoints, $note);
	// 		if($result){
	// 			//扣点后返回該人身上
	// 			$result = $bank->creditFrom( $event_id, $nowAdmin_id, $toAdmin_id, $nowAdminPoints, $toPoints, $note);
	// 			if(!$result){
	// 				$errMsg[] = 'CreditFail';
	// 			}
	// 		}else{
	// 			$errMsg[] = 'DebitFail';
	// 		}
	// 		if(empty($errMsg)){
	// 			breakoff($langAry['MinusPointsSuccess'], $referer);
	// 			unset($db, $bank);
	// 			exit;
	// 		}		
	// 	}
	// 	if($db->total_row <= 0){$errMsg[] = $langAry['ERRNoID'];}
	// 	breakoff($langAry['MinusPointsFail'].'('.join("-", $errMsg).')'.(DEBUG || $nowAdmin_agid == '1' ? '('.$bank->getErrorMsg().')' : ''), $referer);
	// 	unset($db, $bank);
	// 	exit;
	// //代理替会员补点
	// case 'plusMemPoints':
	// 	$referer  = $_SERVER['HTTP_REFERER'];
	// 	$event_id = '25';//替下线会员充值
	// 	if(($nowAdmin_agid == '2')){//不是管理员或代理不能替会员补点
	// 		$_SESSION['err'] = '3';
	// 		header("location: /index.php");
	// 		exit;
	// 	}
	// 	if(empty($_POST['id'])){
	// 		$_SESSION['err'] = '3';
	// 		header("location: /index.php");
	// 		exit;
	// 	}
	// 	if(empty($_POST['points']) || !preg_match('/^([0-9]+)$/', $_POST['points'])){
	// 		breakoff($langAry['ERRPlusPoints'], $referer);
	// 		exit;
	// 	}	
	// 	if(empty($_POST['note'])){
	// 		unset($_POST['note']);
	// 	}

	// 	//检查给予会员的资料库资料
	// 	$toMemberID = $_POST['id'];
	// 	$toPoints   = $_POST['points'];
	// 	$db = new PDO_DB(DB_HOST, DB_USER, DB_PWD, DB_NAME);
	// 	$db->selectTB('A_Member');
	// 	$db->getData("*", "LEFT JOIN Currency ON (A_Member.MemberCurrency = Currency.code)
	// 	 WHERE A_Member.MemberID = '".$toMemberID."'");
	// 	$db->execute();
	// 	$result = '';
	// 	$errMsg = array();
	// 	if($db->total_row > 0){
	// 		$note       = NULL;
	// 		$memberData = $db->row;
	// 		$points     = $_POST['points'];
	// 		if(!isset($memberData['rate'])){
	// 			breakoff($langAry['ERRNoCurrency'], $referer);
	// 			exit;
	// 		}

	// 		if(($points*$memberData['rate']) > $nowAdminPoints){
	// 			if($nowAdmin_agid != '1'){
	// 				breakoff($langAry['ERRPlusPointsOverSelf'], $referer);
	// 				exit;
	// 			}
	// 			$event_id       = '21';
	// 			$nowAdminPoints = $_POST['points'];
	// 		}

	// 		$db->selectTB('Admin');
	// 		$db->getData("*", "WHERE admin_id = '".$memberData['UpAdmin_id']."'");
	// 		$db->execute();
	// 		if($db->total_row <= 0){
	// 			breakoff($langAry['ERRNoUpAccount'], $referer);
	// 			unset($db);
	// 			exit;
	// 		}

	// 		$upAdminData = $db->row;
	// 		$allowManger = false;
	// 		$Points      = new Points($nowLoginData);
	// 		$Points->produceLink($upAdminData);
	// 		$allowManger = $Points->allowSearch;
	// 		unset($Points);
	// 		//該登入者不允許變更
	// 		if(!$allowManger){
	// 			$_SESSION['err'] = '3';
	// 			header('location: /index.php');
	// 			exit;
	// 		}
			
	// 		if(!empty($_POST['note'])){$note = $_POST['note'];}
	// 		$bank = new Bank();
	// 		//替下线充值扣点
	// 		$result = $bank->debitTo( $event_id, $nowAdmin_id, $toMemberID, $nowAdminPoints, $toPoints, $note, True, ['MemberCurrency'=>$memberData['code'], 'CurrencyRate'=>$memberData['rate']]);
	// 		if($result){
	// 			//上级补点
	// 			if($nowAdmin_agid != '1'){$event_id = '2';}//上级充值
	// 			$result = $bank->rechargeMem( $event_id, $toMemberID, $nowAdmin_id, $toPoints, $note, ['MemberCurrency'=>$memberData['code'], 'CurrencyRate'=>$memberData['rate']]);
	// 			if(!$result){
	// 				$errMsg[] = 'RechargeFail';
	// 			}
	// 		}else{
	// 			$errMsg[] = 'DebitFail';
	// 		}
	// 		if(empty($errMsg)){
	// 			breakoff($langAry['PlusPointsSuccess'], $referer);
	// 			unset($db, $bank);
	// 			exit;
	// 		}		
	// 	}
	// 	if($db->total_row <= 0){$errMsg[] = $langAry['ERRNoID'];}
	// 	breakoff($langAry['PlusPointsFail'].'('.join("-", $errMsg).')'.(DEBUG || $nowAdmin_agid == '1' ? '('.$bank->getErrorMsg().')' : ''), $referer);
	// 	unset($db, $bank);
	// 	exit;
	// //对会员扣点
	// case 'minusMemPoints':
	// 	$referer  = $_SERVER['HTTP_REFERER'];
	// 	$event_id = '20';//管理員扣點
	// 	if($nowAdmin_agid != '1'){
	// 		$event_id = '29';//扣點
	// 	}
	// 	if(empty($_POST['id'])){
	// 		$_SESSION['err'] = '3';
	// 		header("location: /index.php");
	// 		exit;
	// 	}
	// 	if(empty($_POST['points']) || !preg_match('/^([0-9]+)$/', $_POST['points'])){
	// 		breakoff($langAry['ERRMinusPoints'], $referer);
	// 		exit;
	// 	}	
	// 	if(empty($_POST['note'])){
	// 		unset($_POST['note']);
	// 	}

	// 	//检查给予会员的资料库资料
	// 	$toMemberID = $_POST['id'];
	// 	$toPoints   = $_POST['points'];
	// 	$db = new PDO_DB(DB_HOST, DB_USER, DB_PWD, DB_NAME);
	// 	$db->selectTB('MemberFinance2');
	// 	$db->getData("*", "INNER JOIN A_Member ON (MemberFinance2.MemberId = A_Member.MemberID)
	// 	 LEFT JOIN Currency ON (A_Member.MemberCurrency = Currency.code)
	// 	 WHERE MemberFinance2.MemberId = '".$toMemberID."' AND MemberFinance2.PointType = 'Game'");
	// 	$db->execute();
	// 	$result = '';
	// 	$errMsg = array();
	// 	if($db->total_row > 0){
	// 		$note       = NULL;
	// 		$memberData = $db->row;
	// 		if(!isset($memberData['rate'])){
	// 			breakoff($langAry['ERRNoCurrency'], $referer);
	// 			exit;
	// 		}
	// 		$db->selectTB('Admin');
	// 		$db->getData("*", "WHERE admin_id = '".$memberData['UpAdmin_id']."'");
	// 		$db->execute();
	// 		if($db->total_row <= 0){
	// 			breakoff($langAry['ERRNoUpAccount'], $referer);
	// 			unset($db);
	// 			exit;
	// 		}

	// 		$upAdminData = $db->row;
	// 		$allowManger = false;
	// 		$Points      = new Points($nowLoginData);
	// 		$Points->produceLink($upAdminData);
	// 		$allowManger = $Points->allowSearch;
	// 		unset($Points);
	// 		//該登入者不允許變更
	// 		if(!$allowManger){
	// 			$_SESSION['err'] = '3';
	// 			header('location: /index.php');
	// 			exit;
	// 		}

	// 		if(!empty($_POST['note'])){$note = $_POST['note'];}
	// 		$bank = new Bank();
	// 		$nowMemberPoints = $memberData['Points']/$bank->MoneyPointRate;
	// 		if(($toPoints*$memberData['rate']) > $nowMemberPoints){
	// 			breakoff($langAry['ERRMinusPointsOver'], $referer);
	// 			unset($db);
	// 			exit;
	// 		}
	// 		//扣点
	// 		$result = $bank->deductionMem( $event_id, $toMemberID, $nowAdmin_id, $toPoints, $note, ['MemberCurrency'=>$memberData['code'], 'CurrencyRate'=>$memberData['rate']]);
	// 		if($result){
	// 			if($nowAdmin_agid != '1'){
	// 				$result = $bank->creditFrom( $event_id, $nowAdmin_id, $toMemberID, $nowAdminPoints, $toPoints, $note, true, ['MemberCurrency'=>$memberData['code'], 'CurrencyRate'=>$memberData['rate']]);
	// 				if(!$result){
	// 					$errMsg[] = 'RechargeFail';
	// 				}
	// 			}
	// 		}else{
	// 			$errMsg[] = 'DeductionFail';
	// 		}
	// 		if(empty($errMsg)){
	// 			breakoff($langAry['MinusPointsSuccess'], $referer);
	// 			unset($db, $bank);
	// 			exit;
	// 		}		
	// 	}
	// 	if($db->total_row <= 0){$errMsg[] = $langAry['ERRNoID'];}
	// 	breakoff($langAry['MinusPointsFail'].'('.join("-", $errMsg).')'.(DEBUG || $nowAdmin_agid == '1' ? '('.$bank->getErrorMsg().')' : ''), $referer);
	// 	unset($db, $bank);
	// 	exit;
	default:
		$_SESSION['err'] = '3';
		header("location: /index.php");
		exit;
}
$_SESSION['err'] = '3';
header("location: /index.php");
exit;

function addAdminLogs($text, $AuthId, $note='')
{
	$db = new PDO_DB( DB_HOST, DB_USER, DB_PWD, DB_NAME);
	$db->selectTB('AdminLogs');
	$db->insertData([
		'admin_id'   => $AuthId,
		'text'       => json_encode($text),
		'note'       => (empty($note) ? null : $note),
		'created_at' => date('Y-m-d H:i:s')
	]);
	$result = $db->execute();
	unset($db);
	return $result;
}

function breakoff($msg, $referer = 'index.php'){
	$ary['msg'] = $msg;
	$_SESSION['_err'] = json_encode($ary);
	header('location: '.$referer);
	exit;
}
?>