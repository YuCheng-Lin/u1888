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
$ag_id              = $html->WebLogin->ag_id;
$updateAdminBtn     = '';
$cssImport          = array();
$cssImport[]        = '<link rel="stylesheet" href="css/style.css">';
$javascriptImport   = array();
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

//取得管理表资料
$notice       = '';
$admin_list   = new PDO_DB(DB_HOST, DB_USER, DB_PWD, DB_NAME);
$limitDate    = date( "Y-m-d", mktime(0,0,0,date("m")-3,date("d"),date("Y")));//限制搜尋僅前三個
$thisMonFirst = date('Y-m-01');
$thisMonLast  = date('Y-m-t');

$admin_acc = $html->WebLogin->admin_acc;
if(!empty($_GET['acc'])){//搜寻代理帐号
	$admin_acc = trim($_GET['acc']);
	$admin_acc = stripslashes($admin_acc);
	$admin_acc = str_replace("'", "''", $admin_acc);
	$admin_acc = stripslashes($admin_acc);
}

$startDate = $thisMonFirst;
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

//回主帐号连结
$goBackMine = '';
if($admin_acc != $html->WebLogin->admin_acc){
	$goBackMine = '<a href="'.$_SERVER['SCRIPT_NAME'].'" class="btn btn-default">'.$html->lang['BackMainAcc'].'</a>';
}

