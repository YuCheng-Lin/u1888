<?php
include_once $_SERVER['DOCUMENT_ROOT'].'/class/Template.class.php';
if(empty($_POST) || empty($_POST['action'])){
	header("Location: /index.php?err=3");
	exit;
}
$login = new Template('empty');
$login->WebLogin->chkLogin($_SESSION['_admin_acc'], $_SESSION['_admin_pwd']);
$status = $login->WebLogin->getStatus();
if($status != '0' || $login->WebLogin->nowPagePower != 'w'){
	header("location: /index.php?err=".$status);
	exit;
}
$nowLoginId = $login->WebLogin->admin_id;

try{
	$langAry  = $login->lang;
}catch(Exception $e){
}
unset($login);

switch ($action = $_POST['action']) {
	//編輯描述
	case $action == 'updDetail':
		$referer = $_SERVER['HTTP_REFERER'];
		if(empty($_POST['id'])){
			breakoff($langAry['ERRNoID'], '/index.php?err=3&msg=noid');
			exit;
		}
		$id              = $_POST['id'];
		$GameDescription = empty($_POST['GameDescription']) ? null : $_POST['GameDescription'];

		$db = new PDO_DB(DB_HOST, DB_USER, DB_PWD, DB_NAME);
		$db->selectTB('Game');
		$db->getData("*", "WHERE GameID = '".$id."'");
		$db->execute();
		if($db->total_row <= 0){
			breakoff($langAry['ERRNoID'], '/index.php?err=3&msg=noid');
			exit;
		}
		$updateAry = [
			'GameDescription' => $GameDescription,
			'updated_at'      => date('Y-m-d H:i:s')
		];
		$db->updateData($updateAry, "WHERE GameID = '".$id."'");
		$text = [
			'query' => $db->getCurrentQueryString(),
			'value' => $updateAry
		];
		$result = $db->execute();
		$text['result'] = $result;
		$note = '[System]編輯遊戲描述';
		addAdminLogs($text, $nowLoginId, $note);
		if(is_bool($result) && $result){
			breakoff($langAry['UpdateSuccess'], $referer);
		}else{
			breakoff($langAry['UpdateFail'].(DEBUG ? '('.$result.')' : ''), $referer);
		}
		unset($db);
		exit;
	//修改機率
	case $action == 'updGameRate':
		$referer = $_SERVER['HTTP_REFERER'];
		if(empty($_POST['id'])){
			breakoff($langAry['ERRNoID'], '/index.php?err=3&msg=noid');
			exit;
		}
		$id   = $_POST['id'];

		if(!isset($_POST['GameRate']) || $_POST['GameRate'] < 0 || !is_numeric($_POST['GameRate']) || preg_match('/-(.*)/', $_POST['GameRate'])){
			breakoff($langAry['ERRGameRateEnter'], $referer);
			exit;
		}
		$GameRate = $_POST['GameRate'];
		if($GameRate < 0 || $GameRate > 120){
			breakoff($langAry['ERRGameRateRange'], $referer);
			exit;
		}

		$db = new PDO_DB(DB_HOST, DB_USER, DB_PWD, DB_NAME);
		$db->selectTB('Game');
		$db->getData("*", "WHERE GameID = '".$id."'");
		$db->execute();
		if($db->total_row <= 0){
			breakoff($langAry['ERRNoID'], '/index.php?err=3&msg=noid');
			exit;
		}
		$updateAry = [
			'GameRate'   => $GameRate,
			'updated_at' => date('Y-m-d H:i:s')
		];

		//須先確認是否正確送出Socket
		//修改桌機率必須抓出資料庫有幾桌
		// $db->selectTB('TableInfo');
		// $db->getData("*", "WHERE GameType = '".$id."'");
		// $db->execute();
		// if($db->total_row <= 0){
		// 	breakoff($langAry['ERRGameTableNone'], $referer);
		// 	exit;
		// }
		// $endTableNum = $db->total_row - 1;

		//傳入server告知指定桌機率
		try {
			// $msg = "6@1011@27@0@99@99@#"
			$msg[] = '6';
			$msg[] = '1011';
			$msg[] = $id;//遊戲編號
			$msg[] = 0;//遊戲模式
			$msg[] = 0;//起始桌數
			$msg[] = 99;//結束桌數 目前預設不分桌子
			// $msg[] = $endTableNum;
			$msg[] = $GameRate;

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

			$db->selectTB('Game');
			$db->updateData($updateAry, "WHERE GameID = '".$id."'");
			$text = [
				'query' => $db->getCurrentQueryString(),
				'value' => $updateAry
			];
			$result = $db->execute();
			$text['result'] = $result;
			$note = '[System]修正遊戲機率';
			addAdminLogs($text, $nowLoginId, $note);
			if(is_bool($result) && $result){
				breakoff($langAry['UpdateSuccess'], $referer);
			}else{
				breakoff($langAry['UpdateFail'].(DEBUG ? '('.$result.')' : ''), $referer);
			}
		} catch (Exception $e) {
			breakoff($langAry['UpdateFail'].(DEBUG || $nowLoginAg == '1' ? 'Server Error, please retry.('.$e->getMessage().')' : ''), $referer);
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