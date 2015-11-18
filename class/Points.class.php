<?php
include_once $_SERVER['DOCUMENT_ROOT'].'/class/PDO_DB.class.php';
class Points{
	/**
	 * DB Connection
	 */
	public $db = null;
	
	/**
	 * 遊戲輸贏資料庫名稱
	 */
	public $DB_WINLOSE = 'Game_WinloseLog';

	/**
	 * 分析輸贏資料庫名稱
	 */
	public $DB_ANALYSIS_NAME = 'Game_Analysis';

	/**
	 * 輸贏資料表前綴
	 */
	public $tablePrefix = 'Winlose_';

	/**
	 * 輸贏資料表
	 */
	public $table = '';

	/**
	 * 報表計算開始時間
	 */
	public $StartTime = '00:00:00';

	/**
	 * 報表計算結束時間
	 */
	public $EndTime = '23:59:59';

	/**
	 * 公司最高分紅占成
	 */
	public $MaxCommissionRate = 100;

	/**
	 * 公司最高返水額度
	 */
	public $MaxReturnRate = 7;

	/**
	 * 目前登入帳號陣列
	 */
	public $nowAdminData = [];

	/**
	 * 目前搜尋帳號資料
	 */
	public $chkAdminData = [
		'BetTotal'          => 0,
		'WinLoseTotal'      => 0,
		'downBetTotal'      => 0,//目前搜尋代理下層總押注點數
		'downWinLoseTotal'  => 0,//目前搜尋代理下層總輸贏點數
		'downRemoteJPTotal' => 0,//目前搜尋代理下層總指定活動獎金Total
		'remoteJPTotal'     => 0,//目前搜尋代理下層總指定活動獎金Total
		'commissionTotal'   => 0,//目前搜尋代理總分紅
		'returnTotal'       => 0,//目前搜尋代理下層總返點點數
		'upCommissionTotal' => 0,//目前搜尋上層總分紅
		'upReturnTotal'     => 0,//目前搜尋上層總返點數
		'total'             => 0,//目前搜尋總返點分紅加總
		'upTotal'           => 0,//上層Total
	];

	/**
	 * 目前搜尋的佔成率
	 */
	public $chkAdminRate = 0;

	/**
	 * 上層代理資料
	 */
	public $upAgentData = array();

	/**
	 * 下層代理資料
	 */
	public $downAgentData;

	/**
	 * 下層會員資料
	 */
	public $downMemData;

	 /**
	  * 是否同团队可搜寻
	  */
	public $allowSearch = false;

	/**
	 * 麵包屑
	 */
	public $breadcrumb = array();

	/**
	 * 上層資料
	 */
	public $upAdminData = array();

	/**
	 * 现在比率
	 * MoneyPointRate
	 */
	public $MoneyPointRate = '1000000';

	/**
	 * 輸贏紀錄
	 */
	public $row = [];

	/**
	 * 搜尋紀錄
	 */
	public $recordInfo = [];

	/**
	 * 分頁資訊
	 */
	public $pagination = [];

	/**
	 * 新增返點成功計數-Admin
	 */
	public $adminRateCount = 0;

	/**
	 * 新增返點成功計數-Member
	 */
	public $memRateCount = 0;

	/**
	 * 新增成功計數-Admin
	 */
	public $addAdminCount = 0;

	/**
	 * 新增成功計數-Member
	 */
	public $addMemCount = 0;

	/**
	 * 遊戲圖素位置
	 */
	public $gameImgLoca = '/img/game/';

	public function __construct($nowAdminData=''){
		if(!empty($nowAdminData)){
			if(is_object($nowAdminData)){
				$this->nowAdminData = (array)$nowAdminData;
			}
			if(is_array($nowAdminData)){
				$this->nowAdminData = $nowAdminData;
			}
		}
		$this->db               = new PDO_DB(DB_HOST, DB_USER, DB_PWD, DB_NAME);
		$this->DB_WINLOSE       = DB_WINLOSE_NAME;
		$this->DB_ANALYSIS_NAME = DB_ANALYSIS_NAME;
		//现金比率
		$this->db->selectTB('SysValue');
		$this->db->getData("*");
		$this->db->execute();
		if($this->db->total_row > 0){
			do{
				$SysKey = $this->db->row['SysKey'];
				$this->$SysKey = $this->db->row['SysValue'];
			}while($this->db->row = $this->db->fetch_assoc());
		}
	}

	public function __destruct(){
		unset($this->db);
	}

