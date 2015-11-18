<?php
if(!isset($_SESSION)){
	session_start();
}
include_once $_SERVER['DOCUMENT_ROOT'].'/class/Template.class.php';
include_once $_SERVER['DOCUMENT_ROOT'].'/class/Points.class.php';

if(empty($_SESSION['_admin_acc']) || empty($_SESSION['_admin_pwd'])){
	$_SESSION['err'] = '1';
	header("Location: /index.php");
	exit;
}

if(empty($_POST) || empty($_POST['action'])){
	$_SESSION['err'] = '3';
	header("Location: /index.php");
	exit;
}
$login = new Template('empty',false);
$login->WebLogin->chkLogin($_SESSION['_admin_acc'], $_SESSION['_admin_pwd']);
$status = $login->WebLogin->getStatus();
if($status != '0'){
	$_SESSION['err'] = $status;
	header("location: /index.php");
	exit;
}
//只有閱讀權限
if($login->WebLogin->nowPagePower == 'r'){
	$_SESSION['err'] = '6';
	header("location: /index.php");
	exit;
}
$nowLoginData  = (array)$login->WebLogin;
$dateAry       = $login->dateArray();
$count         = count($dateAry['start']);
$startDateTime = $dateAry['start'][$count-2].' '.$login->StartTime;
$endDateTime   = $dateAry['end'][$count-1].' '.$login->EndTime;
$langAry       = $login->lang;
$BonusRate     = $login->BonusRate;
unset($login);

