<?php
header("Content-Type:text/html; charset=utf-8");
include_once $_SERVER['DOCUMENT_ROOT'].'/class/PDO_DB.class.php';

set_time_limit(180);

$DB_WINLOSE = 'Game_WinloseLog';

$MemberID = '36';
$count    = 5000;

// $GameId       = '25';
// $GameId       = '26';
// $WinLoseState = '';

// $GameId       = '27';
// $WinLoseState = '玩家 mp0006 : 押閒:100點.(亮燈) 莊家 9點, 閒家 7點, 莊家贏 玩家 mp0006 輸:合計100點 ;{"Bank":"26,16,19","Player":"21,9,0","Winlose":"Bank","Point":"-100"}';

// $GameId       = '28';
// $WinLoseState = '下注:3,5二骰 100點3,6二骰 100點4,6二骰 100點三骰11 100點三骰小 100點三骰大 100點5雙骰 100點3圍骰 100點開盅5,1,2;{"Bank":"5,1,2","Result":"8","Point":"-600"}';

$GameId       = '29';
$WinLoseState = '輪盤轉到22點;{"Bank":"37","Result":"37","Point":"-200"}';
$Bet = '2000000';
$CommBase = '2000000';
$WinLose = '-2000000';
$CurrentPoints = '865000000';

$TableName = 'Winlose_'.$MemberID;
$RoundCode = randNum(2).'-'.randNum(4);


$db = new PDO_DB(DB_HOST, DB_USER, DB_PWD, $DB_WINLOSE);
$db->selectTB($TableName);
// $db->getData("*");
// $db->pagingMSSQL(100, 'CreateDate');
// $db->execute();


$insertAry = [
	'GameMode'      => '0',
	'GameId'        => $GameId,
	'TableId'       => 1,
	'RoundCode'     => $RoundCode,
	'GetJPMoney'    => 0,
	'Bet'           => $Bet,
	'CommBase'      => $CommBase,
	'WinLose'       => $WinLose,
	'CurrentPoints' => $CurrentPoints,
	'WinLoseState'  => $WinLoseState,
	'BetState'      => 'None',
	'LogIP'         => '127.0.0.1',
	'LogPath'       => 'test.txt',
	'CreateDate'    => date('Y-m-d H:i:s'),
];

try {
	for($i=1; $i <= $count ; $i++){
		$insertAry['RoundId'] = $i;

		$db->insertData($insertAry);
		$db->execute();
	}
} catch (Exception $e) {
	echo '<pre>';
	print_r($e->getMessage());
	exit;
}
echo 'Success';
exit;

// $db = new PDO_DB(DB_HOST, DB_USER, DB_PWD, DB_NAME);
// $db = new PDO_DB('202.150.211.182:19999', DB_USER, DB_PWD, DB_NAME);
// $db->selectTB('A_Member');
// $db->getData('*');
// $result = $db->execute();









// 產生n位隨機數
function randNum($n){
	$verifyNum = $n;
	$Ary = array('1','2','3','4','5','6','7','8','9','1','2','3','4','5','6','7','8','9','A','B','C','D','E','F','G','H','I','J','K','L','M','N','P','Q','R','S','T','U','V','W','X','Y','Z');
	$Cont = '';
	srand ((double) microtime() * 10000000);
	$rand_keys = array_rand($Ary, $n);
	for($i=0; $i<count($rand_keys); $i++){
		$Cont .= $Ary[$rand_keys[$i]];
	}
	return $Cont;
}
?>