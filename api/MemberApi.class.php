<?php
include_once $_SERVER['DOCUMENT_ROOT'].'/class/Bank.class.php';
include_once $_SERVER['DOCUMENT_ROOT'].'/class/Language.class.php';

class MemberApi extends Bank{

	public $agentInfo   = null;
	public $userInfo    = null;
	public $upAdminInfo = null;
	public $langAry     = null;
	public $MemberData  = null;

	public function __construct($data=null)
	{
		parent::__construct();
		$langAry = new Language();
		$this->langAry = $langAry->getLanguage();
		if(!is_null($data)){
			$this->setBaseData($data);
		}
	}

	/**
	 * 基本參數驗證
	 */
	public function setBaseData($array)
	{
		try {
			if(!isset($array['MemberAccount']) || !isset($array['sign']) || !isset($array['code'])){
				throw new Exception('No Parameters');
			}
			if(empty($array['MemberAccount'])){
				throw new Exception($this->langAry['ERRAccount']);
			}
			if($array['sign'] != md5($array['MemberAccount'].'@*&'.$array['code'])){
				throw new Exception($this->langAry['IndexErrorMsg3']);
			}
		} catch (Exception $e) {
			$this->response(['result'=>'fail', 'msg' => $e->getMessage()]);
		}
		$this->MemberAccount = $array['MemberAccount'];
		$this->code          = $array['code'];
		$this->sign          = $array['sign'];
		$this->NewAccount    = $this->code.'@'.$array['MemberAccount'];
	}

	/**
	 * 註冊帳號
	 */
	public function register()
	{
		if(empty($this->agentInfo)){
			$this->response(['result'=>'fail', 'msg'=>$this->langAry['ERRNoID']]);
		}
		if($this->repeatNickName()){
			$this->response(['result'=>'fail', 'msg'=>$this->langAry['ERRNickNameRepeat']]);
		}
		if(empty($this->upAdminInfo)){
			$this->response(['result'=>'fail', 'msg'=>$this->langAry['ERRNoUpAccount']]);
		}

		$insertData['MemberAccount']  = $this->NewAccount;
		$insertData['MemberPassword'] = substr(md5(rand()),0,6);
		$insertData['NickName']       = $insertData['MemberAccount'];
		$insertData['UpAdmin_id']     = $this->agentInfo['assignAdmin_id'];
		$insertData['MaxCredit']      = empty($this->userInfo['Points']) ? 0 : $this->userInfo['Points'];//新建帳號的起始金額
		$insertData['MemberSource']   = $this->code;//帳號來源Code

		$this->conn->selectTB('A_Member');
		$this->conn->insertData($insertData);
		$text = [
			'query' => $this->conn->getCurrentQueryString(),
			'value' => $insertData,
		];
		$result = $this->conn->execute();
		$text['result'] = $result;
		$this->addAdminLogs($text, null, '[System Api]New Member');

		if(is_string($result)){
			$this->response(['result'=>'fail', 'msg'=>$this->langAry['AddFail'].(DEBUG ? '('.$result.')' : '')]);
		}
		if(is_bool($result) && $result){
			//修正上级的下线总数
			$updateData = array();
			$updateData['downMemCount']  = ($this->upAdminInfo['downMemCount'] + 1);
			$updateData['admin_updtime'] = date('Y-m-d H:i:s');

			$this->conn->selectTB('Admin');
			$this->conn->updateData($updateData, "WHERE admin_id = '".$this->upAdminInfo['admin_id']."'");
			$text = [
				'query' => $this->conn->getCurrentQueryString(),
				'value' => $updateData,
			];
			$result = $this->conn->execute();
			$text['result'] = $result;
			$this->addAdminLogs($text, null, '[System Api]修正上级的下线总数');

			//开新帐号成功-第一次點數充值
			$newMemberID  = $this->conn->getLastInsertId();
			$event_id     = '30';//聯運商點數轉入
			$note         = '[System Api]New Member';
			$incomePoints = $this->userInfo['Points'];
			if(!$this->rechargeMem($event_id, $newMemberID, null, $incomePoints, $note)){
				$this->response(['result'=>'fail', 'msg'=>'RechargeMemFail'.(DEBUG ? '('.$this->getErrorMsg().')' : '')]);
			}
			$this->MemberData();
			return true;
		}
	}