try {
	$breadcrumb = '';
	$range      = 'all';
	if(!empty($_GET['range']) && $_GET['range'] != $range){
		$range = 'one';
	}

	//所有交易類別
	$type = 'all';
	if(!empty($_GET['type'])){
		$type = $_GET['type'];
	}
	$admin_list->selectTB('DianShuYiDongShiJian');
	$admin_list->getData("*", "WHERE Hidden = '0'");
	$admin_list->execute();
	$typeOptions = '';
	$typeAry     = [];
	if($admin_list->total_row > 0){
		do{
			$typeOptions .= '<option value="'.$admin_list->row['RowId'].'"';
			$typeOptions .= $type==$admin_list->row['RowId'] ? ' selected="selected"' : '';
			$typeOptions .= '>'.$html->lang['PointTitle'.$admin_list->row['RowId']].'</option>';
			$typeAry[]    = $admin_list->row['RowId'];
		}while($admin_list->row = $admin_list->fetch_assoc());
		if($type != 'all' && !in_array($type, $typeAry)){
			throw new Exception('No Type');
		}
	}

	// 搜尋該代理
	$admin_list->selectTB('Admin');
	$admin_list->getData("*", "WHERE admin_acc = '".$admin_acc."'");
	$admin_list->execute();
	if($admin_list->total_row <= 0){
		throw new Exception("No Data");
	}
	$adminData = $admin_list->row;
	$point = new Points($html->WebLogin);
	$point->produceLink($adminData);
	$breadcrumb = join("", array_reverse($point->breadcrumb));
	if(!$point->allowSearch){
		throw new Exception('Not Allow', 1);
	}

	$sqlInList = [$adminData['admin_id']];
	$accAry[$adminData['admin_id']] = $adminData['admin_acc'];

	if($range == 'all'){
		//查詢該代理單一下層
		$admin_list->getData("*", "WHERE upAdmin_id = '".$adminData['admin_id']."'");
		$admin_list->execute();
		$downAgent = [];
		if($admin_list->total_row > 0){
			do{
				$downAgent[] = $admin_list->row;
				$sqlInList[] = $admin_list->row['admin_id'];
				$accAry[$admin_list->row['admin_id']] = $admin_list->row['admin_acc'];
			}while($admin_list->row = $admin_list->fetch_assoc());
		}
	}

	//搜尋資料
	$sql = "LEFT JOIN Admin ON (AdminBank.toAdmin_id = Admin.admin_id OR AdminBank.fromAdmin_id = Admin.admin_id)
	 LEFT JOIN A_Member ON (AdminBank.toMemberID = A_Member.MemberID OR AdminBank.fromMemberID = A_Member.MemberID)
	 WHERE AdminBank.admin_id IN ('".join("','", $sqlInList)."')
	 AND AdminBank.addtime >= '".$startDate." ".$html->StartTime."'
	 AND AdminBank.addtime <= '".$endDate." ".$html->EndTime."'";
	if($type != 'all'){
		$sql .= " AND event_id = '".$type."'";
	}
	$admin_list->selectTB('AdminBank');
	$admin_list->getData("AdminBank.*, Admin.admin_acc, A_Member.MemberAccount", $sql);
	$admin_list->pagingMSSQL(30, 'AdminBank.addtime');
	$admin_list->execute();

	if($admin_list->total_row <= 0){
		throw new Exception("No Data");
	}

	if($admin_list->total_row > 0){
		$tableRowTemp = $html->regexMatch($tableRowData, '<!--__tableRowStart', 'tableRowEnd__-->');
		$tableRow     = '';
		//現在參數
		$getData = $_SERVER['QUERY_STRING'];
		$getData = preg_split('/acc=(.*?)&/', $getData);
		do{
			$row = $admin_list->row;
			$row['income']      = $row['income']==0 ? '0' : $row['income'];
			$row['outgo']       = $row['outgo']==0 ? '0' : $row['outgo'];
			$row['afterPoints'] = $row['afterPoints'];
			$row['acc']         = $row['admin_id'] != $adminData['admin_id'] ? '<a href="?acc='.$accAry[$row['admin_id']].(empty($getData[1]) ? '' : '&'.$getData[1]).'">'.$accAry[$row['admin_id']].'</a>' : $accAry[$row['admin_id']];
			$row['ChangeTitle'] = $html->lang['PointTitle'.$row['event_id']];
			$row['source_acc']  = empty($row['admin_acc']) ? empty($row['MemberAccount']) ? '--' : $row['MemberAccount'] : $row['admin_acc'];
			$tableRow .= $html->regexReplace($row, $tableRowTemp);
		}while($admin_list->row = $admin_list->fetch_assoc());		

		$pagination   = $admin_list->createNumBar();
		$pageTemp     = $html->regexMatch($tableRowData, '<!--__paginationStart', 'paginationEnd__-->');
		$recordsInfo  = $admin_list->recordInfo($html->lang['RecordInfoStyle']);
		$tableRowData = $html->regexReplace([
			'tableRow'      => $tableRow,
			'betRecordInfo' => $recordsInfo['default_style'],
			'pagination'    => $html->regexReplace(['pagination'=>$pagination['default_style']], $pageTemp)
		], $tableRowData, '<!--__', '__-->');
	}
	unset($admin_list, $point);
} catch (Exception $e) {
	switch ($e->getCode()) {
		case 1:
			$notice = $html->__systemConfig['__notAllowSearch__'];
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
$compilerAry['__breadcrumb__']       = $breadcrumb;

$compilerAry['__setdate_p1M_from__']  = date( "Y-m-d", mktime(0,0,0,date("m")-1,1,date("Y")));
$compilerAry['__setdate_p1M_to__']    = date( "Y-m-t", mktime(23,59,59,	date("m")-1,date("d"),date("Y")));
$compilerAry['__setdate_nowW_from__'] = date( "Y-m-d", mktime(0,0,0,date("m"),date("d")-date("w")-6,date("Y")));
$compilerAry['__setdate_nowW_to__']   = date( "Y-m-d", mktime(23,59,59,date("m"),date("d")-date("w"),date("Y")));
$compilerAry['__setdate_YD_from__']   = date( "Y-m-d", mktime(0,0,0,date("m"),date("d")-1,date("Y")));
$compilerAry['__setdate_YD_to__']     = date( "Y-m-d", mktime(23,59,59,date("m"),date("d")-1,date("Y")));
$compilerAry['__setdate_nowD_from__'] = date( "Y-m-d", mktime(0,0,0,date("m"),date("d"),date("Y")));
$compilerAry['__setdate_nowD_to__']   = date( "Y-m-d", mktime(23,59,59,date("m"),date("d"),date("Y")));
$compilerAry['__p3M__']               = date( "Y-m-d", mktime(0,0,0,date("m")-3,date("d"),date("Y")));

$compilerAry['__start_time__']  = $startDate;
$compilerAry['__end_time__']    = $endDate;
$compilerAry['__acc__']         = $admin_acc;
$compilerAry['__typeOptions__'] = $typeOptions;
$compilerAry['__goBackMine__']  = $goBackMine;
$compilerAry['__'.$range.'_selected__'] = ' selected="selected"';

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
unset($html, $admin_list);
exit;

function chkUpAdmin($admin_id){
	$db = new PDO_DB(DB_HOST, DB_USER, DB_PWD, DB_NAME);
	$db->selectTB('Admin');
	$db->getData("admin_id, admin_acc, upAdmin_id", "WHERE admin_id = '".$admin_id."'");
	$db->execute();
	$ary['admin_acc'] = '';
	$ary['upAdmin_id'] = NULL;
	if($db->total_row > 0){
		$ary = $db->row;
	}
	unset($db);
	return $ary;
}
?>