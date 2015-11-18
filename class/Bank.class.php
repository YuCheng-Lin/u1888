<?php
header('Content-Type:text/html;charset=utf8;');
include_once $_SERVER['DOCUMENT_ROOT'].'/class/PDO_DB.class.php';

class Bank{
	/**
	 * 资料库连线
	 */
	public $conn;

	/**
	 * 银行资料表
	 */
	public $table;

	/**
	 * 点数异动事件
	 * 参考最下面附件一
	 */
	public $eventAry = array();

	/**
	 * 现在比率
	 * MoneyPointRate
	 */
	public $MoneyPointRate = '1000000';

	/**
	 * MainPointType
	 */
	public $MainPointType;

	/**
	 * 错误讯息，需要自取
	 */
	private $errorMsg;

	public function __construct($table='AdminBank'){
		//建立资料库连线
		$this->conn = new PDO_DB(DB_HOST, DB_USER, DB_PWD, DB_NAME);
		$this->conn->selectTB('DianShuYiDongShiJian');
		$this->conn->getData("*");
		$this->conn->execute();
		if($this->conn->total_row > 0){
			do{
				$this->eventAry[$this->conn->row['RowId']] = $this->conn->row['EventName'];
			}while($this->conn->row = $this->conn->fetch_assoc());
		}
		$this->table = $table;
		//现金比率
		$this->conn->selectTB('SysValue');
		$this->conn->getData("*", "WHERE SysKey = 'MoneyPointRate'");
		$this->conn->execute();
		if($this->conn->total_row > 0){
			$this->MoneyPointRate = $this->conn->row['SysValue'];
		}
		//主要點數型態
		$this->MainPointType = 'Game';
	}

	/**
	 * 管理员对下线会员扣点
	 */
	public function deductionMem($event_id, $MemberID, $fromAdmin_id, $outgoPoints, $note=NULL, $options){
		try{
			if(!array_key_exists($event_id, $this->eventAry)){
				throw new Exception("EventKey not exists");
			}
			$this->conn->selectTB('MemberFinance2');
			$this->conn->getData("*","WHERE MemberId = '".$MemberID."' AND PointType = '".$this->MainPointType."'");
			$this->conn->execute();
			$Points = 0;
			if($this->conn->total_row <= 0){
				throw new Exception("Can't not find Pointtype");
			}

			//20150706-新增貨幣
			$options['MemberCurrency'] = empty($options['MemberCurrency']) ? CURRENCY : $options['MemberCurrency'];
			$options['CurrencyRate']   = empty($options['CurrencyRate']) ? 1 : $options['CurrencyRate'];
			$options['CurrencyAmount'] = $outgoPoints;
			//幣值計算
			$outgoPoints = $options['CurrencyAmount']*$options['CurrencyRate'];

			$beforePoints = $this->conn->total_row > 0 ? $this->conn->row['Points'] : $Points;
			$outgoPoints = ($outgoPoints*$this->MoneyPointRate);

			if($outgoPoints > $beforePoints){
				throw new Exception("OutgoPoints(".$outgoPoints.") greater than memPoints(".$beforePoints.")");
			}

			$updateData = array();
			$updateData['Points'] = ($beforePoints-$outgoPoints);

			$this->conn->updateData($updateData, "WHERE MemberId = '".$MemberID."' AND PointType = '".$this->MainPointType."'");
			
			//寫入操作Log
			$text = [
				'query' => $this->conn->getCurrentQueryString(), 
				'value' => $updateData
			];
			$result = $this->conn->execute();
			$text['result'] = $result;
			$this->addAdminLogs($text, $fromAdmin_id, $note);

			if(!is_bool($result) || !$result){
				throw new Exception("Update fail(".$result.")");
			}
			if(is_bool($result) && $result){
				//扣点成功直接写入纪录
				$result = $this->deductionLog($event_id, $MemberID, $fromAdmin_id, $beforePoints, $outgoPoints, $note, $options);
				if(!$result){
					throw new Exception('Deduction Success, but log fail('.$this->getErrorMsg().')');
				}
				return $result;
			}		
			throw new Exception("No result");
		}catch (Exception $e){
			$this->errorMsg = 'Deduction error：'.$e->getMessage();
			return false;
		}
	}

