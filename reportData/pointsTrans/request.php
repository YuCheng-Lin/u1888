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
$nowLoginId = $login->WebLogin->admin_id;
unset($login);

try{
	include_once $_SERVER['DOCUMENT_ROOT'].'/class/Language.class.php';
	$language = new Language();
	$langAry  = $language->getLanguage();
}catch(Exception $e){
}

switch ($action = $_POST['action']) {
	//編輯備註
	case $action == 'updRecord':
		$referer = $_SERVER['HTTP_REFERER'];
		if(empty($_POST['id'])){
			breakoff($langAry['ERRNoID'], '/index.php?err=3&msg=noid');
			exit;
		}
		$id     = $_POST['id'];
		$note   = empty($_POST['note']) ? '' : $_POST['note'];

		$db = new PDO_DB(DB_HOST, DB_USER, DB_PWD, DB_NAME);
		$db->selectTB('AdminWinLose');
		$db->getData("*", "WHERE id = '".$id."'");
		$db->execute();
		if($db->total_row <= 0){
			breakoff($langAry['ERRNoID'], '/index.php?err=3&msg=noid');
			exit;
		}
		$updateAry = [
			'note'       => $note,
			'updated_at' => date('Y-m-d H:i:s')
		];
		$db->updateData($updateAry, "WHERE id = '".$id."'");
		$text = [
			'query' => $db->getCurrentQueryString(),
			'value' => $updateAry
		];
		$result = $db->execute();
		$text['result'] = $result;
		addAdminLogs($text, $nowLoginId, $note);
		if(is_bool($result) && $result){
			breakoff($langAry['UpdateSuccess'], $referer);
		}else{
			breakoff($langAry['UpdateFail'].(DEBUG ? '('.$result.')' : ''), $referer);
		}
		unset($db);
		exit;
	//編輯備註
	case $action == 'updMemRecord':
		$referer = $_SERVER['HTTP_REFERER'];
		if(empty($_POST['id'])){
			breakoff($langAry['ERRNoID'], '/index.php?err=3&msg=noid');
			exit;
		}
		$id     = $_POST['id'];
		$note   = empty($_POST['note']) ? '' : $_POST['note'];

		$db = new PDO_DB(DB_HOST, DB_USER, DB_PWD, DB_NAME);
		$db->selectTB('MemWinLose');
		$db->getData("*", "WHERE id = '".$id."'");
		$db->execute();
		if($db->total_row <= 0){
			breakoff($langAry['ERRNoID'], '/index.php?err=3&msg=noid');
			exit;
		}
		$updateAry = [
			'note'       => $note,
			'updated_at' => date('Y-m-d H:i:s')
		];
		$db->updateData($updateAry, "WHERE id = '".$id."'");
		$text = [
			'query' => $db->getCurrentQueryString(),
			'value' => $updateAry
		];
		$result = $db->execute();
		$text['result'] = $result;
		addAdminLogs($text, $nowLoginId, $note);
		if(is_bool($result) && $result){
			breakoff($langAry['UpdateSuccess'], $referer);
		}else{
			breakoff($langAry['UpdateFail'].(DEBUG ? '('.$result.')' : ''), $referer);
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