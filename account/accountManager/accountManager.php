<?php
//----------------------------------开发完毕后，请删除
//计算执行时间
$stime=explode(" ",microtime());
$ss=$stime[0]+$stime[1];
//----------------------------------开发完毕后，请删除
include_once $_SERVER['DOCUMENT_ROOT'].'/class/Template.class.php';
include_once $_SERVER['DOCUMENT_ROOT'].'/class/Points.class.php';

//载入基础样版
$html = new Template($_SERVER['DOCUMENT_ROOT']."/tpl/public_temp.html");

//未登入
if($html->WebLogin->getStatus() != '0'){
	header("location: /index.php?err=".$html->WebLogin->getStatus());
	exit;
}
//下期占成設定
$dateAry        = $html->dateArray();
$count          = count($dateAry['start']);
$startDate      = $dateAry['start'][$count-2];//預設查詢最新
$endDate        = $dateAry['end'][$count-1];
$ag_id          = $html->WebLogin->ag_id;
$updateAdminBtn = '';
$addMemberBtn   = '<button data-whatever="'.$html->lang['AddMasterAgent'].'" data-target="#dialogModal" data-fn="addMember" class="dialogModal btn btn-sm btn-info pull-right" type="button">'.$html->lang['AddMasterAgent'].'</button>';
$addAgentBtn    = '<button data-whatever="'.$html->lang['AddAgent'].'" data-target="#dialogModal" data-fn="addAgent" class="dialogModal btn btn-sm btn-default pull-right" type="button">'.$html->lang['AddAgent'].'</button>';
$addSubAgentBtn = '<button data-whatever="'.$html->lang['AddSubAgent'].'" data-target="#dialogModal" data-fn="addSubAgent" class="dialogModal btn btn-sm btn-default pull-right" type="button">'.$html->lang['AddSubAgent'].'</button>';
$addMemBtn      = '<button data-whatever="'.$html->lang['AddMember'].'" data-target="#dialogModal" data-fn="addMem" class="dialogModal btn btn-sm btn-default pull-right" type="button">'.$html->lang['AddMember'].'</button>';
//20150709-泰國需求增加一功能交易轉帳取消同頁面管理
$plusBtn        = '';
$minusBtn       = '';
// $plusBtn        = '<button data-whatever="%s" data-target="#dialogModal" data-toggle="tooltip" data-placement="top" title="%s" data-fn="%s" data-id="%s" class="dialogModal btn btn-xs btn-info badge pull-right" type="button" style="margin-left:5px;padding: 1px 1px 2px 3px;"><span aria-hidden="true" class="glyphicon glyphicon-plus"></span></button>';
// $minusBtn       = '<button data-whatever="%s" data-target="#dialogModal" data-toggle="tooltip" data-placement="top" title="%s" data-fn="%s" data-id="%s" class="dialogModal btn btn-xs btn-danger badge pull-right" type="button" style="margin-left:5px;padding: 2px 2px 1px;"><span aria-hidden="true" class="glyphicon glyphicon-minus"></span></button>';
$refreshBtn     = '<button data-fn="refreshPoints" data-id="%s" data-toggle="tooltip" data-placement="top" title="'.$html->lang['RefreshPoints'].'" class="refreshBtn btn btn-xs btn-success badge pull-right" type="button" style="margin-left:5px;padding: 2px 2px 1px;"><span aria-hidden="true" class="glyphicon glyphicon-refresh"></span></button>';
$updBtn         = '<button data-whatever="'.$html->lang['UpdateData'].'" data-toggle="tooltip" data-placement="top" title="'.$html->lang['UpdateData'].'" data-target="#dialogModal" data-fn="%s" data-id="%s" class="dialogModal btn btn-xs btn-default pull-left" type="button"><span class="glyphicon glyphicon-cog" aria-hidden="true"></span></button>';
$stopBtn        = '<button data-whatever="'.$html->lang['StopAccount'].'" data-toggle="tooltip" data-placement="top" title="'.$html->lang['StopAccount'].'" data-target="#dialogModal" data-fn="%s" data-id="%s" class="dialogModal btn btn-xs btn-warning pull-left" type="button"><span class="glyphicon glyphicon-ban-circle" aria-hidden="true"></span></button>';
$unblockBtn     = '<button data-whatever="'.$html->lang['UnblockAccount'].'" data-toggle="tooltip" data-placement="top" title="'.$html->lang['UnblockAccount'].'" data-target="#dialogModal" data-fn="%s" data-id="%s" class="dialogModal btn btn-xs btn-primary pull-left" type="button"><span class="glyphicon glyphicon-play" aria-hidden="true"></span></button>';
$delBtn         = '<button data-whatever="'.$html->lang['DelAccount'].'" data-toggle="tooltip" data-placement="top" title="'.$html->lang['DelAccount'].'" data-target="#dialogModal" data-fn="%s" data-id="%s" class="dialogModal btn btn-xs btn-danger pull-left" type="button"><span class="glyphicon glyphicon-trash" aria-hidden="true"></span></button>';
$pointLogBtn    = '<a href="/reportData/pointLog/pointLog.php?acc=%s" target="_blank" data-toggle="tooltip" data-placement="top" title="'.$html->lang['MenuName-pointLog'].'" class="btn btn-xs btn-info pull-left"><span class="glyphicon glyphicon-usd" aria-hidden="true"></span></a>';
$betLogBtn      = '<a href="/reportData/betLog/betLog.php?mem=%s" target="_blank" data-toggle="tooltip" data-placement="top" title="'.$html->lang['MenuName-betLog'].'" class="btn btn-xs btn-info pull-left"><span class="glyphicon glyphicon-usd" aria-hidden="true"></span></a>';

