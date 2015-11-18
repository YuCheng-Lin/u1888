<?php
header("Content-Type:text/html; charset=utf-8");

//启用session
if (!isset($_SESSION)) {
	session_start();
}


//载入资料库
include_once $_SERVER['DOCUMENT_ROOT'].'/class/PDO_DB.class.php';
//选单物件
include_once $_SERVER['DOCUMENT_ROOT'].'/class/mainNav.class.php';

//登入系统
class WebLogin extends mainNav{
	/**
	 * 帐号密码资料表
	 * @var String
	 */
	var $table;
	
	/**
	 * 管理者登入的session_id
	 * @var String
	 */
	var $s_id;
	
	/**
	 * 管理者编号
	 * @var Int
	 */
	var $admin_id;
	
	/**
	 * 管理者帐号
	 * @var String
	 */
	var $admin_acc;
	
	/**
	 * 管理者密码
	 * @var String
	 */
	var $admin_pwd;
	
	/**
	 * 管理者所属单位
	 * @var Int
	 */
	var $ag_id;
	
	/**
	 * 管理者姓名
	 * @var String
	 */
	var $admin_name;
	
	/**
	 * 管理者职称
	 * @var String
	 */
	var $admin_title;
	
	/**
	 * 管理者帐号启用
	 * @var Boolean
	 */
	var $admin_enable = false;
	
	/**
	 * 管理者权限
	 * @var String
	 */
	var $admin_power = array();
	
	/**
	 * 管理者可视权限
	 * @var Boolean
	 */
	var $admin_view = array();
	
	/**
	 * 可登入ip
	 */
	var $allowLoginIp = [];
	
	/**
	 * 当前页面的权限
	 * @var String 空值:无权限，w:读写，r:读
	 */
	var $nowPagePower = '';
	
	/**
	 * 管理者帐号开通时间
	 * @var String
	 */
	var $admin_addtime;
	
	/**
	 * 管理者登入时间
	 * @var String
	 */
	var $admin_login_time = '---';
	
	/**
	 * 管理者最后登入时间
	 * @var String
	 */
	var $admin_last_login_time = '---';
	
