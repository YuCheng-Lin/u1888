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
if($html->WebLogin->ag_id != '1'){
	header("Location: /index.php?err=3");
	exit;
}

$MoneyPointRate = $html->MoneyPointRate;
$userIp = $html->WebLogin->getUserIp();


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
unset($html);
exit;

/**
 * 新增下期活動獎金
 */
function addBonus(){
	global $html;

	$dateAry       = $html->dateArray();
	$count         = count($dateAry['start']);
	$startDateTime = $dateAry['start'][$count-2].' '.$html->StartTime;
	$endDateTime   = $dateAry['end'][$count-1].' '.$html->EndTime;
	$compilerAry   = [];

	$db = new PDO_DB(DB_HOST, DB_USER, DB_PWD, DB_NAME);

	//取得目前額度
	$nowBonus = 0;
	$db->selectTB('RemoteJPBonus_Param');
	$db->getData("*");
	$db->execute();
	if($db->total_row > 0){
		$nowBonus += $db->row['Param1'];
	}

	//查詢當期活動額度設置
	$db->selectTB('AdminBonus');
	$db->getData("*", "WHERE startDateTime = '".$startDateTime."' AND endDateTime = '".$endDateTime."'");
	$db->execute();
	//已有該期活動獎金
	if($db->total_row > 0){
		$bonusData = $db->row;

		$subTemp  = $html->getFile($_SERVER['DOCUMENT_ROOT']."/tpl/system_settings/sysValue/editBonus.html");
		$compilerAry = [
			'__nowMaxBonus__' => number_format($bonusData['bonus']/$html->BonusRate, 2),
			'__nowDate__'     => $startDateTime.' ～ '.$endDateTime,
			'__nowBonus__'    => number_format($nowBonus/$html->BonusRate, 2),
		];
	}
	//沒有該期活動獎金
	if($db->total_row <= 0){
		//查詢上期
		$db->getData("*", "WHERE endDateTime < '".$startDateTime."' ORDER BY endDateTime DESC");
		$db->execute();
		if($db->total_row <= 0){
			//查無上期
			$previousDate = '查無上期';
			$preMaxBonus  = '查無上期';
		}
		if($db->total_row > 0){
			$previousDate = $db->row['startDateTime'].' ～ '.$db->row['endDateTime'];
			$preMaxBonus  = $db->row['bonus'];
		}

		$subTemp  = $html->getFile($_SERVER['DOCUMENT_ROOT']."/tpl/system_settings/sysValue/addBonus.html");
		$compilerAry = [
			'__preMaxBonus__'  => number_format($preMaxBonus/$html->BonusRate, 2),
			'__previousDate__' => $previousDate,
			'__nowBonus__'     => number_format($nowBonus/$html->BonusRate, 2),
			'__nextDate__'     => $startDateTime.' ～ '.$endDateTime,
		];
	}


	//重新组合页面
	$main = $html->compiler($compilerAry);

	$sendAry = [];
	$sendAry['result'] = $main;
	
	echo json_encode($sendAry);
	unset($html, $db);
	exit;
}

/**
 * 計算報表的畫面
 */
function calWinLose(){
	global $html;
	$sendAry = [];

	$subTemp  = $html->getFile($_SERVER['DOCUMENT_ROOT']."/tpl/system_settings/sysValue/calculatePoints.html");

	$html->dateCount = 6;//預設期數，如有更改須連同變更request.php
	$dateAry = $html->dateArray();
	$count   = count($dateAry['start']);
	$options = '';
	$select  = empty($_GET['select']) ? $count-1 : $_GET['select'];
	for ($i=$count-2; $i > 0; $i--) {
		$options .= '<option';
		if($i == $select){
			$options .= ' selected="selected"';
		}
		$options .= ' value="'.($i).'">'.(sprintf($html->lang['GameOption'], $i)).'：'.$dateAry['start'][$i-1].' '.$html->StartTime.' ～ '.$dateAry['end'][$i].' '.$html->EndTime.'</option>';
	}

	$compilerAry = [
		'__options__' => $options
	];

	//重新组合页面
	$main = $html->compiler($compilerAry);

	$sendAry = [];
	$sendAry['result'] = $main;
	
	echo json_encode($sendAry);
	unset($html, $db);
	exit;
}

/**
 * 計算報表
 */