	/**
	 * 使用區間計算自己分紅及報表加總
	 */
	public function getRangeReport($analyAdminData, $startDate, $endDate, $GameId = 'all')
	{
		$isManager = false;
		$sql = " AND admin_id = '".$analyAdminData['admin_id']."'";
		if($analyAdminData['ag_id'] == '1'){
			$sql = " AND upAdmin_id IS NULL";//搜尋總代層
			$isManager = true;
		}

		$db = $this->db;

		$selectRaw = "MAX(commissionRate) AS commissionRate, 
MAX(ReturnRate) AS ReturnRate, MAX(downCount) AS downCount, MAX(downMemCount) AS downMemCount, 
SUM(CONVERT(FLOAT, downBetTotal)) AS downBetTotal, SUM(CAST(downWinLoseTotal AS FLOAT)) AS downWinLoseTotal,
SUM(CAST(downRemoteJPTotal AS FLOAT)) AS downRemoteJPTotal, SUM(CAST(commissionTotal AS FLOAT)) AS commissionTotal,
SUM(CAST(returnTotal AS FLOAT)) AS returnTotal, SUM(CAST(upCommissionTotal AS FLOAT)) AS upCommissionTotal,
SUM(CAST(upReturnTotal AS FLOAT)) AS upReturnTotal";
		$whereRaw = "WHERE startDateTime >= '".$startDate." ".$this->StartTime."' AND endDateTime <= '".$endDate." ".$this->EndTime."'";

		//計算自己本身報表
		$db->selectTB('AdminWinLose');
		$db->getData($selectRaw, $whereRaw.$sql);
		$db->execute();
		//自身資料合併
		$this->chkAdminData = $db->row + $this->chkAdminData;
		$this->chkAdminData['downBetTotal']      = round($this->chkAdminData['downBetTotal'], 6);
		$this->chkAdminData['downWinLoseTotal']  = round($this->chkAdminData['downWinLoseTotal'], 6);
		$this->chkAdminData['downRemoteJPTotal'] = round($this->chkAdminData['downRemoteJPTotal'], 6);
		$this->chkAdminData['commissionTotal']   = round($this->chkAdminData['commissionTotal'], 6);
		$this->chkAdminData['returnTotal']       = round($this->chkAdminData['returnTotal'], 6);
		$this->chkAdminData['upCommissionTotal'] = round($this->chkAdminData['upCommissionTotal'], 6);
		$this->chkAdminData['upReturnTotal']     = round($this->chkAdminData['upReturnTotal'], 6);

		$this->chkAdminData['total']   = $this->chkAdminData['commissionTotal'] + $this->chkAdminData['returnTotal'];
		$this->chkAdminData['upTotal'] = $this->chkAdminData['upCommissionTotal'] + $this->chkAdminData['upReturnTotal'];

		$sql = $isManager ? $sql : " AND upAdmin_id = '".$analyAdminData['admin_id']."'";
		$whereRaw .= $sql;

		//計算下層報表
		$db->selectTB('AdminWinLose');
		$db->getData("ag_id, admin_id, upAdmin_id,".$selectRaw, $whereRaw." GROUP BY ag_id,admin_id,upAdmin_id");
		$db->execute(true);
		if($db->total_row > 0){
			foreach ($db->row as $value) {
				$value['BetTotal']      = round($value['downBetTotal'], 6);
				$value['WinLoseTotal']  = round($value['downWinLoseTotal'], 6);

				$value['downBetTotal']      = round($value['downBetTotal'], 6);
				$value['downWinLoseTotal']  = round($value['downWinLoseTotal'], 6);
				$value['downRemoteJPTotal'] = round($value['downRemoteJPTotal'], 6);
				$value['commissionTotal']   = round($value['commissionTotal'], 6);
				$value['returnTotal']       = round($value['returnTotal'], 6);
				$value['upCommissionTotal'] = round($value['upCommissionTotal'], 6);
				$value['upReturnTotal']     = round($value['upReturnTotal'], 6);

				$value['Total'] = $value['commissionTotal'] + $value['returnTotal'];

				if($isManager){
					$this->downAgentData[$analyAdminData['admin_id']][$value['admin_id']] = $value;
				}else{
					$this->downAgentData[$value['upAdmin_id']][$value['admin_id']] = $value;
				}
			}
		}


		//計算會員層級報表
		$selectRaw = "MemberId, upAdmin_id, MAX(commissionRate) AS commissionRate, 
MAX(ReturnRate) AS ReturnRate, MAX(BetCount) AS BetCount, 
SUM(CAST(BetTotal AS FLOAT )) AS BetTotal, SUM(CAST(WinLoseTotal AS FLOAT)) AS WinLoseTotal,
SUM(CAST(remoteJPTotal AS FLOAT)) AS remoteJPTotal, SUM(CAST(commissionTotal AS FLOAT)) AS commissionTotal,
SUM(CAST(returnTotal AS FLOAT)) AS returnTotal, SUM(CAST(upCommissionTotal AS FLOAT)) AS upCommissionTotal,
SUM(CAST(upReturnTotal AS FLOAT)) AS upReturnTotal";
		$db->selectTB('MemWinLose');
		$db->getData($selectRaw, $whereRaw." GROUP BY MemberId,upAdmin_id");
		$db->row = [];
		$db->execute(true);
		if($db->total_row > 0){
			foreach ($db->row as $key => $value) {
				$value['BetTotal']          = round($value['BetTotal'], 6);
				$value['WinLoseTotal']      = round($value['WinLoseTotal'], 6);
				$value['remoteJPTotal']     = round($value['remoteJPTotal'], 6);
				$value['commissionTotal']   = round($value['commissionTotal'], 6);
				$value['returnTotal']       = round($value['returnTotal'], 6);
				$value['upCommissionTotal'] = round($value['upCommissionTotal'], 6);
				$value['upReturnTotal']     = round($value['upReturnTotal'], 6);

				$this->downMemData[$value['upAdmin_id']][$value['MemberId']] = $value;
			}
		}
		return $this->chkAdminData;

/**
 * 2015-10-20 優化報表速度-淘汰即時計算
 */

		//記錄最上層初始值
		$chkAdminDataInit   = $this->chkAdminData;
		$chkAdminDataSum    = $chkAdminDataInit;
		$downAgentData      = [];
		$downMemData        = [];
		do{
			$this->chkAdminData = $this->getAdminRate($this->chkAdminData, $startDate, $startDate);
			//計算一天報表
			$this->getSearchAdminData($this->chkAdminData, $startDate, $startDate, $GameId);
			//加總計算後的值
			if($chkAdminDataSum['admin_id'] == $this->chkAdminData['admin_id']){
				$chkAdminDataSum['BetTotal']          += $this->chkAdminData['BetTotal'];
				$chkAdminDataSum['WinLoseTotal']      += $this->chkAdminData['WinLoseTotal'];
				$chkAdminDataSum['downBetTotal']      += $this->chkAdminData['downBetTotal'];
				$chkAdminDataSum['downWinLoseTotal']  += $this->chkAdminData['downWinLoseTotal'];
				$chkAdminDataSum['downRemoteJPTotal'] += $this->chkAdminData['downRemoteJPTotal'];
				$chkAdminDataSum['remoteJPTotal']     += $this->chkAdminData['remoteJPTotal'];
				$chkAdminDataSum['commissionTotal']   += $this->chkAdminData['commissionTotal'];
				$chkAdminDataSum['returnTotal']       += $this->chkAdminData['returnTotal'];
				$chkAdminDataSum['upCommissionTotal'] += $this->chkAdminData['upCommissionTotal'];
				$chkAdminDataSum['upReturnTotal']     += $this->chkAdminData['upReturnTotal'];
				$chkAdminDataSum['total']             += $this->chkAdminData['total'];
				$chkAdminDataSum['upTotal']           += $this->chkAdminData['upTotal'];

				$chkAdminDataSum['commissionRate'] = $this->chkAdminData['commissionRate'];//讓分紅為最後的值
			}

			// downAgentData
			if(!empty($this->downAgentData[$this->chkAdminData['admin_id']])){
				foreach ($this->downAgentData[$this->chkAdminData['admin_id']] as $key => $value) {
					if(empty($downAgentData[$key])){
						$downAgentData[$key] = $value;
					}else{
						$downAgentData[$key]['WinLoseTotal']      += $value['WinLoseTotal'];
						$downAgentData[$key]['BetTotal']          += $value['BetTotal'];    
						$downAgentData[$key]['remoteJPTotal']     += $value['remoteJPTotal'];
						$downAgentData[$key]['upCommissionTotal'] += $value['upCommissionTotal'];
						$downAgentData[$key]['upReturnTotal']     += $value['upReturnTotal'];
						$downAgentData[$key]['commissionTotal']   += $value['commissionTotal'];
						$downAgentData[$key]['returnTotal']       += $value['returnTotal'];
						$downAgentData[$key]['Total']             += $value['Total'];
				
						$downAgentData[$key]['commissionRate'] = $value['commissionRate'];//讓分紅為最後的值
					}
				}
			}

			// downMemData
			if(!empty($this->downMemData[$this->chkAdminData['admin_id']])){
				foreach ($this->downMemData[$this->chkAdminData['admin_id']] as $key => $value) {
					if(empty($downMemData[$key])){
						$downMemData[$key] = $value;
					}else{
						$downMemData[$key]['WinLoseTotal']      += $value['WinLoseTotal'];
						$downMemData[$key]['BetTotal']          += $value['BetTotal'];    
						$downMemData[$key]['remoteJPTotal']     += $value['remoteJPTotal'];
						$downMemData[$key]['BetCount']          += $value['BetCount'];
						$downMemData[$key]['returnTotal']       += $value['returnTotal'];
						$downMemData[$key]['upCommissionTotal'] += $value['upCommissionTotal'];
					}
				}
			}
			//如果不是最後一天
			//初始化最上層及下層帳號
			if($startDate != $endDate){
				$this->chkAdminData = $chkAdminDataInit;
				unset($this->downAgentData, $this->downMemData);
			}

			$startDate = date('Y-m-d', strtotime('+1day '.$startDate));//起始加一天
		}while($startDate <= $endDate);
		$this->chkAdminData                                   = $chkAdminDataSum;
		$this->downAgentData[$this->chkAdminData['admin_id']] = $downAgentData;
		$this->downMemData[$this->chkAdminData['admin_id']]   = $downMemData;
		return $this->chkAdminData;
	}

	/**
	 * 維護計算報表取得總控台底下所有階層並記錄
	 */
	public function getAdminWinLose($analyAdminData, $startDate, $endDate)
	{
		$this->getSearchAdminData($analyAdminData, $startDate, $endDate);
		//自己的報表寫入紀錄
		if($this->chkAdminData['downBetTotal'] > 0){
			$result = $this->findAdminWinLose($this->chkAdminData, $startDate, $endDate, true);
		}
		//有下層代理-都必須寫入AdminWinLose
		if(count($this->downAgentData) > 0){
			foreach ($this->downAgentData as $value) {
				foreach ($value as $v) {
					$result = $this->findAdminWinLose($v, $startDate, $endDate, true);
				}
			}
		}
		//有下層會員-MemWinLose
		if(count($this->downMemData) > 0){
			foreach ($this->downMemData as $value) {
				foreach ($value as $v) {
					$result = $this->findMemWinLose($v, $startDate, $endDate, true);
				}
			}
		}
	}

