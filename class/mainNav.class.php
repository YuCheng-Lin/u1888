<?php
//载入资料库
include_once dirname(__FILE__).'/PDO_DB.class.php';

class mainNav {
	
	/**
	 * 选单导览资料表
	 * @var String
	 */
	var $main_table = 'dbo.main_nav';
	
	/**
	 * 目前选定的功能项 name
	 * @var String
	 */
	var $nowPageName = '';
	
	/**
	 * 目前选定的功能项 id
	 * @var Int
	 */
	var $nowPageId = 0;
	
	/**
	 * 目前选定的功能项父节点
	 * @var Int
	 */
	var $nowPageParent = 0;
	
	/**
	 * 当前页面的中文名称 title
	 * @var String
	 */
	var $nowPageTitle = '';
	
	
	/**
	 * 所有选单
	 * @var Array
	 */
	var $mykey = array(); 
	
	/**
	 * 所有选单父系id
	 * @var Array
	 */
	var $myupid = array();
	
	
	/**
	 * 根节点
	 * @var Int
	 */
	var $root;
	
	/**
	 * 个人权限(mainNav table id)
	 * @var Array (二维)
	 */
	var $proPower = array();
	
	
	/**
	 * 总表
	 * @var Array
	 */
	var $all_nav = array();
	
	/**
	 * 建构子
	 * @param $power 个人权限Array("1"=>"w", "2"=>"r");
	 * 
	 */
	function mainNav($power='', $langAry=array()){
		if(!isset($_SESSION)){
			session_start();
		}

		if(!is_array($power) || count($power) <= 0){
			die("Permissions are not specified");
		}else{
			$this->proPower = $power;
		}

		$nav = new PDO_DB(DB_HOST, DB_USER, DB_PWD, DB_NAME);
		$nav->selectTB($this->main_table);
		$nav->getData("*","ORDER BY main_priority ASC");
		$nav->execute();
		do{
			$power = $this->checkInitPowerById($nav->row['main_id']); 
			if($power[0]){
				// echo "main_id:".$nav->row['main_id']."---p0:".$power[0]."---p1:".$power[1]."<br />";
				$this->mykey[$nav->row['main_id']]['main_id']          = $nav->row['main_id'];
				$this->mykey[$nav->row['main_id']]['main_parent_node'] = $nav->row['main_parent_node'];
				$this->mykey[$nav->row['main_id']]['main_title']       = $langAry['MenuName-'.$nav->row['main_name']];
				$this->mykey[$nav->row['main_id']]['main_name']        = $nav->row['main_name'];
				$this->mykey[$nav->row['main_id']]['main_path']        = $nav->row['main_path'];
				$this->myupid[$nav->row['main_parent_node']][]         = $nav->row['main_id'];
				// $this->mykey[$nav->row['main_id']]['main_title'] = $nav->row['main_title'];
			}
			
		}while($nav->row = $nav->fetch_assoc());
		
		if($this->nowPageId != 0){
			$this->getPage($this->nowPageId);
		}
		
		unset($nav,$language);
		
		// echo "<pre>";
		// print_r($this->mykey);
		// print_r($this->myupid);
		// exit;
		
	}
	
	
	
	function getPage($main_id=""){
		
		if($main_id == ""){
			die("Page index is not specified.");
		}
		
		if(!is_array($this->mykey) || count($this->mykey) <= 0){
			die("Permissions are not specified");
		}
		
		// echo "nowPageId:".$main_id;
		// echo "<pre>";
		// print_r($this->mykey);
		
		if(!empty($this->mykey[$main_id])){
			$this->nowPageId    = 	$this->mykey[$main_id]['main_id'];
			$this->nowPageName  = 	$this->mykey[$main_id]['main_name'];
			$this->nowPageTitle = 	$this->mykey[$main_id]['main_title'];
		}		
	}
	
	
	
	
	/**
	 * 产生选单树状结构
	 * @param $root 根目录id 
	 * @param $ulClass 将产生出来的ul，指定class属性 
	 * @param $ulId 将产生出来的ul，指定id属性
	 * @param $href 是否产生连结
	 */
	function getTree($root, $ulClass='', $ulId='', $href=true){
		$this->root = $root;
		
		if($ulClass != ''){
			$ulClass = ' class="'. $ulClass .'"';
		}
		
		if($ulId != ''){
			$ulId = ' id="'. $ulId .'"';
		}
		
		$this->showkey($root, $href);
		$this->all_nav[0] = '<ul'.$ulId.$ulClass.'>'; //将选单加入css
//		$this->__systemConfig['__mainNav__'] = join("",$this->all_nav);
		
		return join("", $this->all_nav);
	}
	
	
	
