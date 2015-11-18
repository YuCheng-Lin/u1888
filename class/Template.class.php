<?php
/**
 * 样版
 * "<!--__MainContent__-->" 为样版内需要替换的标签
 */
class Template{
	
	/**
	 * 系统设定档
	 * @var Array
	 */
	var $__systemConfig = array();
	
	/**
	 * 当前页面路径
	 * @var String
	 */
	var $URI = '';
	
	/**
	 * 同档名样版(含路径)
	 * @var String
	 */
	var $fileName = '';
	
	
	/**
	 * 共用样版
	 * @var String
	 */
	var $publicTemp = "";
	
	/**
	 * 含特定隐藏区块的样版
	 * @var String
	 */
	var $notReplacedTemp = '';
	
	
	/**
	 * 不含特定隐藏区块的样版
	 * @var String
	 */
	var $finalTemp = '';
	
	
	/**
	 * 登入模组
	 * @var Object
	 */
	var $WebLogin;

	
	/**
	* 允许访问IP
	* @var Array
	*/
	var $allowIP = array("127.0.0.1");//本机

	/**
	* 語言陣列
	*/
	var $lang = array();

	/**
	 * 預設是否開放分紅返水設定
	 */
	var $RateSettingSwitch = 'n';

	/**
	 * 維護時間
	 */
	var $maintainDay = 'Monday';

	/**
	 * 維護開始時間
	 */
	var $maintainStartTime = '00:00:00';

	/**
	 * 報表計算開始時間
	 */
	public $StartTime = '00:00:00';

	/**
	 * 報表計算結束時間
	 */
	public $EndTime = '23:59:59';

	/**
	 * 期數產生設定
	 */
	public $dateCount = 4;
	