	public function deductionLog($event_id, $MemberID, $fromAdmin_id, $beforePoints, $outgoPoints, $note=NULL, $options){
		try{
			if(!array_key_exists($event_id, $this->eventAry)){
				throw new Exception("EventKey not exists");
			}
			$insertData = array();
			$insertData['MemberId']    = $MemberID;//收入帐号
			$insertData['SrcMemberId'] = $fromAdmin_id;//来源帐号对照Admin资料表的admin_id
			$insertData['BeforePoint'] = $beforePoints;
			$insertData['ChangeEvent'] = $event_id;
			$insertData['Outgo']       = $outgoPoints;
			$insertData['AfterPoint']  = ($insertData['BeforePoint'] - $insertData['Outgo']);
			$insertData['ZongYuE']     = $insertData['AfterPoint'];
			if(!empty($note))$insertData['BeiZhu'] = $note;

			//20150706-新增貨幣
			$insertData['MemberCurrency'] = $options['MemberCurrency'];
			$insertData['CurrencyRate']   = $options['CurrencyRate']  ;
			$insertData['CurrencyAmount'] = $options['CurrencyAmount'];
			
			$this->conn->selectTB('MainPointMingXi');
			$this->conn->insertData($insertData);
			$result = $this->conn->execute();
			if(!is_bool($result) || !$result){
				throw new Exception("Insert fail(".$result.")");
			}
			if(is_bool($result) && $result){
				return $result;
			}		
			throw new Exception("No result");
		}catch (Exception $e){
			$this->errorMsg = 'DeductionLog error：'.$e->getMessage();
			return false;
		}
	}

	/**
	 * 通知Server點數異動
	 * @param  integer $PointSource 0:補扣點;1:點卡儲值
	 */
	public function rechargeCallGameServer($MemberId, $Points, $PointSource = 0)
	{
		try {
			$errMsg = [];
			// 環境為local不通知Server
			if(ENV == 'local'){
				throw new Exception('Local don\'t need connect server');
			}

			// 通知玩家進行點數的更新
			// 封包欄位 				5			(從封包代號算起到結束號@#之間的欄位)	
			// @封包代號				1004			
			// @玩家的唯一識別碼ID		20905		玩家的唯一識別碼	
			// @更新後的有價點數		5000		更新後的有價點數	
			// @更新後的免費點			1000		更新後的免費點數	
			// @新增更新後的點數型態	0,1			0:正常點數 1:點卡點數
			// @新增點數更新儲值來源	1			新增點數更新儲來源.0:後台儲值,1:點數卡儲值
			// @結束符號				#			
			// $msg = "4@1004@20905@5000@1000@#"
			$msg[] = '5';
			$msg[] = '1004';
			$msg[] = $MemberId;
			$msg[] = $Points;
			$msg[] = 0;
			$msg[] = $PointSource;
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
			return true;
		} catch (Exception $e) {
			$this->errorMsg = $e->getMessage();
		}
		return false;
	}

	/**
	 * 上级代理对下线会员充值
	 */
	public function rechargeMem($event_id, $MemberID, $fromAdmin_id, $incomePoints, $note=NULL, $options=[]){
		try{
			if(!array_key_exists($event_id, $this->eventAry)){
				throw new Exception("EventKey not exists");
			}
			$this->conn->selectTB('MemberFinance2');
			$this->conn->getData("*","WHERE MemberId = '".$MemberID."' AND PointType = '".$this->MainPointType."'");
			$this->conn->execute();
			$Points = 0;
			$addPointType = false;
			if($this->conn->total_row <= 0){
				$addPointType = true;
				// 五种币值都要新增才能正常进入游戏
				$pointTypeAry = array("Free","Game","Main","ShiShiCai","Wager");
				foreach ($pointTypeAry as $key => $value) {
					$insertData = array();
					$insertData['MemberId']  = $MemberID;
					$insertData['PointType'] = $value;
					$insertData['Points']    = 0;

					$this->conn->insertData($insertData);
					$this->conn->execute();
				}
			}

			//20150706-新增貨幣
			$options['MemberCurrency'] = empty($options['MemberCurrency']) ? CURRENCY : $options['MemberCurrency'];
			$options['CurrencyRate']   = empty($options['CurrencyRate']) ? 1 : $options['CurrencyRate'];
			$options['CurrencyAmount'] = $incomePoints;
			//幣值計算
			$incomePoints = $options['CurrencyAmount']*$options['CurrencyRate'];

			$beforePoints = $addPointType ? $Points : $this->conn->row['Points'];
			$incomePoints = ($incomePoints*$this->MoneyPointRate);

			$updateData = array();
			$updateData['Points'] = $beforePoints+$incomePoints;
			$this->conn->updateData($updateData, "WHERE MemberId = '".$MemberID."' AND PointType = '".$this->MainPointType."'");
			
			//寫入操作Log
			$text = [
				'query' => $this->conn->getCurrentQueryString(), 
				'value' => $updateData
			];
			$result = $this->conn->execute();
			$text['result'] = $result;
			$this->addAdminLogs($text, $fromAdmin_id, $note);

			if(!is_bool($result) || !$result){
				throw new Exception("Update fail(".$result.")");
			}
			if(is_bool($result) && $result){
				//充值成功直接写入纪录
				$result = $this->rechargeLog($event_id, $MemberID, $fromAdmin_id, $beforePoints, $incomePoints, $note, $options);
				if(!$result){
					throw new Exception('Recharge Success, but log fail('.$this->getErrorMsg().')');
				}
				return $result;
			}		
			throw new Exception("No result");
		}catch (Exception $e){
			$this->errorMsg = 'Recharge error：'.$e->getMessage();
			return false;
		}
	}

