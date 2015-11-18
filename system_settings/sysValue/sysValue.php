<?php
include_once $_SERVER['DOCUMENT_ROOT'].'/class/Template.class.php';
include_once $_SERVER['DOCUMENT_ROOT'].'/class/Points.class.php';

//载入基础样版
//開發用
$developer = false;
$html = new Template($_SERVER['DOCUMENT_ROOT']."/tpl/public_temp.html", $developer);

//未登入
if($html->WebLogin->getStatus() != '0'){
	header("location: /index.php?err=".$html->WebLogin->getStatus());
	exit;
}
$cssImport          = array();
// $cssImport[]     = '<link rel="stylesheet" href="css/style.css">';
$javascriptImport   = array();
$javascriptImport[] = '<script type="text/javascript" src="js/custom.js"> </script>';
// $recoveryBtn     = '<button data-fn="recovery" class="btn btn-sm btn-danger pull-right sysBtn" type="button">恢復額度</button>';
$calculateBtn       = '<button data-whatever="計算報表" data-target="#dialogModal" data-modal-submit="false" data-fn="calWinLose" class="dialogModal btn btn-sm btn-danger pull-right" type="button">計算報表</button>';
$bonusBtn           = '<button data-whatever="活動獎金設置" data-target="#dialogModal" data-fn="addBonus" class="dialogModal btn btn-sm btn-warning pull-right" type="button">活動獎金設置</button>';
$addRateBtn         = '<button data-fn="addRate" class="btn btn-sm btn-info pull-right sysBtn" type="button">新增下期占成</button>';
$updateAdminBtn     = '';
//管理者本页权限
switch($html->WebLogin->nowPagePower){
	case 'w':
		$updateAdminBtn .= $bonusBtn;
		$updateAdminBtn .= $calculateBtn;
		$updateAdminBtn .= $addRateBtn;
		break;
	case 'r':
		break;
	default:
		$_SESSION['err'] = '6';
		header("location: /index.php");
		exit;
}

//取得样版中的样版资料，作为次样版
$tableRowData = $html->getFile();
$notice       = '';
$mergeAry     = array();
$db           = new PDO_DB(DB_HOST, DB_USER, DB_PWD, DB_NAME);
$db->selectTB('SysValue');
$db->getData("*");
$db->execute();
if($db->total_row > 0){
	do{
		if($db->row['SysKey'] == 'RateSettingSwitch'){
			$mergeAry['__RateSettingSwitchName__'] = $db->row['SysValue']=='y' ? '<span class="text-danger">開放</span>' : '<span class="text-muted">關閉</span>';
		}
		$mergeAry['__'.$db->row['SysKey'].'__'] = $db->row['SysValue'];
	}while($db->row = $db->fetch_assoc());
}

//-----------------------------------------将欲取代的内容与样版重新组合-------------------------------------------------
//欲取代的内容
$compilerAry = array();

//载入本页样版
$compilerAry['__MainContent__']      = $tableRowData;//取次样版
$compilerAry['__cssImport__']        = join("\n", $cssImport);//引用css
$compilerAry['__javascriptImport__'] = join("\n", $javascriptImport);//引用javascript
$compilerAry['__updateAdmin__']      = $updateAdminBtn;//按钮
$compilerAry['__notice__']           = $notice;//系统提示
$compilerAry                         = $compilerAry + $mergeAry;

//重新组合页面
$cleanHtml = $html->compiler($compilerAry);
echo $cleanHtml;
unset($html, $db);
exit;
?>