	/**
	 * 建构子
	 * @param $publicTemp String 共用样版路径,"empty":载入空白样版。
	 * @param $unLoginKick Boolean 未登入时是否导页。
	 * 
	 */
	function Template($publicTempPath='empty', $unLoginKick=true){
		
		//判断浏览器版本(IE)
		if(!$this->chkUserBrowserVer()){
			$this->__systemConfig['__updateBrowser__'] = '<div style="color: red; text-align: center; padding: 0pt; margin: 0pt; font-size: 17px; font-weight: bold;">强烈建议升级您的浏览器</div>';
		}
		
		if($publicTempPath == 'empty'){
			$this->publicTemp = '';
		}else{
			if(file_exists($publicTempPath) && $publicTempPath != ''){
				//存至共用样版
				$this->publicTemp = file_get_contents($publicTempPath);
			}else{
				die("Public Template is not find.");
			}
		}
		$this->URI = $_SERVER['REQUEST_URI'];
		if($this->URI == '/'){
			$this->URI = '/index.php';
		}

		try{
			include_once $_SERVER['DOCUMENT_ROOT'].'/class/Language.class.php';
			$language = new Language();
			$this->lang = $language->getLanguage(@$_POST['lang']);
			foreach ($this->lang as $key => $value) {
				$this->__systemConfig['__Lang_'.$key.'__'] = $value;
			}
			$this->__systemConfig['__LangSelected_'.$_SESSION['LANGUAGE'].'__'] = ' selected="selected"';
		}catch(Exception $e){
		}

		//常用设定(依索引值排列)		
		$this->__systemConfig['__sendReady__']        = $this->lang['IndexErrorMsg1'];
		$this->__systemConfig['__errAcc__']           = $this->lang['IndexErrorMsg2'];
		$this->__systemConfig['__illegal__']          = $this->lang['IndexErrorMsg3'];
		$this->__systemConfig['__userOnline__']       = $this->lang['IndexErrorMsg4'];	//使用者正在线上。
		$this->__systemConfig['__noPermission__']     = $this->lang['IndexErrorMsg5'];
		$this->__systemConfig['__noPagePermission__'] = $this->lang['IndexErrorMsg6'];
		$this->__systemConfig['__notFound__']         = '<div class="alert alert-danger text-center">'.$this->lang['IndexErrorMsg7'].'</div>';
		$this->__systemConfig['__notAllowSearch__']   = '<div class="alert alert-danger text-center">'.$this->lang['IndexErrorMsg8'].'</div>';
		$this->__systemConfig['__logOutSuccess__']    = $this->lang['IndexLogoutMsg'];
		$this->__systemConfig['__updateSuccess__']    = $this->lang['IndexUpdPwdMsg'];
		$this->__systemConfig['__logOutBtn__']        = '<a href="/logout.php">'.$this->lang['LogoutTitle'].'</a>';
		
		//登入模组
		include_once $_SERVER['DOCUMENT_ROOT'].'/class/WebLogin.class.php';
		$this->WebLogin = new WebLogin();
		//IP阻挡
// 		$userIP = $this->WebLogin->getUserIp();
// 		if(!in_array($userIP, $this->allowIP)){
// 			$this->WebLogin->logout();
// 		}
		//資料庫設定值
		$db = new PDO_DB(DB_HOST, DB_USER, DB_PWD, DB_NAME);
		$db->selectTB('SysValue');
		$db->getData("*");
		$db->execute();
		if($db->total_row > 0){
			do{
				$SysKey = $db->row['SysKey'];
				$this->$SysKey = $db->row['SysValue'];
			}while($db->row = $db->fetch_assoc());
		}
		unset($db);

		//错误输出
		if(!empty($_SESSION['_err'])){
			$sysErrAry = json_decode($_SESSION['_err'], true);
			$errJs     = '<script>$(function(){alert("'.$sysErrAry['msg'].'");});</script>';
			unset($_SESSION['_err']);
			$this->__systemConfig['__SysJavascriptImport__']	= $errJs;
		}		
		
		//判断是否有登入
		if(!empty($_SESSION['_admin_acc']) && !empty($_SESSION['_admin_pwd'])){
			$aid = $_SESSION['_admin_id'];
			$acc = $_SESSION['_admin_acc'];
			$pwd = $_SESSION['_admin_pwd'];
			$this->WebLogin->chkLogin($acc, $pwd);
		}else{
			$aid = '';
			$acc = '';
			$pwd = '';
			$this->WebLogin->logout();
		}
		//检测登入状态
		$this->WebLogin->getStatus();
		
		switch($this->WebLogin->loginStatus){
			case '0':	//已登入
				$this->__systemConfig['__adminName__'] = $this->WebLogin->admin_name;	//管理者名称
				//导览选单(依权限载入选单)
				$mainNavHtml = $this->WebLogin->getPowerMenu("nav navbar-nav", $this->lang); //指定选单ul的class 
				$this->__systemConfig['__mainNav__']     = $mainNavHtml;	//导览选单
				$this->__systemConfig['__pageName__']    = $this->WebLogin->nowPageTitle;	//本页标题
				$this->__systemConfig['__perInfoMenu__'] = $this->WebLogin->perInfoMenu;
				if($_SESSION['LANGUAGE'] != 'en'){
					$this->__systemConfig['__Lang_ValidateMsgSrc__'] = '<script type="text/javascript" src="/js_v2/jquery-validation-1.13.1/localization/messages_'.$_SESSION['LANGUAGE'].'.js"> </script>';
				}
				if(EMERGENCY == 'y' && in_array($this->WebLogin->ag_id, ['3'])){
					$alarm    = '<button data-whatever="'.$this->lang['AlarmModal'].'" data-toggle="tooltip" data-placement="top" title="'.$this->lang['AlarmModal'].'" data-target="#dialogModal" data-fn="alarmSingle" data-id="'.$this->WebLogin->admin_id.'" class="dialogModal btn btn-danger pull-right" data-public="public" type="button" style="margin: 5px;"><span class="glyphicon glyphicon-flash" aria-hidden="true"></span></button>';//20150720-增加緊急處理按鈕
					$disAlarm = '<button data-whatever="'.$this->lang['DisAlarmModal'].'" data-toggle="tooltip" data-placement="top" title="'.$this->lang['DisAlarmModal'].'" data-target="#dialogModal" data-fn="disAlarmSingle" data-id="'.$this->WebLogin->admin_id.'" class="dialogModal btn btn-success pull-right" data-public="public" type="button" style="margin: 5px;"><span class="glyphicon glyphicon-flash" aria-hidden="true"></span></button>';//20150720-增加緊急處理按鈕
					if($this->WebLogin->alarmType == 'y'){
						$this->__systemConfig['__EmergencyBtn__'] = $disAlarm;
					}else{
						$this->__systemConfig['__EmergencyBtn__'] = $alarm;
					}
				}
			break;
			
			default:	//未登入
				$this->WebLogin->logout();
				if($unLoginKick){
					$_SESSION['err'] = $this->WebLogin->loginStatus;
					header("location: /index.php");
					exit;
				}
			break;
		}
		
	}