	/**
	 * 产生html 选单 (含权限)
	 * @param Int $key 父系ID (UPID)
	 * @param String $href 是否启用连结，不启用时为单纯秀出权限
	 */
	function showkey($key, $href=false){
		if(isset($this->myupid[$key])){
			if(count($this->myupid[$key]) > 0){	//sub menu's ul
				$this->all_nav[] = '<ul class="dropdown-menu">';
			}
		
		// echo "<pre>";
		// print_r($this->mykey);
		// print_r($this->myupid);
		// exit;
		
			for($i=0;$i < count($this->myupid[$key]); $i++){
				$id    = intval($this->myupid[$key][$i]);
				$power = $this->checkInitPowerById($id);//权限
				$upid  = intval($this->mykey[$id]['main_parent_node']);
				$title = $this->mykey[$id]['main_title'];
				$name  = $this->mykey[$id]['main_name'];
				$path  = $href ? $this->mykey[$id]['main_path'] : 'javascript:;';
				$active= $this->nowPageId == $id ? 'active' : '';
				
				$count = 0; //防止Notice。

				if(isset($this->myupid[$id])){	//如果有子系
					$count = count($this->myupid[$id]);
				}
				if( $count > 0){//有子系的Menu
					$active = in_array($this->nowPageId, $this->myupid[$id]) ? 'active' : '';
					$this->all_nav[] = '<li class="dropdown '.$active.'">';
					$this->all_nav[] = '<a class="dropdown-toggle" data-toggle="dropdown" pid="'. $id .'" pow="'. $power[1] .'" href="'.$path.'">';
					$this->all_nav[] = $title.' <b class="caret"></b></a>';
					$this->showkey($this->myupid[$key][$i], $href);
					$this->all_nav[] = '</li>';
				}else if($upid == $this->root){//没有子系又在根目录
					$firstMenu = '<li class="'.$active.'">';
					$firstMenu .= '<a class="" pid="'. $id .'" pow="'. $power[1] .'" href="'.$path.'">';
					$firstMenu .= $title.'</a>';
					$firstMenu .= '</li>';
					if($id == '1'){//个人资料
						$this->perInfoMenu = $firstMenu;
						continue;
					}
					$this->all_nav[] = $firstMenu;
					// $this->all_nav[] = '<li class="'.$active.'">';
					// $this->all_nav[] = '<a class="" pid="'. $id .'" pow="'. $power[1] .'" href="'.$path.'">';
					// $this->all_nav[] = $title.'</a>';
					// $this->showkey($this->myupid[$key][$i], $href);
					// $this->all_nav[] = '</li>';
				}else{
					$this->all_nav[] = '<li class="'.$active.'"><a pid="'. $id .'" pow="'. $power[1] .'" href="'.$path.'">' . $title . '</a></li>';
				}
			}
			
			if(count($this->myupid[$key]) > 0){
				$this->all_nav[] = '</ul>';
			}
		}
	}
	
	
	
	/**
	 * 判断单一功能项目是否有权(使用id)
	 * @param $id 功能 id
	 * 
	 * @return array, array(true, w) 可读可写, array(true, r) 仅读取, array(false, null) 无权限
	 */
	function checkInitPowerById($id){
		
		$proPower = $this->proPower;
		
		$ary = array();
		
		if(isset($proPower[$id]) && $proPower[$id] != ''){
			$ary[0] = true;
			$ary[1] = $proPower[$id];
		}
		
		if(count($ary) <= 0){
			$ary[0] = false;
			$ary[1] = null;
		}
		
		return $ary;
	}	
	
	
	
	/**
	 * 抓取项目名称(使用id)
	 * @param $id 功能 id
	 * 
	 * @return String
	 */
	function getInitName($id){
		
		if(isset($this->mykey[$id]['main_title']) && $this->mykey[$id]['main_title'] != ''){
			return $this->mykey[$id]['main_title'];
		}
		return false;
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
}





//$json = '{"1":"w","2":"w","3":"w","4":"w","5":"w","6":"w","7":"w","8":"w","9":"w","10":"w","11":"w","12":"w","14":"w"}';
//$json = '{"1":"w","2":"w","3":"w","4":"w","5":"w","6":"w","7":"w","8":"w","9":"w"}';
//$permissions = json_decode($json,true);
//$mainNav = new mainNav($permissions);
//
//echo $mainNav->getTree(0);

//print_r($permissions);











?>