<?php
include_once $_SERVER['DOCUMENT_ROOT'].'/class/Template.class.php';
include_once $_SERVER['DOCUMENT_ROOT'].'/class/Points.class.php';

//载入基础样版
$html = new Template($_SERVER['DOCUMENT_ROOT']."/tpl/public_temp.html");

//未登入
if($html->WebLogin->getStatus() != '0'){
	header("location: /index.php?err=".$html->WebLogin->getStatus());
	exit;
}
$ag_id            = $html->WebLogin->ag_id;
$viewBtn          = '<button data-whatever="'.$html->lang['DetailContent'].'" data-target="#dialogModal" data-fn="viewDetail" data-id="%s" data-toggle="tooltip" data-placement="top" title="'.$html->lang['DetailContent'].'" data-modal-submit="false" class="dialogModal btn btn-xs btn-info pull-left" type="button"><span class="glyphicon glyphicon-list" aria-hidden="true"></span></button>';
$updateAdminBtn   = '';
$cssImport        = [];
$javascriptImport = [];
//管理者本页权限
switch($html->WebLogin->nowPagePower){
	case 'w':
		break;
	case 'r':
		break;
	default:
		header("location: /index.php?err=6");
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

//日期區間
$limitDate    = date( "Y-m-d", mktime(0,0,0,date("m")-2,date("d"),date("Y")));//限制搜尋僅前二個月
$thisMonFirst = date('Y-m-01');
$thisMonLast  = date('Y-m-t');
$startDate    = $thisMonFirst;
if(!empty($_GET['date_timepicker_start'])){
	$startDate = $_GET['date_timepicker_start'];
}
$endDate = $thisMonLast;
if(!empty($_GET['date_timepicker_end'])){
	$endDate = $_GET['date_timepicker_end'];
}
//如果超過預設限制搜尋大小
if($startDate < $limitDate){
	$startDate = $thisMonFirst;
	$endDate   = $thisMonLast;
}

//遊戲列表
$gameAry     = array();
$gameNameAry = array();
$db  = new PDO_DB( DB_HOST, DB_USER, DB_PWD, DB_NAME);
$db->selectTB('Game');
$db->getData("*");
$result = $db->execute();
if($db->total_row > 0){
	do{
		$gameAry[$db->row['GameID']]     = $db->row;
		$gameNameAry[$db->row['GameID']] = $db->row['GameName'];
	}while($db->row = $db->fetch_assoc());
}
$gameList = '';
$GameId = empty($_GET['game']) || $_GET['game'] == 'all' || !array_key_exists($_GET['game'], $gameAry) ? 'all' : $_GET['game'];
$Game_Borad_ID = empty($_GET['Game_Type_Select']) ? '' : $_GET['Game_Type_Select'];
foreach ($gameAry as $key => $value) {
	if($value['IsOnline']){
		$gameList .= '<option '.($value['GameID'] == $GameId ? 'selected="selected"' : '').' value="'.$value['GameID'].'">'.$html->lang['GameName'.$value['GameID']].'</option>';
	}
}
unset($db);

//取得管理表资料
$notice     = '';
$admin_list = new PDO_DB(DB_HOST, DB_USER, DB_PWD, DB_NAME);

try {
	$mem_Type = 0;
	if($Game_Borad_ID != "" && $mem == ""){
		$mem_Type = 1;
		$pointClass = new Points($html->WebLogin);
		$mem =  $pointClass->getWinLoseRecords_DBName($Game_Borad_ID);
	}

	//會員帐号搜寻
	if(empty($mem)){
		throw new Exception('No MemberAccount', 3);
	}
	$admin_list->selectTB('A_Member');
	$admin_list->getData('*',"WHERE MemberAccount = '".$mem."'");
	$admin_list->execute();
	if($admin_list->total_row <= 0){
		throw new Exception('No Match Member');
	}
	$memberData = $admin_list->row;

	$admin_list->selectTB('Admin');
	$admin_list->getData("*", "WHERE admin_id = '".$memberData['UpAdmin_id']."'");
	$admin_list->execute();
	if($admin_list->total_row <= 0){
		throw new Exception('No Match UpAdmin');
	}
	$upAdminData = $admin_list->row;

	$pointClass = new Points($html->WebLogin);
	$pointClass->produceLink($upAdminData);

	if(!$pointClass->allowSearch){
		throw new Exception("not allow to search", 2);
	}

	$tableExist = $pointClass->chkDBTableExistById($memberData['MemberID']);
	if(!$tableExist){
		throw new Exception('No DB Table('. $pointClass->table.')');
	}

	$rows    = $pointClass->getWinloseRecords(20, $startDate, $endDate, $GameId, $Game_Borad_ID);
	$betTemp = $html->regexMatch($tableRowData, '<!--__betRecordsStart', 'betRecordsEnd__-->');
	$betHtml = '';

	if(count($rows) <= 0 || empty($rows)){
		throw new Exception('No Records');
	}

	foreach ($rows as $value) {
		$value['MemberID']      = $memberData['MemberID'];
		$value['MemberAccount'] = $memberData['MemberAccount'];
		$value['Bet']           = $value['Bet'];
		$value['WinLose']       = $value['WinLose'];
		$value['CurrentPoints'] = $value['CurrentPoints'];
		$value['GameTitle']     = $html->lang['GameName'.$value['GameId']];
		$value['btn']           = sprintf($viewBtn, $memberData['MemberID'].';'.$value['RoundCode'].'@'.$value['RoundId']);
		$value['class']         = '';

		if(in_array($html->WebLogin->ag_id, ['1', '2']) && $value['GetJPMoney'] > 0){
			$value['WinLose'] .= '(JP：'.$value['GetJPMoney'].')';
			$value['class']   = 'bg-warning';
		}

		$betHtml .= $html->regexReplace($value, $betTemp);
	}
	$pageTemp = $html->regexMatch($tableRowData, '<!--__paginationStart', 'paginationEnd__-->');
	$tableInfo = [
		'betRecords'    => $betHtml,
		'betRecordInfo' => $pointClass->recordInfo,
		'pagination'    => $html->regexReplace(['pagination'=>$pointClass->pagination['default_style']], $pageTemp)
	];
	$tableRowData = $html->regexReplace($tableInfo, $tableRowData, '<!--__', '__-->');
	if($mem_Type == 1){
		$mem = "";
	}
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
$compilerAry['__MainContent__']         = $tableRowData;//取次样版
$compilerAry['__cssImport__']           = join("\n", $cssImport);//引用css
$compilerAry['__javascriptImport__']    = join("\n", $javascriptImport);//引用javascript
$compilerAry['__updateAdmin__']         = $updateAdminBtn;//按钮
$compilerAry['__notice__']              = $notice;//系统提示
$compilerAry['__acc__']                 = $mem;
$compilerAry['__Game_Type_Select__'] = $Game_Borad_ID;
$compilerAry['__gameList__']            = $gameList;

$compilerAry['__setdate_p1M_from__']  = date( "Y-m-d", mktime(0,0,0,date("m")-1,1,date("Y")));
$compilerAry['__setdate_p1M_to__']    = date( "Y-m-t", mktime(23,59,59,	date("m")-1,date("d"),date("Y")));
$compilerAry['__setdate_nowW_from__'] = date( "Y-m-d", mktime(0,0,0,date("m"),date("d")-date("w")-6,date("Y")));
$compilerAry['__setdate_nowW_to__']   = date( "Y-m-d", mktime(23,59,59,date("m"),date("d")-date("w"),date("Y")));
$compilerAry['__setdate_YD_from__']   = date( "Y-m-d", mktime(0,0,0,date("m"),date("d")-1,date("Y")));
$compilerAry['__setdate_YD_to__']     = date( "Y-m-d", mktime(23,59,59,date("m"),date("d")-1,date("Y")));
$compilerAry['__setdate_nowD_from__'] = date( "Y-m-d", mktime(0,0,0,date("m"),date("d"),date("Y")));
$compilerAry['__setdate_nowD_to__']   = date( "Y-m-d", mktime(23,59,59,date("m"),date("d"),date("Y")));
$compilerAry['__p3M__']               = date( "Y-m-d", mktime(0,0,0,date("m")-2,date("d"),date("Y")));

$compilerAry['__start_time__']  = $startDate;
$compilerAry['__end_time__']    = $endDate;

//-----------------------------------------将欲取代的内容与样版重新组合-------------------------------------------------

//重新组合页面
$cleanHtml = $html->compiler($compilerAry);
echo $cleanHtml;
unset($html, $admin_list);
exit;
?>