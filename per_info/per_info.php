<?php
//----------------------------------开发完毕后，请删除
//计算执行时间
$stime=explode(" ",microtime());
$ss=$stime[0]+$stime[1];
//----------------------------------开发完毕后，请删除
include_once $_SERVER['DOCUMENT_ROOT'].'/class/Template.class.php';
include_once $_SERVER['DOCUMENT_ROOT'].'/class/Points.class.php';

//载入基础样版
$html = new Template($_SERVER['DOCUMENT_ROOT']."/tpl/public_temp.html");

//未登入
if($html->WebLogin->getStatus() != '0'){
	header("location: /index.php?err=".$html->WebLogin->getStatus());
	exit;
}
$updateAdminBtn   = '';
$cssImport        = array();
$javascriptImport = array();
//管理者本页权限
switch($html->WebLogin->nowPagePower){
	case 'w':
		$cssImport[]        = '<link rel="stylesheet" href="css/style.css">';
		$javascriptImport[] = '<script type="text/javascript" src="js/custom.js"> </script>';
		// $updateAdminBtn     = '<button data-whatever="资料更新" data-target="#dialogModal" data-fn="updateSelf" class="dialogModal btn btn-sm btn-default pull-right" type="button">资料更新</button>'; 
		break;
	case 'r':
		break;
	default:
		header("location: /index.php?err=6");
		exit;
}

//取得样版中的样版资料，作为次样版
$tableRowData = $html->getFile();
$notice       = '';

//取得管理表资料
$admin_list = new PDO_DB(DB_HOST, DB_USER, DB_PWD, DB_NAME);
$admin_list->selectTB('Admin');	//选择资料表
$admin_list->getData("*", "WHERE admin_id = '" . $html->WebLogin->admin_id . "'");
$admin_list->execute();
//下期占成設定
$dateAry   = $html->dateArray();
$count     = count($dateAry['start']);
$startDate = $dateAry['start'][$count-2];//預設查詢最新
$endDate   = $dateAry['end'][$count-1];

if($admin_list->total_row > 0){
	//产生单笔资料(用次样版$tableRowData做为基本格式)
	$admin_list->row['admin_last_login_time'] = empty($admin_list->row['admin_last_login_time']) ? '--' : $admin_list->row['admin_last_login_time'];
	$admin_list->row['ag_title'] = $html->lang['Group'.$admin_list->row['ag_id']];
	// 20150526-拿掉分紅顯示
	// 20150701-開啟分紅顯示
	//代理可視自己分紅占成%數
	if(!in_array($admin_list->row['ag_id'], ['1', '2'])){
		$commissionTemp = $html->regexMatch($tableRowData, '<!--__commissionStart', 'commissionEnd__-->');
		//自己返水占成率
		$Points          = new Points();
		$admin_list->row = $Points->getAdminRate($admin_list->row, $startDate, $endDate, false, true);
		$admin_list->row['commissionRate'] = isset($admin_list->row['commissionRate']) ? $admin_list->row['commissionRate'] : 0;
		$admin_list->row['ReturnRate']     = isset($admin_list->row['ReturnRate']) ? $admin_list->row['ReturnRate'] : 0;
		$admin_list->row['commissionTemp'] = $html->regexReplace(($admin_list->row+$html->lang), $commissionTemp);
	}
	$tableRowData = $html->regexReplace($admin_list->row, $tableRowData, "<!--__", "__-->");
}else{
	$notice = $html->__systemConfig['__notFound__'];
}

//-----------------------------------------将欲取代的内容与样版重新组合-------------------------------------------------
//欲取代的内容
$compilerAry = array();

//载入本页样版
$compilerAry['__MainContent__']      = $tableRowData;//取次样版
$compilerAry['__cssImport__']        = join("\n", $cssImport);//引用css
$compilerAry['__javascriptImport__'] = join("\n", $javascriptImport);//引用javascript
$compilerAry['__updateAdmin__']      = $updateAdminBtn;//资料更新按钮
$compilerAry['__notice__']           = $notice;//系统提示

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
unset($html, $admin_list, $Points);
exit;
?>