function calculateWinLose()
{
	$referer = $_SERVER['HTTP_REFERER'];
	$stime=explode(" ",microtime());
	$ss=$stime[0]+$stime[1];

	global $html;
	$sendAry = array();

	if(empty($_POST['select'])){
		$sendAry = array();
		$sendAry['systemErr'] = 'alert("請選擇期數");';
		echo json_encode($sendAry);
		exit;
	}

	$html->dateCount = 6;//預設期數，如有更改須連同變更functionLib.php
	$dateAry = $html->dateArray();
	$count   = count($dateAry['start']);
	$options = '';
	$select  = $_POST['select'];
	for ($i=$count-1; $i > 0; $i--) {
		$options .= '<option';
		if($i == $select){
			$options .= ' selected="selected"';
		}
		$options .= ' value="'.($i).'">'.(sprintf($html->lang['GameOption'], $i)).'：'.$dateAry['start'][$i-1].' '.$html->StartTime.' ～ '.$dateAry['end'][$i].' '.$html->EndTime.'</option>';
	}

	$startDate = $dateAry['start'][$count-2];//預設查詢最新
	if(!empty($dateAry['start'][$select-1])){
		$startDate = $dateAry['start'][$select-1];
	}
	$endDate = $dateAry['end'][$count-1];
	if(!empty($dateAry['end'][$select])){
		$endDate = $dateAry['end'][$select];
	}

	// echo '<pre>';
	// print_r($startDate);
	// print_r($endDate);
	// exit;

	$addAdminCount = 0;
	$addMemCount   = 0;

	//由總控台計算並且寫入紀錄
	$db = new PDO_DB(DB_HOST, DB_USER, DB_PWD, DB_NAME);
	$db->selectTB('Admin');
	$db->getData("*", "WHERE ag_id = '2'");//總控台
	$db->execute();
	if($db->total_row > 0){
		do{
			// echo '計算總控台：'.$db->row['admin_acc'];
			$Points = new Points($html->WebLogin);
			$Points->chkAdminData += $db->row;
			$result = $Points->getAdminWinLose($db->row, $startDate, $endDate);

			$addAdminCount += $Points->addAdminCount;
			$addMemCount   += $Points->addMemCount;

			// echo ' 代理：'.$Points->addAdminCount;
			// echo ' 會員：'.$Points->addMemCount;

			unset($Points);
			// echo '<br>';
		}while($db->row = $db->fetch_assoc());
	}

	//计算执行时间	
	$mtime=explode(" ",microtime());
	$es=$mtime[0]+$mtime[1];
	$mtime=$es-$ss;	//总耗时

	$sendAry = array();
	$sendAry['result']    = 'success';
	$sendAry['resultMsg'] = 'alert("計算代理成功筆數：'.$addAdminCount.'\n計算會員成功筆數：'.$addMemCount.'\n計算耗時：'.$mtime.'")';

	echo json_encode($sendAry);
	unset($db, $Points, $html);
	exit;
}

/**
 * 恢復額度
 */
function recovery(){
	global $MoneyPointRate;
	$sendAry = array();
	$db      = new PDO_DB(DB_HOST, DB_USER, DB_PWD, DB_NAME);

	//恢復代理
	$queryString = "UPDATE Admin SET points = MaxCredit";
	$db->current_query_string = $queryString;
	$db->sth   = $db->conn->prepare($queryString);
	$result    = $db->sth->execute();
	$resultMsg = array();
	if(is_string($result)){
		$resultMsg[] = 'RecoveryAgent Fail';
	}
	if(is_bool($result) && $result){
		$resultMsg[] = 'RecoveryAgent Success';
	}

	//恢復會員
	$queryString = "UPDATE MemberFinance2
SET MemberFinance2.Points = 
(SELECT (A_Member.MaxCredit*".$MoneyPointRate.") FROM A_Member WHERE A_Member.MemberID = MemberFinance2.MemberId)
WHERE MemberFinance2.PointType = 'Game'";
	$db->current_query_string = $queryString;
	$db->sth   = $db->conn->prepare($queryString);
	$result    = $db->sth->execute();
	if(is_string($result)){
		$resultMsg[] = 'RecoveryMember Fail';
	}
	if(is_bool($result) && $result){
		$resultMsg[] = 'RecoveryMember Success';
	}

	$sendAry = array();
	$sendAry['result']    = 'success';
	$sendAry['resultMsg'] = join("、", $resultMsg);
	
	echo json_encode($sendAry);
	unset($db);
	exit;
}

/**
 * 新增下期占成
 */
