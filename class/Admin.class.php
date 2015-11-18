<?php
include_once $_SERVER['DOCUMENT_ROOT'].'/class/PDO_DB.class.php';
include_once $_SERVER['DOCUMENT_ROOT'].'/class/Points.class.php';

class Admin{
	/**
	 * 總停權數-代理
	 */
	var $suspendAdminCount = 0;
	/**
	 * 總停權數-會員
	 */
	var $suspendMemCount = 0;
	/**
	 * 總刪除數-代理
	 */
	var $delAdminCount = 0;
	/**
	 * 總刪除數-會員
	 */
	var $delMemCount = 0;

	/**
	 * 確認是否有權限管理
	 */
	public static function chkAllowManger($adminData, $nowLoginData)
	{
		$allowManger = false;
		$Points      = new Points($nowLoginData);
		$Points->produceLink($adminData);
		$allowManger = $Points->allowSearch;
		unset($Points);
		return $allowManger;
	}

	/**
	 * AlarmType
	 * 0:正常狀態
	 * 1:小遊戲狀態(防止Police)
	 * 2:回到大廳
	 * 3:同時踢離大廳或遊戲
	 */
	public function alarmAdmin($admin_id, $alarmType, $nowLoginData)
	{
		try {
			$AuthId = $nowLoginData['admin_id'];

			$db = new PDO_DB( DB_HOST, DB_USER, DB_PWD, DB_NAME);
			$db->selectTB('Admin');
			$db->getData("*", "WHERE admin_id = '".$admin_id."'");
			$db->execute();
			if($db->total_row <= 0){
				throw new Exception("Not match admin");
			}
			$adminData = $db->row;
			if(!$this->chkAllowManger($adminData, $nowLoginData)){
				throw new Exception("Not allow manager");
			}

			//下層有會員則必須一併黑名單
			if($adminData['downMemCount'] > 0){
				$db->selectTB('A_Member');
				$db->getData("*", "WHERE UpAdmin_id = '".$adminData['admin_id']."'");
				$db->execute();
				do{
					$this->alarmMember($db->row['MemberID'], $alarmType, $nowLoginData);
				}while($db->row = $db->fetch_assoc());
			}

			//下層會員是否黑名單
			$updateData = [
				'alarmType'         => $alarmType == '0' ? 'n' : 'y',
				'alarmExecAdmin_id' => $AuthId,
				'admin_updtime'     => date('Y-m-d H:i:s')
			];
			$db->selectTB('Admin');
			$db->updateData($updateData, "WHERE admin_id = '".$adminData['admin_id']."'");
			$text = ['query'=>$db->getCurrentQueryString(), 'value'=>$updateData];
			$text['result'] = $db->execute();
			//寫入操作Log
			$this->addAdminLogs($text, $AuthId, '[System]Set Agent "'.$adminData['admin_acc'].'" alarm to '.($alarmType == '0' ? 'n' : 'y'));

			//下層有代理則必須一併黑名單
			if($adminData['downCount'] > 0){
				$db->selectTB('Admin');
				$db->getData("*", "WHERE upAdmin_id = '".$adminData['admin_id']."'");
				$db->execute();
				do{
					$this->alarmAdmin($db->row['admin_id'], $alarmType, $nowLoginData);
				}while($db->row = $db->fetch_assoc());
			}

			unset($db);
			return true;
		}catch(Exception $e){
			return false;
		}
	}

