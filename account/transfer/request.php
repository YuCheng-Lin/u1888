<?php
/**
 * 帐户系统
 */
include_once $_SERVER['DOCUMENT_ROOT'].'/class/Template.class.php';
include_once $_SERVER['DOCUMENT_ROOT'].'/class/WebLogin.class.php';
include_once $_SERVER['DOCUMENT_ROOT'].'/class/Bank.class.php';
include_once $_SERVER['DOCUMENT_ROOT'].'/class/Points.class.php';
include_once $_SERVER['DOCUMENT_ROOT'].'/class/Admin.class.php';

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
$nowLoginData           = (array)$login->WebLogin;
$nowAdmin_id            = $login->WebLogin->admin_id;
$nowAdmin_agid          = $login->WebLogin->ag_id;
$nowAdminPoints         = $login->WebLogin->points;
$nowAdminDownCount      = $login->WebLogin->downCount;
$nowAdminReturnRate     = $login->WebLogin->ReturnRate;
$nowAdminDownMemCount   = $login->WebLogin->downMemCount;
$nowAdminCommissionRate = $login->WebLogin->commissionRate;
$RateSettingSwitch      = $login->RateSettingSwitch;
$MaxCommissionRate      = $login->MaxCommissionRate;
$MaxReturnRate          = $login->MaxReturnRate;
$MoneyPointRate         = $login->MoneyPointRate;
$dateAry                = $login->dateArray();
$count                  = count($dateAry['start']);
//下期占成設定
$startDate = $dateAry['start'][$count-2];
$endDate   = $dateAry['end'][$count-1];
unset($login);
try{
	include_once $_SERVER['DOCUMENT_ROOT'].'/class/Language.class.php';
	$language = new Language();
	$langAry  = $language->getLanguage();
}catch(Exception $e){
}