	public function rechargeLog($event_id, $MemberID, $fromAdmin_id, $beforePoints, $incomePoints, $note=NULL, $options){
		try{
			if(!array_key_exists($event_id, $this->eventAry)){
				throw new Exception("EventKey not exists");
			}
			$insertData = array();
			$insertData['MemberId']    = $MemberID;//收入帐号
			$insertData['SrcMemberId'] = $fromAdmin_id;//来源帐号对照Admin资料表的admin_id
			$insertData['BeforePoint'] = $beforePoints;
			$insertData['ChangeEvent'] = $event_id;
			$insertData['Income']      = $incomePoints;
			$insertData['AfterPoint']  = $insertData['BeforePoint'] + $insertData['Income'];
			$insertData['ZongYuE']     = $insertData['AfterPoint'];
			if(!empty($note))$insertData['BeiZhu'] = $note;

			//20150706-新增貨幣
			$insertData['MemberCurrency'] = $options['MemberCurrency'];
			$insertData['CurrencyRate']   = $options['CurrencyRate']  ;
			$insertData['CurrencyAmount'] = $options['CurrencyAmount'];
			
			$this->conn->selectTB('MainPointMingXi');
			$this->conn->insertData($insertData);
			$result = $this->conn->execute();
			if(!is_bool($result) || !$result){
				throw new Exception("Insert fail(".$result.")");
			}
			if(is_bool($result) && $result){
				return $result;
			}		
			throw new Exception("No result");
		}catch (Exception $e){
			$this->errorMsg = 'RechargeLog error：'.$e->getMessage();
			return false;
		}
	}

	/**
	 * 一、上级对下线代理充值
	 * 二、扣點返點
	 * @param  boolean $notAdmin     True:表示写入会员编号栏位, False:代理编号栏位
	 */
	public function creditFrom($event_id, $admin_id, $fromID, $beforePoints, $incomePoints, $note=NULL, $notAdmin=false, $options=[]){
		try{
			if(!array_key_exists($event_id, $this->eventAry)){
				throw new Exception("EventKey not exists");
			}
			$this->conn->selectTB('Admin');
			$this->conn->getData("*","WHERE admin_id = '".$admin_id."'");
			$this->conn->execute();
			if($this->conn->total_row <= 0){
				throw new Exception("Can't find credit person");
			}
			if($this->conn->row['points'] != $beforePoints){
				throw new Exception("DB Points is not equal beforePoints");
			}
			if($notAdmin){
				//20150706-新增貨幣
				$options['MemberCurrency'] = empty($options['MemberCurrency']) ? CURRENCY : $options['MemberCurrency'];
				$options['CurrencyRate']   = empty($options['CurrencyRate']) ? 1 : $options['CurrencyRate'];
				$options['CurrencyAmount'] = $incomePoints;
				//幣值計算
				$incomePoints = $options['CurrencyAmount']*$options['CurrencyRate'];
			}

			$updateData = array();
			$updateData['points']        = ($beforePoints+$incomePoints);
			$updateData['admin_updtime'] = date('Y-m-d H:i:s');

			$this->conn->selectTB('Admin');
			$this->conn->updateData($updateData, "WHERE admin_id = '".$admin_id."'");

			//寫入操作Log
			$text = [
				'query' => $this->conn->getCurrentQueryString(), 
				'value' => $updateData
			];
			$result = $this->conn->execute();
			$text['result'] = $result;
			$this->addAdminLogs($text, $admin_id, $note);

			if(!is_bool($result) || !$result){
				throw new Exception("Update fail(".$result.")");
			}
			if(is_bool($result) && $result){
				//充值成功直接写入纪录
				$result = $this->incomeLog($event_id, $admin_id, $fromID, $beforePoints, $incomePoints, $note, $notAdmin, $options);
				if(!$result){
					throw new Exception('Credit Success, but log fail('.$this->getErrorMsg().')');
				}
				return $result;
			}		
			throw new Exception("No result");
		}catch (Exception $e){
			$this->errorMsg = 'Debit error：'.$e->getMessage();
			return false;
		}
	}