	/**
	 * alarmType=1:緊急通知玩家轉換小遊戲
	 * alarmType=0:使玩家回復正常遊戲
	 */
	public function alarmMember($MemberId, $alarmType, $nowLoginData)
	{
		try {
			$AuthId = $nowLoginData['admin_id'];

			$db = new PDO_DB( DB_HOST, DB_USER, DB_PWD, DB_NAME);
			$db->selectTB('A_Member');
			$db->getData("*","WHERE MemberID = '".$MemberId."'");
			$db->execute();
			if($db->total_row <= 0){
				throw new Exception("Not match Member");
			}
			$memberData = $db->row;

			$db->selectTB('Admin');
			$db->getData("*", "WHERE admin_id = '".$memberData['UpAdmin_id']."'");
			$db->execute();
			if($db->total_row <= 0){
				throw new Exception("Not match upAdmin");
			}
			$adminData = $db->row;

			if(!$this->chkAllowManger($adminData, $nowLoginData)){
				throw new Exception("Not allow manager");
			}

			$note = '[System]Set Member "'.$memberData['MemberAccount'].'" alarmType to "'.$alarmType.'"';

			//黑名單類型
			$updateData = [
				'AlarmType'    => $alarmType,
				'AlarmAgentID' => $AuthId,
				'ModifiedDate' => date('Y-m-d H:i:s')
			];
			$db->selectTB('A_Member');
			$db->updateData($updateData, "WHERE MemberID = '".$memberData['MemberID']."'");
			$text = ['query'=>$db->getCurrentQueryString(), 'value'=>$updateData];
			$text['result'] = $db->execute();
			//寫入操作Log
			$this->addAdminLogs($text, $AuthId, $note);

			//會員變更Log
			$db->selectTB('MemberAuthorizeChange');
			$db->insertData([
				'MemberID'    => $MemberId,
				'ChangeType'  => 'DisAlarmAccount',
				'Memos'       => (empty($note) ? NULL : $note),
				'ExecAgentID' => $AuthId,
				'CreateDate'  => date('Y-m-d H:i:s')
			]);

			try {
				// 環境為local不通知Server
				if(ENV == 'local'){
					throw new Exception('Local don\'t need connect server');
				}

				// 緊急處理
				// 封包欄位 				3			(從封包代號算起到結束號@#之間的欄位)	
				// @封包代號				1005		
				// @玩家的唯一識別碼ID		20905		玩家的唯一識別碼
				// @通知玩家在遊戲狀態		1		1: 回到小遊戲. 2: 回到大廳,3:同時踢離大廳及遊戲(若玩家正好在遊戲中)
				// @結束符號				#		
				// $msg = "3@1005@20905@1@#"				通知ID20905玩家,遊戲狀態跳到小遊戲		
				$msg[] = '3';
				$msg[] = '1005';
				$msg[] = $memberData['MemberID'];
				$msg[] = $alarmType;
				$msg[] = '#';

				$msg = join("@", $msg);

				$socket = @fsockopen(GAME_HOST, GAME_PORT);
				if(!$socket){
					throw new Exception('socket die');
				}
				$msg_array = str_split($msg,1);
				$output = '';
				foreach($msg_array as $index => $value){
					$output .= pack("c2", ord($value), 0);
				}

				$tmp = fwrite($socket, $output, strlen($output));
				fclose($socket);
				if(strlen($output) != $tmp){
					throw new Exception('response error');
				}
			} catch (Exception $e) {
				return DEBUG || $nowLoginAg == '1' ? '('.$e->getMessage().')' : '';
			}

			unset($db);
			return true;
		}catch(Exception $e){
			return false;
		}
	}

