<?php
include_once $_SERVER['DOCUMENT_ROOT'].'/class/Template.class.php';

//载入基础样版
$html = new Template($_SERVER['DOCUMENT_ROOT']."/tpl/public_temp.html");

//未登入
if($html->WebLogin->getStatus() != '0'){
	header("location: /index.php?err=".$html->WebLogin->getStatus());
	exit;
}
$ag_id              = $html->WebLogin->ag_id;
$addBtn             = '<button type="button" data-whatever="'.$html->lang['AddRemoteReward'].'" data-target="#dialogModal" data-fn="addRemote" class="dialogModal btn btn-sm btn-success pull-right">'.$html->lang['AddRemoteReward'].'</button>';
$updateAdminBtn     = '';
$cssImport          = [];
$javascriptImport[] = '<script type="text/javascript" src="js/custom.js"></script>';
$javascriptImport[] = '
<script type="text/javascript">
	$(function(){
		$(".dateBtn").click(function(){
			var dateString = $(this).data("string");
			$("#select option[value="+dateString+"]").attr("selected","selected").siblings("option").removeAttr("selected").parents("form").submit();
		});
	});</script>';
//管理者本页权限
switch($html->WebLogin->nowPagePower){
	case 'w':
		$updateAdminBtn = $addBtn;
		break;
	case 'r':
		break;
	default:
		$_SESSION['err'] = '6';
		header("location: /index.php");
		exit;
}
//取得样版中的样版资料，作为次样版
$tableRowData = $html->getFile();

$mem = '';
if(!empty($_GET['mem'])){//搜寻会员帐号-代理帐号必须为空，以代理优先查询
	$mem = trim($_GET['mem']);
	$mem = stripslashes($mem);
	$mem = str_replace("'", "''", $mem);
	$mem = stripslashes($mem); 
	$admin_acc = '';
}

$dateAry = $html->dateArray();
$count   = count($dateAry['start']);
$options = '';
$select  = empty($_GET['select']) ? $count-1 : $_GET['select'];
$today   = date('Y-m-d');
$lastday = date('Y-m-d', strtotime('-1 day'));
for ($i=$count-1; $i > 0; $i--) {
	$options .= '<option';
	if($i == $select){
		$options .= ' selected="selected"';
	}
	$options .= ' value="'.($i).'">'.(sprintf($html->lang['GameOption'], $i)).'：'.$dateAry['start'][$i-1].' '.$html->StartTime.' ～ '.$dateAry['end'][$i].' '.$html->EndTime.'</option>';
}
$options .= '<option value="today"'.($select=='today' ? ' selected="selected"' : '').'>'.$html->lang['DateToday'].'：'.$today.' '.$html->StartTime.' ～ '.$today.' '.$html->EndTime.'</option>';
$options .= '<option value="lastday"'.($select=='lastday' ? ' selected="selected"' : '').'>'.$html->lang['DateLastDay'].'：'.$lastday.' '.$html->StartTime.' ～ '.$lastday.' '.$html->EndTime.'</option>';

$startDate = $dateAry['start'][$count-2];//預設查詢最新
if(!empty($dateAry['start'][$select-1])){
	$startDate = $dateAry['start'][$select-1];
}
$endDate = $dateAry['end'][$count-1];
if(!empty($dateAry['end'][$select])){
	$endDate = $dateAry['end'][$select];
}
if($select == 'today'){
	$startDate = $today;
	$endDate   = $today;
}
if($select == 'lastday'){
	$startDate = $lastday;
	$endDate   = $lastday;
}
$startDateTime = $startDate.' '.$html->StartTime;
$endDateTime   = $endDate.' '.$html->EndTime;

//狀態列表
//RemoteState 遠端指定中彩金狀態(0:無,1:準備出彩金中,2:出彩金成功,3:玩家不在線上出獎失敗,4:玩家不在遊戲中出獎失敗,5:指定獎金比預存的獎金高出獎失敗,6:玩家在遊戲中離線出獎失敗,7:玩家在遊戲中出獎條件不符出獎失敗)。
$status = !isset($_GET['status']) ? 'all' : $_GET['status'];
$statusAry = [
	'0' => 'Fail',//失敗
	'1' => 'Processing',//出彩中
	'2' => 'Success',//成功
];
$statusClassAry = [
	'0' => 'danger',//失敗
	'1' => 'muted',//出彩中
	'2' => 'success',//成功
];
$statusList = '';
foreach ($statusAry as $key => $value) {
	$statusList .= '<option value="'.$key.'"'.(($key==$status&&$status!='all')? ' selected="selected"' : '').'>'.$html->lang['Status'.$value].'</option>';
}

// RemoteBonusType 遠端指定中彩金種類(0:無,1:指定中玩家中彩金,2:指定玩家中大獎,3:隨機指定玩家中彩金)