$pointPagePower = $html->WebLogin->checkInitPowerById(8)[0] ? true : false;
$betPagePower   = $html->WebLogin->checkInitPowerById(9)[0] ? true : false;

//data-fn ActionName
$plusMemPoints    = 'plusMemPoints';
$plusAgentPoints  = 'plusAgentPoints';
$minusAgentPoints = 'minusAgentPoints';
$minusMemPoints   = 'minusMemPoints';
$updAdminData     = 'updAgentData';
$updMemData       = 'updMemData';
$stopAdminData    = 'stopAgentData';
$stopMemData      = 'stopMemData';
$unblockAdminData = 'unblockAgentData';
$unblockMemData   = 'unblockMemData';
$delAdminData     = 'delAgentData';
$delMemData       = 'delMemData';

//20150721-新增警報系統
$alarmBtn          = '';
$alarmSingleBtn    = '';
$disAlarmSingleBtn = '';
$alarmMemBtn       = '';
$disAlarmMemBtn    = '';

$cssImport          = array();
$cssImport[]        = '<link rel="stylesheet" href="css/style.css">';
$javascriptImport   = array();
$javascriptImport[] = '<script type="text/javascript" src="js/custom.js"> </script>';
//管理者本页权限
switch($html->WebLogin->nowPagePower){
	case 'w':
		if(EMERGENCY == 'y' && in_array($ag_id, ['1', '2', '3'])){
			$alarmBtn          = '<button data-whatever="'.$html->lang['AlarmModal'].'" data-toggle="tooltip" data-placement="top" title="'.$html->lang['AlarmModal'].'" data-target="#dialogModal" data-fn="%s" data-id="%s" class="dialogModal btn btn-sm btn-danger pull-right" type="button"><span class="glyphicon glyphicon-flash" aria-hidden="true"></span></button>';//20150720-增加緊急處理按鈕
			$alarmOneBtn       = '<button data-whatever="'.$html->lang['AlarmModal'].'" data-toggle="tooltip" data-placement="top" title="'.$html->lang['AlarmModal'].'" data-target="#dialogModal" data-fn="%s" data-id="%s" class="dialogModal btn btn-xs btn-danger pull-left" type="button"><span class="glyphicon glyphicon-flash" aria-hidden="true"></span></button>';//20150720-增加緊急處理按鈕
			$disAlarmBtn       = '<button data-whatever="'.$html->lang['DisAlarmModal'].'" data-toggle="tooltip" data-placement="top" title="'.$html->lang['DisAlarmModal'].'" data-target="#dialogModal" data-fn="%s" data-id="%s" class="dialogModal btn btn-xs btn-success pull-left" type="button"><span class="glyphicon glyphicon-flash" aria-hidden="true"></span></button>';//20150720-增加緊急處理按鈕
			$alarmBtn          = sprintf($alarmBtn, 'alarm', '');//20150721-新增警報系統
			$alarmSingleBtn    = sprintf($alarmOneBtn, 'alarmSingle', '%s');
			$disAlarmSingleBtn = sprintf($disAlarmBtn, 'disAlarmSingle', '%s');
			// $alarmMemBtn       = sprintf($alarmOneBtn, 'alarmMem', '%s');
			// $disAlarmMemBtn    = sprintf($disAlarmBtn, 'disAlarmMem', '%s');
		}
		switch ($ag_id) {
			case '3'://代理帐号的新增会员按钮
				$updateAdminBtn = $addMemBtn;
				if($html->WebLogin->canAddAgent){
					$updateAdminBtn = $addMemBtn.$addAgentBtn;
				}
				$plusAgentBtn      = sprintf($plusBtn, $html->lang['AgentPlusPoint'], $html->lang['AgentPlusPoint'], $plusAgentPoints, '%s');
				$plusMemBtn        = sprintf($plusBtn, $html->lang['AgentPlusPoint'], $html->lang['AgentPlusPoint'], $plusMemPoints, '%s');
				$minusBtn          = sprintf($minusBtn, $html->lang['AgentMinusPoint'], $html->lang['AgentMinusPoint'], '%s', '%s');
				$delBtn            = '';//20150715-新增只有管理員有刪除功能
				break;
			case '2'://总代帐号的新增代理按钮
				$plusAgentBtn      = sprintf($plusBtn, $html->lang['MAgentPlusPoint'], $html->lang['MAgentPlusPoint'], $plusAgentPoints, '%s');
				$plusMemBtn        = sprintf($plusBtn, $html->lang['AgentPlusPoint'], $html->lang['AgentPlusPoint'], $plusMemPoints, '%s');
				$minusBtn          = sprintf($minusBtn, $html->lang['AgentMinusPoint'], $html->lang['AgentMinusPoint'], '%s', '%s');//扣点按钮
				$updateAdminBtn    = $addAgentBtn.$alarmBtn;
				$delBtn            = '';//20150715-新增只有管理員有刪除功能
				break;
			case '1'://管理员的新增总代按钮
				$plusBtn           = sprintf($plusBtn, $html->lang['ManagerPlusPoint'], $html->lang['ManagerPlusPoint'], '%s', '%s');//补点按钮
				$minusBtn          = sprintf($minusBtn, $html->lang['ManagerMinusPoint'], $html->lang['ManagerMinusPoint'], '%s', '%s');//扣点按钮
				$updateAdminBtn    = $addMemBtn.$addAgentBtn.$addMemberBtn.$alarmBtn;
				break;
		}
		break;
	case 'r':
		$plusAgentBtn      = '';
		$plusMemBtn        = '';
		$minusBtn          = '';
		$plusBtn           = '';
		$stopBtn           = '';
		$delBtn            = '';
		$updBtn            = '';
		$unblockBtn        = '';
		break;
	default:
		$_SESSION['err'] = '6';
		header("location: /index.php");
		exit;
}