	/**
	 * 會員刪除
	 */
	public function delMember($MemberId, $note='', $nowLoginData){
		try{
			$AuthId = $nowLoginData['admin_id'];

			$db = new PDO_DB( DB_HOST, DB_USER, DB_PWD, DB_NAME);
			$db->selectTB('A_Member');
			$db->getData("*","WHERE MemberID = '".$MemberId."'");
			$db->execute();
			if($db->total_row <= 0){
				throw new Exception("Not match Member");
			}
			$memberData = $db->row;

			$db->selectTB('Admin');
			$db->getData("*", "WHERE admin_id = '".$memberData['UpAdmin_id']."'");
			$db->execute();
			if($db->total_row <= 0){
				throw new Exception("Not match upAdmin");
			}
			$adminData = $db->row;

			if(!$this->chkAllowManger($adminData, $nowLoginData)){
				throw new Exception("Not allow manager");
			}

			$db->selectTB('A_Member');
			$db->deleteData("WHERE MemberID = '".$MemberId."'");
			$text = ['query'=>$db->getCurrentQueryString()];
			$text['result'] = $db->execute();
			//寫入操作Log
			$this->addAdminLogs($text, $AuthId, $note);

			//扣除上層代理Count
			$this->updAdminCount($memberData['UpAdmin_id'], $AuthId, true);

			$this->delMemCount++;

			//刪除點數資料
			$db->selectTB('MemberFinance2');
			$db->deleteData("WHERE MemberId = '".$MemberId."'");
			$text = ['query'=>$db->getCurrentQueryString()];
			$text['result'] = $db->execute();
			//寫入操作Log
			$this->addAdminLogs($text, $AuthId, $note);

			//刪除AdminRate返點資料
			$db->selectTB('AdminRate');
			$db->deleteData("WHERE acc_id = '".$MemberId."' AND ag_id = '0'");
			$text = ['query'=>$db->getCurrentQueryString()];
			$text['result'] = $db->execute();
			//寫入操作Log
			$this->addAdminLogs($text, $AuthId, $note);

			//刪除MainPointMingXi明細資料
			$db->selectTB('MainPointMingXi');
			$db->deleteData("WHERE MemberId = '".$MemberId."'");
			$text = ['query'=>$db->getCurrentQueryString()];
			$text['result'] = $db->execute();
			//寫入操作Log
			$this->addAdminLogs($text, $AuthId, $note);

			//刪除MemberWinLose輸贏紀錄
			$db->selectTB('MemberWinLose');
			$db->deleteData("WHERE MemberId = '".$MemberId."'");
			$text = ['query'=>$db->getCurrentQueryString()];
			$text['result'] = $db->execute();
			//寫入操作Log
			$this->addAdminLogs($text, $AuthId, $note);

			//刪除MemberOnline在線紀錄
			$db->selectTB('MemberOnline');
			$db->deleteData("WHERE MemberId = '".$MemberId."'");
			$text = ['query'=>$db->getCurrentQueryString()];
			$text['result'] = $db->execute();
			//寫入操作Log
			$this->addAdminLogs($text, $AuthId, $note);

			//刪除MemberUpdateNotifycation點數變更通知
			$db->selectTB('MemberUpdateNotifycation');
			$db->deleteData("WHERE MemberId = '".$MemberId."'");
			$text = ['query'=>$db->getCurrentQueryString()];
			$text['result'] = $db->execute();
			//寫入操作Log
			$this->addAdminLogs($text, $AuthId, $note);

			//刪除MoneyNotifycation
			$db->selectTB('MoneyNotifycation');
			$db->deleteData("WHERE MemberId = '".$MemberId."'");
			$text = ['query'=>$db->getCurrentQueryString()];
			$text['result'] = $db->execute();
			//寫入操作Log
			$this->addAdminLogs($text, $AuthId, $note);

			//刪除MemberIpHistory歷史紀錄
			$db->selectTB('MemberIpHistory');
			$db->deleteData("WHERE MemberId = '".$MemberId."'");
			$text = ['query'=>$db->getCurrentQueryString()];
			$text['result'] = $db->execute();
			//寫入操作Log
			$this->addAdminLogs($text, $AuthId, $note);

			//會員刪除Log
			$db->selectTB('MemberAuthorizeChange');
			$db->insertData([
				'MemberID'    => $MemberId,
				'ChangeType'  => 'DeleteAccount',
				'Memos'       => (empty($note) ? NULL : $note),
				'ExecAgentID' => $AuthId,
				'CreateDate'  => date('Y-m-d H:i:s')
			]);
			$db->execute();
			$db->selectTB('MemberDeleteLog');
			$db->insertData([
				'ExecutorId'    => $AuthId,
				'MemberAccount' => $memberData['MemberAccount'],
				'CreateDate'    => date('Y-m-d H:i:s')
			]);
			$db->execute();

			unset($db);
			return true;
		}catch(Exception $e){
			return false;
		}
	}

	/**
	 * 扣掉上層Count
	 */
	public function updAdminCount($upAdmin_id, $AuthId, $mem=false)
	{
		try {
			$db = new PDO_DB( DB_HOST, DB_USER, DB_PWD, DB_NAME);
			$db->selectTB('Admin');
			$db->getData("*", "WHERE admin_id = '".$upAdmin_id."'");
			$db->execute();
			if($db->total_row > 0){
				$updateData = [
					'down'.($mem ? 'Mem' : '').'Count' => --$db->row['down'.($mem ? 'Mem' : '').'Count'],
					'admin_updtime'                    => date('Y-m-d H:i:s')
				];
				$db->updateData($updateData, "WHERE admin_id = '".$upAdmin_id."'");
				$text = ['query'=>$db->getCurrentQueryString(), 'value'=>$updateData];
				$text['result'] = $db->execute();
				//寫入操作Log
				$this->addAdminLogs($text, $AuthId, '[System]扣掉上層Count人數');
				unset($db);
				return true;
			}
			return false;			
		} catch (Exception $e) {
			return false;			
		}
	}

