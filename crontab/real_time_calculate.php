<?php
include_once $_SERVER['DOCUMENT_ROOT'].'/class/Points.class.php';

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
		$startDate = date('Y-m-d');
		$endDate   = $startDate;
		if(!empty($_GET['lastday']) && $_GET['lastday'] == 'lastday'){
			$startDate = date('Y-m-d', strtotime('-1day'));
			$endDate   = $startDate;
		}
		if(!empty($_GET['date']) && !empty(strtotime($_GET['date']))){
			$startDate = date('Y-m-d', strtotime($_GET['date']));
			$endDate   = $startDate;
		}
		$Points    = new Points();
		$Points->chkAdminData += $db->row;
		$result        = $Points->getSearchAdminData($db->row, $startDate, $endDate);
		$startDateTime = $startDate.' '.$Points->StartTime;
		$endDateTime   = $endDate.' '.$Points->EndTime;

		if($Points->chkAdminData['downBetTotal'] > 0){
			$insertAry = [];
			$insertAry['ag_id']             = $Points->chkAdminData['ag_id'];
			$insertAry['admin_id']          = $Points->chkAdminData['admin_id'];
			$insertAry['upAdmin_id']        = $Points->chkAdminData['upAdmin_id'];
			$insertAry['commissionRate']    = $Points->chkAdminData['commissionRate'];
			$insertAry['ReturnRate']        = $Points->chkAdminData['ReturnRate'];
			$insertAry['downCount']         = $Points->chkAdminData['downCount'];
			$insertAry['downMemCount']      = $Points->chkAdminData['downMemCount'];
			$insertAry['downBetTotal']      = $Points->chkAdminData['BetTotal'];
			$insertAry['downWinLoseTotal']  = $Points->chkAdminData['WinLoseTotal'];
			$insertAry['downRemoteJPTotal'] = $Points->chkAdminData['remoteJPTotal'];
			$insertAry['commissionTotal']   = $Points->chkAdminData['commissionTotal'];
			$insertAry['returnTotal']       = $Points->chkAdminData['returnTotal'];
			$insertAry['upCommissionTotal'] = $Points->chkAdminData['upCommissionTotal'];
			$insertAry['upReturnTotal']     = $Points->chkAdminData['upReturnTotal'];
			$insertAry['startDateTime']     = $startDateTime;
			$insertAry['endDateTime']       = $endDateTime;
			$insertAry['updated_at']        = date('Y-m-d H:i:s');

			$attributes = [];
			$attributes['ag_id']         = $Points->chkAdminData['ag_id'];
			$attributes['admin_id']      = $Points->chkAdminData['admin_id'];
			$attributes['startDateTime'] = $startDateTime;
			$attributes['endDateTime']   = $endDateTime;

			$db->updateOrCreate($attributes, $insertAry, 'AdminWinLose');

			$addAdminCount += 1;
		}

		//有下層代理-都必須寫入AdminWinLose
		if(count($Points->downAgentData) > 0){
			foreach ($Points->downAgentData as $value) {
				foreach ($value as $v) {
					if(!empty($v['BetTotal']) && $v['BetTotal'] > 0){
						$insertAry = [];
						$insertAry['ag_id']             = $v['ag_id'];
						$insertAry['admin_id']          = $v['admin_id'];
						$insertAry['upAdmin_id']        = $v['upAdmin_id'];
						$insertAry['commissionRate']    = $v['commissionRate'];
						$insertAry['ReturnRate']        = $v['ReturnRate'];
						$insertAry['downCount']         = $v['downCount'];
						$insertAry['downMemCount']      = $v['downMemCount'];
						$insertAry['downBetTotal']      = $v['BetTotal'];
						$insertAry['downWinLoseTotal']  = $v['WinLoseTotal'];
						$insertAry['downRemoteJPTotal'] = $v['remoteJPTotal'];
						$insertAry['commissionTotal']   = $v['commissionTotal'];
						$insertAry['returnTotal']       = $v['returnTotal'];
						$insertAry['upCommissionTotal'] = $v['upCommissionTotal'];
						$insertAry['upReturnTotal']     = $v['upReturnTotal'];
						$insertAry['startDateTime']     = $startDateTime;
						$insertAry['endDateTime']       = $endDateTime;
						$insertAry['updated_at']        = date('Y-m-d H:i:s');

						$attributes = [];
						$attributes['ag_id']         = $v['ag_id'];
						$attributes['admin_id']      = $v['admin_id'];
						$attributes['startDateTime'] = $startDateTime;
						$attributes['endDateTime']   = $endDateTime;

						$db->updateOrCreate($attributes, $insertAry, 'AdminWinLose');

						$addAdminCount += 1;
					}
				}
			}
		}

		//有下層會員-MemWinLose
		if(count($Points->downMemData) > 0){
			foreach ($Points->downMemData as $value) {
				foreach ($value as $v) {
					if(!empty($v['BetTotal']) && $v['BetTotal'] > 0){
						$insertAry = [];
						$insertAry['MemberId']          = $v['MemberID'];
						$insertAry['upAdmin_id']        = $v['UpAdmin_id'];
						$insertAry['ReturnRate']        = $v['ReturnRate'];
						$insertAry['BetTotal']          = $v['BetTotal'];
						$insertAry['WinLoseTotal']      = $v['WinLoseTotal'];
						$insertAry['remoteJPTotal']     = $v['remoteJPTotal'];
						$insertAry['BetCount']          = $v['BetCount'];
						$insertAry['returnTotal']       = $v['returnTotal'];
						$insertAry['upCommissionTotal'] = $v['upCommissionTotal'];
						$insertAry['startDateTime']     = $startDateTime;
						$insertAry['endDateTime']       = $endDateTime;
						$insertAry['updated_at']        = date('Y-m-d H:i:s');

						$attributes = [];
						$attributes['MemberId']      = $v['MemberID'];
						$attributes['upAdmin_id']    = $v['UpAdmin_id'];
						$attributes['startDateTime'] = $startDateTime;
						$attributes['endDateTime']   = $endDateTime;

						$db->updateOrCreate($attributes, $insertAry, 'MemWinLose');

						$addMemCount += 1;
					}
				}
			}
		}

		if(!empty($_GET['debug'])){
			echo '<pre>';
			print_r($Points->chkAdminData);
			print_r($Points->downAgentData);
			print_r($Points->downMemData);
		}

		echo ' 代理：'.$addAdminCount;
		echo ' 會員：'.$addMemCount;

		unset($Points);
		echo '<br>';
	}while($db->row = $db->fetch_assoc());
}
exit;
?>