//取得样版中的样版资料，作为次样版
$tableRowData = $html->getFile();

//20150720-加入子帳號判斷
$subAdminData = [];
if($html->WebLogin->ag_id == '4'){
	$db = new PDO_DB(DB_HOST, DB_USER, DB_PWD, DB_NAME);
	$db->selectTB('Admin');
	$db->getData("*", "WHERE admin_id = '".$html->WebLogin->subAdmin."'");
	$db->execute();
	if($db->total_row <= 0){
		$admin_acc = $html->WebLogin->admin_acc;//找不到子帳號，預設自己
	}
	if($db->total_row > 0){
		$admin_acc    = $db->row['admin_acc'];
		$subAdminData = $db->row;
	}
}else{
	$admin_acc = $html->WebLogin->admin_acc;
}
if(!empty($_GET['acc'])){//搜寻代理帐号
	$admin_acc = trim($_GET['acc']);
	$admin_acc = stripslashes($admin_acc);
	$admin_acc = str_replace("'", "''", $admin_acc);
	$admin_acc = stripslashes($admin_acc);
}
$mem = '';
if(empty($_GET['acc']) && !empty($_GET['mem'])){//搜寻会员帐号-代理帐号必须为空，以代理优先查询
	$mem = trim($_GET['mem']);
	$mem = stripslashes($mem);
	$mem = str_replace("'", "''", $mem);
	$mem = stripslashes($mem); 
	$admin_acc = '';
}
//回主帐号连结
$goBackMine = '';
if(($html->WebLogin->ag_id != '4' && $admin_acc != $html->WebLogin->admin_acc) || ($html->WebLogin->ag_id == '4' && !empty($subAdminData['admin_acc']) && $admin_acc != $subAdminData['admin_acc'])){
	$goBackMine = '<a href="'.$_SERVER['SCRIPT_NAME'].'" class="btn btn-default pull-right">'.$html->lang['BackMainAcc'].'</a>';
}

//取得管理表资料
$notice      = '';
$enableOK    = $html->regexMatch($tableRowData, '<!--__enableOKStart', 'enableOKEnd__-->');
$enableNO    = $html->regexMatch($tableRowData, '<!--__enableNOStart', 'enableNOEnd__-->');
$enableNOTip = $html->regexMatch($tableRowData, '<!--__enableNOTipStart', 'enableNOTipEnd__-->');
$agentTemp   = $html->getFile($_SERVER['DOCUMENT_ROOT'].'/tpl/account/accountManager/agentTemp.html');
$memTemp     = $html->getFile($_SERVER['DOCUMENT_ROOT'].'/tpl/account/accountManager/memTemp.html');
$admin_list  = new PDO_DB(DB_HOST, DB_USER, DB_PWD, DB_NAME);

