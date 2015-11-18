<?php
class GetMacAddr 
{ 
	var $return_array = array(); // 返回带有MAC地址的字符串数组 
	var $mac_addr=array(); 

	public function GetMacAddr() 
	{ 
	} 

	public function GetServerMac()
	{
		switch (strtolower(PHP_OS) ) 
		{ 
			case "linux":$this->getServerforLinux();break; 
			case "solaris":break; 
			case "unix":break; 
			case "aix":break; 
			default:$this->getServerforWindows();break; 
		}   

		$temp_array = array(); 
		foreach ( $this->return_array as $value ) 
		{ 
			if ( preg_match( "/[0-9a-f][0-9a-f][:-]"."[0-9a-f][0-9a-f][:-]".
			"[0-9a-f][0-9a-f][:-]"."[0-9a-f][0-9a-f][:-]"."[0-9a-f][0-9a-f][:-]"."[0-9a-f][0-9a-f]/i", $value, $temp_array ) ) 
			{ 
				$this->mac_addr[] = $temp_array[0]; 
			} 
		} 
		unset($temp_array); 
		return $this->mac_addr; 
	}
  
	public function getServerforWindows() 
	{ 
		@exec("ipconfig /all", $this->return_array); 
		if ( $this->return_array ) 
			return $this->return_array; 
		else{ 
			$ipconfig = $_SERVER["WINDIR"]."\system32\ipconfig.exe"; 
			if ( is_file($ipconfig) ) 
				@exec($ipconfig." /all", $this->return_array); 
			else 
				@exec($_SERVER["WINDIR"]."\system\ipconfig.exe /all", $this->return_array); 
			return $this->return_array; 
		} 
	} 
  
	public function getServerforLinux() 
	{ 
		@exec("ifconfig -a", $this->return_array); 
		return $this->return_array; 
	}

	public function GetClientMac() { 
		$return_array = array(); 
		$temp_array   = array(); 
		$mac_addr     = ""; 
		@exec("arp -a",$return_array); 
		foreach($return_array as $value) { 
			if(strPos($value,$_SERVER["REMOTE_ADDR"]) !== false && preg_match("/(:?[0-9a-f]{2}[:-]){5}[0-9a-f]{2}/i",$value,$temp_array)) { 
				$mac_addr = $temp_array[0]; 
				break; 
			} 
		} 
		return ($mac_addr); 
	}
} 
 
 //调用示例
 $mac = new GetMacAddr(); 
 echo "<pre>"; 
 print_r( $mac->GetServerMac()); 
 print_r( $mac->GetClientMac()); 

function get_mac()
{
	//輸出完整指令
	exec("nbtstat -A ".$_SERVER["REMOTE_ADDR"],$list);
	$temp_array = array();
	foreach ( $list as $value ){
		if ( preg_match( "/[0-9a-f][0-9a-f][:-]". "[0-9a-f][0-9a-f][:-]". "[0-9a-f][0-9a-f][:-]". "[0-9a-f][0-9a-f][:-]". "[0-9a-f][0-9a-f][:-]". "[0-9a-f][0-9a-f]/i", $value, $temp_array ) ){
			return $temp_array[0];
		}
	}
}
$macaddress = get_mac();
echo "<br>MAC: ".$macaddress;
exit;
?>