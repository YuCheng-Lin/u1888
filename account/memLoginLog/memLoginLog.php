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
$db           = new PDO_DB(DB_HOST, DB_USER, DB_PWD, DB_NAME);
$limitDate    = date( "Y-m-d", mktime(0,0,0,date("m")-2,date("d"),date("Y")));//限制搜尋
$thisMonFirst = date('Y-m-01');
$thisMonLast  = date('Y-m-t');

$admin_acc = $html->WebLogin->admin_acc;
if(!empty($_GET['acc'])){//搜寻代理帐号
	$admin_acc = trim($_GET['acc']);
	$admin_acc = stripslashes($admin_acc);
	$admin_acc = str_replace("'", "''", $admin_acc);
	$admin_acc = stripslashes($admin_acc);
}

$mem = '';
if(!empty($_GET['mem'])){//搜寻會員帐号
	$mem = trim($_GET['mem']);
	$mem = stripslashes($mem);
	$mem = str_replace("'", "''", $mem);
	$mem = stripslashes($mem);
}

$ip = '';
if(!empty($_GET['ip'])){//搜寻ip
	$ip = trim($_GET['ip']);
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
	if(!empty($ip) && !filter_var($ip, FILTER_VALIDATE_IP)){
		throw new Exception('Ip is not valid');
	}

	// 搜尋該代理
	$adminData = (array)$html->WebLogin;
	if(!empty($admin_acc)){
		$db->selectTB('Admin');
		$db->getData("*", "WHERE admin_acc = '".$admin_acc."'");
		$db->execute();
		if($db->total_row <= 0){
			throw new Exception("No Data");
		}
		$adminData = $db->row;
	}
	$Points = new Points($html->WebLogin);
	$Points->produceLink($adminData);
	if(!$Points->allowSearch){
		throw new Exception('Not Allow', 1);
	}
	$breadcrumb = join("", array_reverse($Points->breadcrumb));

	//搜尋下層
	$sqlInList = [];
	$downAdminData = getAllDownAdmin($adminData);
	if(is_array($downAdminData)){
		$sqlInList = array_keys($downAdminData);
	}
	array_push($sqlInList, $adminData['admin_id']);
	// echo '<pre>';
	// print_r($downAdminData);
	// print_r($sqlInList);
	// exit;

	//搜尋資料
	$sql = "LEFT JOIN A_Member ON (MemberIpHistory.MemberId = A_Member.MemberID)
	 WHERE A_Member.UpAdmin_id IN ('".join("','", $sqlInList)."')
	 AND MemberIpHistory.LastUseTime >= '".$startDate."'
	 AND MemberIpHistory.LastUseTime <= '".$endDate."'";

	if($mem){
		$sql .= " AND A_Member.MemberAccount = '".$mem."'";
	}

	if($ip){
		$sql .= " AND MemberIpHistory.Ip = '".$ip."'";
	}

	$db->selectTB('MemberIpHistory');
	$db->getData("MemberIpHistory.*, A_Member.MemberAccount, A_Member.NickName, A_Member.UpAdmin_id", $sql);
	$db->pagingMSSQL(30, 'MemberIpHistory.LastUseTime');
	// echo $db->getCurrentQueryString();
	// exit;
	$db->execute();

	if($db->total_row <= 0){
		throw new Exception("No Data");
	}

	if($db->total_row > 0){
		$tableRowTemp = $html->regexMatch($tableRowData, '<!--__tableRowStart', 'tableRowEnd__-->');
		$tableRow     = '';
		//現在參數
		$getData = $_SERVER['QUERY_STRING'];
		$getData = preg_split('/acc=(.*?)&/', $getData);
		do{
			$row = $db->row;
			$row['upAccount'] = ($row['UpAdmin_id']==$adminData['admin_id']) ? $adminData['admin_acc'] : (empty($downAdminData[$row['UpAdmin_id']]) ? '--' : '<a href="?acc='.$downAdminData[$row['UpAdmin_id']]['admin_acc'].(empty($getData[1]) ? '' : '&'.$getData[1]).'">'.$downAdminData[$row['UpAdmin_id']]['admin_acc'].'</a>');
			$tableRow .= $html->regexReplace($row, $tableRowTemp);
		}while($db->row = $db->fetch_assoc());		

		$pagination   = $db->createNumBar();
		$pageTemp     = $html->regexMatch($tableRowData, '<!--__paginationStart', 'paginationEnd__-->');
		$recordsInfo  = $db->recordInfo($html->lang['RecordInfoStyle']);
		$tableRowData = $html->regexReplace([
			'tableRow'      => $tableRow,
			'betRecordInfo' => $recordsInfo['default_style'],
			'pagination'    => $html->regexReplace(['pagination'=>$pagination['default_style']], $pageTemp)
		], $tableRowData, '<!--__', '__-->');
	}
	unset($db, $Points);
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
$compilerAry['__limitDate__']         = $limitDate;

$compilerAry['__start_time__'] = $startDate;
$compilerAry['__end_time__']   = $endDate;
$compilerAry['__acc__']        = $admin_acc;
$compilerAry['__mem__']        = $mem;
$compilerAry['__ip__']         = $ip;
$compilerAry['__goBackMine__'] = $goBackMine;

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
unset($html, $db);
exit;

function getAllDownAdmin($adminData){
	$downAdminData = [];
	$sql = "WHERE upAdmin_id = '".$adminData['admin_id']."'";
	if($adminData['ag_id'] == '1'){
		$sql = "WHERE ag_id = '2'";
	}
	$db = new PDO_DB(DB_HOST, DB_USER, DB_PWD, DB_NAME);
	$db->selectTB('Admin');
	$db->getData("*", $sql);
	// echo '<pre>';
	// print_r($db->getCurrentQueryString());
	// exit;
	$db->execute();
	if($db->total_row > 0){
		do{
			$downAdminData[$db->row['admin_id']] = $db->row;
			if($db->row['downCount'] > 0){
				$downAdminData = $downAdminData + getAllDownAdmin($db->row);
			}
		}while($db->row = $db->fetch_assoc());
		return $downAdminData;
	}
	unset($db);
	return false;
}
?>