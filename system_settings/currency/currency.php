<?php
include_once $_SERVER['DOCUMENT_ROOT'].'/class/Template.class.php';

//载入基础样版
$html = new Template($_SERVER['DOCUMENT_ROOT']."/tpl/public_temp.html");

//未登入
if($html->WebLogin->getStatus() != '0'){
	header("location: /index.php?err=".$html->WebLogin->getStatus());
	exit;
}
$ag_id  = $html->WebLogin->ag_id;
$addBtn = '<button type="button" data-whatever="'.$html->lang['Add'].'" data-target="#dialogModal" data-fn="add" class="dialogModal btn btn-sm btn-success pull-right">'.$html->lang['Add'].'</button>';
$refreshBtn = '<button type="button" class="btn btn-sm btn-danger pull-right refreshCurrency">'.$html->lang['Refresh'].'</button>';
$updBtn = '<a data-whatever="'.$html->lang['UpdateData'].'" data-target="#dialogModal" data-fn="update" data-id="%s" class="dialogModal pull-left" href="javascript:;">'.$html->lang['UpdateData'].'</a>';
$delBtn = '<a style="margin-left:5px;" data-whatever="'.$html->lang['Delete'].'" data-target="#dialogModal" data-fn="delete" data-id="%s" class="dialogModal text-danger pull-left" href="javascript:;">'.$html->lang['Delete'].'</a>';
$updateAdminBtn     = '';
$cssImport          = [];
$javascriptImport[] = '<script type="text/javascript" src="js/custom.js"></script>';
//管理者本页权限
switch($html->WebLogin->nowPagePower){
	case 'w':
		if($html->WebLogin->ag_id == '1'){
			$updateAdminBtn .= $addBtn;
		}
		if($html->WebLogin->ag_id != '1'){
			$delBtn = '';
		}
		$updateAdminBtn .= $refreshBtn;
		$javascriptImport[] = '<script type="text/javascript">
		$(function(){
			$(".refreshCurrency").on("click", function(){
				if(confirm("'.$html->lang['ConfirmBtn'].'")){
					$.getJSON("/currency.php").done(function(json){
						if(json.result == "ok"){
							alert("'.$html->lang['StatusSuccess'].'");
							location.reload();
						}
					}).fail(function(){
						alert("'.$html->lang['StatusFail'].'");
					});
				}
			});
		});
		</script>';
		break;
	case 'r':
		$updBtn = '';
		$delBtn = '';
		break;
	default:
		$_SESSION['err'] = '6';
		header("location: /index.php");
		exit;
}
//取得样版中的样版资料，作为次样版
$tableRowData = $html->getFile();

//取得资料
$notice   = '';
$db       = new PDO_DB(DB_HOST, DB_USER, DB_PWD, DB_NAME);
$enableOK = $html->regexMatch($tableRowData, '<!--__enableOKStart', 'enableOKEnd__-->');
$enableNO = $html->regexMatch($tableRowData, '<!--__enableNOStart', 'enableNOEnd__-->');
try {
	$db->selectTB('Currency');
	$db->getData("*");
	$db->pagingMSSQL(20, 'updated_at');
	$db->execute();
	if($db->total_row <= 0){
		throw new Exception('No Row');
	}
	$tableRows    = '';
	$tableRowTemp = $html->regexMatch($tableRowData, '<!--__tableRecordsStart', 'tableRecordsEnd__-->');
	do{
		$db->row['enabled']   = $db->row['enabled'] == 'y' ? $enableOK : $enableNO;
		$db->row['operation'] = sprintf($updBtn, $db->row['id']);
		$db->row['operation'] .= sprintf($delBtn, $db->row['id']);
		$tableRows .= $html->regexReplace($db->row, $tableRowTemp);
	}while($db->row = $db->fetch_assoc());

	$pageTemp  = $html->regexMatch($tableRowData, '<!--__paginationStart', 'paginationEnd__-->');
	$tableInfo = [
		'tableRecords' => $tableRows,
		'RecordInfo' => $db->recordInfo($html->lang['RecordInfoStyle'])['default_style'],
		'pagination' => $html->regexReplace(['pagination'=>$db->createNumBar()['default_style']], $pageTemp),
	];

	$tableRowData = $html->regexReplace($tableInfo, $tableRowData, '<!--__', '__-->');
} catch (Exception $e) {
	switch ($e->getCode()) {
		default:
			$notice = $html->__systemConfig['__notFound__'];
			break;
	}
	if(DEBUG){
		$notice .= $e->getMessage();
	}
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

//-----------------------------------------将欲取代的内容与样版重新组合-------------------------------------------------

//重新组合页面
$cleanHtml = $html->compiler($compilerAry);
echo $cleanHtml;
unset($html, $db);
exit;
?>