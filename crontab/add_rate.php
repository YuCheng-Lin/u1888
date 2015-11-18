<?php
include_once $_SERVER['DOCUMENT_ROOT'].'/class/Template.class.php';
include_once $_SERVER['DOCUMENT_ROOT'].'/class/Points.class.php';

$html    = new Template('empty', false);
$sendAry = array();
//下期日期
$todayDate = date('Y-m-d');
if(!empty($_GET['date']) && !empty(strtotime($_GET['date']))){
	$todayDate = $_GET['date'];
}
$nowTime   = $todayDate.' '.$html->maintainStartTime;//跳到下一期時間
$dateAry   = $html->dateArray($nowTime);
$count     = count($dateAry['start']);
$startDate = $dateAry['start'][$count-2];
$endDate   = $dateAry['end'][$count-1];

$adminCount = 0;
$memCount   = 0;
// echo '<pre>';
// print_r($dateAry);
// print_r($startDate);
// echo '<br>';
// print_r($endDate);
// exit;

//取得所有Admin(代理)做新增
$Points = new Points();
$db     = new PDO_DB(DB_HOST, DB_USER, DB_PWD, DB_NAME);
$db->selectTB('Admin');
$db->getData("*","WHERE ag_id = '3'");
$db->execute();
if($db->total_row > 0){
	do{
		$db->row['startDate'] = $startDate;
		$db->row['endDate']   = $endDate;
		$Points->getAdminRate($db->row, $startDate, $endDate, false, true);
	}while($db->row = $db->fetch_assoc());
	$adminCount = $Points->adminRateCount;
}

$sendAry = array();
$sendAry['result']    = 'success';
$sendAry['resultMsg'] = 'add count : '.$adminCount;

echo json_encode($sendAry);
unset($db, $Points);
exit;
?>