	/**
	 * 帐号登入状况
	 * @var String 0:正常登入, 1:帐密任一为空, 2:帐密错误, 3:请依正常方式操作, 4:使用者在线上, 5:帐号遭停权
	 */
	var $loginStatus = '0';
	
	
	/**
	 * 建构子
	 * @param String $db_table 管理者帐号资料表
	 */
	function WebLogin( $db_table = 'Admin'){
		$this->table = $db_table;
	}
	
	
	/**
	 * 管理者登入
	 * @param String $acc 帐号
	 * @param String $pwd 密码
	 * @param String $last4 身份证末四码
	 * @param Boolean $update 是否更新资料库(最后登入时间)
	 * @return boolean
	 */
	public function chkLogin($acc='', $pwd='', $update=false){
		
		if($acc=='' || $pwd==''){
			$this->unsetSession();
			$this->loginStatus = '1';
			return false;
		}
		
		$this->admin_acc 	= trim($acc);
// 		$this->admin_id_last4 	= trim($last4);
		
		if(strlen($pwd) == 32){
			$this->admin_pwd 	= trim($pwd);
		}else{
			$this->admin_pwd 	= trim($pwd);
			// $this->admin_pwd 	= md5(trim($pwd));
		}
		
		
// 		die("len:".strlen($pwd)."----acc:".$this->admin_acc."---pwd:".$this->admin_pwd."---sessionId:".session_id());
		
		$login  = new PDO_DB(DB_HOST, DB_USER, DB_PWD, DB_NAME);
		$login->selectTB($this->table);
		$login->getData("*", "WHERE admin_acc = '".$this->admin_acc."' AND admin_pwd = '".$this->admin_pwd."'");
		$login->execute();
		if($login->total_row > 0 ){
			foreach ($login->row as $k=>$v){
				$this->$k = $v;
			}
			$this->admin_enable  = $login->row['admin_enable'] == 'y' ? true : false ;
			$this->canAddAgent   = $login->row['canAddAgent'] == 'y' ? true : false ;
			$this->s_id          = $update ? '' : $login->row['s_id'];//后踢前
			// $this->s_id       = $login->row['s_id'];//前踢后
			$this->admin_id      = $login->row['admin_id'];
			$this->ag_id         = $login->row['ag_id'];
			$this->admin_addtime = $login->row['admin_addtime'];
			$this->admin_name    = empty($login->row['admin_name']) ? $login->row['admin_acc'] : $login->row['admin_name'];
			
			if(!empty($login->row['admin_login_time'])){
				$this->admin_login_time = $login->row['admin_login_time'];
			}
			
			if(!empty($login->row['admin_last_login_time'])){
				$this->admin_last_login_time = $login->row['admin_last_login_time'];
			}
			
			if(!empty($login->row['admin_power'])){
				$this->admin_power = json_decode($login->row['admin_power'], true);
			}

			if(!empty($login->row['allowLoginIp'])){
				$this->allowLoginIp = explode(",", $login->row['allowLoginIp']);
			}
			
			$_SESSION['_admin_id']	= $login->row['admin_id'];
			$_SESSION['_admin_acc']	= $login->row['admin_acc'];
			$_SESSION['_admin_pwd']	= $login->row['admin_pwd'];
			

			
			if($update){
				//如果帐号状态为启用中
				if($this->admin_enable && empty($this->s_id)){
					//更新资料表 s_id(session_id)
					$updateSID = array();
					$updateSID['s_id']             = session_id();
					$updateSID['admin_login_time'] = date("Y-m-d H:i:s");
					$login->updateData($updateSID, "WHERE admin_id = '".$this->admin_id."'");
					$login->execute();
					$this->admin_login_time = $updateSID['admin_login_time'];
					$this->s_id             = $updateSID['s_id'];
				}
				
			}else{
				//如果登入后，session_id仍为空(有可能管理者从资料库踢人)
				if($this->s_id == ''){
					// $this->unsetSession();
					//请依正常方式操作。
					$this->loginStatus = '3';
				}
			}
			$this->getPagePower();
			// echo '<pre>';
			// print_r($this);
			// exit;
			
			unset($login);
			return true;
			
		}else{
//			session_destroy();
			unset($login);
			// $this->unsetSession();
			$this->loginStatus = '2';
			return false;
		}
	}
	
	
	/**
	 * 取得目前页面权限
	 */
	private function getPagePower(){
		
		$getfilePath = array();
		$getfilePath = explode(".", $_SERVER['REQUEST_URI']);
		
		$filePath = array();
		$filePath = explode("/", $getfilePath[0]);
		
//		array_shift($filePath);
		$fileName = array_pop($filePath); 	//取档名
		$systemName = array_pop($filePath);	//取系统名
		
		$getSystemId = new PDO_DB(DB_HOST, DB_USER, DB_PWD, DB_NAME);
		$getSystemId->selectTB("main_nav");
		$getSystemId->getData("main_id", "WHERE main_name = '". $systemName ."'");
		$getSystemId->execute();
			// echo '<pre>';
			// print_r($getSystemId);
			// exit;
		if($getSystemId->total_row > 0){
			$systemId = $getSystemId->row['main_id'];
			if(isset($this->admin_power[$systemId]) && $this->admin_power[$systemId] != ''){
				$this->nowPagePower = $this->admin_power[$systemId];
				$this->nowPageId = $systemId;	//mainNav Class
			}
		}else{
			//非资料库页面
			$this->nowPagePower = 'r';
		}
		unset($getSystemId);
	}
	
	
	
	
	/**
	 * 确认登入状态
	 * @return String
	 */
	public function getStatus(){
		if($this->loginStatus != '0'){
			// $this->unsetSession();
			return $this->loginStatus;
		}
		//无session记录时
		if(!isset($_SESSION['_admin_acc']) || !isset($_SESSION['_admin_pwd'])){
			// $this->unsetSession();
			//请依正常方式操作。
			$this->loginStatus = '3';
			return $this->loginStatus;
		}
		
		//前踢后
		// if($this->s_id != session_id() && ($this->s_id != null || $this->s_id != '')){
		// 	// $this->unsetSession();
		// 	//使用者正在线上。
		// 	$this->loginStatus = '4';
		// 	return $this->loginStatus;
		// }
		
		//后踢前
		if($this->s_id != session_id() && ($this->s_id != null || $this->s_id != '')){
			$this->unsetSession();
			//使用者正在线上。
			$this->loginStatus = '4';
			return $this->loginStatus;
		}
		
		//帐号停权
		if(!$this->admin_enable){
			$this->logout();
			//帐号停止使用。
			$this->loginStatus = '5';
			return $this->loginStatus;
		}
		
		//本页无权限
		if($this->nowPagePower == ''){
			$this->logout();
			//本页无权限，请重新登入。
			$this->loginStatus = '6';
			return $this->loginStatus;
		}

		//總控台必須檢查ip是否符合登入
		// if($this->ag_id == '2'){
		// 	if(count($this->allowLoginIp) <= 0){
		// 		$this->logout();
		// 		$this->loginStatus = '7';
		// 		return $this->loginStatus;
		// 	}
		// 	if(!in_array($this->getUserIp(), $this->allowLoginIp)){
		// 		$this->logout();
		// 		$this->loginStatus = '8';
		// 		return $this->loginStatus;
		// 	}
		// }

		
		if(!empty($_SESSION['_admin_acc']) && !empty($_SESSION['_admin_pwd'])){
			if($this->chkLogin($_SESSION['_admin_acc'],$_SESSION['_admin_pwd'])){
				$this->loginStatus = '0';
				return $this->loginStatus;
			}else{
				$this->unsetSession();
				//帐密错误
				$this->loginStatus = '2';
				return $this->loginStatus;
			}
		}else{
			//请依正常方式操作。
			$this->loginStatus = '3';
			return $this->loginStatus;
		}
		return $this->loginStatus;
	}
	
	
	/**
	 * 管理者登出
	 * @return boolean
	 */
	public function logout(){
		if(isset($_SESSION['_admin_id']) && $_SESSION['_admin_id'] != 0){
			$this->admin_id = $_SESSION['_admin_id'];
			
			$logout = new PDO_DB(DB_HOST, DB_USER, DB_PWD, DB_NAME);
			$logout->selectTB($this->table);
			$logout->getData("*", "WHERE admin_id = '". $this->admin_id ."'");
			$logout->execute();
			$this->admin_login_time = $logout->row['admin_login_time'];
			$updateAry = array();
			// $updateAry['s_id'] = '';//前踢后机制
			$updateAry['admin_last_login_time'] = $this->admin_login_time;
			$updateAry['last_login_ip']         = $this->getUserIp();
			$logout->updateData($updateAry, "WHERE admin_id = '". $this->admin_id ."'");
			$logout->getCurrentQueryString();
			$logout->execute();
			unset($logout);
		}
		$this->unsetSession();
		return true;
	}
	
	
	/**
	 * 管理者私人选单
	 * @return String 依权限产生选单
	 */
	public function getPowerMenu($ulClass="sf-menu", $langAry=array()){
		$this->mainNav($this->admin_power, $langAry);
		return $this->getTree(0, $ulClass);	//sf-menu为 css样式
	}
	
