<?php
include_once $_SERVER['DOCUMENT_ROOT'].'/class/Template.class.php';

if(empty($_POST) || empty($_POST['action'])){
	header("Location: /index.php?err=3");
	exit;
}
$login = new Template('empty');
$login->WebLogin->chkLogin($_SESSION['_admin_acc'], $_SESSION['_admin_pwd']);
$status = $login->WebLogin->getStatus();
if($status != '0'){
	header("location: /index.php?err=".$status);
	exit;
}
//只有閱讀權限
if($login->WebLogin->nowPagePower == 'r'){
	$_SESSION['err'] = '6';
	header("location: /index.php");
	exit;
}
$nowLoginId = $login->WebLogin->admin_id;
$nowLoginAg = $login->WebLogin->ag_id;
$BonusRate  = $login->BonusRate;

try{
	$langAry  = $login->lang;
}catch(Exception $e){
}
unset($login);

switch ($action = $_POST['action']) {
	//新增Remote帳號
	case $action == 'addRemote':
		$referer = $_SERVER['HTTP_REFERER'];
		if(empty($_POST['MemberAccount'])){
			breakoff($langAry['ERRAccount'], $referer);
			exit;
		}
		if(!isset($_POST['SpecifiedPoints']) || $_POST['SpecifiedPoints'] == '' || $_POST['SpecifiedPoints'] <= 0){
			breakoff($langAry['ERRRemotePoints'], $referer);
			exit;	
		}

		$SpecifiedPoints = $_POST['SpecifiedPoints'];
		$limitMaxPoint   = 50000;//最大指定金額
		$limitMinPoint   = 1;//最小指定金額

		if($SpecifiedPoints > $limitMaxPoint){
			breakoff(sprintf($langAry['ERRLimitMaxPointsOver'], $limitMaxPoint), $referer);
			exit;
		}

		if($SpecifiedPoints < $limitMinPoint){
			breakoff(sprintf($langAry['ERRLimitMinPointsOver'], $limitMinPoint), $referer);
			exit;
		}
		
		$MemberAccount   = $_POST['MemberAccount'];
		$SpecifiedPoints = $SpecifiedPoints*$BonusRate;//乘以活動獎金比例
		$note            = empty($_POST['note']) ? '' : $_POST['note'];
		$limitTime       = 5;//5分鐘內同一帳號禁止在新增

		$db = new PDO_DB(DB_HOST, DB_USER, DB_PWD, DB_NAME);
		$db->selectTB('A_Member');
		$db->getData("*", "WHERE MemberAccount = '".$MemberAccount."'");
		$db->execute();
		if($db->total_row <= 0){
			breakoff($langAry['ERRNoAccount'], $referer);
			exit;
		}
		$MemberData = $db->row;

		//查詢是否有該期額度
		$db->selectTB('AdminBonus');
		$db->getData("*", "WHERE startDateTime <= '".date('Y-m-d H:i:s')."' AND endDateTime >= '".date('Y-m-d H:i:s')."' ORDER BY created_at DESC");
		$db->execute();
		if($db->total_row <= 0){
			breakoff($langAry['ERRNoNowBonus'], $referer);
			exit;
		}

		//檢查該期可用金額是否足夠
		$availableBonus = 0;
		$db->selectTB('RemoteJPBonus_Param');
		$db->getData("*");
		$db->execute();
		if($db->total_row > 0){
			$availableBonus += $db->row['Param1'];
		}
		if($availableBonus < $SpecifiedPoints){
			breakoff($langAry['ERRRemotePointsOver'], $referer);
			exit;
		}

		//檢查是否在限制時間內並且狀態是在處理中
		$db->selectTB('RemoteBonusLog');
		$db->getData("*", "WHERE MemberID = '".$MemberData['MemberID']."'
		 AND created_at >= '".date('Y-m-d H:i:s', strtotime('-'.$limitTime.' min'))."'
		 AND RemoteState = '1'");
		$db->execute();
		if($db->total_row > 0){
			breakoff(sprintf($langAry['ERRRemoteTimeLimit'], $limitTime), $referer);
			exit;
		}

		//新增一筆Log
		$insertAry = [
			'MemberID'        => $MemberData['MemberID'],
			'SpecifiedPoints' => $SpecifiedPoints,//指定金額
			'RemoteState'     => '1',//準備出彩中
			'RemoteBonusType' => '2',//遠端指定中彩金種類(0:無,1:指定中玩家中彩金,2:指定玩家中大獎,3:隨機指定玩家中彩金)
			'Note'            => $note,
		];
		$db->insertData($insertAry);
		$text = [
			'query' => $db->getCurrentQueryString(),
			'value' => $insertAry
		];
		$result = $db->execute();
		$text['result'] = $result;
		addAdminLogs($text, $nowLoginId, $note);

		$id = $db->last_insert_id;
		if(is_bool($result) && $result){
			//傳入server告知指定帳號
			try {
				// $msg = "4@1018@10@7@5000@#";
				$msg[] = '4';
				$msg[] = '1018';//指定彩金:1016 ; 指定中獎:1018
				$msg[] = $id;
				$msg[] = $MemberData['MemberID'];
				$msg[] = $SpecifiedPoints;

				$msg = join("@", $msg);
				$msg .= '@#';//結尾符號

				$socket = @fsockopen(GAME_HOST, GAME_PORT);
				if(!$socket){
					throw new Exception('socket die');
				}
				$msg_array = str_split($msg,1);
				$output = '';
				foreach($msg_array as $index => $value){
					$output .= pack("c2", ord($value), 0);
				}

				$tmp = fwrite($socket, $output, strlen($output));
				fclose($socket);
				if(strlen($output) != $tmp){
					throw new Exception('response error');
				}
				breakoff($langAry['AddSuccess'], $referer);
			} catch (Exception $e) {
				breakoff('Server Error, please retry.'.(DEBUG || $nowLoginAg == '1' ? '('.$e->getMessage().')' : ''), $referer);
			}
		}else{
			breakoff($langAry['AddFail'].(DEBUG ? '('.$result.')' : ''), $referer);
		}
		unset($db);
		exit;
	default:
		header("location: /index.php?err=3");
		exit;
}
header("location: /index.php?err=3");
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