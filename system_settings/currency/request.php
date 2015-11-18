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

$referer = $_SERVER['HTTP_REFERER'];
switch ($action = $_POST['action']) {
	//新增幣別
	case $action == 'add':
		if($nowLoginAg != '1'){
			$_SESSION['err'] = '6';
			header("location: /index.php");
			exit;
		}
		if(empty($_POST['code']) || empty($_POST['title']) || empty($_POST['rate'])){
			breakoff($langAry['ERRNoPost'], $referer);
			exit;
		}
		if(!is_numeric($_POST['rate'])){
			breakoff($langAry['ERRNoPost'], $referer);
			exit;
		}

		$insertAry = [
			'code'  => $_POST['code'],
			'title' => $_POST['title'],
			'rate'  => $_POST['rate'],
		];

		$db = new PDO_DB(DB_HOST, DB_USER, DB_PWD, DB_NAME);
		$db->selectTB('Currency');
		$db->insertData($insertAry);
		$text = [
			'query' => $db->getCurrentQueryString(),
			'value' => $insertAry
		];
		$note   = '[System]Created a new Currency';
		$result = $db->execute();
		$text['result'] = $result;
		addAdminLogs($text, $nowLoginId, $note);

		if(is_bool($result) && $result){
			breakoff($langAry['AddSuccess'], $referer);
		}else{
			breakoff($langAry['AddFail'].(DEBUG ? '('.$result.')' : ''), $referer);
		}
		unset($db);
		exit;
	case 'update':
		if(empty($_POST['title']) || empty($_POST['rate']) || empty($_POST['id'])){
			breakoff($langAry['ERRNoPost'], $referer);
			exit;
		}
		if(!is_numeric($_POST['rate'])){
			breakoff($langAry['ERRNoPost'], $referer);
			exit;
		}
		$_POST['enabled'] = empty($_POST['enabled']) ? 'n' : 'y';

		$db = new PDO_DB(DB_HOST, DB_USER, DB_PWD, DB_NAME);
		$db->selectTB('Currency');
		$db->getData("*", "WHERE id = '".$_POST['id']."'");
		$db->execute();
		if($db->total_row <= 0){
			breakoff($langAry['ERRNoID'], $referer);
			exit;
		}
		$updateAry = [
			'title'      => $_POST['title'],
			'rate'       => $_POST['rate'],
			'enabled'    => $_POST['enabled'],
			'updated_at' => date('Y-m-d H:i:s'),
		];
		$db->updateData($updateAry, "WHERE id = '".$_POST['id']."'");
		$text = [
			'query' => $db->getCurrentQueryString(),
			'value' => $updateAry
		];
		$note           = '[System]Updated the Currency';
		$result         = $db->execute();
		$text['result'] = $result;
		addAdminLogs($text, $nowLoginId, $note);

		if(is_bool($result) && $result){
			breakoff($langAry['UpdateSuccess'], $referer);
		}else{
			breakoff($langAry['UpdateFail'].(DEBUG ? '('.$result.')' : ''), $referer);
		}
		unset($db);
		exit;
	case 'delete':
		if($nowLoginAg != '1'){
			$_SESSION['err'] = '6';
			header("location: /index.php");
			exit;
		}
		if(empty($_POST['id'])){
			breakoff($langAry['ERRNoID'], $referer);
			exit;
		}

		$db = new PDO_DB(DB_HOST, DB_USER, DB_PWD, DB_NAME);
		$db->selectTB('Currency');
		$db->getData("*", "WHERE id = '".$_POST['id']."'");
		$db->execute();
		if($db->total_row <= 0){
			breakoff($langAry['ERRNoID'], $referer);
			exit;
		}
		$db->deleteData("WHERE id = '".$_POST['id']."'");
		$text = [
			'query' => $db->getCurrentQueryString(),
			'value' => $_POST['id']
		];
		$note           = '[System]Deleted the Currency';
		$result         = $db->execute();
		$text['result'] = $result;
		addAdminLogs($text, $nowLoginId, $note);

		if(is_bool($result) && $result){
			breakoff($langAry['DelSuccess'], $referer);
		}else{
			breakoff($langAry['DelFail'].(DEBUG ? '('.$result.')' : ''), $referer);
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