//取得管理表资料
$notice = '';
$db     = new PDO_DB(DB_HOST, DB_USER, DB_PWD, DB_NAME);
try {
	//統計現有額度
	$Statistics = 0;
	//查詢該期額度
	$db->selectTB('AdminBonus');
	$db->getData("*", "WHERE startDateTime <= '".$startDateTime."' AND endDateTime >= '".$endDateTime."' ORDER BY created_at DESC");
	$db->execute();
	$MaxBonus       = 0;
	$availableBonus = 0;
	if($db->total_row > 0){
		$MaxBonus += ($db->row['bonus']/$html->BonusRate);
		$availableBonus += ($db->row['surplusBonus']/$html->BonusRate);
	}
	//該期可用餘額
	$nowDateTime = date('Y-m-d H:i:s');
	if($startDateTime <= $nowDateTime && $endDateTime >= $nowDateTime){
		$db->selectTB('RemoteJPBonus_Param');
		$db->getData("*");
		$db->execute();
		if($db->total_row > 0 && $MaxBonus > 0){
			$availableBonus = 0;
			$availableBonus += ($db->row['Param1']/$html->BonusRate);
		}
	}

	//會員帐号搜寻
	$sql = '';
	if(!empty($mem)){
		$sql .= " AND A_Member.MemberAccount = '".$mem."'";
	}
	if(!empty($statusAry[$status])){
		if($status == 0){
			$sql .= " AND (RemoteBonusLog.RemoteState = '".$status."' OR RemoteBonusLog.RemoteState > '2')";
		}else{
			$sql .= " AND RemoteBonusLog.RemoteState = '".$status."'";
		}
	}
	$db->selectTB('RemoteBonusLog');
	$db->getData('RemoteBonusLog.*, A_Member.MemberAccount',"LEFT JOIN A_Member ON (A_Member.MemberID = RemoteBonusLog.MemberID)
	 WHERE RemoteBonusType = '2'
	 AND RemoteBonusLog.created_at >= '".$startDateTime."'
	 AND RemoteBonusLog.created_at <= '".$endDateTime."'".$sql);
	$db->pagingMSSQL(20, 'RemoteBonusLog.created_at');
	$db->execute();
	if($db->total_row <= 0){
		throw new Exception('No Row');
	}
	$tableRows    = '';
	$tableRowTemp = $html->regexMatch($tableRowData, '<!--__tableRecordsStart', 'tableRecordsEnd__-->');
	do{
		$db->row['GameTitle']       = empty($db->row['GameType']) ? '--' : $html->lang['GameName'.$db->row['GameType']];
		$db->row['RemoteBonusTime'] = empty($db->row['RemoteBonusTime']) ? '--' : $db->row['RemoteBonusTime'];
		$db->row['Note']            = empty($db->row['Note']) ? '--' : $db->row['Note'];
		if(!empty($db->row['RemoteJPoints']) && $db->row['RemoteJPoints'] > 0){
			$db->row['RemoteJPoints'] = ($db->row['RemoteJPoints']/$html->BonusRate);
			if($db->row['RemoteState'] == '2'){
				$Statistics += $db->row['RemoteJPoints'];
			}
			$db->row['RemoteJPoints'] = number_format($db->row['RemoteJPoints'], 2);
		}else{
			$db->row['RemoteJPoints'] = '--';
		}

		//遠端指定中彩金狀態(0:無,1:準備出彩金中,2:出彩金成功,3:玩家不在線上出獎失敗,4:玩家不在遊戲中出獎失敗,5:指定獎金比預存的獎金高出獎失敗,6:玩家在遊戲中離線出獎失敗,7:玩家在遊戲中出獎條件不符出獎失敗)
		$status = $db->row['RemoteState'];
		if($db->row['RemoteState'] == 0 || $db->row['RemoteState'] > 2){
			$status = 0;
		}
		$db->row['RemoteState'] = '<span class="text-'.$statusClassAry[$status].'">'.$html->lang['Status'.$statusAry[$status]].(in_array($html->WebLogin->ag_id, ['1']) && $status==0 ? '('.$db->row['RemoteState'].')' : '').'</span>';

		//前端顯示除以活動獎金比例
		$db->row['SpecifiedPoints'] = ($db->row['SpecifiedPoints']/$html->BonusRate);
		$db->row['SpecifiedPoints'] = number_format($db->row['SpecifiedPoints'], 2);

		$tableRows .= $html->regexReplace($db->row, $tableRowTemp);
	}while($db->row = $db->fetch_assoc());

	$pageTemp  = $html->regexMatch($tableRowData, '<!--__paginationStart', 'paginationEnd__-->');
	$tableInfo = [
		'tableRecords' => $tableRows,
		'RecordInfo' => $db->recordInfo($html->lang['RecordInfoStyle'])['default_style'],
		'pagination' => $html->regexReplace(['pagination'=>$db->createNumBar()['default_style']], $pageTemp),
	];

	$tableRowData = $html->regexReplace($tableInfo, $tableRowData, '<!--__', '__-->');
} catch (Exception $e) {
	switch ($e->getCode()) {
		case '2':
			$notice = $html->__systemConfig['__notAllowSearch__'];
			break;
		case '3':
			$notice = '<div class="alert text-center">'.$html->lang['ERRAccount'].'</div>';
			break;
		default:
			$notice = $html->__systemConfig['__notFound__'];
			break;
	}
	if(DEBUG){
		$notice .= $e->getMessage();
	}
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
$compilerAry['__acc__']              = $mem;
$compilerAry['__options__']          = $options;
$compilerAry['__statusList__']       = $statusList;
$compilerAry['__Statistics__']       = number_format($Statistics, 2);
$compilerAry['__availableBonus__']   = number_format($availableBonus, 2);
$compilerAry['__MaxBonus__']         = number_format($MaxBonus, 2);

//-----------------------------------------将欲取代的内容与样版重新组合-------------------------------------------------

//重新组合页面
$cleanHtml = $html->compiler($compilerAry);
echo $cleanHtml;
unset($html, $db);
exit;
?>