//代理帐号搜寻
if(!empty($admin_acc)){
	$breadcrumb[] = '<li class="active">'.$admin_acc.'</li>';
	$admin_list->selectTB('Admin');	//选择资料表
	$admin_list->getData("*", "WHERE admin_acc = '".$admin_acc."'");
	$admin_list->execute();

	//搜尋有該代理
	if($admin_list->total_row > 0){
		try{
			//产生单笔资料(用次样版$tableRowData做为基本格式)
			$enable    = $admin_list->row['admin_enable'] == 'y' ? true : false;
			$alarmType = $admin_list->row['alarmType'] == 'y' ? true : false;

			$admin_list->row['admin_last_login_time'] = empty($admin_list->row['admin_last_login_time']) ? '--' : $admin_list->row['admin_last_login_time'];
			$admin_list->row['last_login_ip']         = empty($admin_list->row['last_login_ip']) ? '--' : $admin_list->row['last_login_ip'];
			$admin_list->row['admin_enable']          = $admin_list->row['admin_enable'] == 'y' ? $enableOK : $html->regexReplace(['note'=>$admin_list->row['disable_note']], $enableNOTip);
			$admin_list->row['canAddAgent']           = ($admin_list->row['canAddAgent'] == 'y' || in_array($admin_list->row['ag_id'], ['1','2'])) ? $enableOK : $enableNO;
			$admin_list->row['points']                = $admin_list->row['points'];
			$admin_list->row['adminUpdBtn']           = '--';
			$admin_list->row['ag_title']              = $html->lang['Group'.$admin_list->row['ag_id']];
			// 2014-12-5 修正代理總數、會員總數欄位
			// $admin_list->row['downCount']             = $admin_list->row['ag_id'] != '3' ? $admin_list->row['downCount'] : $admin_list->row['downMemCount']; 
			
			$ag_id           = $admin_list->row['ag_id'];
			$admin_id        = $admin_list->row['admin_id'];
			$upAdmin_id      = $admin_list->row['upAdmin_id'];
			$downCount       = $admin_list->row['downCount'];
			$downMemCount    = $admin_list->row['downMemCount'];
			$checkUpAdmin_id = $upAdmin_id;

			$Points = new Points($html->WebLogin);
			$Points->produceLink($admin_list->row);
			//无权限搜寻不是自己团队的人
			$breadcrumb = $Points->breadcrumb;
			$allowSearch = $Points->allowSearch;
			if(!$allowSearch){ throw new Exception("not allow to search"); }
			//回傳正確Rate
			$admin_list->row = $Points->getAdminRate($admin_list->row, $startDate, $endDate);

			$operationBtn = [];
			//管理员權限
			if($html->WebLogin->ag_id == '1'){
				$thisBtn = sprintf($minusBtn, $minusAgentPoints, $admin_list->row['admin_id']);//扣点按钮
				$admin_list->row['points'] = $admin_list->row['points'].$thisBtn;
				$thisBtn = sprintf($plusBtn, $plusAgentPoints, $admin_list->row['admin_id']);//补点按钮
				$admin_list->row['points'] = $admin_list->row['points'].$thisBtn;

				$operationBtn[] = sprintf($updBtn, $updAdminData, $admin_list->row['admin_id']);//修改資料
				if($pointPagePower){
					$operationBtn[] = sprintf($pointLogBtn, $admin_list->row['admin_acc']);//資金明細
				}
				if($enable){
					$operationBtn[] = sprintf($stopBtn, $stopAdminData, $admin_list->row['admin_id']);//代理停權按鈕
				}else{
					$operationBtn[] = sprintf($unblockBtn, $unblockAdminData, $admin_list->row['admin_id']);//代理解除封鎖按鈕
				}
				$operationBtn[] = sprintf($delBtn, $delAdminData, $admin_list->row['admin_id']);//刪除代理按鈕
				//20150721-新增警報系統
				if($alarmType){
					$operationBtn[] = sprintf($disAlarmSingleBtn, $admin_list->row['admin_id']);
				}else{
					$operationBtn[] = sprintf($alarmSingleBtn, $admin_list->row['admin_id']);
				}
			}
			//查詢帳號之上層 = 目前登入帳號
			elseif($admin_list->row['upAdmin_id'] == $html->WebLogin->admin_id){
				$thisBtn = sprintf($minusBtn, $minusAgentPoints, $admin_list->row['admin_id']);//扣点按钮
				$admin_list->row['points'] = $admin_list->row['points'].$thisBtn;
				$thisBtn = sprintf($plusAgentBtn, $admin_list->row['admin_id']);
				$admin_list->row['points'] = $admin_list->row['points'].$thisBtn;

				$operationBtn[] = sprintf($updBtn, $updAdminData, $admin_list->row['admin_id']);//修改資料
				if($pointPagePower){
					$operationBtn[] = sprintf($pointLogBtn, $admin_list->row['admin_acc']);//資金明細
				}
				if($enable){
					$operationBtn[] = sprintf($stopBtn, $stopAdminData, $admin_list->row['admin_id']);//代理停權按鈕
				}else{
					$operationBtn[] = sprintf($unblockBtn, $unblockAdminData, $admin_list->row['admin_id']);//代理解除封鎖按鈕
				}
				$operationBtn[] = sprintf($delBtn, $delAdminData, $admin_list->row['admin_id']);//刪除代理按鈕
				//20150721-新增警報系統
				if($alarmType){
					$operationBtn[] = sprintf($disAlarmSingleBtn, $admin_list->row['admin_id']);
				}else{
					$operationBtn[] = sprintf($alarmSingleBtn, $admin_list->row['admin_id']);
				}
			}
			//登入帳號為上層且有權限管理
			elseif($admin_list->row['adminUpdBtn'] == '--' && $allowSearch && $admin_id != $html->WebLogin->admin_id){
				$operationBtn[] = sprintf($updBtn, $updAdminData, $admin_list->row['admin_id']);//修改資料
				if($pointPagePower){
					$operationBtn[] = sprintf($pointLogBtn, $admin_list->row['admin_acc']);//資金明細
				}
				if($enable){
					$operationBtn[] = sprintf($stopBtn, $stopAdminData, $admin_list->row['admin_id']);//代理停權按鈕
				}else{
					$operationBtn[] = sprintf($unblockBtn, $unblockAdminData, $admin_list->row['admin_id']);//代理解除封鎖按鈕
				}
				$operationBtn[] = sprintf($delBtn, $delAdminData, $admin_list->row['admin_id']);//刪除代理按鈕
				//20150721-新增警報系統
				if($alarmType){
					$operationBtn[] = sprintf($disAlarmSingleBtn, $admin_list->row['admin_id']);
				}else{
					$operationBtn[] = sprintf($alarmSingleBtn, $admin_list->row['admin_id']);
				}
			}
			//本人權限
			elseif($admin_list->row['adminUpdBtn'] == '--' && $allowSearch && $admin_id == $html->WebLogin->admin_id){
				if($pointPagePower){
					$operationBtn[] = sprintf($pointLogBtn, $admin_list->row['admin_acc']);//資金明細
				}
			}
			$admin_list->row['adminUpdBtn'] = join("", $operationBtn);
			if(empty($admin_list->row['adminUpdBtn'])){
				$admin_list->row['adminUpdBtn'] = '--';
			}
			$tableData = $admin_list->row;

			//下线总数 > 0 搜寻下线代理
			if($ag_id == '1' || $downCount > 0){
				//如果是管理員可看到全部總代
				$sql = "WHERE upAdmin_id = '".$admin_id."'";
				if($ag_id == '1'){
					$sql = "WHERE ag_id = '2'";
				}

				$admin_list->selectTB('Admin');	//选择资料表
				$admin_list->getData("*", $sql);
				$admin_list->execute();
				if($admin_list->total_row > 0){
					$agentList    = '';
					$agentSubTemp = $html->regexMatch($agentTemp, '<!--__downAgentStart', 'downAgentEnd__-->');

					do{
						$enable    = $admin_list->row['admin_enable'] == 'y' ? true : false;
						$alarmType = $admin_list->row['alarmType'] == 'y' ? true : false;

						$admin_list->row['admin_last_login_time'] = empty($admin_list->row['admin_last_login_time']) ? '--' : $admin_list->row['admin_last_login_time'];
						$admin_list->row['last_login_ip']         = empty($admin_list->row['last_login_ip']) ? '--' : $admin_list->row['last_login_ip'];
						$admin_list->row['admin_enable']          = $admin_list->row['admin_enable'] == 'y' ? $enableOK : $html->regexReplace(['note'=>$admin_list->row['disable_note']], $enableNOTip);
						$admin_list->row['canAddAgent']           = ($admin_list->row['canAddAgent'] == 'y' || in_array($admin_list->row['ag_id'], ['1','2'])) ? $enableOK : $enableNO;
						$admin_list->row['points']                = $admin_list->row['points'];
						$admin_list->row['adminUpdBtn']           = '--';
						$admin_list->row['ag_title']              = $html->lang['Group'.$admin_list->row['ag_id']];
						// 2014-12-5 修正代理總數、會員總數欄位
						// $admin_list->row['downCount']             = $admin_list->row['ag_id'] != '3' ? $admin_list->row['downCount'] : $admin_list->row['downMemCount']; 
						$admin_list->row = $Points->getAdminRate($admin_list->row, $startDate, $endDate);
						$operationBtn = [];
						//管理员權限
						if($html->WebLogin->ag_id == '1'){
							$thisBtn = sprintf($minusBtn, $minusAgentPoints, $admin_list->row['admin_id']);//扣点按钮
							$admin_list->row['points'] = $admin_list->row['points'].$thisBtn;
							$thisBtn = sprintf($plusBtn, $plusAgentPoints, $admin_list->row['admin_id']);//补点按钮
							$admin_list->row['points'] = $admin_list->row['points'].$thisBtn;

							$operationBtn[] = sprintf($updBtn, $updAdminData, $admin_list->row['admin_id']);//修改資料
							if($pointPagePower){
								$operationBtn[] = sprintf($pointLogBtn, $admin_list->row['admin_acc']);//資金明細
							}
							if($enable){
								$operationBtn[] = sprintf($stopBtn, $stopAdminData, $admin_list->row['admin_id']);//代理停權按鈕
							}else{
								$operationBtn[] = sprintf($unblockBtn, $unblockAdminData, $admin_list->row['admin_id']);//代理解除封鎖按鈕
							}
							$operationBtn[] = sprintf($delBtn, $delAdminData, $admin_list->row['admin_id']);//刪除代理按鈕
							//20150721-新增警報系統
							if($alarmType){
								$operationBtn[] = sprintf($disAlarmSingleBtn, $admin_list->row['admin_id']);
							}else{
								$operationBtn[] = sprintf($alarmSingleBtn, $admin_list->row['admin_id']);
							}
						}
						// 上層就是登入者
						elseif($admin_list->row['upAdmin_id'] == $html->WebLogin->admin_id){
							$thisBtn = sprintf($minusBtn, $minusAgentPoints, $admin_list->row['admin_id']);//扣点按钮
							$admin_list->row['points'] = $admin_list->row['points'].$thisBtn;
							$thisBtn = sprintf($plusAgentBtn, $admin_list->row['admin_id']);
							$admin_list->row['points'] = $admin_list->row['points'].$thisBtn;

							$operationBtn[] = sprintf($updBtn, $updAdminData, $admin_list->row['admin_id']);//修改資料
							if($pointPagePower){
								$operationBtn[] = sprintf($pointLogBtn, $admin_list->row['admin_acc']);//資金明細
							}
							if($enable){
								$operationBtn[] = sprintf($stopBtn, $stopAdminData, $admin_list->row['admin_id']);//代理停權按鈕
							}else{
								$operationBtn[] = sprintf($unblockBtn, $unblockAdminData, $admin_list->row['admin_id']);//代理解除封鎖按鈕
							}
							$operationBtn[] = sprintf($delBtn, $delAdminData, $admin_list->row['admin_id']);//刪除代理按鈕
							//20150721-新增警報系統
							if($alarmType){
								$operationBtn[] = sprintf($disAlarmSingleBtn, $admin_list->row['admin_id']);
							}else{
								$operationBtn[] = sprintf($alarmSingleBtn, $admin_list->row['admin_id']);
							}
						}
						//表示是上層有權限管理
						elseif($admin_list->row['adminUpdBtn'] == '--' && $allowSearch){
							$operationBtn[] = sprintf($updBtn, $updAdminData, $admin_list->row['admin_id']);//修改資料
							if($pointPagePower){
								$operationBtn[] = sprintf($pointLogBtn, $admin_list->row['admin_acc']);//資金明細
							}
							if($enable){
								$operationBtn[] = sprintf($stopBtn, $stopAdminData, $admin_list->row['admin_id']);//代理停權按鈕
							}else{
								$operationBtn[] = sprintf($unblockBtn, $unblockAdminData, $admin_list->row['admin_id']);//代理解除封鎖按鈕
							}
							$operationBtn[] = sprintf($delBtn, $delAdminData, $admin_list->row['admin_id']);//刪除代理按鈕
							//20150721-新增警報系統
							if($alarmType){
								$operationBtn[] = sprintf($disAlarmSingleBtn, $admin_list->row['admin_id']);
							}else{
								$operationBtn[] = sprintf($alarmSingleBtn, $admin_list->row['admin_id']);
							}
						}
						$admin_list->row['adminUpdBtn'] = join("", $operationBtn);
						if(empty($admin_list->row['adminUpdBtn'])){
							$admin_list->row['adminUpdBtn'] = '--';
						}
						$agentList .= $html->regexReplace($admin_list->row, $agentSubTemp);
					}while($admin_list->row = $admin_list->fetch_assoc());
					$mergeAry     = array("downAgent" => $agentList);
					$agentList    = $html->regexReplace($mergeAry, $agentTemp, '<!--__', '__-->');//套上次样板
					$mergeAry     = array("agentTemp" => $agentList);
					$tableRowData = $html->regexReplace($mergeAry, $tableRowData, '<!--__', '__-->');//套上主样板
				}
			}
			//下线总数 > 0 搜寻下线会员
			if($downMemCount > 0){
				$admin_list->selectTB('A_Member');	//选择资料表
				$admin_list->getData("A_Member.*, MemberFinance2.Points, MemberAuthorizeChange.Memos, Currency.title", 
					"LEFT JOIN MemberFinance2 ON (MemberFinance2.MemberId = A_Member.MemberID)
					 LEFT JOIN MemberAuthorizeChange ON (MemberAuthorizeChange.MACID = (SELECT TOP 1 MemberAuthorizeChange.MACID FROM MemberAuthorizeChange WHERE MemberAuthorizeChange.ExecAgentID = A_Member.PauseAccountAgentID AND A_Member.PauseAccount = '1' AND MemberAuthorizeChange.ChangeType = 'PauseAccount' ORDER BY MemberAuthorizeChange.CreateDate DESC))
					 LEFT JOIN Currency ON (A_Member.MemberCurrency = Currency.code)
					 WHERE A_Member.UpAdmin_id = '".$admin_id."' AND MemberFinance2.PointType = 'Game'");
				$admin_list->execute();
				if($admin_list->total_row > 0){
					$memList  = '';
					$listTemp = $html->regexMatch($memTemp, '<!--__downMemberStart', 'downMemberEnd__-->');
					do{
						$enable    = $admin_list->row['PauseAccount'] == 0 ? true : false;
						$alarmType = $admin_list->row['AlarmType'] == 1 ? true : false;

						$admin_list->row['LastLoginDate'] = empty($admin_list->row['LastLoginDate']) ? '--' : $admin_list->row['LastLoginDate'];
						$admin_list->row['LastLoginIp']   = empty($admin_list->row['LastLoginIp']) ? '--' : $admin_list->row['LastLoginIp'];
						$admin_list->row['PauseAccount']  = $admin_list->row['PauseAccount'] == 0 ? $enableOK : $html->regexReplace(['note'=>$admin_list->row['Memos']], $enableNOTip);
						$admin_list->row['Points']        = $admin_list->row['Points']/1000000;
						$admin_list->row['Points']        = '<span>'.$admin_list->row['Points'].'</span>';
						$admin_list->row['adminUpdBtn']   = '--';
						//更新點數按鈕 
						$thisBtn = sprintf($refreshBtn, $admin_list->row['MemberID']);
						$admin_list->row['Points'] = $admin_list->row['Points'].$thisBtn;

						$operationBtn = [];
						//管理员權限
						if($html->WebLogin->ag_id == '1'){
							$thisBtn = sprintf($minusBtn, $minusMemPoints, $admin_list->row['MemberID']);//扣点按钮
							$admin_list->row['Points'] = $admin_list->row['Points'].$thisBtn;
							$thisBtn = sprintf($plusBtn, $plusMemPoints, $admin_list->row['MemberID']);//补点按钮
							$admin_list->row['Points'] = $admin_list->row['Points'].$thisBtn;

							$operationBtn[] = sprintf($updBtn, $updMemData, $admin_list->row['MemberID']);//修改資料
							if($betPagePower){
								$operationBtn[] = sprintf($betLogBtn, $admin_list->row['MemberAccount']);//押注明細
							}
							if($enable){
								$operationBtn[] = sprintf($stopBtn, $stopMemData, $admin_list->row['MemberID']);//會員停權按鈕
							}else{
								$operationBtn[] = sprintf($unblockBtn, $unblockMemData, $admin_list->row['MemberID']);//會員解除封鎖按鈕
							}
							$operationBtn[] = sprintf($delBtn, $delMemData, $admin_list->row['MemberID']);//刪除會員按鈕
							//20150721-新增警報系統
							if($alarmType){
								$operationBtn[] = sprintf($disAlarmMemBtn, $admin_list->row['MemberID']);
							}else{
								$operationBtn[] = sprintf($alarmMemBtn, $admin_list->row['MemberID']);
							}
						}
						//只有上層能夠管理
						elseif($admin_list->row['UpAdmin_id'] == $html->WebLogin->admin_id){
							$thisBtn = sprintf($minusBtn, $minusMemPoints, $admin_list->row['MemberID']);//扣点按钮
							$admin_list->row['Points'] = $admin_list->row['Points'].$thisBtn;
							$thisBtn = sprintf($plusMemBtn, $admin_list->row['MemberID']);
							$admin_list->row['Points'] = $admin_list->row['Points'].$thisBtn;

							$operationBtn[] = sprintf($updBtn, $updMemData, $admin_list->row['MemberID']);//修改資料
							if($betPagePower){
								$operationBtn[] = sprintf($betLogBtn, $admin_list->row['MemberAccount']);//押注明細
							}
							if($enable){
								$operationBtn[] = sprintf($stopBtn, $stopMemData, $admin_list->row['MemberID']);//會員停權按鈕
							}else{
								$operationBtn[] = sprintf($unblockBtn, $unblockMemData, $admin_list->row['MemberID']);//會員解除封鎖按鈕
							}
							$operationBtn[] = sprintf($delBtn, $delMemData, $admin_list->row['MemberID']);//刪除會員按鈕
							//20150721-新增警報系統
							if($alarmType){
								$operationBtn[] = sprintf($disAlarmMemBtn, $admin_list->row['MemberID']);
							}else{
								$operationBtn[] = sprintf($alarmMemBtn, $admin_list->row['MemberID']);
							}
						}
						//表示是上層有權限管理
						elseif($admin_list->row['adminUpdBtn'] == '--' && $allowSearch){
							$operationBtn[] = sprintf($updBtn, $updMemData, $admin_list->row['MemberID']);//修改資料
							if($betPagePower){
								$operationBtn[] = sprintf($betLogBtn, $admin_list->row['MemberAccount']);//押注明細
							}
							if($enable){
								$operationBtn[] = sprintf($stopBtn, $stopMemData, $admin_list->row['MemberID']);//會員停權按鈕
							}else{
								$operationBtn[] = sprintf($unblockBtn, $unblockMemData, $admin_list->row['MemberID']);//會員解除封鎖按鈕
							}
							$operationBtn[] = sprintf($delBtn, $delMemData, $admin_list->row['MemberID']);//刪除會員按鈕
							//20150721-新增警報系統
							if($alarmType){
								$operationBtn[] = sprintf($disAlarmMemBtn, $admin_list->row['MemberID']);
							}else{
								$operationBtn[] = sprintf($alarmMemBtn, $admin_list->row['MemberID']);
							}
						}
						$admin_list->row['adminUpdBtn'] = join("", $operationBtn);
						if(empty($admin_list->row['adminUpdBtn'])){
							$admin_list->row['adminUpdBtn'] = '--';
						}

						$memList .= $html->regexReplace($admin_list->row, $listTemp);
					}while($admin_list->row = $admin_list->fetch_assoc());
					$mergeAry     = array("downMember" => $memList);
					$memList      = $html->regexReplace($mergeAry, $memTemp, '<!--__', '__-->');//套上次样板
					$mergeAry     = array("memTemp" => $memList);
					$tableRowData = $html->regexReplace($mergeAry, $tableRowData, '<!--__', '__-->');//套上主样板
				}
			}
			$tableRowData = $html->regexReplace($tableData, $tableRowData, "<!--__", "__-->");
		}catch(Exception $e){
			$notice = $html->__systemConfig['__notAllowSearch__'];
		}
	}
}
//会员帐号搜寻
if(!empty($mem)){
	$breadcrumb[] = '<li class="active">'.$mem.'</li>';
	$admin_list->selectTB('A_Member');	//选择资料表
	$admin_list->getData("A_Member.*, MemberFinance2.Points, MemberAuthorizeChange.Memos, Currency.title", 
		"LEFT JOIN MemberFinance2 ON (MemberFinance2.MemberId = A_Member.MemberID)
		 LEFT JOIN MemberAuthorizeChange ON (MemberAuthorizeChange.MACID = (SELECT TOP 1 MemberAuthorizeChange.MACID FROM MemberAuthorizeChange WHERE MemberAuthorizeChange.ExecAgentID = A_Member.PauseAccountAgentID AND A_Member.PauseAccount = '1' AND MemberAuthorizeChange.ChangeType = 'PauseAccount' ORDER BY MemberAuthorizeChange.CreateDate DESC))
		 LEFT JOIN Currency ON (A_Member.MemberCurrency = Currency.code)
		 WHERE A_Member.MemberAccount = '".$mem."' AND MemberFinance2.PointType = 'Game'");
	$admin_list->execute();
	if($admin_list->total_row > 0){
		try{
			$enable    = $admin_list->row['PauseAccount'] == 0 ? true : false;
			$alarmType = $admin_list->row['AlarmType'] == 1 ? true : false;

			$admin_list->row['admin_acc']             = '<a href="/reportData/betLog/betLog.php?mem='.$admin_list->row['MemberAccount'].'" target="_blank">'.$admin_list->row['MemberAccount'].'</a>';
			$admin_list->row['admin_name']            = $admin_list->row['NickName'];
			$admin_list->row['ag_title']              = $html->lang['GroupMem'];
			$admin_list->row['downCount']             = '0';
			$admin_list->row['downMemCount']          = '0';
			$admin_list->row['points']                = $admin_list->row['Points']/1000000;
			$admin_list->row['points']                = '<span>'.$admin_list->row['points'].'</span>';
			$admin_list->row['commissionRate']        = 0;
			$admin_list->row['admin_addtime']         = $admin_list->row['CreateDate'];
			$admin_list->row['admin_enable']          = $admin_list->row['PauseAccount'] == '0' ? $enableOK : $html->regexReplace(['note'=>$admin_list->row['Memos']], $enableNOTip);
			$admin_list->row['canAddAgent']           = $enableNO;
			$admin_list->row['admin_last_login_time'] = empty($admin_list->row['LastLoginDate']) ? '--' : $admin_list->row['LastLoginDate'];
			$admin_list->row['last_login_ip']         = empty($admin_list->row['LastLoginIp']) ? '--' : $admin_list->row['LastLoginIp'];
			$admin_list->row['adminUpdBtn']           = '--';
			//更新點數按鈕 
			$thisBtn = sprintf($refreshBtn, $admin_list->row['MemberID']);
			$admin_list->row['points'] = $admin_list->row['points'].$thisBtn;

			$upAdmin_id  = $admin_list->row['UpAdmin_id'] == '0' ? 1 : $admin_list->row['UpAdmin_id'];

			$Points      = new Points($html->WebLogin);
			$upAdminData = $Points->chkUpAdmin($upAdmin_id);
			$Points->produceLink($upAdminData, $admin_list->row);
			$breadcrumb  = $Points->breadcrumb;
			$allowSearch = $Points->allowSearch;//是否同团队可搜寻
			//无权限搜寻不是自己团队的人
			if(!$allowSearch){ throw new Exception("not allow to search"); }

			$operationBtn = [];
			//管理员權限
			if($html->WebLogin->ag_id == '1'){
				$thisBtn = sprintf($minusBtn, $minusMemPoints, $admin_list->row['MemberID']);//扣点按钮
				$admin_list->row['points'] = $admin_list->row['points'].$thisBtn;
				$thisBtn = sprintf($plusBtn, $plusMemPoints, $admin_list->row['MemberID']);//补点按钮
				$admin_list->row['points'] = $admin_list->row['points'].$thisBtn;

				$operationBtn[] = sprintf($updBtn, $updMemData, $admin_list->row['MemberID']);//修改資料
				if($betPagePower){
					$operationBtn[] = sprintf($betLogBtn, $admin_list->row['MemberAccount']);//押注明細
				}
				if($enable){
					$operationBtn[] = sprintf($stopBtn, $stopMemData, $admin_list->row['MemberID']);//會員停權按鈕
				}else{
					$operationBtn[] = sprintf($unblockBtn, $unblockMemData, $admin_list->row['MemberID']);//會員解除封鎖按鈕
				}
				$operationBtn[] = sprintf($delBtn, $delMemData, $admin_list->row['MemberID']);//刪除會員按鈕
				//20150721-新增警報系統
				if($alarmType){
					$operationBtn[] = sprintf($disAlarmMemBtn, $admin_list->row['MemberID']);
				}else{
					$operationBtn[] = sprintf($alarmMemBtn, $admin_list->row['MemberID']);
				}
			}
			//該會員上層就是登入者
			elseif($admin_list->row['UpAdmin_id'] == $html->WebLogin->admin_id){
				$thisBtn = sprintf($minusBtn, $minusMemPoints, $admin_list->row['MemberID']);//扣点按钮
				$admin_list->row['points'] = $admin_list->row['points'].$thisBtn;
				$thisBtn = sprintf($plusMemBtn, $admin_list->row['MemberID']);
				$admin_list->row['points'] = $admin_list->row['points'].$thisBtn;

				$operationBtn[] = sprintf($updBtn, $updMemData, $admin_list->row['MemberID']);//修改資料
				if($betPagePower){
					$operationBtn[] = sprintf($betLogBtn, $admin_list->row['MemberAccount']);//押注明細
				}
				if($enable){
					$operationBtn[] = sprintf($stopBtn, $stopMemData, $admin_list->row['MemberID']);//會員停權按鈕
				}else{
					$operationBtn[] = sprintf($unblockBtn, $unblockMemData, $admin_list->row['MemberID']);//會員解除封鎖按鈕
				}
				$operationBtn[] = sprintf($delBtn, $delMemData, $admin_list->row['MemberID']);//刪除會員按鈕
				//20150721-新增警報系統
				if($alarmType){
					$operationBtn[] = sprintf($disAlarmMemBtn, $admin_list->row['MemberID']);
				}else{
					$operationBtn[] = sprintf($alarmMemBtn, $admin_list->row['MemberID']);
				}
			}
			//表示是上層有權限管理
			elseif($allowSearch){
				$operationBtn[] = sprintf($updBtn, $updMemData, $admin_list->row['MemberID']);//修改資料
				if($betPagePower){
					$operationBtn[] = sprintf($betLogBtn, $admin_list->row['MemberAccount']);//押注明細
				}
				if($enable){
					$operationBtn[] = sprintf($stopBtn, $stopMemData, $admin_list->row['MemberID']);//會員停權按鈕
				}else{
					$operationBtn[] = sprintf($unblockBtn, $unblockMemData, $admin_list->row['MemberID']);//會員解除封鎖按鈕
				}
				$operationBtn[] = sprintf($delBtn, $delMemData, $admin_list->row['MemberID']);//刪除會員按鈕
				//20150721-新增警報系統
				if($alarmType){
					$operationBtn[] = sprintf($disAlarmMemBtn, $admin_list->row['MemberID']);
				}else{
					$operationBtn[] = sprintf($alarmMemBtn, $admin_list->row['MemberID']);
				}
			}
			$admin_list->row['adminUpdBtn'] = join("", $operationBtn);
			if(empty($admin_list->row['adminUpdBtn'])){
				$admin_list->row['adminUpdBtn'] = '--';
			}
			$tableRowData = $html->regexReplace($admin_list->row, $tableRowData, "<!--__", "__-->");
		}catch(Exception $e){
			$notice = $html->__systemConfig['__notAllowSearch__'];
		}
	}
}

if($admin_list->total_row <= 0){
	$notice = $html->__systemConfig['__notFound__'];
}

//-----------------------------------------将欲取代的内容与样版重新组合-------------------------------------------------
//欲取代的内容
$compilerAry = array();

//载入本页样版
$compilerAry['__MainContent__']      = $tableRowData;//取次样版
$compilerAry['__cssImport__']        = join("\n", $cssImport);//引用css
$compilerAry['__javascriptImport__'] = join("\n", $javascriptImport);//引用javascript
$compilerAry['__updateAdmin__']      = $updateAdminBtn;//按钮
$compilerAry['__notice__']           = $notice;//系统提示
$compilerAry['__breadcrumb__']       = join("", array_reverse($breadcrumb));
$compilerAry['__goBackMine__']       = $goBackMine;
$compilerAry['__accSearch__']        = $admin_acc;
$compilerAry['__memSearch__']        = $mem;

//----------------------------------开发完毕后，请删除
//计算执行时间
$mtime=explode(" ",microtime());
$es=$mtime[0]+$mtime[1];
$mtime=$es-$ss;	//总耗时
//----------------------------------开发完毕后，请删除
$compilerAry['__mtime__'] = '<div style="text-align:right;color:#255c88;">系统执行耗时:'. $mtime .'</div>';			//系统提示
//-----------------------------------------将欲取代的内容与样版重新组合-------------------------------------------------

//重新组合页面
$cleanHtml = $html->compiler($compilerAry);
echo $cleanHtml;
unset($html, $admin_list, $Points);
exit;
?>