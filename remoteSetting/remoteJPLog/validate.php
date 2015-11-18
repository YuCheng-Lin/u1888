<?php
include_once $_SERVER['DOCUMENT_ROOT'].'/class/PDO_DB.class.php';
include_once $_SERVER['DOCUMENT_ROOT'].'/class/Language.class.php';

$lang = new Language();
$langAry = $lang->getLanguage();

if(empty($_REQUEST['MemberAccount'])){
	echo '"'.$langAry['ERRNoAccount'].'"';
	exit;
}

$db = new PDO_DB( DB_HOST, DB_USER, DB_PWD, DB_NAME);
$db->selectTB('A_Member');
$db->getData("*", "WHERE MemberAccount = '".$_REQUEST['MemberAccount']."'");
$db->execute();
if($db->total_row <= 0){
	echo '"'.$langAry['ERRNoAccount'].'"';
	exit;
}
unset($db, $lang);
echo 'true';
exit;
?>