	/**
	 * 代理刪除
	 */
	public function delAdmin($admin_id, $note='', $nowLoginData){
		try{
			$AuthId = $nowLoginData['admin_id'];

			$db = new PDO_DB( DB_HOST, DB_USER, DB_PWD, DB_NAME);

			//刪除代理資料
			$db->selectTB('Admin');
			$db->getData("*","WHERE admin_id = '".$admin_id."'");
			$db->execute();
			if($db->total_row <= 0){
				throw new Exception("Not match admin");
			}
			$adminData = $db->row;
			if(!$this->chkAllowManger($adminData, $nowLoginData)){
				throw new Exception("Not allow manager");
			}

			//下層有代理則必須一併刪除
			if($adminData['downCount'] > 0){
				$db->selectTB('Admin');
				$db->getData("*", "WHERE upAdmin_id = '".$adminData['admin_id']."'");
				$db->execute();
				do{
					$this->delAdmin($db->row['admin_id'], $note, $nowLoginData);
				}while($db->row = $db->fetch_assoc());
			}

			//下層有會員則必須一併停權
			if($adminData['downMemCount'] > 0){
				$db->selectTB('A_Member');
				$db->getData("*", "WHERE UpAdmin_id = '".$adminData['admin_id']."'");
				$db->execute();
				do{
					$this->delMember($db->row['MemberID'], $note, $nowLoginData);
				}while($db->row = $db->fetch_assoc());
			}

			$db->selectTB('Admin');
			$db->deleteData("WHERE admin_id = '".$admin_id."'");
			$text = ['query'=>$db->getCurrentQueryString()];
			$text['result'] = $db->execute();
			//寫入操作Log
			$this->addAdminLogs($text, $AuthId, $note);

			//扣除上層代理Count
			$this->updAdminCount($adminData['upAdmin_id'], $AuthId);

			//刪除AdminBank點數資料
			$db->selectTB('AdminBank');
			$db->deleteData("WHERE admin_id = '".$admin_id."'");
			$text = ['query'=>$db->getCurrentQueryString()];
			$text['result'] = $db->execute();
			//寫入操作Log
			$this->addAdminLogs($text, $AuthId, $note);

			//刪除AdminRate返點資料
			$db->selectTB('AdminRate');
			$db->deleteData("WHERE acc_id = '".$admin_id."' AND ag_id = '".$adminData['ag_id']."'");
			$text = ['query'=>$db->getCurrentQueryString()];
			$text['result'] = $db->execute();
			//寫入操作Log
			$this->addAdminLogs($text, $AuthId, $note);
			$this->delAdminCount++;

			unset($db);
			return true;
		}catch(Exception $e){
			return false;
		}
	}

	/**
	 * 代理解除封鎖
	 */
	public function unblockAdmin($admin_id, $note='', $nowLoginData){
		try{
			$AuthId = $nowLoginData['admin_id'];

			$db = new PDO_DB( DB_HOST, DB_USER, DB_PWD, DB_NAME);
			$db->selectTB('Admin');
			$db->getData("*","WHERE admin_id = '".$admin_id."'");
			$db->execute();
			if($db->total_row <= 0){
				throw new Exception("Not match admin");
			}
			$adminData = $db->row;
			if(!$this->chkAllowManger($adminData, $nowLoginData)){
				throw new Exception("Not allow manager");
			}

			$updateData = [
				'admin_enable'  => 'y',
				'disable_note'  => date('Y-m-d H:i:s').(empty($note) ? '' : '-'.$note),
				'disable_admin' => $AuthId,
				'admin_updtime' => date('Y-m-d H:i:s')
			];
			$db->updateData($updateData, "WHERE admin_id = '".$admin_id."'");
			$text = ['query'=>$db->getCurrentQueryString(), 'value'=>$updateData];
			$text['result'] = $db->execute();
			//寫入操作Log
			$this->addAdminLogs($text, $AuthId, $note);
			unset($db);
			return true;
		}catch(Exception $e){
			return false;
		}
	}


	/**
	 * 會員解除封鎖
	 */
	public function unblockMember($MemberId, $note='', $nowLoginData){
		try{
			$AuthId = $nowLoginData['admin_id'];
			
			$db = new PDO_DB( DB_HOST, DB_USER, DB_PWD, DB_NAME);
			$db->selectTB('A_Member');
			$db->getData("*","WHERE MemberID = '".$MemberId."'");
			$db->execute();
			if($db->total_row <= 0){
				throw new Exception("Not match Member");
			}
			$memberData = $db->row;
			$db->selectTB('Admin');
			$db->getData("*", "WHERE admin_id = '".$memberData['UpAdmin_id']."'");
			$db->execute();
			if($db->total_row <= 0){
				throw new Exception("Not match upAdmin");
			}
			$adminData = $db->row;

			if(!$this->chkAllowManger($adminData, $nowLoginData)){
				throw new Exception("Not allow manager");
			}

			$updateData = [
				'PauseAccount'        => 0,
				'PauseAccountAgentID' => $AuthId,
				'ModifyPauseDate'     => date('Y-m-d H:i:s')
			];
			$db->selectTB('A_Member');
			$db->updateData($updateData, "WHERE MemberID = '".$MemberId."'");
			$text = ['query'=>$db->getCurrentQueryString(), 'value'=>$updateData];
			$text['result'] = $db->execute();
			//寫入操作Log
			$this->addAdminLogs($text, $AuthId, $note);

			$db->selectTB('MemberAuthorizeChange');
			$db->insertData([
				'MemberID'    => $MemberId,
				'ChangeType'  => 'PauseAccount',
				'Memos'       => (empty($note) ? NULL : $note),
				'ExecAgentID' => $AuthId,
				'CreateDate'  => date('Y-m-d H:i:s')
			]);
			$db->execute();
			unset($db);
			return true;
		}catch(Exception $e){
			return false;
		}
	}

