<?php
include_once dirname(__FILE__).'/class/Mysql.class.php';

//距離現在5分鐘前
$nowTime = date("Y-m-d H:i:s", mktime(date("H"), date("i")-5, date("s"), date("m"), date("d"), date("Y")));

$clearSession = new Mysql(DB_HOST, DB_USER, DB_PWD, DB_NAME);
$clearSession->selectTB('admin');
$clearAry = array();
$clearAry['s_id'] 				= NULL;
$clearAry['admin_action_time'] 	= NULL;
$clearSession->updateData($clearAry, 
							"WHERE admin_action_time < '". $nowTime ."' AND 
									admin_action_time IS NOT NULL AND 
									s_id IS NOT NULL");

unset($clearSession);
$sendData = array();
$sendData['result'] = 'success';
echo json_encode($sendData);
exit;
?>