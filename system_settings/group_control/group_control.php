<?php

//----------------------------------开发完毕后，请删除
//计算执行时间
$stime=explode(" ",microtime());
$ss=$stime[0]+$stime[1];
//----------------------------------开发完毕后，请删除

include_once $_SERVER['DOCUMENT_ROOT'].'/class/Template.class.php';


//载入基础样版
$html             = new Template($_SERVER['DOCUMENT_ROOT']."/tpl/public_temp.html", false);
$edit             = '--';
$createAdminBtn   = '';
$cssImport        = [];
$javascriptImport = [];

$html->WebLogin->nowPagePower = 'w';

$addRateBtn = '<button data-fn="createGroup" class="btn btn-sm btn-info pull-right sysBtn" type="button">新增群组</button>';

//管理者本页权限
switch($html->WebLogin->nowPagePower){
	case 'w':
		$edit = '<a href="javascript:;" class="editAdmin" fn="editGroup"><img src="/img/hammer_screwdriver.png" alt="修改" title="修改"/></a>
			 	 <a href="javascript:;" class="delAdmin" fn="delGroup"><img src="/img/cross.png" alt="删除" title="删除"/></a>';
		// $createAdminBtn = '<a href="javascript:;" class="button createAdmin" fn="createGroup" style="margin:8px 10px 0 0;">新增群组</a>'; 
		$createAdminBtn   = $addRateBtn; 
		$cssImport[]        = '<link rel="stylesheet" href="css/style.css" />';
		$javascriptImport[] = '<script type="text/javascript" src="js/group_control.js"> </script>';
	break;
	case 'r':
	break;
	default:
		header("location: /index.php?err=6");
		exit;
}

//取得样版中的样版资料，作为次样版
$tableRowData = $html->regexMatch($html->getFile(), '<!--__tableData_Start', 'tableData_End__-->');


//取得管理表资料列表
$admin_list = new PDO_DB(DB_HOST, DB_USER, DB_PWD, DB_NAME);
$admin_list->selectTB('AdminGroup');	//选择资料表
// $admin_list->rows_per_page = 30;	//单页资料印出笔数
$admin_list->getData("*", "ORDER BY ag_id DESC");
$admin_list->execute();

//所有管理者帐号清单
$tableAllRow = '';
$record      = '';
$pagination  = '';
$notice      = '';

if($admin_list->total_row > 0){
	do{
		//编辑功能项
		$admin_list->row['admin_edit'] = $edit;
		$ag_power_html = '';
		$ag_power      = json_decode($admin_list->row['ag_power'], true);
		
		if(is_array($ag_power)){
			foreach ($ag_power as $key=>$val){
				switch ($val){
					case 'w':
						$val = '写';
					break;
					case 'r':
						$val = '读';
					break;
				}
				$initPower = $html->WebLogin->getInitName($key);
				if($initPower != false){
					$ag_power_html .= '<div class="bg-info box">
											<div class="box-title">'. $html->WebLogin->getInitName($key) .'</div>
											<div class="text-warning box-power">'. $val .'</div>
										</div>';
				}
			}
		}
		
		$admin_list->row['ag_power'] = $ag_power_html;
		//产生单笔资料(用次样版$tableRowData做为基本格式)
		$tableAllRow .= $html->regexReplace($admin_list->row, $tableRowData, "__", "__");
	}while($admin_list->row = $admin_list->fetch_assoc());
	
	//资料记录(页次 1 / 1 , 本页显示 1 - 3 笔 , 全部共 3 笔纪录)
	// $record = '<span class="pages" style="color:#aaa;">'.$admin_list->record_info().'</span>';
	
	//资料分页(上一页 1,2,3,4,5 下一页)
	// $pagination = $admin_list->create_num_bar('5',true);
	
}else{
//	$notice = '<div style="padding:30px 0 0 0; text-align:center;">查询不到任何资料。</div>';
	$notice = $html->__systemConfig['__notFound__'];
	
}

//-----------------------------------------将欲取代的内容与样版重新组合-------------------------------------------------
//欲取代的内容
$compilerAry = array();

//载入本页样版
$compilerAry['__MainContent__']      = $html->getFile();		//取次样版
$compilerAry['__cssImport__']        = join("\n", $cssImport);				//引用css
$compilerAry['__javascriptImport__'] = join("\n", $javascriptImport);		//引用javascript
$compilerAry['__tableContent__']     = $tableAllRow;			//自定义取代的内容
$compilerAry['__record__']           = $record;				//资料记录
$compilerAry['__pagination__']       = $pagination;			//资料分页
$compilerAry['__updateAdmin__']      = $createAdminBtn;		//新增管理者按钮
$compilerAry['__notice__']           = $notice;				//系统提示

//----------------------------------开发完毕后，请删除
//计算执行时间
$mtime=explode(" ",microtime());
$es=$mtime[0]+$mtime[1];
$mtime=$es-$ss;	//总耗时
//----------------------------------开发完毕后，请删除
$compilerAry['__mtime__'] = '<div style="text-align:right;color:#255c88;">系统执行耗时:'. $mtime .'</div>';			//系统提示
//-----------------------------------------将欲取代的内容与样版重新组合-------------------------------------------------

//重新组合页面
$cleanHtml = $html->compiler($compilerAry);
echo $cleanHtml;
unset($html);
unset($admin_list);
exit;
?>