	/**
	 * 收入纪录
	 */
	public function incomeLog($event_id, $admin_id, $fromID, $beforePoints, $incomePoints, $note=NULL, $notAdmin=false, $options){
		try{
			if(!array_key_exists($event_id, $this->eventAry)){
				throw new Exception("EventKey not exists");
			}
			$insertData = array();
			$insertData['admin_id'] = $admin_id;//收入帐号
			
			//转入帐号分会员及代理
			//點數從會員得到
			if($notAdmin){
				$insertData['fromMemberID'] = $fromID;
				//20150706-新增貨幣
				$insertData['MemberCurrency'] = $options['MemberCurrency'];
				$insertData['CurrencyRate']   = $options['CurrencyRate'];
				$insertData['CurrencyAmount'] = $options['CurrencyAmount'];
			}else{
				//點數從代理或總代或管理員得到
				$insertData['fromAdmin_id'] = $fromID;
			}

			$insertData['beforePoints'] = $beforePoints;
			$insertData['event_id']     = $event_id;
			$insertData['income']       = $incomePoints;
			if(!empty($note))$insertData['note'] = $note;
			$insertData['afterPoints']  = ($insertData['beforePoints'] + $insertData['income']);
			
			$this->conn->selectTB($this->table);
			$this->conn->insertData($insertData);
			$result = $this->conn->execute();
			if(!is_bool($result) || !$result){
				throw new Exception("Insert fail(".$result.")");
			}
			if(is_bool($result) && $result){
				return $result;
			}		
			throw new Exception("No result");
		}catch (Exception $e){
			$this->errorMsg = 'Income error：'.$e->getMessage();
			return false;
		}
	}

	/**
	 * 上级充值前扣款
	 * @param  int     $toAccount_id 代理编号或会员编号
	 * @param  boolean $notAdmin     True:表示写入会员编号栏位, False:代理编号栏位
	 */
	public function debitTo($event_id, $admin_id, $toAccount_id, $beforePoints, $outgoPoints, $note=NULL, $notAdmin=false, $options=[]){
		try{
			if(!array_key_exists($event_id, $this->eventAry)){
				throw new Exception("EventKey not exists");
			}
			$this->conn->selectTB('Admin');
			$this->conn->getData("*","WHERE admin_id = '".$admin_id."'");
			$this->conn->execute();
			if($this->conn->total_row <= 0){
				throw new Exception("Can't find debit person");
			}
			//不是管理员的话检查资料库点数是否正确
			if($this->conn->row['ag_id'] != '1' && $this->conn->row['points'] != $beforePoints){
				throw new Exception("DB Points is not equal(".$this->conn->row['points']." != ".$beforePoints.")");
			}
			if($notAdmin){
				//20150706-新增貨幣
				$options['MemberCurrency'] = empty($options['MemberCurrency']) ? CURRENCY : $options['MemberCurrency'];
				$options['CurrencyRate']   = empty($options['CurrencyRate']) ? 1 : $options['CurrencyRate'];
				$options['CurrencyAmount'] = $outgoPoints;
				//幣值計算
				$outgoPoints = $options['CurrencyAmount']*$options['CurrencyRate'];
			}

			if($outgoPoints > $beforePoints){
				throw new Exception("OutgoPoints(".$outgoPoints.") greater than selfPoints(".$beforePoints.")");
			}

			$updateData = array();
			$updateData['points']        = ($beforePoints-$outgoPoints);
			$updateData['admin_updtime'] = date('Y-m-d H:i:s');

			$this->conn->selectTB('Admin');
			$this->conn->updateData($updateData, "WHERE admin_id = '".$admin_id."'");

			//寫入操作Log
			$text = [
				'query' => $this->conn->getCurrentQueryString(), 
				'value' => $updateData
			];
			$result = $this->conn->execute();
			$text['result'] = $result;
			$this->addAdminLogs($text, $admin_id, $note);

			if(!is_bool($result) || !$result){
				throw new Exception("Update fail(".$result.")");
			}
			if(is_bool($result) && $result){
				//扣款成功直接写入纪录
				$result = $this->outgoLog($event_id, $admin_id, $toAccount_id, $beforePoints, $outgoPoints, $note, $notAdmin, $options);
				if(!$result){
					throw new Exception('Debit Success, but log fail('.$this->getErrorMsg().')');
				}
				return $result;
			}		
			throw new Exception("No result");
		}catch (Exception $e){
			$this->errorMsg = 'Debit error：'.$e->getMessage();
			return false;
		}
	}

