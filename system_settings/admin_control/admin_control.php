<?php

//----------------------------------开发完毕后，请删除
//计算执行时间
$stime=explode(" ",microtime());
$ss=$stime[0]+$stime[1];
//----------------------------------开发完毕后，请删除

include_once $_SERVER['DOCUMENT_ROOT'].'/class/Template.class.php';


//载入基础样版
$html = new Template($_SERVER['DOCUMENT_ROOT']."/tpl/public_temp.html");

//管理者本页权限
switch($html->WebLogin->nowPagePower){
	case 'w':
		$editSelf = '<a href="javascript:;" class="viewAdmin" fn="viewAdmin"><img src="/img/view.png" alt="检视" title="检视"/></a>'; 
		$edit = '<a href="javascript:;" class="viewAdmin" fn="viewAdmin"><img src="/img/view.png" alt="检视" title="检视"/></a>
			 	 <a href="javascript:;" class="editAdmin" fn="editAdmin"><img src="/img/hammer_screwdriver.png" alt="修改" title="修改"/></a>
			 	 <a href="javascript:;" class="delAdmin" fn="delAdmin"><img src="/img/cross.png" alt="删除" title="删除"/></a>';
		$createAdminBtn = '<a href="javascript:;" class="button createAdmin" fn="createAdmin" style="margin:8px 10px 0 0;">新增帐号</a>'; 
		$javascriptImport = '<script type="text/javascript" src="/js/admin_control.js"> </script>
							 <script src="/js/jquery.validationEngine-tw.js" type="text/javascript"> </script>
							 <script src="/js/jquery.validationEngine.js" type="text/javascript"> </script>';
		$cssImport = '<link rel="stylesheet" href="/css/validationEngine.jquery.css" type="text/css" />';
	break;
	
	case 'r':
		$editSelf = '<a href="javascript:;" class="viewAdmin" fn="viewAdmin"><img src="/img/view.png" alt="检视" title="检视"/></a>';
		$edit = '<a href="javascript:;" class="viewAdmin" fn="viewAdmin"><img src="/img/view.png" alt="检视" title="检视"/></a>';
		$createAdminBtn = '';
		$javascriptImport = '<script type="text/javascript" src="/js/admin_control.js"> </script>'; 
		$cssImport = '';
	break;
	
	default:
		$editSelf = '';
		$edit = '';
		$createAdminBtn = '';
		$javascriptImport = '';
		$cssImport = '';
	break;
}

//取得样版中的样版资料，作为次样版
$tableRowData = $html->regexMatch($html->getFile(), '<!--__tableData_Start', 'tableData_End__-->');


//搜寻项
if(isset($_GET['searchKeyword']) && $_GET['searchKeyword'] != ''){
	$searchKeyword = $_GET['searchKeyword'];
	$searchSQL = "(admin_group.ag_title LIKE '%". $searchKeyword ."%' OR 
				  admin_acc LIKE '%". $searchKeyword ."%' OR 
				  admin_name LIKE '%". $searchKeyword ."%' OR 
				  admin_title LIKE '%". $searchKeyword ."%' OR 
				  admin_phone LIKE '%". $searchKeyword ."%' OR 
				  admin_mail LIKE '%". $searchKeyword ."%')";
}else{
	$searchSQL = "";
}


//取得管理表资料列表
$admin_list = new Mysql(DB_HOST, DB_USER, DB_PWD, DB_NAME);
$admin_list->selectTB('admin');	//选择资料表
$admin_list->rows_per_page = 50;	//单页资料印出笔数

if($html->WebLogin->ag_id == '1'){
	$agSQL = " admin.admin_id > 0 ";
}else{
	$agSQL = " admin.ag_id='". $html->WebLogin->ag_id ."' ";
}

if($searchSQL != ''){
	$agSQL = " AND " . $agSQL;
}