	/**
	 * 代理停權
	 */
	public function suspendAdmin($admin_id, $note='', $nowLoginData){
		try{
			$AuthId = $nowLoginData['admin_id'];
			
			$db = new PDO_DB( DB_HOST, DB_USER, DB_PWD, DB_NAME);
			$db->selectTB('Admin');
			$db->getData("*","WHERE admin_id = '".$admin_id."'");
			$text['result'] = $db->execute();
			if($db->total_row <= 0){
				throw new Exception("Not match admin");
			}
			$adminData = $db->row;
			if(!$this->chkAllowManger($adminData, $nowLoginData)){
				throw new Exception("Not allow manager");
			}

			$updateData = [
				'admin_enable'  => 'n',
				'disable_note'  => date('Y-m-d H:i:s').(empty($note) ? '' : '-'.$note),
				'disable_admin' => $AuthId,
				'admin_updtime' => date('Y-m-d H:i:s')
			];
			$db->updateData($updateData, "WHERE admin_id = '".$admin_id."'");
			$text = ['query'=>$db->getCurrentQueryString(), 'value'=>$updateData];
			$text['result'] = $db->execute();
			//寫入操作Log
			$this->addAdminLogs($text, $AuthId, $note);

			//下層有代理則必須一併停權
			if($adminData['downCount'] > 0){
				$db->selectTB('Admin');
				$db->getData("*", "WHERE upAdmin_id = '".$adminData['admin_id']."'");
				$db->execute();
				do{
					$this->suspendAdmin($db->row['admin_id'], $note, $nowLoginData);
					$this->suspendAdminCount++;
				}while($db->row = $db->fetch_assoc());
			}

			//下層有會員則必須一併停權
			if($adminData['downMemCount'] > 0){
				$db->selectTB('A_Member');
				$db->getData("*", "WHERE UpAdmin_id = '".$adminData['admin_id']."'");
				$db->execute();
				do{
					$this->suspendMember($db->row['MemberID'], $note, $nowLoginData);
					$this->suspendMemCount++;
				}while($db->row = $db->fetch_assoc());
			}

			unset($db);
			return true;
		}catch(Exception $e){
			return false;
		}
	}

	/**
	 * 會員停權
	 */
	public function suspendMember($MemberId, $note='', $nowLoginData){
		try{
			$AuthId = $nowLoginData['admin_id'];
			
			$db = new PDO_DB( DB_HOST, DB_USER, DB_PWD, DB_NAME);
			$db->selectTB('A_Member');
			$db->getData("*","WHERE MemberID = '".$MemberId."'");
			$db->execute();
			if($db->total_row <= 0){
				throw new Exception("Not match Member");
			}
			$memberData = $db->row;
			$db->selectTB('Admin');
			$db->getData("*", "WHERE admin_id = '".$memberData['UpAdmin_id']."'");
			$db->execute();
			if($db->total_row <= 0){
				throw new Exception("Not match upAdmin");
			}
			$adminData = $db->row;

			if(!$this->chkAllowManger($adminData, $nowLoginData)){
				throw new Exception("Not allow manager");
			}

			$updateData = [
				'PauseAccount'        => 1,
				'PauseAccountAgentID' => $AuthId,
				'ModifyPauseDate'     => date('Y-m-d H:i:s')
			];
			$db->selectTB('A_Member');
			$db->updateData($updateData, "WHERE MemberID = '".$MemberId."'");
			$text = ['query'=>$db->getCurrentQueryString(), 'value'=>$updateData];
			$text['result'] = $db->execute();
			//寫入操作Log
			$this->addAdminLogs($text, $AuthId, $note);
			//會員停權Log
			$db->selectTB('MemberAuthorizeChange');
			$db->insertData([
				'MemberID'    => $MemberId,
				'ChangeType'  => 'PauseAccount',
				'Memos'       => (empty($note) ? NULL : $note),
				'ExecAgentID' => $AuthId,
				'CreateDate'  => date('Y-m-d H:i:s')
			]);
			$db->execute();

			unset($db);
			return true;
		}catch(Exception $e){
			return false;
		}
	}

	public function addAdminLogs($text, $AuthId, $note='')
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
}
?>