	/**
	 * 支出纪录
	 */
	public function outgoLog($event_id, $admin_id, $toAccount_id, $beforePoints, $outgoPoints, $note=NULL, $notAdmin=false, $options){
		try{
			if(!array_key_exists($event_id, $this->eventAry)){
				throw new Exception("EventKey not exists");
			}
			if($outgoPoints > $beforePoints){
				throw new Exception("OutgoPoints(".$outgoPoints.") greater than selfPoints(".$beforePoints.")");
			}
			$insertData = array();
			$insertData['admin_id']     = $admin_id;//支出帐号
			$insertData['beforePoints'] = $beforePoints;
			$insertData['event_id']     = $event_id;
			$insertData['outgo']        = $outgoPoints;
			if(!empty($note))$insertData['note'] = $note;
			$insertData['afterPoints']  = ($insertData['beforePoints'] - $insertData['outgo']);
			//转入帐号分会员及代理
			//会员帐号ID
			if($notAdmin){
				$insertData['toMemberID'] = $toAccount_id;
				//20150706-新增貨幣
				$insertData['MemberCurrency'] = $options['MemberCurrency'];
				$insertData['CurrencyRate']   = $options['CurrencyRate'];
				$insertData['CurrencyAmount'] = $options['CurrencyAmount'];
			}else{//代理帐号ID
				$insertData['toAdmin_id'] = $toAccount_id;
			}
			
			$this->conn->selectTB($this->table);
			$this->conn->insertData($insertData);
			$result = $this->conn->execute();

			if(!is_bool($result) || !$result){
				throw new Exception("Insert fail(".$result.")");
			}
			if(is_bool($result) && $result){
				return $result;
			}		
			throw new Exception("No result");
		}catch (Exception $e){
			$this->errorMsg = 'Outgo error：'.$e->getMessage();
			return false;
		}
	}

	public function addAdminLogs($text, $AuthId, $note='')
	{
		$html = new Template();
		$db   = new PDO_DB( DB_HOST, DB_USER, DB_PWD, DB_NAME);
		$db->selectTB('AdminLogs');
		$db->insertData([
			'admin_id'   => $html->WebLogin->admin_id,
			'text'       => json_encode($text),
			'note'       => (empty($note) ? null : $note),
			'created_at' => date('Y-m-d H:i:s')
		]);
		$result = $db->execute();
		unset($db, $html);
		return $result;
	}

	public function getErrorMsg(){
		return $this->errorMsg;
	}

	public function __destruct(){
		$this->conn = NULL;
	}
}

//附件一、DianShuYiDongShiJian
//点数异动事件-资料表
// 1	充值
// 2	上级充值
// 3	提现
// 4	下线返点
// 5	红利
// 6	转出至彩票
// 7	由彩票转入
// 8	转出至游戏
// 9	由游戏转入
// 10	补点
// 11	手续费
// 12	点数转出
// 13	点数转入
// 14	转出到主帐户
// 15	由主帐户转入
// 16	投注
// 17	派彩
// 18	游戏返点
// 19	返款
// 20	管理员扣点
// 21	管理员补点
// 22	提现拒绝
// 23	系统赠点
// 24	游戏结果
// 25	替下线充值
// 28   开新帐号
// 29   扣點
?>