switch ($action = $_POST['action']){
	case ($action == 'addBonus'):
		$referer = $_SERVER['HTTP_REFERER'];

		$db = new PDO_DB(DB_HOST, DB_USER, DB_PWD, DB_NAME);

		//取得目前額度
		$nowBonus = 0;
		$remoteBonusData = [];
		$db->selectTB('RemoteJPBonus_Param');
		$db->getData("*");
		$db->execute();
		if($db->total_row > 0){
			$remoteBonusData = $db->row;
			$nowBonus += $db->row['Param1'];
		}

		//查詢當期活動設置
		$db->selectTB('AdminBonus');
		$db->getData("*", "WHERE startDateTime = '".$startDateTime."' AND endDateTime = '".$endDateTime."'
		 ORDER BY created_at DESC , updated_at DESC");
		$db->execute();
		if($db->total_row > 0){
			if(!isset($_POST['addBonus']) || !preg_match('/^([0-9]+)$/', $_POST['addBonus']) || $_POST['addBonus'] < 0){
				breakoff('請輸入補充額度', $referer);
				exit;
			}	
			$bonusData = $db->row;
			$addBonus  = $_POST['addBonus']*$BonusRate;
			$nowBonus += $addBonus;

			//補充當期活動獎金
			$updateAry = [
				'Param1' => $nowBonus,
			];
			$db->selectTB('RemoteJPBonus_Param');
			$db->updateData($updateAry, "WHERE Sno = '".$remoteBonusData['Sno']."'");

			$text = [
				'query' => $db->getCurrentQueryString(),
				'value' => $updateAry
			];
			$result = $db->execute();
			$text['result'] = $result;
			addAdminLogs($text, $nowLoginData['admin_id'], '[System]補充活動獎金額度');

			//補充當期活動獎金
			$updateAry = [
				'bonus'        => $bonusData['bonus']+$addBonus,
				'surplusBonus' => $nowBonus,
				'updated_at'   => date('Y-m-d H:i:s'),
			];
			$db->selectTB('AdminBonus');
			$db->updateData($updateAry, "WHERE id = '".$bonusData['id']."'");

			$text = [
				'query' => $db->getCurrentQueryString(),
				'value' => $updateAry
			];
			$result = $db->execute();
			$text['result'] = $result;
			addAdminLogs($text, $nowLoginData['admin_id'], '[System]補充活動獎金額度');

			breakoff('補充活動金額成功，目前額度：'.(number_format($nowBonus/$BonusRate, 2)), $referer);
			unset($db);
			exit;
		}
		if($db->total_row <= 0){
			if(!isset($_POST['addBonus']) || !preg_match('/^([0-9]+)$/', $_POST['addBonus']) || $_POST['addBonus'] < 0){
				breakoff('請輸入補充額度', $referer);
				exit;
			}	
			$setBonus = $_POST['addBonus']*$BonusRate;
			
			//查詢上期
			$db->getData("*", "WHERE endDateTime < '".$startDateTime."' ORDER BY endDateTime DESC");
			$db->execute();
			if($db->total_row > 0){
				//有查到上期更改上期剩餘Bonus
				$updateAry = [
					'surplusBonus' => $nowBonus,
					'updated_at'   => date('Y-m-d H:i:s'),
				];
				$db->selectTB('AdminBonus');
				$db->updateData($updateAry, "WHERE id = '".$db->row['id']."'");

				$text = [
					'query' => $db->getCurrentQueryString(),
					'value' => $updateAry
				];
				$result = $db->execute();
				$text['result'] = $result;
				addAdminLogs($text, $nowLoginData['admin_id'], '[System]修正上期剩餘Bonus');
			}

			//新增下期活動獎金
			$insertAry = [
				'bonus'         => $setBonus,
				'surplusBonus'  => $setBonus,
				'startDateTime' => $startDateTime,
				'endDateTime'   => $endDateTime,
			];
			$db->selectTB('AdminBonus');
			$db->insertData($insertAry);

			$text = [
				'query' => $db->getCurrentQueryString(),
				'value' => $insertAry
			];
			$result = $db->execute();
			$text['result'] = $result;
			addAdminLogs($text, $nowLoginData['admin_id'], '[System]新增下期活動獎金');

			//修改RemoteBonus的獎金
			$updateAry = [
				'Param1' => $setBonus,
			];
			$db->selectTB('RemoteJPBonus_Param');
			$db->updateData($updateAry, "WHERE Sno = '".$remoteBonusData['Sno']."'");

			$text = [
				'query' => $db->getCurrentQueryString(),
				'value' => $updateAry
			];
			$result = $db->execute();
			$text['result'] = $result;
			addAdminLogs($text, $nowLoginData['admin_id'], '[System]設置新一期活動獎金');

			breakoff('下期活動獎金已新增並設置完成', $referer);
			unset($db);
			exit;
		}
		breakoff($langAry['IndexErrorMsg7'], $referer);
		exit;
	case ($action == 'calWinLose'):
		$referer = $_SERVER['HTTP_REFERER'];
		$stime=explode(" ",microtime());
		$ss=$stime[0]+$stime[1];

		$html = new Template();
		$sendAry = array();

		$html->dateCount = 6;//預設期數，如有更改須連同變更functionLib.php
		$dateAry = $html->dateArray();
		$count   = count($dateAry['start']);
		$options = '';
		$select  = empty($_POST['select']) ? $count-1 : $_POST['select'];
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

		$addAdminCount = 0;
		$addMemCount   = 0;

		//由總控台計算並且寫入紀錄
		$db = new PDO_DB(DB_HOST, DB_USER, DB_PWD, DB_NAME);
		$db->selectTB('Admin');
		$db->getData("*", "WHERE ag_id = '2'");//總控台
		$db->execute();
		if($db->total_row > 0){
			do{
				echo '計算總控台：'.$db->row['admin_acc'];
				$Points = new Points($html->WebLogin);
				$Points->chkAdminData += $db->row;
				$result = $Points->getAdminWinLose($db->row, $startDate, $endDate);

				$addAdminCount += $Points->addAdminCount;
				$addMemCount   += $Points->addMemCount;

				echo ' 代理：'.$Points->addAdminCount;
				echo ' 會員：'.$Points->addMemCount;

				unset($Points);
				echo '<br>';
			}while($db->row = $db->fetch_assoc());
		}

		//计算执行时间	
		$mtime=explode(" ",microtime());
		$es=$mtime[0]+$mtime[1];
		$mtime=$es-$ss;	//总耗时
		
		breakoff('計算代理成功筆數：'.$addAdminCount.'\r\n計算會員成功筆數：'.$addMemCount.'\r\n計算耗時：'.$mtime, $referer);
		unset($db, $Points);
		exit;

	case ($action == 'RateSwitchChange'):
		$referer = $_SERVER['HTTP_REFERER'];
		if(empty($_POST['RateSettingSwitch'])){
			header("Location: /index.php?err=3");
			exit;
		}
		if(!in_array($_POST['RateSettingSwitch'], array('y','n'))){
			header("Location: /index.php?err=3");
			exit;
		}


		$db = new PDO_DB(DB_HOST, DB_USER, DB_PWD, DB_NAME);
		$db->selectTB('SysValue');
		$db->getData("*","WHERE SysKey = 'RateSettingSwitch'");
		$db->execute();
		if($db->total_row > 0){
			$updateAry = array();
			$updateAry['SysValue'] = $_POST['RateSettingSwitch'] == 'n' ? 'y' : 'n';

			$db->updateData($updateAry, "WHERE SysKey = 'RateSettingSwitch'");
			$result = $db->execute();
			breakoff('Success', $referer);
			unset($db);
			exit;
		}
		breakoff('Fail', $referer);
		unset($db);
		exit;
}
header("location: /index.php?err=3");
exit;

function breakoff($msg, $referer = 'index.php'){
	$ary['msg'] = $msg;
	$_SESSION['_err'] = json_encode($ary);
	header('location: '.$referer);
	exit;
}

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
?>