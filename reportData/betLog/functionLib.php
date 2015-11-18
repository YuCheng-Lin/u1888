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

$userIp = $html->WebLogin->getUserIp();
unset($html);



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
exit;

/**
 * 詳細內容
 */
function viewDetail(){
	$sendAry = array();
	$html = new Template(); 
	$html->publicTemp = $html->getFile($_SERVER['DOCUMENT_ROOT']."/tpl/reportData/betLog/betDetail.html");
	//欲取代的内容
	$compilerAry = array();

	$data      = $_POST['id'];
	$data      = explode(";", $data);
	$id        = $data[0];
	$code      = $data[1];
	$data      = explode("@", $code);
	$roundCode = $data[0];
	$roundId   = $data[1];

	$Points = new Points($html->WebLogin);
	if(!$Points->chkDBTableExistById($id)){
		$sendAry['systemErr'] = 'alert("'.$html->lang['IndexErrorMsg7'].'");';
		echo json_encode($sendAry);
		exit;
	}
	$row = $Points->getSingleWinLoseLog($id, $roundCode, $roundId);
	if(count($row) <= 0){
		$sendAry['systemErr'] = 'alert("'.$html->lang['IndexErrorMsg7'].'");';
		echo json_encode($sendAry);
		exit;
	}

	//分析牌組
	try {
		if(empty($row['WinLoseStateAry'])){
			throw new Exception('No Json');
		}
		$gameResult = $Points->getGameDetail($row['GameId'], $row['WinLoseStateAry']);
		$gameTemp   = $row['GameId'] == 27 ? $html->lang['GameTHBank'].'：' : '';//如果是百家樂必須顯示莊家
		switch ($row['GameId']) {
			case '23'://龍虎
				foreach ($gameResult['Player'] as $value) {
					$gameTemp .= '<img src="'.$value.'" />';
				}
				unset($gameResult['Player']);
				$gameTemp .= ' ';
				foreach ($gameResult['Bank'] as $key => $value) {
					$gameTemp .= '<img src="'.$value.'" />';
				}
				break;
			case '24'://5龍
			case '25'://浩克
			case '26'://足球女郎
			case '31'://高速公路王
			case '32'://海豚
				if(empty($gameResult['Bank'])){
					throw new Exception('No Bank');
				}
				foreach ($gameResult['Bank'] as $key => $value) {
					if($key != 0 && $key%5 == 0){
						$gameTemp .= '<br />';
					}
					$gameTemp .= '<img src="'.$value.'" />';
				}
				break;
			case '30'://猴子爬樹
			case '33'://賽車小瑪麗
				$gameTemp .= '<img src="'.$gameResult['BPT'].'" />';
				$gameTemp .= '<br />';
				foreach ($gameResult['Bank'] as $key => $value) {
					$gameTemp .= '<img src="'.$value.'" />';
				}
				break;
			case '37':
				$gameTemp .= $html->lang['GameTHBet'].'：'.$gameResult['Bet'];
				$gameTemp .= '<br />';
				$gameTemp .= $html->lang['GameTHTotalBet'].'：'.$gameResult['TotelBet'];
				$gameTemp .= '<br />';
				$gameTemp .= $html->lang['GameTHWinPoint'].'：'.$gameResult['Point'];
				break;
			default:
				if(empty($gameResult['Bank'])){
					throw new Exception('No Bank');
				}
				foreach ($gameResult['Bank'] as $value) {
					$gameTemp .= '<img src="'.$value.'" />';
				}
				break;
		}

		if(!empty($gameResult['Player'])){
			$gameTemp .= $row['GameId'] == 27 ? '<br />'.$html->lang['GameTHPlayer'].'：' : '<br />';//如果是百家樂必須顯示閒家
			foreach ($gameResult['Player'] as $value) {
				$gameTemp .= '<img src="'.$value.'" />';
			}
		}
		$row['GameContent'] = $gameTemp;
	} catch (Exception $e) {
		if(DEBUG){
			$row['GameContent'] = $row['WinLoseState'].'<br />'.json_encode($row['WinLoseStateAry']).'<br />'.$e->getMessage();
		}
	}
	if($html->WebLogin->ag_id == '1'){
		$row['GameContent'] .= '<br />'.$row['WinLoseState'];
	}

	$db = new PDO_DB(DB_HOST, DB_USER, DB_PWD, DB_NAME);
	$db->selectTB('A_Member');
	$db->getData("*", "WHERE MemberID = '".$id."'");
	$db->execute();
	if($db->total_row <= 0){
		$sendAry['systemErr'] = 'alert("'.$html->lang['IndexErrorMsg7'].'");';
		echo json_encode($sendAry);
		exit;
	}

	$row['Bet']           = number_format($row['Bet'], 2);
	$row['WinLose']       = number_format($row['WinLose'], 2);
	$row['CurrentPoints'] = number_format($row['CurrentPoints'], 2);
	$row['MemberAccount'] = $db->row['MemberAccount'];

	$html->publicTemp = $html->regexReplace($row, $html->publicTemp, '<!--__', '__-->');

	//重新组合页面
	$main = $html->compiler($compilerAry);
	
	$sendAry['result'] = $main;
	
	echo json_encode($sendAry);
	unset($html, $db, $Points);
	exit;
}
?>