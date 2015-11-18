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
$ag_id              = $html->WebLogin->ag_id;
$updateAdminBtn     = '';
$cssImport          = [];
$cssImport[]        = '<link rel="stylesheet" href="css/style.css">';
$javascriptImport   = [];
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

//回主帐号连结
$goBackMine = '';
if($admin_acc != $html->WebLogin->admin_acc){
	$goBackMine = '<a href="'.$_SERVER['SCRIPT_NAME'].'" class="btn btn-default">'.$html->lang['BackMainAcc'].'</a>';
}

//取得样版中的样版资料，作为次样版
$tableRowData = $html->getFile();

//取得资料
$notice  = '';
$db      = new PDO_DB(DB_HOST, DB_USER, DB_PWD, DB_NAME);
$online  = 0;
$lobby   = 0;
$playing = 0;

try {
	$breadcrumb = '';
	if(!empty($ip) && !filter_var($ip, FILTER_VALIDATE_IP)){
		throw new Exception('Ip is not valid');
	}

	$dateAry   = $html->dateArray();
	$startDate = $dateAry['start'][count($dateAry['start'])-2];
	$endDate   = $dateAry['end'][count($dateAry['end'])-1];

	$sql = "WHERE admin_acc = '".$admin_acc."'";
	if(!empty($mem)){
		$db->selectTB('A_Member');
		$db->getData("*", "WHERE MemberAccount = '".$mem."'");
		$db->execute();
		if($db->total_row <= 0){
			throw new Exception('No Data');
		}
		$sql = "WHERE admin_id = '".$db->row['UpAdmin_id']."'";
	}

	$db->selectTB('Admin');
	$db->getData("*", $sql);
	$db->execute();
	if($db->total_row <= 0){
		throw new Exception('No Data');
	}
	$adminData = $db->row;
	$admin_acc = $adminData['admin_acc'];

	//權限
	$Points = new Points($html->WebLogin);
	$Points->produceLink($adminData);
	if(!$Points->allowSearch){
		throw new Exception('Not Allow', 1);
	}
	$breadcrumb = join("", array_reverse($Points->breadcrumb));

	//取得所有遊戲
	$db->selectTB('Game');
	$db->getData("*", "WHERE IsOnline = '1'");
	$db->execute();
	if($db->total_row <= 0){
		throw new Exception('No Game');
	}
	$allGame = [];
	do{
		$allGame[$db->row['GameID']] = empty($html->lang['GameName'.$db->row['GameID']]) ? '--' : $html->lang['GameName'.$db->row['GameID']];
	}while($db->row = $db->fetch_assoc());

	$downAdminData = getAllDownAdmin($adminData);

	$idList = array_keys($downAdminData);
	array_unshift($idList, $adminData['admin_id']);//加入自己的編號

	//查出下面會員有在線上的人
	$db->selectTB('GamePlayersDetail');
	$db->getData("GamePlayersDetail.GameID,A_Member.MemberID,A_Member.MemberAccount,A_Member.NickName,A_Member.UpAdmin_id",
	 "INNER JOIN A_Member ON (GamePlayersDetail.UserID = A_Member.MemberID)
	  WHERE A_Member.UpAdmin_id IN ('".join("','", $idList)."')");
	$db->pagingMSSQL(30, 'GamePlayersDetail.UserID');
	$db->execute();

	if($db->total_row <= 0){
		throw new Exception('No Data');
	}
	if($db->total_row > 0){
		$tableRowTemp = $html->regexMatch($tableRowData, '<!--__tableRowStart', 'tableRowEnd__-->');
		$tableRow     = '';
		//現在參數
		$getData = $_SERVER['QUERY_STRING'];
		$getData = preg_split('/acc=(.*?)&/', $getData);
		do{
			$row = $db->row;
			if(!empty($mem) && $mem != $row['MemberAccount']){
				continue;
			}
			//取得最後登入ip及時間
			$ipAry = getMemberLastIp($row['MemberID']);
			if(!empty($ipAry['Ip']) && !empty($ip) && $ip != $ipAry['Ip']){
				continue;
			}
			$row['Ip']          = empty($ipAry['Ip']) ? '--' : $ipAry['Ip'];
			$row['LastUseTime'] = empty($ipAry['LastUseTime']) ? '--' : $ipAry['LastUseTime'];

			$row['Points'] = getMemberPoint($row['MemberID'])/$html->MoneyPointRate;
			$row['upAccount'] = ($row['UpAdmin_id']==$adminData['admin_id']) ? $adminData['admin_acc'] : (empty($downAdminData[$row['UpAdmin_id']]) ? '--' : '<a href="?acc='.$downAdminData[$row['UpAdmin_id']]['admin_acc'].(empty($getData[1]) ? '' : '&'.$getData[1]).'">'.$downAdminData[$row['UpAdmin_id']]['admin_acc'].'</a>');

			//當週總輸贏
			$row['thisWeekWinlose'] = $Points->chkMemberOutgo($row, $startDate, $endDate)['WinLoseTotal'];

			//GameID如果是0 則表示在大廳
			if($row['GameID'] == '0'){
				$row['GameTitle'] = $html->lang['THLobby'];
				$lobby += 1;
			}else{
				$row['GameTitle'] = empty($allGame[$row['GameID']]) ? '--' : $allGame[$row['GameID']];
				$playing += 1;
			}
			$tableRow .= $html->regexReplace($row, $tableRowTemp);
			//在線人數增加
			$online += 1;
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
	unset($db);
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

$compilerAry['__breadcrumb__'] = $breadcrumb;
$compilerAry['__online__']     = $online;
$compilerAry['__lobby__']      = $lobby;
$compilerAry['__playing__']    = $playing;

$compilerAry['__acc__']        = $admin_acc;
$compilerAry['__mem__']        = $mem;
$compilerAry['__ip__']         = $ip;
$compilerAry['__goBackMine__'] = $goBackMine;
//-----------------------------------------将欲取代的内容与样版重新组合-------------------------------------------------

//重新组合页面
$cleanHtml = $html->compiler($compilerAry);
echo $cleanHtml;
unset($html, $db);
exit;

function getMemberLastIp($MemberId){
	$db = new PDO_DB(DB_HOST, DB_USER, DB_PWD, DB_NAME);
	$db->selectTB('MemberIpHistory');
	$db->getData("TOP(1) Ip, LastUseTime", "WHERE MemberId = '".$MemberId."' ORDER BY LastUseTime DESC");
	$db->execute();
	if($db->total_row <= 0){
		return [];
	}
	return $db->row;
}

function getMemberPoint($MemberId){
	$db = new PDO_DB(DB_HOST, DB_USER, DB_PWD, DB_NAME);
	$db->selectTB('MemberFinance2');
	$db->getData("SUM(Points) AS Points", "WHERE MemberId = '".$MemberId."' AND PointType IN ('Game', 'Wager') GROUP BY MemberId");
	$db->execute();
	if($db->total_row <= 0){
		return 0;
	}
	return $db->row['Points'];
}

function getAllDownAdmin($adminData){
	$downAdminData = [];
	$sql = "WHERE upAdmin_id = '".$adminData['admin_id']."'";
	if($adminData['ag_id'] == '1'){
		$sql = "WHERE ag_id = '2'";
	}
	$db = new PDO_DB(DB_HOST, DB_USER, DB_PWD, DB_NAME);
	$db->selectTB('Admin');
	$db->getData("*", $sql);
	$db->execute();
	if($db->total_row > 0){
		do{
			$downAdminData[$db->row['admin_id']] = $db->row;
			if($db->row['downCount'] > 0){
				$downAdminData = $downAdminData + getAllDownAdmin($db->row);
			}
		}while($db->row = $db->fetch_assoc());
	}
	unset($db);
	return $downAdminData;
}
?>