function addRate(){
	global $html;
	$sendAry = array();
	//下期日期
	// $nowTime   = '2015-02-16 09:23:46';
	$todayDate = date('Y-m-d');
	$nowTime   = $todayDate.' '.$html->maintainStartTime;//跳到下一期時間
	$dateAry   = $html->dateArray($nowTime);
	$count     = count($dateAry['start']);
	$startDate = $dateAry['start'][$count-2];
	$endDate   = $dateAry['end'][$count-1];

	//今天不是星期一不能使用此功能
	// if($todayDate != $startDate){
	// 	$sendAry = array();
	// 	$sendAry['systemErr'] = 'alert("今天不是維護不允許使用此功能，如需使用請通知系統管理員修正");';
	// 	echo json_encode($sendAry);
	// 	exit;
	// }

	$adminCount = 0;
	$memCount   = 0;
	// echo '<pre>';
	// print_r($dateAry);
	// print_r($startDate);
	// print_r($endDate);
	// exit;

	//取得所有Admin(代理)做新增
	$Points = new Points($html->WebLogin);
	$db     = new PDO_DB(DB_HOST, DB_USER, DB_PWD, DB_NAME);
	$db->selectTB('Admin');
	// $db->getData("*","WHERE admin_id = '1057'");
	$db->getData("*","WHERE ag_id IN('3')");
	$db->execute();
	if($db->total_row > 0){
		do{
			$db->row['startDate'] = $startDate;
			$db->row['endDate']   = $endDate;
			$Points->getAdminRate($db->row, $startDate, $endDate, false, true);
			// $thisRate = chkRate($db->row);
			// if(is_array($thisRate)){
			// 	$insertAry = array();
			// 	$insertAry['ag_id']          = $db->row['ag_id'];
			// 	$insertAry['acc_id']         = $db->row['admin_id'];
			// 	$insertAry['endDate']        = $endDate;
			// 	$insertAry['startDate']      = $startDate;
			// 	$insertAry['ReturnRate']     = $thisRate['ReturnRate'];
			// 	$insertAry['commissionRate'] = $thisRate['commissionRate'];

			// 	insertRate($insertAry);
			// }
				// $adminCount++;
		}while($db->row = $db->fetch_assoc());
		$adminCount = $Points->adminRateCount;
	}
	//取得所有會員做新增下期Rate
	// $db->selectTB('A_Member');
	// // $db->getData("*","WHERE MemberID = '38'");
	// $db->getData("*","WHERE UpAdmin_id != '0'");
	// $db->execute();
	// if($db->total_row > 0){
	// 	do{
	// 		$db->row['startDate'] = $startDate;
	// 		$db->row['endDate']   = $endDate;
	// 		$db->row['ag_id']     = '0';
	// 		$db->row['admin_id']  = $db->row['MemberID'];
	// 		$thisRate = chkRate($db->row);
	// 		if(is_array($thisRate)){
	// 			$insertAry = array();
	// 			$insertAry['ag_id']          = $db->row['ag_id'];
	// 			$insertAry['acc_id']         = $db->row['admin_id'];
	// 			$insertAry['endDate']        = $endDate;
	// 			$insertAry['startDate']      = $startDate;
	// 			$insertAry['ReturnRate']     = $thisRate['ReturnRate'];
	// 			$insertAry['commissionRate'] = $thisRate['commissionRate'];

	// 			insertRate($insertAry);
	// 			$memCount++;
	// 		}
	// 	}while($db->row = $db->fetch_assoc());
	// }

	$sendAry = array();
	$sendAry['result']    = 'success';
	$sendAry['resultMsg'] = '總共新增代理 '.$adminCount.' 筆';
	// $sendAry['resultMsg'] = '總共新增代理 '.$adminCount.' 筆、會員 '.$memCount.'筆';

	echo json_encode($sendAry);
	unset($db, $html, $Points);
	exit;
}

//檢查上期Rate
function chkRate($dataAry){
	$db = new PDO_DB(DB_HOST, DB_USER, DB_PWD, DB_NAME);
	$db->selectTB('AdminRate');
	$db->getData("*",
	"WHERE ag_id = '".$dataAry['ag_id']."'
	 AND acc_id = '".$dataAry['admin_id']."'
	 AND startDate >= '".$dataAry['startDate']."'
	 AND endDate <= '".$dataAry['endDate']."'");
	$db->execute();
	//表示沒有新增過下期Rate
	if($db->total_row <= 0){
		$db->getData("*",
		"WHERE ag_id = '".$dataAry['ag_id']."'
		 AND acc_id = '".$dataAry['admin_id']."'
		 AND endDate <= '".$dataAry['startDate']."'
		 ORDER BY addtime DESC, updtime DESC");
		$db->execute();
		if($db->total_row > 0){
			return $db->row;
		}
		//未有新增過Rate則使用自己本身的Rate
		if($db->total_row <= 0){
			return $dataAry;
		}
	}
	return false;
}

//新增下期Rate
function insertRate($insertAry){
	$db = new PDO_DB(DB_HOST, DB_USER, DB_PWD, DB_NAME);
	$db->selectTB('AdminRate');
	$db->insertData($insertAry);
	$db->execute();
	unset($db);
	return true;
}
?>