$admin_list->getData("*, admin_group.ag_title",
		"LEFT JOIN admin_group ON (admin.ag_id = admin_group.ag_id)
		 WHERE ".$searchSQL . $agSQL ." ORDER BY admin_enable ASC, admin_id DESC");


//所有管理者帐号清单
$tableAllRow = '';
$record = '';
$pagination = '';
$notice = '';

if($admin_list->total_row > 0){
	do{
		
		//编辑功能项(不能修改自己)，最后权限管理者例外
		if($html->WebLogin->ag_id != '1'){
			if($admin_list->row['admin_id'] == $html->WebLogin->admin_id){
				$admin_list->row['admin_edit'] = $editSelf;
			}else{
				$admin_list->row['admin_edit'] = $edit;
			}
		}else{
			$admin_list->row['admin_edit'] = $edit;
		}
		
		
		//线上情况
		if($admin_list->row['s_id'] != '' && $admin_list->row['s_id'] != null){
			$admin_list->row['s_id'] = '<img src="/img/light_open.png" alt="上线中" title="上线中"/>';
		}else{
			$admin_list->row['s_id'] = '<img src="/img/light_close.png" alt="下线" title="下线"/>';
		}
		
		
		
		//帐号停用启用图示
		if($admin_list->row['admin_enable'] == 'y'){
			$admin_list->row['admin_enable'] = '<img src="/img/accpet.png" alt="启用" title="启用"/>';
		}else{
			$admin_list->row['admin_enable'] = '<img src="/img/cross_circle.png" alt="停用" title="停用"/>';
		}
		
		//电子信箱
		if($admin_list->row['admin_mail'] == '' || $admin_list->row['admin_mail'] == null){
			$admin_list->row['admin_mail'] = '---';
		}
		
		//产生单笔资料(用次样版$tableRowData做为基本格式)
		$tableAllRow .= $html->regexReplace($admin_list->row, $tableRowData, "__", "__");
		
	}while($admin_list->row = $admin_list->fetch_assoc());
	
	//资料记录(页次 1 / 1 , 本页显示 1 - 3 笔 , 全部共 3 笔纪录)
	$record = '<span class="pages" style="color:#aaa;">'.$admin_list->record_info().'</span>';
	
	//资料分页(上一页 1,2,3,4,5 下一页)
	$pagination = $admin_list->create_num_bar('5',true);
	
}else{
//	$notice = '<div style="padding:30px 0 0 0; text-align:center;">查询不到任何资料。</div>';
	$notice = $html->__systemConfig['__notFound__'];
	
}





//-----------------------------------------将欲取代的内容与样版重新组合-------------------------------------------------
//欲取代的内容
$compilerAry = array();

//载入本页样版
$compilerAry['__MainContent__'] = 				$html->getFile();		//取次样版
$compilerAry['__cssImport__'] = 				$cssImport;				//引用css
$compilerAry['__javascriptImport__'] = 			$javascriptImport;		//引用javascript
$compilerAry['__tableContent__'] = 				$tableAllRow;			//自定义取代的内容
$compilerAry['__record__'] = 					$record;				//资料记录
$compilerAry['__pagination__'] = 				$pagination;			//资料分页
$compilerAry['__createAdmin__'] =	 			$createAdminBtn;		//新增管理者按钮
$compilerAry['__notice__'] = 					$notice;				//系统提示

//----------------------------------开发完毕后，请删除
//计算执行时间
$mtime=explode(" ",microtime());
$es=$mtime[0]+$mtime[1];
$mtime=$es-$ss;	//总耗时
//----------------------------------开发完毕后，请删除
$compilerAry['__mtime__'] = 					'<div style="text-align:right;color:#255c88;">系统执行耗时:'. $mtime .'</div>';			//系统提示



//-----------------------------------------将欲取代的内容与样版重新组合-------------------------------------------------

//重新组合页面
$cleanHtml = $html->compiler($compilerAry);
echo $cleanHtml;
unset($html);
unset($admin_list);

?>