switch ($action = $_POST['action']) {
	//总代或管理员替代理补点
	case 'plusAgentPoints':
		$referer  = $_SERVER['HTTP_REFERER'];
		$event_id = '25';//替下线充值
		// 2014-12-6 新增代理可以補代理
		// if($nowAdmin_agid == '3'){//不是管理员或总代不能补代理
		// 	header("location: /index.php?err=3");
		// 	exit;
		// }
		if(empty($_POST['id'])){
			$_SESSION['err'] = '3';
			header("location: /index.php");
			exit;
		}
		if(empty($_POST['points']) || !preg_match('/^([0-9]+)$/', $_POST['points'])){
			breakoff($langAry['ERRPlusPoints'], $referer);
			exit;
		}	
		if($_POST['points'] > $nowAdminPoints){
			if($nowAdmin_agid != '1'){//如果不是管理员
				breakoff($langAry['ERRPlusPointsOverSelf'], $referer);
				exit;
			}
			$nowAdminPoints = $_POST['points'];
			$event_id       = '21';//管理员补点
		}
		if(empty($_POST['note'])){
			unset($_POST['note']);
		}

		//检查给予代理的资料库资料
		$toAdmin_id = $_POST['id'];
		$toPoints   = $_POST['points'];
		$db = new PDO_DB(DB_HOST, DB_USER, DB_PWD, DB_NAME);
		$db->selectTB('Admin');
		$db->getData("*","WHERE admin_id = '".$toAdmin_id."'");
		$db->execute();
		$result = '';
		$errMsg = array();
		if($db->total_row > 0){
			$toAdminPoints = $db->row['points'];
			$toAdmin_acc   = $db->row['admin_acc'];

			$allowManger = false;
			$Points      = new Points($nowLoginData);
			$Points->produceLink($db->row);
			$allowManger = $Points->allowSearch;
			unset($Points);
			//該登入者不允許變更
			if(!$allowManger){
				$_SESSION['err'] = '3';
				header('location: /index.php');
				exit;
			}

			$note = NULL;
			if(!empty($_POST['note'])){$note = $_POST['note'];}
			$bank = new Bank();
			//替下线充值扣点
			$result = $bank->debitTo( $event_id, $nowAdmin_id, $toAdmin_id, $nowAdminPoints, $toPoints, $note);
			if($result){
				//上线补点
				if($nowAdmin_agid != '1'){$event_id = '2';}//上级充值
				$result = $bank->creditFrom( $event_id, $toAdmin_id, $nowAdmin_id, $toAdminPoints, $toPoints, $note);
				if(!$result){
					$errMsg[] = 'CreditFail';
				}
			}else{
				$errMsg[] = 'DebitFail';
			}
			if(empty($errMsg)){
				breakoff($langAry['PlusPointsSuccess'], $referer);
				unset($db, $bank);
				exit;
			}		
		}
		if($db->total_row <= 0){$errMsg[] = $langAry['ERRNoID'];}
		breakoff($langAry['PlusPointsFail'].'('.join("-", $errMsg).')'.(DEBUG || $nowAdmin_agid == '1' ? '('.$bank->getErrorMsg().')' : ''), $referer);
		unset($db, $bank);
		exit;
	//对代理商扣点
	case 'minusAgentPoints':
		$referer  = $_SERVER['HTTP_REFERER'];
		$event_id = '20';//管理员扣点
		if($nowAdmin_agid != '1'){//不是管理员或总代不能补代理
			$event_id = '29';//扣点
		}
		if(empty($_POST['id'])){
			$_SESSION['err'] = '3';
			header("location: /index.php");
			exit;
		}
		if(empty($_POST['points']) || !preg_match('/^([0-9]+)$/', $_POST['points'])){
			breakoff($langAry['ERRMinusPoints'], $referer);
			exit;
		}	
		if(empty($_POST['note'])){
			unset($_POST['note']);
		}

		//检查给予代理的资料库资料
		$toAdmin_id = $_POST['id'];
		$toPoints   = $_POST['points'];
		$db = new PDO_DB(DB_HOST, DB_USER, DB_PWD, DB_NAME);
		$db->selectTB('Admin');
		$db->getData("*","WHERE admin_id = '".$toAdmin_id."'");
		$db->execute();
		$result = '';
		$errMsg = array();
		if($db->total_row > 0){
			$toAdminPoints = $db->row['points'];
			$toAdmin_acc   = $db->row['admin_acc'];

			$allowManger = false;
			$Points      = new Points($nowLoginData);
			$Points->produceLink($db->row);
			$allowManger = $Points->allowSearch;
			unset($Points);
			//該登入者不允許變更
			if(!$allowManger){
				$_SESSION['err'] = '3';
				header('location: /index.php');
				exit;
			}

			if($_POST['points'] > $toAdminPoints){
				breakoff($langAry['ERRMinusPointsOver'], $referer);
				exit;
			}
			$note = NULL;
			if(!empty($_POST['note'])){$note = $_POST['note'];}
			$bank = new Bank();
			//对代理扣点
			$result = $bank->debitTo( $event_id, $toAdmin_id, $nowAdmin_id, $toAdminPoints, $toPoints, $note);
			if($result){
				//扣点后返回該人身上
				$result = $bank->creditFrom( $event_id, $nowAdmin_id, $toAdmin_id, $nowAdminPoints, $toPoints, $note);
				if(!$result){
					$errMsg[] = 'CreditFail';
				}
			}else{
				$errMsg[] = 'DebitFail';
			}
			if(empty($errMsg)){
				breakoff($langAry['MinusPointsSuccess'], $referer);
				unset($db, $bank);
				exit;
			}		
		}
		if($db->total_row <= 0){$errMsg[] = $langAry['ERRNoID'];}
		breakoff($langAry['MinusPointsFail'].'('.join("-", $errMsg).')'.(DEBUG || $nowAdmin_agid == '1' ? '('.$bank->getErrorMsg().')' : ''), $referer);
		unset($db, $bank);
		exit;
	//代理替会员补点
	case 'plusMemPoints':
		$referer  = $_SERVER['HTTP_REFERER'];
		$event_id = '25';//替下线会员充值
		if(($nowAdmin_agid == '2')){//不是管理员或代理不能替会员补点
			$_SESSION['err'] = '3';
			header("location: /index.php");
			exit;
		}
		if(empty($_POST['id'])){
			$_SESSION['err'] = '3';
			header("location: /index.php");
			exit;
		}
		if(empty($_POST['points']) || !preg_match('/^([0-9]+)$/', $_POST['points'])){
			breakoff($langAry['ERRPlusPoints'], $referer);
			exit;
		}	
		if(empty($_POST['note'])){
			unset($_POST['note']);
		}

		//检查给予会员的资料库资料
		$toMemberID = $_POST['id'];
		$toPoints   = $_POST['points'];
		$db = new PDO_DB(DB_HOST, DB_USER, DB_PWD, DB_NAME);
		$db->selectTB('A_Member');
		$db->getData("*", "INNER JOIN Currency ON (A_Member.MemberCurrency = Currency.code)
		 INNER JOIN MemberFinance2 ON (MemberFinance2.MemberId = A_Member.MemberID)
		 WHERE A_Member.MemberID = '".$toMemberID."' AND MemberFinance2.PointType = 'Game'");
		$db->execute();
		$result = '';
		$errMsg = array();
		if($db->total_row > 0){
			$note       = NULL;
			$memberData = $db->row;
			$points     = $_POST['points'];
			if(!isset($memberData['rate'])){
				breakoff($langAry['ERRNoCurrency'], $referer);
				exit;
			}
			$calculatePoints = ($points*$memberData['rate']);
			//貨幣計算後是否超過登入擁有者
			if($calculatePoints > $nowAdminPoints){
				if($nowAdmin_agid != '1'){
					breakoff($langAry['ERRPlusPointsOverSelf'], $referer);
					exit;
				}
				$event_id       = '21';
				$nowAdminPoints = $_POST['points'];
			}

			//20150712-新增遊戲平台幣上限
			//貨幣匯率計算後是否大於平台遊戲幣上限
			if($calculatePoints > MAXMEMMONEY){
				breakoff($langAry['ERRPointsOverMax'], $referer);
				exit;
			}
			//會員身上錢幣+上補充的點數大於平台上限
			$afterPoints = ($memberData['Points']/$MoneyPointRate) + $calculatePoints;
			if($afterPoints > MAXMEMMONEY){
				breakoff($langAry['ERRPointsOverMax'], $referer);
				exit;
			}

			$db->selectTB('Admin');
			$db->getData("*", "WHERE admin_id = '".$memberData['UpAdmin_id']."'");
			$db->execute();
			if($db->total_row <= 0){
				breakoff($langAry['ERRNoUpAccount'], $referer);
				unset($db);
				exit;
			}

			$upAdminData = $db->row;
			$allowManger = false;
			$Points      = new Points($nowLoginData);
			$Points->produceLink($upAdminData);
			$allowManger = $Points->allowSearch;
			unset($Points);
			//該登入者不允許變更
			if(!$allowManger){
				$_SESSION['err'] = '3';
				header('location: /index.php');
				exit;
			}
			
			if(!empty($_POST['note'])){$note = $_POST['note'];}
			$bank = new Bank();
			//替下线充值扣点
			$result = $bank->debitTo( $event_id, $nowAdmin_id, $toMemberID, $nowAdminPoints, $toPoints, $note, True, ['MemberCurrency'=>$memberData['code'], 'CurrencyRate'=>$memberData['rate']]);
			if($result){
				//上级补点
				if($nowAdmin_agid != '1'){$event_id = '2';}//上级充值
				$result = $bank->rechargeMem( $event_id, $toMemberID, $nowAdmin_id, $toPoints, $note, ['MemberCurrency'=>$memberData['code'], 'CurrencyRate'=>$memberData['rate']]);
				if(!$result){
					$errMsg[] = 'RechargeFail';
				}
			}else{
				$errMsg[] = 'DebitFail';
			}
			if(empty($errMsg)){
				if(!$bank->rechargeCallGameServer($memberData['MemberID'], $afterPoints)){
					$errMsg[] = $bank->getErrorMsg();
				}

				breakoff($langAry['PlusPointsSuccess'].join("-", $errMsg), $referer);
				unset($db, $bank);
				exit;
			}		
		}
		if($db->total_row <= 0){$errMsg[] = $langAry['ERRNoID'];}
		breakoff($langAry['PlusPointsFail'].'('.join("-", $errMsg).')'.(DEBUG || $nowAdmin_agid == '1' ? '('.$bank->getErrorMsg().')' : ''), $referer);
		unset($db, $bank);
		exit;
	//对会员扣点
	case 'minusMemPoints':
		$referer  = $_SERVER['HTTP_REFERER'];
		$event_id = '20';//管理員扣點
		if($nowAdmin_agid != '1'){
			$event_id = '29';//扣點
		}
		if(empty($_POST['id'])){
			$_SESSION['err'] = '3';
			header("location: /index.php");
			exit;
		}
		if(empty($_POST['points']) || !preg_match('/^([0-9]+)$/', $_POST['points'])){
			breakoff($langAry['ERRMinusPoints'], $referer);
			exit;
		}	
		if(empty($_POST['note'])){
			unset($_POST['note']);
		}

		//检查给予会员的资料库资料
		$toMemberID = $_POST['id'];
		$toPoints   = $_POST['points'];
		$db = new PDO_DB(DB_HOST, DB_USER, DB_PWD, DB_NAME);
		$db->selectTB('MemberFinance2');
		$db->getData("*", "INNER JOIN A_Member ON (MemberFinance2.MemberId = A_Member.MemberID)
		 LEFT JOIN Currency ON (A_Member.MemberCurrency = Currency.code)
		 WHERE MemberFinance2.MemberId = '".$toMemberID."' AND MemberFinance2.PointType = 'Game'");
		$db->execute();
		$result = '';
		$errMsg = array();
		if($db->total_row > 0){
			$note       = NULL;
			$memberData = $db->row;
			if(!isset($memberData['rate'])){
				breakoff($langAry['ERRNoCurrency'], $referer);
				exit;
			}
			$db->selectTB('Admin');
			$db->getData("*", "WHERE admin_id = '".$memberData['UpAdmin_id']."'");
			$db->execute();
			if($db->total_row <= 0){
				breakoff($langAry['ERRNoUpAccount'], $referer);
				unset($db);
				exit;
			}

			$upAdminData = $db->row;
			$allowManger = false;
			$Points      = new Points($nowLoginData);
			$Points->produceLink($upAdminData);
			$allowManger = $Points->allowSearch;
			unset($Points);
			//該登入者不允許變更
			if(!$allowManger){
				$_SESSION['err'] = '3';
				header('location: /index.php');
				exit;
			}

			if(!empty($_POST['note'])){$note = $_POST['note'];}
			$bank = new Bank();
			$nowMemberPoints = $memberData['Points']/$bank->MoneyPointRate;
			$calculatePoints = $toPoints*$memberData['rate'];
			if($calculatePoints > $nowMemberPoints){
				breakoff($langAry['ERRMinusPointsOver'], $referer);
				unset($db);
				exit;
			}
			$afterPoints = $nowMemberPoints - $calculatePoints;
			//扣点
			$result = $bank->deductionMem( $event_id, $toMemberID, $nowAdmin_id, $toPoints, $note, ['MemberCurrency'=>$memberData['code'], 'CurrencyRate'=>$memberData['rate']]);
			if($result){
				if($nowAdmin_agid != '1'){
					$result = $bank->creditFrom( $event_id, $nowAdmin_id, $toMemberID, $nowAdminPoints, $toPoints, $note, true, ['MemberCurrency'=>$memberData['code'], 'CurrencyRate'=>$memberData['rate']]);
					if(!$result){
						$errMsg[] = 'RechargeFail';
					}
				}
			}else{
				$errMsg[] = 'DeductionFail';
			}
			if(empty($errMsg)){
				if(!$bank->rechargeCallGameServer($memberData['MemberID'], $afterPoints)){
					$errMsg[] = $bank->getErrorMsg();
				}

				breakoff($langAry['MinusPointsSuccess'].join("-", $errMsg), $referer);
				unset($db, $bank);
				exit;
			}		
		}
		if($db->total_row <= 0){$errMsg[] = $langAry['ERRNoID'];}
		breakoff($langAry['MinusPointsFail'].'('.join("-", $errMsg).')'.(DEBUG || $nowAdmin_agid == '1' ? '('.$bank->getErrorMsg().')' : ''), $referer);
		unset($db, $bank);
		exit;
	default:
		$_SESSION['err'] = '3';
		header("location: /index.php");
		exit;
}
$_SESSION['err'] = '3';
header("location: /index.php");
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