	function dateArray($nowTime=''){
		// $nowTime = '2015-04-26 23:00:00';
		$nowTime = empty($nowTime) ? date('Y-m-d H:i:s') : $nowTime;
		$setTime = $this->dateCheck($nowTime);

		$result = array();
		if(!empty($setTime)){
			for ($i=$this->dateCount; $i > 0 ; $i--) { 
				$result[] = $this->d(strtotime('-'.$i.' week '.$this->maintainDay.' '.$setTime));
			}
			// $result[] = $this->d(strtotime('-4 week '.$this->maintainDay.' '.$setTime));
			// $result[] = $this->d(strtotime('-3 week '.$this->maintainDay.' '.$setTime));
			// $result[] = $this->d(strtotime('-2 week '.$this->maintainDay.' '.$setTime));
			// $result[] = $this->d(strtotime('-1 week '.$this->maintainDay.' '.$setTime));
			$result[] = $this->d(strtotime($this->maintainDay.' this week '.$setTime));
		}
		$lastDayAry = [];
		foreach ($result as $value) {
			$lastDayAry[] = $this->d(strtotime('-1 day '.$value.' '.$this->maintainStartTime));
		}
		// echo '<pre>';
		// print_r($result);
		// print_r($lastDayAry);
		// exit;
		return ['start' => $result , 'end' => $lastDayAry];
	}

	function d($strtotime){
		return date('Y-m-d', $strtotime);
	}

	function dateCheck($nowTime){
		$setTime = '';
		//如果今天是維護當天
		if($this->d(strtotime($nowTime)) == $this->d(strtotime($this->maintainDay.' this week '.$nowTime))
		 && $nowTime < date('Y-m-d '.$this->maintainStartTime, strtotime(''.$this->maintainDay.' this week '.$nowTime))
			){
			$setTime = $this->d(strtotime($this->maintainDay.' '.$nowTime));
		}
		if($this->d(strtotime($nowTime)) == $this->d(strtotime($this->maintainDay.' this week '.$nowTime))
		 && $nowTime >= date('Y-m-d '.$this->maintainStartTime, strtotime(''.$this->maintainDay.' this week '.$nowTime))
			){
			$setTime = $this->d(strtotime('+1 week '.$this->maintainDay.' '.$nowTime));
		}
		//如果今天是星期日必須設定時間為
		if($this->d(strtotime($nowTime)) == $this->d(strtotime('Sunday last week '.$nowTime))){
			$setTime = $this->d(strtotime($this->maintainDay.' this week '.$nowTime));
		}
		if(empty($setTime)){
			$setTime = $this->d(strtotime($this->maintainDay.' '.$nowTime));
		}
		return $setTime;
	}
	

	
	/**
	 * 检查浏览器版本(IE7(含)以下的版本禁止使用。)
	 */
	function chkUserBrowserVer(){
		if (preg_match('/(?i)msie [5-8]/i', $_SERVER['HTTP_USER_AGENT'])) {
			//IE >=5 && IE <= 8
//			echo "我是烂IE(IE8含以下)";
			return false;
		} else {
			// other browser
//			echo "我是高智商的浏览器。";
			return true;
		}
	}
	
	
	
	
	