	/**
	 * 清空Login 注册的session
	 */
	public function unsetSession(){
		unset($_SESSION['_admin_id']);
		unset($_SESSION['_admin_acc']);
		unset($_SESSION['_admin_pwd']);
	}
	
	/**
	*  取得用户IP位置
	*/
	public function getUserIp(){
		if(!empty($_SERVER["HTTP_CLIENT_IP"])){
			$cip = $_SERVER["HTTP_CLIENT_IP"];
		}else if(!empty($_SERVER["HTTP_X_FORWARDED_FOR"])){
			$cip = $_SERVER["HTTP_X_FORWARDED_FOR"];
		}else if(!empty($_SERVER["REMOTE_ADDR"])){
			$cip = $_SERVER["REMOTE_ADDR"];
		}else{
			$cip = "error!";
		}
		return $cip;
	}
	
	/**
	 * 與Java AP 溝通 
	 * @param $command 指令
	 * @param $ip AP IP
	 * @param $port AP Port
	 * @param $back 接收回應
	 * 
	 * // 註冊登入
	 * 【100,Login,mid,sid】
	 *  1X002 SID 重複註冊
	 *  1X003 SID 與資料庫比對錯誤
	 *  1X004 查無此帳號
	 * 
	 * // 登出
	 * 【110,Logout,mid,sid】
	 * 【111,Logout,CODE】
	 *  CODE: 1X001 登出成功
	 * 
	 * // 定時回應
	 * 【120,AutoReply,mid,sid】
	 * 【121,AutoReply,CODE】
	 *  CODE: 1X001 成功
	 *  1X002 HASH 中無此 SID
	 */
	public function javaConnnection($command,$ip,$port,$back=false){
	    
		$get = "";
        //server IP, server Port, errno, errstr, timeout seconds
        $fp = @fsockopen($ip, $port, $errno, $errstr, 5);
        
        if (!$fp) {
            return "Server error";
            //echo "<script>alert('$errno:$errstr');</script>";
        } else {
            fwrite($fp, $command."\n");
            if($back){
                    while (!feof($fp)) {
                            $get.= fgets($fp, 128);
                    }
            }
            fclose($fp);
        }
        return $get;
	}
}
?>