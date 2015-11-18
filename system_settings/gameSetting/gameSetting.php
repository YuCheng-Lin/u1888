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
$viewBtn          = '<button data-whatever="'.$html->lang['GameDescription'].'" data-target="#dialogModal" data-fn="viewDetail" data-id="%s" data-toggle="tooltip" data-placement="top" title="'.$html->lang['GameDescription'].'" data-modal-submit="false" class="dialogModal btn btn-xs btn-info pull-left" type="button"><span class="glyphicon glyphicon-eye-open" aria-hidden="true"></span></button>';
$updBtn           = '<button data-whatever="'.$html->lang['EditDescription'].'" data-target="#dialogModal" data-fn="updDetail" data-id="%s" data-toggle="tooltip" data-placement="top" title="'.$html->lang['EditDescription'].'" class="dialogModal btn btn-xs btn-primary pull-left" type="button"><span class="glyphicon glyphicon-cog" aria-hidden="true"></span></button>';
$updateAdminBtn   = '';
$cssImport        = [];
$javascriptImport = [];
//取得样版中的样版资料，作为次样版
$tableRowData = $html->getFile();
$GameRateTemp = '';
$nowPagePower = $html->WebLogin->nowPagePower;
//管理者本页权限
switch($html->WebLogin->nowPagePower){
	case 'w':
		$GameRateTemp = $html->regexMatch($tableRowData, '<!--__formStart', 'formEnd__-->');
		$Btn = $updBtn;
		$javascriptImport[] = '<script>
			$(function(){
				$(".confirm").click(function(){
					if(confirm("'.$html->lang['ConfirmUpdBtn'].'")){
						return true;
					}
					return false;
				});
				$("#select").change(function(){
					$("#searchForm").submit();
				});
			});
		</script>';
		break;
	case 'r':
		$Btn = $viewBtn;
		break;
	default:
		header("location: /index.php?err=6");
		exit;
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
$startDateTime = $startDate.' '.$html->StartTime;
$endDateTime   = $endDate.' '.$html->EndTime;

//遊戲列表
$gameAry     = array();
$db  = new PDO_DB( DB_HOST, DB_USER, DB_PWD, DB_NAME);
$db->selectTB('Game');
$db->getData("*", "WHERE IsOnline = '1'");
$db->pagingMSSQL(10, 'CreateDate');
$result = $db->execute();
if($db->total_row > 0){
	do{
		$gameAry[$db->row['GameID']]     = $db->row;
	}while($db->row = $db->fetch_assoc());
}
$pageTemp    = $html->regexMatch($tableRowData, '<!--__paginationStart', 'paginationEnd__-->');
$pagination  = $db->createNumBar();
$recordsInfo = $db->recordInfo($html->lang['RecordInfoStyle']);
unset($db);

//取得管理表资料
$notice      = '';
$db_analysis = new PDO_DB(DB_HOST, DB_USER, DB_PWD, DB_ANALYSIS_NAME);
try {
	$db_analysis->selectTB('MemberWinLose_Tmp');
	$db_analysis->getData("GameId, COUNT(Bet) AS Count, SUM(Bet) AS Bet, SUM(WinLose) AS WinLose", 
	"WHERE CreateDate >= '".$startDateTime."' AND CreateDate <= '".$endDateTime."'
	 AND GameId IN ('".join("','", array_keys($gameAry))."')
	 GROUP BY GameId");
	$db_analysis->execute();
	if($db_analysis->total_row > 0){
		do{
			$gameAry[$db_analysis->row['GameId']] += $db_analysis->row;
		}while($db_analysis->row = $db_analysis->fetch_assoc());
	}
	$tableRowTemp = $html->regexMatch($tableRowData, '<!--__tableRowStart', 'tableRowEnd__-->');
	$tableRow     = '';

	foreach ($gameAry as $value) {
		$value['GameTitle']  = $html->lang['GameName'.$value['GameID']];
		$value['GameType']   = $html->lang['GameType'.$value['GameTypeID']];
		$value['Bet']        = empty($value['Bet']) ? 0 : number_format($value['Bet']/$html->MoneyPointRate, 2);
		$value['Count']      = empty($value['Count']) ? 0 : number_format($value['Count']);
		$value['WinLose']    = empty($value['WinLose']) ? 0 : number_format($value['WinLose']/$html->MoneyPointRate, 2);
		$value['btn']        = sprintf($Btn, $value['GameID']);
		$value['ConfirmBtn'] = $html->lang['ConfirmBtn'];

		$value['GameRateTemp'] = $value['GameRate'].' %';
		if(!empty($GameRateTemp)){
			$value['GameRateTemp'] = $html->regexReplace($value, $GameRateTemp);
		}


		$tableRow .= $html->regexReplace($value, $tableRowTemp);
	}
	$tableInfo = [
		'tableRow'   => $tableRow,
		'RecordInfo' => $recordsInfo['default_style'],
		'pagination' => $html->regexReplace(['pagination'=>$pagination['default_style']], $pageTemp)
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
$compilerAry['__MainContent__']              = $tableRowData;//取次样版
$compilerAry['__cssImport__']                = join("\n", $cssImport);//引用css
$compilerAry['__javascriptImport__']         = join("\n", $javascriptImport);//引用javascript
$compilerAry['__updateAdmin__']              = $updateAdminBtn;//按钮
$compilerAry['__notice__']                   = $notice;//系统提示

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
unset($html, $db_analysis);
exit;
?>