	public function AgentPointTrans()
	{
		try{
			$event_id = '30';//聯運商點數轉移
			if(!array_key_exists($event_id, $this->eventAry)){
				throw new Exception("EventKey not exists");
			}
			if(empty($this->userInfo)){
				throw new Exception('No user info');
			}
			$this->conn->selectTB('MemberFinance2');
			$this->conn->getData("*","WHERE MemberId = '".$this->MemberData['MemberID']."' AND PointType = '".$this->MainPointType."'");
			$this->conn->execute();
			if($this->conn->total_row <= 0){
				throw new Exception('No Finance');
			}
			$beforePoints = $this->conn->row['Points'];
			$incomePoints = ($this->userInfo['Points']*$this->MoneyPointRate);
			$note         = '[System Api]Transfer Point';

			$updateData = array();
			$updateData['Points'] = $incomePoints;
			$this->conn->updateData($updateData, "WHERE MemberId = '".$this->MemberData['MemberID']."' AND PointType = '".$this->MainPointType."'");
			
			//寫入操作Log
			$text = [
				'query' => $this->conn->getCurrentQueryString(), 
				'value' => $updateData,
			];
			$result = $this->conn->execute();
			$text['result'] = $result;
			$this->addAdminLogs($text, null, $note);

			if(!is_bool($result) || !$result){
				throw new Exception("Update fail(".$result.")");
			}
			if(is_bool($result) && $result){
				//轉換成功直接写入纪录
				$insertData = array();
				$insertData['MemberId']    = $this->MemberData['MemberID'];//收入帐号
				$insertData['SrcMemberId'] = null;//来源帐号对照Admin资料表的admin_id
				$insertData['BeforePoint'] = $beforePoints;
				$insertData['event_id']    = $event_id;
				$insertData['Income']      = $incomePoints;
				$insertData['AfterPoint']  = $incomePoints;
				$insertData['ZongYuE']     = $incomePoints;
				if(!empty($note))$insertData['BeiZhu'] = $note;
				
				$this->conn->selectTB('MainPointMingXi');
				$this->conn->insertData($insertData);
				$result = $this->conn->execute();
				if(!is_bool($result) || !$result){
					throw new Exception("Recharge Success, but log fail(".$result.")");
				}
				if(is_bool($result) && $result){
					return $result;
				}		
			}		
			throw new Exception("No result");
		}catch (Exception $e){
			$this->response(['result'=>'fail', 'msg'=>'Transfer Points error'.(DEBUG ? '('.$e->getMessage().')' : '')]);
		}
	}

	public function MemberData()
	{
		if(!is_null($this->MemberData) || $this->repeatAccount()){
			//帳號來源不是該code
			if($this->MemberData['MemberSource'] != $this->code){
				$this->response(['result'=>'fail', 'msg' => $this->langAry['IndexErrorMsg3']]);
			}
			return $this->MemberData;
		}
		return null;
	}

	/**
	 * 是否有重複帳號問題
	 */
	public function repeatAccount()
	{
		$this->conn->selectTB('A_Member');
		$this->conn->getData("*", "WHERE MemberAccount = '".$this->NewAccount."'");
		$this->conn->execute();
		if($this->conn->total_row > 0){
			$this->MemberData = $this->conn->row;
			return true;
		}
		return false;
	}

	/**
	 * 是否有重複暱稱問題
	 */
	public function repeatNickName()
	{
		$this->conn->selectTB('A_Member');
		$this->conn->getData("MemberID", "WHERE NickName = '".$this->NewAccount."'");
		$this->conn->execute();
		if($this->conn->total_row > 0){
			return true;
		}
		return false;
	}

	/**
	 * 檢查上層帳號
	 */
	public function upAdminInfo()
	{
		if(empty($this->agentInfo)){
			$this->response(['result'=>'fail', 'msg'=>$this->langAry['ERRNoID']]);
		}
		$this->conn->selectTB('Admin');
		$this->conn->getData("*", "WHERE admin_id = '".$this->agentInfo['assignAdmin_id']."'");
		$this->conn->execute();
		if($this->conn->total_row <= 0){
			$this->response(['result'=>'fail', 'msg'=>$this->langAry['ERRNoUpAccount']]);
		}
		return $this->upAdminInfo = $this->conn->row;
	}

	/**
	 * 檢查代理商資料
	 */
	public function agentInfo($code=null)
	{
		if(!is_null($code)){
			$this->code = $code;
		}
		$this->conn->selectTB('Agent');
		$this->conn->getData("*", "WHERE code = '".$this->code."'");
		$this->conn->execute();
		if($this->conn->total_row <= 0){
			$this->response(['result'=>'fail', 'msg'=>$this->langAry['ERRNoID']]);
		}
		return $this->agentInfo = $this->conn->row;
	}

	//玩遊戲去
	public function play()
	{
		header('location: TestApi.html?sn1='.$this->MemberData['MemberAccount'].'&sn2='.$this->MemberData['MemberPassword']);
		exit;
	}

	/**
	 * 虛線返回詢問使用者資訊
	 */
	public function userInfo()
	{
		return $this->userInfo = ['Points'=>300];
	}

	/**
	 * 回應
	 */
	public function response($array)
	{
		echo json_encode($array);
		exit;
	}

	public function addAdminLogs($text, $AuthId, $note=null)
	{
		$db = new PDO_DB( DB_HOST, DB_USER, DB_PWD, DB_NAME);
		$db->selectTB('AdminLogs');
		$db->insertData([
			'admin_id'   => $AuthId,
			'text'       => json_encode($text),
			'note'       => $note,
			'created_at' => date('Y-m-d H:i:s')
		]);
		$result = $db->execute();
		unset($db);
		return $result;
	}
}
?>