	/**
	 * 該期代理報表
	 */
	public function findAdminWinLose($analyAdminData, $startDate, $endDate, $insert=false)
	{
		$startDateTime = $startDate.' '.$this->StartTime;
		$endDateTime   = $endDate.' '.$this->EndTime;

		$db = new PDO_DB(DB_HOST, DB_USER, DB_PWD, DB_NAME);
		$db->selectTB('AdminWinLose');
		$db->getData("*", "WHERE admin_id = '".$analyAdminData['admin_id']."'
		 AND startDateTime = '".$startDateTime."'
		 AND endDateTime = '".$endDateTime."'");
		$result = $db->execute();
		if($db->total_row > 0){
			return $db->row;
		}
		if(!$insert){
			unset($db);
			return false;
		}
		if($insert && !empty($analyAdminData['BetTotal']) && $analyAdminData['BetTotal'] > 0){
			$insertAry = [];
			$insertAry['ag_id']             = $analyAdminData['ag_id'];
			$insertAry['admin_id']          = $analyAdminData['admin_id'];
			$insertAry['upAdmin_id']        = $analyAdminData['upAdmin_id'];
			$insertAry['commissionRate']    = $analyAdminData['commissionRate'];
			$insertAry['ReturnRate']        = $analyAdminData['ReturnRate'];
			$insertAry['downCount']         = $analyAdminData['downCount'];
			$insertAry['downMemCount']      = $analyAdminData['downMemCount'];
			$insertAry['downBetTotal']      = $analyAdminData['BetTotal'];
			$insertAry['downWinLoseTotal']  = $analyAdminData['WinLoseTotal'];
			$insertAry['downRemoteJPTotal'] = $analyAdminData['remoteJPTotal'];
			$insertAry['commissionTotal']   = $analyAdminData['commissionTotal'];
			$insertAry['returnTotal']       = $analyAdminData['returnTotal'];
			$insertAry['upCommissionTotal'] = $analyAdminData['upCommissionTotal'];
			$insertAry['upReturnTotal']     = $analyAdminData['upReturnTotal'];
			$insertAry['startDateTime']     = $startDateTime;
			$insertAry['endDateTime']       = $endDateTime;

			try {
				$db->insertData($insertAry);
				$result = $db->execute();
				if(is_bool($result) && $result){
					$this->addAdminCount++;
				}
				unset($db);
				return $insertAry;
			} catch (Exception $e) {
				unset($db);
				return false;
			}

		}
		unset($db);
		return false;
	}

	/**
	 * 該期會員報表
	 */
	public function findMemWinLose($analyMemData, $startDate, $endDate, $insert=false)
	{
		//從MemWinLose資料表抓取的必須重置欄位名稱
		if(!empty($analyMemData['MemWinLose']) && $analyMemData['MemWinLose']){
			$analyMemData['MemberID'] = $analyMemData['MemberId'];
		}
		$startDateTime = $startDate.' '.$this->StartTime;
		$endDateTime   = $endDate.' '.$this->EndTime;
		$db = new PDO_DB(DB_HOST, DB_USER, DB_PWD, DB_NAME);
		$db->selectTB('MemWinLose');
		$db->getData("*", "WHERE MemberId = '".$analyMemData['MemberID']."'
		 AND startDateTime = '".$startDateTime."'
		 AND endDateTime = '".$endDateTime."'");
		$result = $db->execute();
		if($db->total_row > 0){
			return $db->row;
		}

		if($insert && !empty($analyMemData['BetTotal']) && $analyMemData['BetTotal'] > 0){
			$insertAry = [];
			$insertAry['MemberId']      = $analyMemData['MemberID'];
			$insertAry['upAdmin_id']    = $analyMemData['UpAdmin_id'];
			$insertAry['ReturnRate']    = $analyMemData['ReturnRate'];
			$insertAry['BetTotal']      = $analyMemData['BetTotal'];
			$insertAry['WinLoseTotal']  = $analyMemData['WinLoseTotal'];
			$insertAry['remoteJPTotal'] = $analyMemData['remoteJPTotal'];
			$insertAry['BetCount']      = $analyMemData['BetCount'];
			$insertAry['returnTotal']   = $analyMemData['returnTotal'];
			$insertAry['startDateTime'] = $startDateTime;
			$insertAry['endDateTime']   = $endDateTime;

			try {
				$db->insertData($insertAry);
				$result = $db->execute();
				if(is_bool($result) && $result){
					$this->addMemCount++;
				}
				unset($db);
				return $insertAry;
			} catch (Exception $e) {
				unset($db);
				return false;
			}
		}
		unset($db);
		return false;
	}

	/**
	 * 主要資料輸出及分析運算搜尋結果
	 */
	public function getSearchAdminData($analyAdminData, $startDate, $endDate, $GameId = 'all'){
		//起始運算
		$this->analyticsAdmin($analyAdminData, $startDate, $endDate);
		//繼續檢查有沒有底下代理及會員
		if(!empty($this->downAgentData[$analyAdminData['admin_id']])){
			$topRate = $this->MaxCommissionRate-$analyAdminData['commissionRate'];
			foreach ($this->downAgentData[$analyAdminData['admin_id']] as $key => $value) {
				//獲得上層全部上層占成
				$UpRateTop    = $this->MaxCommissionRate-$value['commissionRate'];
				$UpReturnRate = $this->MaxReturnRate-$value['ReturnRate'];

				//如果是從AdminWinLose資料抓取則無需繼續計算下層
				if(!empty($value['AdminWinLose']) && $value['AdminWinLose']){
					continue;
				}
				//由最底層開始往上運算
				$this->getSearchAdminData($value, $startDate, $endDate);
				//由最底層開始完成代理分紅運算，最終必須返回上層
				if(!empty($this->downAgentData[$analyAdminData['admin_id']][$value['admin_id']])){
					//計算下層
					$RateTop    = ($this->MaxCommissionRate-$value['commissionRate'])/100;//該代理全部上層占成
					$ReturnRate = $value['ReturnRate']/1000;//該代理返水率
					if($this->downAgentData[$analyAdminData['admin_id']][$value['admin_id']]['downCount'] > 0){
						foreach ($this->downAgentData[$value['admin_id']] as $k => $v) {
							$RateSelf       = ($value['commissionRate']-$v['commissionRate'])/100;//分紅占成
							$DownReturnRate = $v['ReturnRate']/1000;//該代理下層返水率
							$DownRateTop    = ($this->MaxCommissionRate-$v['commissionRate'])/100;//該代理下層全部上層
							$Commission     = $v['WinLoseTotal']*$RateSelf;//計算分紅
							//下注*(最高占成-該代理占成)*該代理返水率 - 下注*(最高占成-該代理下層占成)*該代理下層返水率
							$Return         = ($v['BetTotal']*$RateTop*$ReturnRate) - ($v['BetTotal']*$DownRateTop*$DownReturnRate);

							$this->downAgentData[$analyAdminData['admin_id']][$value['admin_id']]['WinLoseTotal']    += $v['WinLoseTotal'];//總輸贏
							$this->downAgentData[$analyAdminData['admin_id']][$value['admin_id']]['remoteJPTotal']   += $v['remoteJPTotal'];//總指定獎金Total
							$this->downAgentData[$analyAdminData['admin_id']][$value['admin_id']]['commissionTotal'] += $Commission;//總分紅
							$this->downAgentData[$analyAdminData['admin_id']][$value['admin_id']]['BetTotal']        += $v['BetTotal'];//總押注
							$this->downAgentData[$analyAdminData['admin_id']][$value['admin_id']]['returnTotal']     += $Return;
							$this->downAgentData[$analyAdminData['admin_id']][$value['admin_id']]['Total']           += $Return + $Commission;//加總結果
							
							// $UpCommission = $value['WinLoseTotal']*$RateTop;
							// $UpReturn     = ($value['BetTotal']*$UpRateTop*$UpReturnRate)-($value['BetTotal']*$RateTop*$ReturnRate);
							//記錄該代理應上繳結果
							// $this->downAgentData[$analyAdminData['admin_id']][$value['admin_id']]['upReturnTotal']     += $UpReturn;
							// $this->downAgentData[$analyAdminData['admin_id']][$value['admin_id']]['upCommissionTotal'] += $UpCommission;
							// $this->downAgentData[$analyAdminData['admin_id']][$value['admin_id']]['UpTotal']      += $UpCommission + $UpReturn;
							// $this->downAgentData[$analyAdminData['admin_id']][$value['admin_id']]['RateTop'] = $RateTop;
						}
					}
					//需返回上層的上層分紅
					if(!empty($this->downAgentData[$analyAdminData['upAdmin_id']][$analyAdminData['admin_id']])){
						$this->downAgentData[$analyAdminData['upAdmin_id']][$analyAdminData['admin_id']]['upCommissionTotal'] += $topRate*$this->downAgentData[$analyAdminData['admin_id']][$value['admin_id']]['WinLoseTotal']/100;
					}
				}
			}
		}

		//由最底層開始完成代理分紅運算，最終必須返回上層 計算下層會員
		if(!empty($this->downMemData[$analyAdminData['admin_id']]) && $analyAdminData['admin_id'] != $this->chkAdminData['admin_id']){
			foreach ($this->downMemData[$analyAdminData['admin_id']] as $i => $j) {
				$RateSelf = $analyAdminData['commissionRate']/100;//分紅占成
				$RateTop  = ($this->MaxCommissionRate-$analyAdminData['commissionRate'])/100;//該代理全部上層占成
				//記錄該會員應上繳結果
				$this->downMemData[$analyAdminData['admin_id']][$i]['upCommissionTotal'] = $j['WinLoseTotal']*$RateSelf;
				$this->downMemData[$analyAdminData['admin_id']][$i]['RateTop']           = $RateTop;
			}
		}
		
		//此為最終查詢帳號支出分紅明細
		if($analyAdminData['admin_id'] == $this->chkAdminData['admin_id']){
			//獲得上層全部上層占成
			// $UpRateTop = (empty($this->upAgentData) ? $this->MaxCommissionRate-$analyAdminData['commissionRate'] : $this->MaxCommissionRate-$this->upAgentData['commissionRate'])/100;
			//如果沒有上層資料表示總代或管理員?目前最高返水率:取上層返水率
			// $UpReturnRate  = (empty($this->upAgentData) ? $this->MaxReturnRate-$analyAdminData['ReturnRate'] : $this->upAgentData['ReturnRate'])/1000;
			
			//檢查有無AdminWinLose資料
			// $result = $this->findAdminWinLose($analyAdminData, $startDate, $endDate);
			$result = '';
			if(is_array($result)){
				$this->chkAdminData['downBetTotal']      = $result['downBetTotal'];//下線總押注點數
				$this->chkAdminData['downWinLoseTotal']  = $result['downWinLoseTotal'];//下線總輸贏點數
				$this->chkAdminData['downRemoteJPTotal'] = $result['downRemoteJPTotal'];//下線總指定JP獎金
				$this->chkAdminData['commissionTotal']   = $result['commissionTotal'];//總分紅
				$this->chkAdminData['returnTotal']       = $result['returnTotal'];//總返點
				$this->chkAdminData['upCommissionTotal'] = $result['upCommissionTotal'];//上層總分紅
				$this->chkAdminData['upReturnTotal']     = $result['upReturnTotal'];//上層總返點

				$this->chkAdminData['total']   = $result['commissionTotal'] + $result['returnTotal'];
				$this->chkAdminData['upTotal'] = $result['upCommissionTotal'] + $result['upReturnTotal'];

				$this->chkAdminData['AdminWinLose'] = true;
				$this->chkAdminData['id']           = $result['id'];
				$this->chkAdminData['note']         = $result['note'];
			}

			//如果找不到資料則必須計算此帳號報表
			if(!is_array($result) && !$result){
				$UpRateTop    = ($this->MaxCommissionRate-$analyAdminData['commissionRate'])/100;
				$UpReturnRate = ($this->MaxReturnRate-$analyAdminData['ReturnRate'])/1000;
				//查詢是否底下有代理
				//總下注*(最高占成100-自己占成)*自己返水成數(不需計算)-總下注*(最高占成100-下面代理占成)*下面代理返水成數(不需計算)
				if(!empty($this->downAgentData[$analyAdminData['admin_id']])){
					foreach ($this->downAgentData[$analyAdminData['admin_id']] as $key => $value) {
						$ReturnRate     = $analyAdminData['ReturnRate']/1000;//查詢之帳號返水率
						$RateTop        = ($this->MaxCommissionRate-$analyAdminData['commissionRate'])/100;//查詢之帳號全部上層占成
						$DownRateSelf   = ($analyAdminData['commissionRate']-$value['commissionRate'])/100;//下層代理分紅占成
						$DownReturnRate = $value['ReturnRate']/1000;//下層代理返水率
						$DownRateTop    = ($this->MaxCommissionRate-$value['commissionRate'])/100;//下層代理全部上層占成
						$Commission     = $value['WinLoseTotal']*$DownRateSelf;
						$Return         = ($value['BetTotal']*$RateTop*$ReturnRate)-($value['BetTotal']*$DownRateTop*$DownReturnRate);
						$UpReturn       = 0;
						$UpCommission   = $value['WinLoseTotal']*$RateTop;
						if(!empty($this->upAgentData)){//上層為公司不需計算
							// $UpReturn = ($value['BetTotal']*$UpRateTop*$UpReturnRate)-($value['BetTotal']*$RateTop*$ReturnRate);
							$UpReturn = (0-($value['BetTotal']*$UpRateTop*$ReturnRate));
						}

						//替換
						$this->chkAdminData['BetTotal']          += $value['BetTotal'];//下線總押注點數
						$this->chkAdminData['WinLoseTotal']      += $value['WinLoseTotal'];//下線總輸贏點數
						$this->chkAdminData['remoteJPTotal']     += $value['remoteJPTotal'];//下線總指定JP獎金
						$this->chkAdminData['downBetTotal']      += $value['BetTotal'];//下線總押注點數
						$this->chkAdminData['downWinLoseTotal']  += $value['WinLoseTotal'];//下線總輸贏點數
						$this->chkAdminData['downRemoteJPTotal'] += $value['remoteJPTotal'];//下線總指定JP獎金
						$this->chkAdminData['commissionTotal']   += $Commission;//總分紅
						$this->chkAdminData['returnTotal']       += $Return;//總返點
						$this->chkAdminData['upCommissionTotal'] += $UpCommission;//上層總分紅
						$this->chkAdminData['upReturnTotal']     += $UpReturn;//上層總返點
						$this->chkAdminData['total']             += $Return + $Commission;
						$this->chkAdminData['upTotal']           += $UpReturn + $UpCommission;
						$this->chkAdminData['RateTop']           = $RateTop;

						//記錄該代理應上繳結果
						// $DownUpReturnRate = ($this->MaxReturnRate-$value['ReturnRate'])/1000;
						// $DownUpCommission = $value['WinLoseTotal']*$DownRateTop;
						// $DownUpReturn     = (0-($value['BetTotal']*$DownRateTop*$DownReturnRate));

						// $this->downAgentData[$analyAdminData['admin_id']][$value['admin_id']]['upReturnTotal']     += $DownUpReturn;
						// $this->downAgentData[$analyAdminData['admin_id']][$value['admin_id']]['upCommissionTotal'] += $DownUpCommission;
						// $this->downAgentData[$analyAdminData['admin_id']][$value['admin_id']]['UpTotal']           += $DownUpCommission + $DownUpReturn;
					}
				}
				//查詢是否有下線會員
				//總下注*(最高占成100-自己占成)*自己返水成數(不需計算)-(下面會員的返水點數)
				if(!empty($this->downMemData[$analyAdminData['admin_id']])){
					foreach ($this->downMemData[$analyAdminData['admin_id']] as $key => $value) {
						if(!isset($value['WinLoseTotal'])){
							echo '<pre>';
							print_r($value);
							continue;
						}
						$RateSelf       = $analyAdminData['commissionRate']/100;//查詢之帳號分紅占成
						$ReturnRate     = $analyAdminData['ReturnRate']/1000;//查詢之帳號返水率
						$RateTop        = ($this->MaxCommissionRate-$analyAdminData['commissionRate'])/100;//全部上層占成
						$DownReturnRate = $value['ReturnRate']/1000;//會員返水率
						$Commission     = $value['WinLoseTotal']*$RateSelf;//計算分紅
						$UpCommission   = $value['WinLoseTotal']*$RateTop;//計算上層分紅
						$Return         = ($value['BetTotal']*$RateTop*$ReturnRate)-($value['BetTotal']*$DownReturnRate);//計算返水
						//$UpReturn       = ($value['BetTotal']*$UpRateTop*$UpReturnRate)-($value['BetTotal']*$RateTop*$ReturnRate);//計算上層返水
						//$UpReturn       = ($value['BetTotal']*$UpRateTop*$UpReturnRate)-($value['BetTotal']*$RateTop*$ReturnRate)-($value['BetTotal']*$UpRateTop*$this->MaxReturnRate/1000);//計算上層返水
						$UpReturn       = 0-($value['BetTotal']*$UpRateTop*$ReturnRate);

						//替換
						$this->chkAdminData['BetTotal']          += $value['BetTotal'];//下線總押注點數
						$this->chkAdminData['WinLoseTotal']      += $value['WinLoseTotal'];//下線總輸贏點數
						$this->chkAdminData['remoteJPTotal']     += $value['remoteJPTotal'];//下線總指定JP獎金
						$this->chkAdminData['downBetTotal']      += $value['BetTotal'];//下線總押注點數
						$this->chkAdminData['downWinLoseTotal']  += $value['WinLoseTotal'];//下線總輸贏點數
						$this->chkAdminData['downRemoteJPTotal'] += $value['remoteJPTotal'];//下線總指定JP獎金
						$this->chkAdminData['commissionTotal']   += $Commission;//總分紅
						$this->chkAdminData['returnTotal']       += $Return;//總返點
						$this->chkAdminData['upCommissionTotal'] += $UpCommission;//上層總分紅
						$this->chkAdminData['upReturnTotal']     += $UpReturn;//上層總返點
						$this->chkAdminData['total']             += $Return + $Commission;
						$this->chkAdminData['upTotal']           += $UpReturn + $UpCommission;

						//20150708-需求算能從各會員得到多少分紅
						$this->downMemData[$analyAdminData['admin_id']][$value['MemberID']]['upCommissionTotal'] = $Commission;
					}
				}
			}
		}
	}

	/**
	 * 分析運算
	 */
	public function analyticsAdmin($analyAdminData, $startDate, $endDate, $GameId='all'){
		$db = new PDO_DB(DB_HOST, DB_USER, DB_PWD, DB_NAME);
		//是否有下線代理
		if($analyAdminData['downCount'] > 0 || $analyAdminData['ag_id'] == '1'){
			$sql = "WHERE upAdmin_id = '".$analyAdminData['admin_id']."'";
			if($analyAdminData['ag_id'] == '1'){
				$sql = "WHERE ag_id = '2'";
			}
			$db->selectTB('Admin');
			$db->getData("*", $sql);
			$db->execute();
			if($db->total_row > 0){
				do{
					$db->row        = $this->getAdminRate($db->row, $startDate, $endDate);//取得期數設定占成
					$id             = $db->row['admin_id'];
					$rateSelf       = $db->row['commissionRate']/100;//自己獲利%數
					$ReturnRateSelf = $db->row['ReturnRate']/1000;//自己獲利反點率
					$upAdmin_id     = $db->row['upAdmin_id'];
					$result         = false;
					if($analyAdminData['ag_id'] == '1'){
						$upAdmin_id = $analyAdminData['admin_id'];
					}
					//無此下層代理-downAgentData[]
					if($upAdmin_id != NULL && empty($this->downAgentData[$upAdmin_id][$id])){
						$db->row['rate']              = $rateSelf;
						$db->row['WinLoseTotal']      = 0;
						$db->row['BetTotal']          = 0;
						$db->row['remoteJPTotal']     = 0;
						$db->row['commissionTotal']   = 0;
						$db->row['returnTotal']       = 0;
						$db->row['Total']             = 0;
						$db->row['upCommissionTotal'] = 0;
						$db->row['upReturnTotal']     = 0;
						$db->row['UpTotal']           = 0;
						$this->downAgentData[$upAdmin_id][$id] = $db->row;
						
						//檢查有無AdminWinLose資料
						// $result = $this->findAdminWinLose($db->row, $startDate, $endDate);
						$result = '';
						if(is_array($result)){
								// echo '<pre>';
								// print_r($result);
								// print_r($db->row);
								// exit;
							// $this->downAgentData[$upAdmin_id][$id] += $result;
							$this->downAgentData[$upAdmin_id][$id]['WinLoseTotal']      += $result['downWinLoseTotal'];//總輸贏
							$this->downAgentData[$upAdmin_id][$id]['BetTotal']          += $result['downBetTotal'];//總押注
							$this->downAgentData[$upAdmin_id][$id]['remoteJPTotal']     += $result['downRemoteJPTotal'];//總JP獎金
							$this->downAgentData[$upAdmin_id][$id]['upCommissionTotal'] += $result['upCommissionTotal'];//總輸贏
							$this->downAgentData[$upAdmin_id][$id]['upReturnTotal']     += $result['upReturnTotal'];//總押注
							$this->downAgentData[$upAdmin_id][$id]['commissionTotal']   += $result['commissionTotal'];//總分紅
							$this->downAgentData[$upAdmin_id][$id]['returnTotal']       += $result['returnTotal'];//返點
							$this->downAgentData[$upAdmin_id][$id]['Total']             += $result['returnTotal'] + $result['commissionTotal'];//加總
							$this->downAgentData[$upAdmin_id][$id]['note']              = $result['note'];//備註

							$this->downAgentData[$upAdmin_id][$id]['AdminWinLose'] = true;
							$this->downAgentData[$upAdmin_id][$id]['id']           = $result['id'];
						}
					}
					//如果找不到資料則必須計算此帳號報表
					if(!is_array($result) && !$result){
						//检查有没有輸贏纪录表示必须分红
						$downOutgoTotal = $this->chkMemOutgo($id, $db->row, $startDate, $endDate, $GameId);
						if(is_array($downOutgoTotal)){
							$Commission = $downOutgoTotal['WinLoseTotal']*$rateSelf;//分紅
							$this->downAgentData[$upAdmin_id][$id]['WinLoseTotal']    += $downOutgoTotal['WinLoseTotal'];//總輸贏
							$this->downAgentData[$upAdmin_id][$id]['BetTotal']        += $downOutgoTotal['BetTotal'];//總押注
							$this->downAgentData[$upAdmin_id][$id]['remoteJPTotal']   += $downOutgoTotal['remoteJPTotal'];//總JP獎金
							$this->downAgentData[$upAdmin_id][$id]['commissionTotal'] += $Commission;//總分紅
							$this->downAgentData[$upAdmin_id][$id]['returnTotal']     += $downOutgoTotal['returnTotal'];//返點
							$this->downAgentData[$upAdmin_id][$id]['Total']           += $downOutgoTotal['returnTotal'] + $Commission;//加總

							//2015-10-22 加入上層分紅
							$this->downAgentData[$upAdmin_id][$id]['upCommissionTotal'] += $downOutgoTotal['WinLoseTotal']*(1-$rateSelf);//加總
						}
					}
				}while($db->row = $db->fetch_assoc());
			}
		}
		//是否有下線會員且僅查詢帳號層使用
		//會員資料，前端顯示用
		if($analyAdminData['admin_id'] == $this->chkAdminData['admin_id'] && $analyAdminData['downMemCount'] > 0){
			$db->selectTB('A_Member');
			$db->getData("*",
			 "WHERE UpAdmin_id = '".$analyAdminData['admin_id']."'");
			$db->execute();
			if($db->total_row > 0){
				do{
					$db->row = $this->getAdminRate($db->row, $startDate, $endDate, true);//取得期數設定占成
					//检查有没有支出纪录表示必须分红
					$id         = $db->row['MemberID'];
					$UpMemberID = $db->row['UpAdmin_id'];
					//檢查MemWinLose
					// $result = $this->findMemWinLose($db->row, $startDate, $endDate);
					$result = '';
					if(is_array($result)){
						$this->downMemData[$UpMemberID][$id] = $result + $db->row;
						$this->downMemData[$UpMemberID][$id]['WinLoseTotal']  = $result['WinLoseTotal'];
						$this->downMemData[$UpMemberID][$id]['remoteJPTotal'] = $result['remoteJPTotal'];
						$this->downMemData[$UpMemberID][$id]['returnTotal']   = $result['returnTotal'];
						$this->downMemData[$UpMemberID][$id]['Total']         = (0-$result['returnTotal']) + $result['WinLoseTotal'];
						$this->downMemData[$UpMemberID][$id]['note']          = $result['note'];

						$this->downMemData[$UpMemberID][$id]['MemWinLose']   = true;
						$this->downMemData[$UpMemberID][$id]['id']           = $result['id'];
					}
					if(!is_array($result) && !$result){
						$downOutgoTotal = $this->chkMemberOutgo($db->row, $startDate, $endDate, $GameId);
						if(is_array($downOutgoTotal)){
							$MemberID     = $downOutgoTotal['toMemberID'];
							$admin_id     = $analyAdminData['admin_id'];
							$ReturnPoints = $downOutgoTotal['BetTotal']*($db->row['ReturnRate']/1000);
							if(empty($this->downMemData[$UpMemberID][$MemberID])){
								$this->downMemData[$UpMemberID][$MemberID]                  = $db->row;
								$this->downMemData[$UpMemberID][$MemberID]['WinLoseTotal']  = $downOutgoTotal['WinLoseTotal'];
								$this->downMemData[$UpMemberID][$MemberID]['BetTotal']      = $downOutgoTotal['BetTotal'];
								$this->downMemData[$UpMemberID][$MemberID]['remoteJPTotal'] = $downOutgoTotal['remoteJPTotal'];
								$this->downMemData[$UpMemberID][$MemberID]['BetCount']      = $downOutgoTotal['BetCount'];
								$this->downMemData[$UpMemberID][$MemberID]['returnTotal']   = $ReturnPoints;
								$this->downMemData[$UpMemberID][$MemberID]['Total']         = (0-$ReturnPoints) + $downOutgoTotal['WinLoseTotal'];//結算結果
							}
						}
					}
				}while($db->row = $db->fetch_assoc());
			}
		}
		unset($db);
	}

	/**
	 * 检查下线会员輸贏點數
	 */
	public function chkMemOutgo($UpAdmin_id, $upAdminData, $startDate, $endDate, $GameId){
		//搜尋下層玩家
		$db = new PDO_DB(DB_HOST, DB_USER, DB_PWD, DB_NAME);
		$db->selectTB('A_Member');
		$db->getData("*","WHERE UpAdmin_id = '".$UpAdmin_id."'");
		$db->execute();
		if($db->total_row <= 0){
			unset($db);
			return false;
		}
		$ary = array();
		$ary['admin_id']      = $UpAdmin_id;
		$ary['WinLoseTotal']  = 0;
		$ary['BetTotal']      = 0;
		$ary['remoteJPTotal'] = 0;
		$ary['BetCount']      = 0;
		$ary['returnTotal']   = 0;
		do{
			$result = $this->chkMemberOutgo($db->row, $startDate, $endDate, $GameId);
			if(is_array($result)){
				$db->row        = $this->getAdminRate($db->row, $startDate, $endDate, true);//取得期數設定占成
				$RateSelf       = $upAdminData['commissionRate']/100;
				$ReturnRate     = $upAdminData['ReturnRate']/1000;//計算減到會員的返水率=自己得到的返水率
				$RateTop        = ($this->MaxCommissionRate-$upAdminData['commissionRate'])/100;
				$DownReturnRate = $db->row['ReturnRate']/1000;//會員返水率
				//總下注*(最高占成-該代理占成)*該代理返水率 - 總下注*會員返水率
				$Return = ($result['BetTotal']*$RateTop*$ReturnRate)-($result['BetTotal']*$DownReturnRate);
				
				// if($_SESSION['_admin_acc'] == 'brian'){
				// 	echo $db->row['MemberAccount'].' ==> ('.$result['BetTotal'].' * '.$RateTop.' * '.$ReturnRate.' = '.($result['BetTotal']*$RateTop*$ReturnRate) .') + ('.$result['BetTotal'].' * '.$DownReturnRate.' = '.($result['BetTotal']*$DownReturnRate) .') = '.$Return.'<br>';
				// }
				// $UpCommissionRate = ($upupAdminData['commissionRate']-$upAdminData['commissionRate'])/100;//上上層的分紅占成
				// $upReturnRate     = ($upupAdminData['ReturnRate']-$upAdminData['ReturnRate'])/1000;//上上層返水率-上層返水率=實際上上層獲得返水率
				// $ReturnPoints     = (0-($result['BetTotal']*$ReturnRate*$CommissionRate)) + ($result['BetTotal']*$upReturnRate*$UpCommissionRate);//計算返點
				$ary['WinLoseTotal']  += $result['WinLoseTotal'];
				$ary['BetTotal']      += $result['BetTotal'];
				$ary['remoteJPTotal'] += $result['remoteJPTotal'];
				$ary['BetCount']      += $result['BetCount'];
				$ary['returnTotal']   += $Return;

				$result['returnTotal'] = $Return;

				$this->downMemData[$UpAdmin_id][$db->row['MemberID']] = $db->row + $result;
			}
		}while($db->row = $db->fetch_assoc());
		unset($db);
		return $ary;
	}

	/**
	 * 检查玩家壓住總點數
	 */
	public function chkMemberOutgo($MemberData, $startDate, $endDate, $GameId = 'all'){
		$MemberID  = $MemberData['MemberID'];
		$tableName = $this->getWinLoseTBName($MemberID);
		if(!$this->chkDBTableExist($tableName)){
			return false;
		}
		// $result = $this->findMemWinLose($MemberData, $startDate, $endDate);
		$result = '';
		if(is_array($result)){
			$result['toMemberID'] = $MemberID;
			return $result;
		}
		if(!is_array($result) && !$result){
			$sql = "WHERE WinLose IS NOT NULL
			  AND CreateDate >= '".$startDate." ".$this->StartTime."'
			  AND CreateDate <= '".$endDate." ".$this->EndTime."'";
			if(!empty($GameId) && $GameId != 'all'){
				$sql .= " AND GameId = '".$GameId."'";
			}
			$db = new PDO_DB(DB_HOST, DB_USER, DB_PWD, $this->DB_WINLOSE);
			$db->selectTB($tableName);
			$db->getData("SUM(WinLose) AS WinLoseTotal, SUM(Bet) AS BetTotal, SUM(GetJPMoney) AS remoteJPTotal, COUNT(Bet) AS BetCount", $sql);
			$result = $db->execute();
			if($db->total_row > 0){
				$ary = $db->row;
				$ary['toMemberID']    = $MemberID;
				$ary['WinLoseTotal']  = $ary['WinLoseTotal']/$this->MoneyPointRate;//總輸贏
				$ary['BetTotal']      = $ary['BetTotal']/$this->MoneyPointRate;//總押注
				$ary['remoteJPTotal'] = $ary['remoteJPTotal']/$this->MoneyPointRate;//總JP獎金

				// if($this->is_Negative($ary['WinLoseTotal'])){//負數表示公司獲利
				// 	unset($db);
				// 	$ary['WinLoseTotal'] = abs($ary['WinLoseTotal']);
				// 	return $ary;
				// }
				// if(!$this->is_Negative($ary['WinLoseTotal'])){//正數表示
				// 	unset($db);
				// 	$ary['WinLoseTotal'] = 0 - $ary['WinLoseTotal'];
				// 	return $ary;
				// }
				return $ary;
				unset($db);
				return false;
			}
			unset($db);
			return false;
		}
		return false;
	}

	/**
	 * 產生上層連結
	 * 檢查是否能夠查詢上層
	 * 使用完此function 需使用$this->allowSearch檢查是否有權限搜尋
	 */
	public function produceLink($chkAdminData, $memData=''){
		$this->chkAdminData += $chkAdminData;
		$li_a = '<li><a href="?acc=%s">%s</a></li>';
		//表示搜寻自己或者是管理员
		if($this->nowAdminData['admin_id'] == $chkAdminData['admin_id'] || $this->nowAdminData['ag_id'] == '1'){
			$this->allowSearch = true;
		}
		//搜寻的上层是自己或子賬號等於搜尋的帳號
		if($this->nowAdminData['admin_id'] == $chkAdminData['upAdmin_id'] || $this->nowAdminData['subAdmin'] == $chkAdminData['admin_id']){
			$this->allowSearch = true;
		}
		//現在參數
		$getData = $_SERVER['QUERY_STRING'];
		$getData = preg_split('/acc=(.*?)&/', $getData);
		//搜寻管理员上级成员产生连结
		$checkUpAdmin_id = $chkAdminData['upAdmin_id'];
		//先行計算此帳號%數
		// $this->chkAdminRate = ($chkAdminData['commissionRate']/100);
		if($checkUpAdmin_id != NULL){
			do{
				$upMemAry        = $this->chkUpAdmin($checkUpAdmin_id);
				$checkUpAdmin_id = $upMemAry['upAdmin_id'];
				$upAdmin_acc     = $upMemAry['admin_acc'];
				if(!empty($upMemAry['admin_acc'])){
					$upperAdmin_id = $upMemAry['admin_id'];
					//检查上层帐号是否有权限观看下級
					if(!$this->allowSearch && 
						($upperAdmin_id == $this->nowAdminData['admin_id'] ||
						 $upperAdmin_id == $this->nowAdminData['subAdmin'])){
						$this->allowSearch = true;
					}
					//記錄查詢帳號的上層
					if($upperAdmin_id == $this->chkAdminData['upAdmin_id']){
						$this->upAgentData = $upMemAry;
					}
					$this->upAdminData[] = $upMemAry;
				}
			}while($checkUpAdmin_id != NULL);

			$allowView = false;//上層可否觀看
			foreach (array_reverse($this->upAdminData) as $key => $value) {
				if(!$allowView && ($value['admin_id'] == $this->nowAdminData['admin_id'] || ($this->nowAdminData['ag_id'] == '4' && $value['admin_id'] == $this->nowAdminData['subAdmin']) || $this->nowAdminData['ag_id'] == '1')){
					$allowView = true;
				}
				if($allowView){
					$this->breadcrumb[] = sprintf($li_a, $value['admin_acc'].(empty($getData[1]) ? '' : '&'.$getData[1]), $value['admin_acc']);
				}else{
					$this->breadcrumb[] = '<li>'.$value['admin_acc'].'</li>';
				}
			}
		}
		if(empty($memData)){
			$this->breadcrumb[] = '<li class="active">'.$chkAdminData['admin_acc'].'</li>';
		}
		if(!empty($memData) && !empty($memData['MemberAccount'])){
			$this->breadcrumb[] = sprintf($li_a, $chkAdminData['admin_acc'].(empty($getData[1]) ? '' : '&'.$getData[1]), $chkAdminData['admin_acc']);
			$this->breadcrumb[] = '<li class="active">'.$memData['MemberAccount'].'</li>';
		}
		$this->breadcrumb = array_reverse($this->breadcrumb);
	}

	/**
	 * Get該期正確Rate
	 */
	public function getAdminRate($RateData, $startDate, $endDate, $is_mem = false, $insert=false){
		$ag_id = $is_mem ? '0' : $RateData['ag_id'];
		$id    = $is_mem ? $RateData['MemberID'] : $RateData['admin_id'];

		$RateData['startDate'] = $startDate;
		$RateData['endDate']   = $endDate;

		//除管理員及總控台以外必須檢查
		if(empty($ag_id) || !in_array($ag_id, ['1', '2'])){
			$this->db->row = [];
			$this->db->selectTB('AdminRate');
			$this->db->getData("*","WHERE acc_id = '".$id."'
			 AND ag_id = '".$ag_id."'
			 AND startDate <= '".$startDate."'
			 AND endDate >= '".$endDate."'
			 ORDER BY addtime DESC, updtime DESC");
			$result = $this->db->execute();
			//沒有找到該期Rate則預設0
			$RateData['commissionRate'] = !empty($this->db->row['commissionRate']) ? $this->db->row['commissionRate'] : 0;
			$RateData['ReturnRate']     = !empty($this->db->row['ReturnRate']) ? $this->db->row['ReturnRate'] : 0;

			//表示沒有新增過該期Rate抓取上一期資料
			if($insert && $this->db->total_row <= 0){
				$this->db->getData("*",
				"WHERE ag_id = '".$RateData['ag_id']."'
				 AND acc_id = '".$RateData['admin_id']."'
				 AND endDate <= '".$startDate."'
				 ORDER BY addtime DESC, updtime DESC");
				$this->db->execute();
				if($this->db->total_row > 0){
					//使用上期Rate
					$RateData['commissionRate'] = $this->db->row['commissionRate'];
					$RateData['ReturnRate']     = $this->db->row['ReturnRate'];
				}
				//未有新增過Rate則使用自己本身的Rate
				$insertAry = array();
				$insertAry['ag_id']          = $RateData['ag_id'];
				$insertAry['acc_id']         = $RateData['admin_id'];
				$insertAry['endDate']        = $endDate;
				$insertAry['startDate']      = $startDate;
				$insertAry['ReturnRate']     = intval($RateData['ReturnRate']);
				$insertAry['commissionRate'] = intval($RateData['commissionRate']);
		// print_r($insertAry);

				$this->db->selectTB('AdminRate');
				$this->db->insertData($insertAry);
				$result = $this->db->execute();
				if(is_bool($result) && $result){
					$is_mem ? $this->memRateCount++ : $this->adminRateCount++;
				}
			}
		}
		return $RateData;
	}

	/**
	 * Get Admin下線最高佔成及返點
	 */
	public function getDownMaxAdminRate($RateData, $startDate, $endDate)
	{
		$RateData['maxCommissionRate'] = 0;
		$RateData['maxReturnRate']     = 0;
		$this->db->row = [];
		$this->db->selectTB('Admin');
		$this->db->getData("MAX(AdminRate.commissionRate) AS maxCommissionRate, MAX(AdminRate.ReturnRate) AS maxReturnRate",
		"INNER JOIN AdminRate ON (AdminRate.acc_id = Admin.admin_id AND AdminRate.ag_id = Admin.ag_id)
		 WHERE upAdmin_id = '".$RateData['admin_id']."'
		 AND startDate <= '".$startDate."'
		 AND endDate >= '".$endDate."'");
		$this->db->execute();
		if($this->db->total_row > 0){
			$RateData['maxCommissionRate'] = $this->db->row['maxCommissionRate'] ? $this->db->row['maxCommissionRate'] : '0';
			$RateData['maxReturnRate']     = $this->db->row['maxReturnRate'] ? $this->db->row['maxReturnRate'] : '0';
		}
		return $RateData;
	}

	/**
	 * Get Member下線最高返點
	 */
	public function getDownMaxMemRate($RateData, $startDate, $endDate)
	{
		$RateData['maxReturnRate'] = isset($RateData['maxReturnRate']) ? $RateData['maxReturnRate'] : '0';
		$this->db->row = [];
		$this->db->selectTB('A_Member');
		$this->db->getData("MAX(AdminRate.ReturnRate) AS maxReturnRate",
		"INNER JOIN AdminRate ON (AdminRate.acc_id = A_Member.MemberID)
		 WHERE ag_id = '0'
		 AND UpAdmin_id = '".$RateData['admin_id']."'
		 AND startDate <= '".$startDate."'
		 AND endDate >= '".$endDate."'");
		$this->db->execute();
		if($this->db->total_row > 0 && $this->db->row['maxReturnRate'] && $RateData['maxReturnRate'] < $this->db->row['maxReturnRate']){
			$RateData['maxReturnRate'] = $this->db->row['maxReturnRate'];
		}
		return $RateData;
	}

	/**
	 * 檢查是否有上層
	 */
	public function chkUpAdmin($admin_id){
		$this->db->selectTB('Admin');
		$this->db->getData("*", "WHERE admin_id = '".$admin_id."'");
		$this->db->execute();
		$ary['admin_acc'] = '';
		$ary['upAdmin_id'] = NULL;
		if($this->db->total_row < 0){
			return $ary;
		}
		$ary = $this->db->row;
		return $ary;
	}

	/**
	 * 判斷正負數
	 */
	public function is_Negative($num){
		switch ((int)$num){               ////如果n是float或doule則轉成int  
			case 0:                       //如果n=0 
				return false;
				break; 
			default:                      // n!=0 
				switch (abs((int)$num)/(int)$num){ //如果n是float或doule則轉成int 
					case 1: 
						// printf("positive\n");
						return false;
						// return 'positive';
					case -1: 
						// printf("negative\n"); 
						return true;
						// return 'negative';
				} 
		} 
	}

	/**
	 * 檢查玩家有沒有輸贏點數資料表
	 */
	public function chkDBTableExist($tableName){
		if(empty($tableName)){
			return false;
		}
		$db = new PDO_DB(DB_HOST, DB_USER, DB_PWD, $this->DB_WINLOSE);
		$db->selectTB('sys.sysobjects');
		$db->getData("COUNT(*) AS Expr1","WHERE xtype = 'U' AND name = '".$tableName."'");
		$db->execute();
		if($db->row['Expr1'] == '1'){
			unset($db);
			return true;
		}
		unset($db);
		return false;
	}

	/**
	 * 取得輸贏資料表名稱
	 */
	public function getWinLoseTBName($id)
	{
		if(empty($id)){
			return false;
		}
		return $this->table = $this->tablePrefix.$id;
	}

	/**
	 * By Id檢查資料表是否存在
	 */
	public function chkDBTableExistById($id)
	{
		if(empty($id)){
			return false;
		}
		return $this->chkDBTableExist($this->getWinLoseTBName($id));
	}

	/**
	 * 取得輸贏記錄使用分頁
	 * @param  [type] $row_per_page 每頁筆數
	 * @param  [type] $startDate    ex:2015-05-01
	 * @param  [type] $endDate      ex:2015-05-07
	 */
	public function getWinLoseRecords($row_per_page, $startDate, $endDate, $GameId='all', $Game_Borad_ID='', $barLength='10')
	{
		$startDateTime = $startDate.' '.$this->StartTime;
		$endDateTime   = $endDate.' '.$this->EndTime;

		if(empty($this->table)){
			return false;
		}
		$sql = "WHERE WinLose IS NOT NULL
		  AND CreateDate >= '".$startDateTime."'
		  AND CreateDate <= '".$endDateTime."'";
		if(!empty($GameId) && $GameId != 'all'){
			$sql .= "AND GameId = '".$GameId."'";
		}
		if(!empty($Game_Borad_ID) && $Game_Borad_ID != ''){
			$sql .= "AND (CAST(RoundCode as VARCHAR(256)) + CAST(RoundId  as VARCHAR(256))) = '".$Game_Borad_ID."'";
		}

		$db = new PDO_DB(DB_HOST, DB_USER, DB_PWD, $this->DB_WINLOSE);
		$db->selectTB($this->table);
		$db->getData("*", $sql);
		$db->pagingMSSQL($row_per_page, 'CreateDate');
		$result = $db->execute();
		$this->setRecordInfo($db);//記錄頁面搜尋資訊
		if($db->total_row > 0){
			do{
				$db->row['Bet']           = $db->row['Bet']/$this->MoneyPointRate;
				$db->row['WinLose']       = $db->row['WinLose']/$this->MoneyPointRate;
				$db->row['GetJPMoney']    = $db->row['GetJPMoney']/$this->MoneyPointRate;
				$db->row['CurrentPoints'] = $db->row['CurrentPoints']/$this->MoneyPointRate;
				$this->row[] = $db->row;
			}while($db->row = $db->fetch_assoc());
			$this->pagination = $db->createNumBar($barLength, true);
		}
		unset($db);
		return $this->row;
	}

	/**
	 * 取得輸贏記錄搜尋每一個資料表並且丟出使用者名稱
	 */
	public function getWinLoseRecords_DBName($Game_Borad_ID='')
	{
		$this->table = "Game_WinloseLog";
		if(!empty($Game_Borad_ID) && $Game_Borad_ID != ''){
			$db = new PDO_DB(DB_HOST, DB_USER, DB_PWD, $this->DB_WINLOSE);
			$db->selectTB("sysobjects");
			$db->getData("name", "where [type] = 'u'");
			$result = $db->execute();

			$DB_Array = [];
			$Out_Name = "";

			if($db->total_row > 0){
				$DB_Num = 0;
				do{
					$DB_Array[$DB_Num] = $db->row['name'];
					$DB_Num++;
				}while($db->row = $db->fetch_assoc());
			}
			unset($db);

			$db = new PDO_DB(DB_HOST, DB_USER, DB_PWD, $this->DB_WINLOSE);
			for($x=0;$x<$DB_Num;$x++){
				$db->selectTB($DB_Array[$x]);
				$db->getData("*", "where (CAST(RoundCode as VARCHAR(256)) + CAST(RoundId  as VARCHAR(256))) = '".$Game_Borad_ID."'");
				$result = $db->execute();
				if($db->total_row > 0){
					$Out_Name = $DB_Array[$x];
					$x = $DB_Num;
				}
			}
			unset($db);

			$db = new PDO_DB(DB_HOST, DB_USER, DB_PWD, "Game_Main");
			$db->selectTB("A_Member");
			$db->getData("MemberAccount", "where MemberID = '".str_replace("Winlose_","",$Out_Name)."'");
			$result = $db->execute();

			if($db->total_row > 0){
				do{
					$Out_Name = $db->row['MemberAccount'];
				}while($db->row = $db->fetch_assoc());
			}
			unset($db);
			return $Out_Name;
		}
	}

	/**
	 * 取得單筆輸贏紀錄
	 */
	public function getSingleWinLoseLog($MemberID, $RoundCode, $RoundId)
	{
		$db = new PDO_DB(DB_HOST, DB_USER, DB_PWD, $this->DB_WINLOSE);
		$db->selectTB($this->table);
		$db->getData("*", "WHERE WinLose IS NOT NULL
			 AND RoundCode = '".$RoundCode."'
			 AND RoundId = '".$RoundId."'");
		$result = $db->execute();
		if($db->total_row > 0){
			$this->row                  = $db->row;
			$this->row                  = $this->setWinLoseState($this->row);
			$this->row['Bet']           = $this->row['Bet']/$this->MoneyPointRate;
			$this->row['WinLose']       = $this->row['WinLose']/$this->MoneyPointRate;
			$this->row['CurrentPoints'] = $this->row['CurrentPoints']/$this->MoneyPointRate;
		}
		unset($db);
		return $this->row;
	}

	/**
	 * 取得最新投注結果並修改其狀態表示已被撈取過
	 */
	public function getNewRecordNSet($MemberID, $MemberAccount)
	{
		$db = new PDO_DB(DB_HOST, DB_USER, DB_PWD, $this->DB_ANALYSIS_NAME);
		$db->selectTB('MemberWinLose_Tmp');
		$db->getData("*", "WHERE Status = '0' AND MemberId = '".$MemberID."'");
		$result = $db->execute();
		if($db->total_row > 0){
			do{
				$row = [];
				$row['MemberId']      = $db->row['MemberId'];
				$row['MemberAccount'] = $MemberAccount;
				$row['RoundId']       = $db->row['RoundId'];
				$row['RoundCode']     = $db->row['RoundCode'];
				$row['CommBase']      = $db->row['CommBase'];
				$row['Bet']           = $db->row['Bet']/$this->MoneyPointRate;
				$row['GetJPMoney']    = $db->row['GetJPMoney']/$this->MoneyPointRate;
				$row['WinLose']       = $db->row['WinLose']/$this->MoneyPointRate;
				$row['Rounds']        = $db->row['Rounds'];
				$row['CreateDate']    = $db->row['CreateDate'];

				$this->row[] = $row;
			}while($db->row = $db->fetch_assoc());
		}
		unset($db);
		return $this->row;
	}

	/**
	 * 設定分頁資訊
	 */
	public function setRecordInfo(PDO_DB $db)
	{
		include_once $_SERVER['DOCUMENT_ROOT'].'/class/Language.class.php';
		$lang = new Language();
		$langAry = $lang->getLanguage();
		unset($lang);
		$recordInfo = $db->recordInfo();
		$recordStyle = $langAry['RecordInfoStyle'];
		$this->recordInfo = sprintf($recordStyle, 
			$recordInfo['current_page'],
			$recordInfo['total_page'],
			$recordInfo['current_record'],
			$recordInfo['current_record_max'],
			$recordInfo['total_records']
		);
	}

	/**
	 * 分析WinLoseState狀態
	 */
	public function setWinLoseState($row)
	{
		$WinLoseState = explode(";", $row['WinLoseState']);
		$row['WinLoseState']    = $WinLoseState[0];
		$row['WinLoseStateAry'] = empty($WinLoseState[1]) ? '' : json_decode($WinLoseState[1], true);
		return $row;
	}

	/**
	 * 分析圖片資訊By 遊戲
	 */
	public function getGameDetail($GameId, $result=[])
	{
		$gameImgLoca = $this->gameImgLoca.$GameId.'/%s.png';
		$response    = [];

		if(!is_array($result)){
			return false;
		}
		switch ($GameId) {
			case '15'://水果盤
			case '16'://小瑪麗
			case '17'://5PK
			case '24'://5龍
			case '31'://高速公路王
			case '32'://海豚
				if(empty($result['Bank'])){
					return false;
				}
				$Bank   = explode(",", $result['Bank']);
				foreach ($Bank as $value) {
					$filename = sprintf($gameImgLoca, trim($value));
					if(file_exists($_SERVER['DOCUMENT_ROOT'].$filename)){
						$response['Bank'][] = $filename;
					}else{
						$response['LostFile'][] = $filename;
					}
				}
				return $response;
			case '25'://浩克
				if(empty($result['Bank'])){
					return false;
				}
				$Bank   = explode(",", $result['Bank']);
				$prefix = '0100';
				foreach ($Bank as $value) {
					$filename = sprintf($gameImgLoca, $prefix.(str_pad($value, 2, "0", STR_PAD_LEFT)));
					if(file_exists($_SERVER['DOCUMENT_ROOT'].$filename)){
						$response['Bank'][] = $filename;
					}
				}
				return $response;
			case '26'://足球女郎
				if(empty($result['Bank'])){
					return false;
				}
				$Bank   = explode(",", $result['Bank']);
				$prefix = 'FlootGirls0100';
				foreach ($Bank as $value) {
					$filename = sprintf($gameImgLoca, $prefix.(str_pad($value, 2, "0", STR_PAD_LEFT)));
					if(file_exists($_SERVER['DOCUMENT_ROOT'].$filename)){
						$response['Bank'][] = $filename;
					}
				}
				return $response;
			case '23'://龍虎
			case '27'://百家樂
				if(empty($result['Bank']) || empty($result['Player'])){
					return false;
				}
				$Bank   = explode(",", $result['Bank']);
				$Player = explode(",", $result['Player']);
				$prefix = 'Baca00';
				foreach ($Bank as $value) {
					$filename = sprintf($gameImgLoca, $prefix.(str_pad($value, 2, "0", STR_PAD_LEFT)));
					if(file_exists($_SERVER['DOCUMENT_ROOT'].$filename)){
						$response['Bank'][] = $filename;
					}
				}
				foreach ($Player as $value) {
					$filename = sprintf($gameImgLoca, $prefix.(str_pad($value, 2, "0", STR_PAD_LEFT)));
					if(file_exists($_SERVER['DOCUMENT_ROOT'].$filename)){
						$response['Player'][] = $filename;
					}
				}
				return $response;
			case '28'://骰寶
				if(empty($result['Bank'])){
					return false;
				}
				$Bank   = explode(",", $result['Bank']);
				$prefix = 'Sicbo00';
				foreach ($Bank as $value) {
					$filename = sprintf($gameImgLoca, $prefix.(str_pad($value, 2, "0", STR_PAD_LEFT)));
					if(file_exists($_SERVER['DOCUMENT_ROOT'].$filename)){
						$response['Bank'][] = $filename;
					}
				}
				return $response;
			case '29'://輪盤
				if($result['Bank'] == ''){
					return false;
				}
				$Bank   = explode(",", $result['Bank']);
				$prefix = '00';
				foreach ($Bank as $value) {
					$filename = sprintf($gameImgLoca, $prefix.(str_pad(($value+1), 2, "0", STR_PAD_LEFT)));
					if(file_exists($_SERVER['DOCUMENT_ROOT'].$filename)){
						$response['Bank'][] = $filename;
					}
				}
				return $response;
			case '30'://猴子爬樹
				if($result['Bank'] == ''){
					return false;
				}
				$Bank = explode(",", $result['Bank']);
				$prefix = '';
				foreach ($Bank as $value) {
					$filename = sprintf($gameImgLoca, trim($value));
					if(file_exists($_SERVER['DOCUMENT_ROOT'].$filename)){
						$response['Bank'][] = $filename;
					}
				}
				if($result['BPT'] == ''){
					return false;
				}
				$BPT = explode(",", $result['BPT']);
				$prefix = 'BPT';
				foreach ($BPT as $value) {
					$filename = sprintf($gameImgLoca, $prefix.trim($value));
					if(file_exists($_SERVER['DOCUMENT_ROOT'].$filename)){
						$response['BPT'] = $filename;
					}
				}
				return $response;
			case '33'://賽車小瑪麗
				if($result['Bank'] == ''){
					return false;
				}
				$Bank = explode(",", $result['Bank']);
				$prefix = 'Bank/';
				foreach ($Bank as $value) {
					$filename = sprintf($gameImgLoca, $prefix.trim($value));
					if(file_exists($_SERVER['DOCUMENT_ROOT'].$filename)){
						$response['Bank'][] = $filename;
					}else{
						$response['LostFile'][] = $filename;
					}
				}
				if($result['BPT'] == ''){
					return false;
				}
				$BPT = explode(",", $result['BPT']);
				$prefix = 'BPT/';
				foreach ($BPT as $value) {
					$filename = sprintf($gameImgLoca, $prefix.trim($value));
					if(file_exists($_SERVER['DOCUMENT_ROOT'].$filename)){
						$response['BPT'] = $filename;
					}
				}
				return $response;
			case '37':
				return $response = $result;
			default:
				return false;
		}
		return false;
	}

	public function dd($mixed)
	{
		if(is_array($mixed)){
			echo '<pre>';
		}
		print_r($mixed);
		exit;
	}
}
?>