	/**
	 * 载入外部模组(类别)
	 * 模组(类别)档名需与模组(类别)名称相同
	 * @param String $modPath
	 */
	function loadMod($modPath){
		
		if(file_exists($modPath)){
			include_once $modPath;
			
			$modFileNameAry = explode("/", $modPath);
			$modFileName = array_pop($modFileNameAry);
			
			$modNameAry = explode(".", $modFileName);
			$modName = $modNameAry[0];
			
			$this->$modName = new $modName();
			return $this->$modName; 
		}
		
	}	
	
	
	/**
	 * 重构URI，更改为样版路径
	 * @param $uri String
	 */
	function URIExplode($uri){
		if($uri != ''){
			$filePath = explode(".", $uri);
			$this->fileName = $_SERVER['DOCUMENT_ROOT']."/tpl".$filePath[0].".html";
			return true;
		}
		return false;
	}
	
	
	/**
	 * 取出单一区块样版
	 * @param $initTemp String 样版路径，如不指定，会直接抓取tpl下，同位置同档名的样版，单档取代不需指定。
	 */
	function getFile($initTemp=''){
		
		if($initTemp != ''){
			if(file_exists($initTemp)){
				
				//次样版(与共用样版合成使用)
				$init_temp = file_get_contents($initTemp);
				
				if (preg_match('%<body>.*</body>%si', $init_temp, $regs)) {
					$init_temp = $regs[0];
					$init_temp = preg_replace('%(<body>|</body>)%si', '', $init_temp);
				}
				
				//当载入空白样版时，设定为单档取代(自己取代自己)
				if($this->publicTemp == ''){
					$this->publicTemp = $init_temp;
				}
				
				return $init_temp;
			}
			die('Init Template is no find('. $initTemp .').');
		}else{
			
			if($this->URIExplode($this->URI)){
				if(file_exists($this->fileName)){
					
					$init_temp = file_get_contents($this->fileName);
					if (preg_match('%<body>.*</body>%si', $init_temp, $regs)) {
						$init_temp = $regs[0];
						$init_temp = preg_replace('%(<body>|</body>)%si', '', $init_temp);
					}
					
					//当载入空白样版时，设定为单档取代(自己取代自己)
					if($this->publicTemp == ''){
						$this->publicTemp = $init_temp;
					}
					
					return $init_temp;
				}
				die("Not find Template File(". $this->fileName .").");
			}
			die("Not find Template File(". $this->URI .").");
		}
		
		
	}
	
	
	
	
	
	/**
	 * 正规表达式，替换样版内特定区块内容
	 * @param $ary Array 欲取代之资料
	 * @param $html String 页面资料
	 * @param $prefix String 前缀字串
	 * @param $suffix String 后缀字串
	 * @param $chgCol Boolen 更改醒目颜色
	 * @return 重组后的页面资料
	 */
	function regexReplace($ary, $html, $prefix='__', $suffix='__', $chgCol=false){
		reset($ary);
		while (!is_null($key = key($ary) ) ) {
			if (preg_match('/'.$prefix.$key.$suffix.'/s', $html)) {
				if($chgCol && $chgCol != ''){
					$ary[$key] = str_replace($chgCol, "<span style=\"color:red;\">".$chgCol."</span>", $ary[$key]);
				}
				$html = preg_replace('/'.$prefix.$key.$suffix.'/s', $ary[$key], $html);
			}
			next($ary);
		}
		return $html;
	}
	
	
	/**
	 * 正规表达式，取出样版内特定区块内容
	 * @param $subject String 样版
	 * @param $prefix String 区块前缀字串
	 * @param $suffix String 区块后缀字串
	 */
	function regexMatch($subject, $prefix, $suffix){
		if (preg_match('/'. $prefix .'.*'. $suffix .'/s', $subject, $regs)) {
			//比对成功时回传
			$result = $regs[0];
			
			//去除前缀字串
			$result = preg_replace('/'. $prefix .'/s', '', $result);
			//去除后缀字串
			$result = preg_replace('/'. $suffix .'/s', '', $result);
			
			return $result;
		} else {
			//比对不成功时，不动作
//			die('/'. $prefix .'.*'. $suffix .'/s Pairing is not successful.');
			
			return false;
		}
	}
	
	
	
	
	/**
	 * 重新封装完整版面(最后才使用)
	 * @param $replaceAry Array 欲取代的内容阵列array(keyword=>content);
	 */
	function compiler($replaceAry=''){
		
		//取代自定义资料
		if(is_array($replaceAry)){
			$html = $this->regexReplace($replaceAry, $this->publicTemp, "<!--", "-->");
		}else{
			$html = $this->publicTemp;
		}
		
		$this->__systemConfig['__.*?__'] = "";	//重要，清除所有未执行取代的标签, 务必放置于最后。
		//取代基本设定资料
		$html = $this->regexReplace($this->__systemConfig, $html, "<!--", "-->");
		
		return $html;
		
	}
	
	
	
	/**
	 * Desteuctor 解构子
	 * 物件结束
	 */
	function __destruct(){
		
		unset($this->login);
		
	}	
	
	
	
	
	
}
?>