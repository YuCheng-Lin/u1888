<?php


include_once $_SERVER['DOCUMENT_ROOT'].'/class/WebLogin.class.php';
include_once $_SERVER['DOCUMENT_ROOT'].'/class/Template.class.php';


//载入基础样版
$html = new Template("empty", false);

//未登入
$status = $html->WebLogin->getStatus();
if($status != '0'){
	$sendAry = array();
	$sendAry['systemErr'] = 'window.location = "/index.php?err='. $status .'";';
	echo json_encode($sendAry);
//	header("Location: /index.php?err=".$status);
	exit;
}

$userIp = $html->WebLogin->getUserIp();
unset($html);

$expect = ['alarmSingle', 'disAlarmSingle'];

if(empty($_POST['type'])){
	$sendAry = array();
	$sendAry['systemErr'] = 'alert("查无资料。");';
	echo json_encode($sendAry);
	exit;
}
if(!function_exists($_POST['type']) && !in_array($_POST['type'], $expect)){
	$sendAry = array();
	$sendAry['systemErr'] = 'alert("查无资料(type)。");';
	echo json_encode($sendAry);
	exit;
}
//同一頁面呼叫
if(in_array($_POST['type'], ['alarmSingle', 'alarmMem', 'disAlarmSingle', 'disAlarmMem'])){
	confirm();
}else{
	$_POST['type']();
}
exit;


/**
*  取得用户IP位置
*/
function getUserIp(){
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
 * 警報處理-單一代理
 * 解除警報處理-單一代理
 */
function confirm(){
	$html = new Template(); 
	$subTemp = $html->getFile($_SERVER['DOCUMENT_ROOT']."/tpl/account/accountManager/confirmTemp.html");
	$html->publicTemp = $subTemp;
	
	//重新组合页面
	$main = $html->compiler();
	
	$sendAry = array();
	$sendAry['result'] = $main;
	
	echo json_encode($sendAry);
	unset($html, $db);
	exit;
}


/**
 * 连线回报
 */
function keepOnLine(){
	//与Java AP 沟通 (已不使用，改为crontab 检查清空)
// 	$chkLogin = new WebLogin();
// 	$mid = $_SESSION['_admin_id'];
// 	$sid = session_id();
// 	$command = "120,AutoReply,".$mid.",".$sid;
// 	$result = $chkLogin->javaConnnection($command, SERVER_AP_HOST, SERVER_AP_PORT, true);
// 	$sendData = array();
// 	$sendData['result'] = $result;
// 	echo json_encode($sendData);
// 	exit;
	
// 	echo "<pre>";
// 	print_r($_SESSION);
// 	exit;
	$admin_id = $_SESSION['_admin_id'];
	$adminOnLine = new Mysql(DB_HOST, DB_USER, DB_PWD, DB_NAME);
	$adminOnLine->selectTB('admin');
	$adminOnLine->getData("*", 
						  "WHERE admin_id = '". $admin_id ."'");
	if($adminOnLine->total_row > 0){
		$updateAry = array();
		$updateAry['admin_action_time'] = date("Y-m-d H:i:s");
		$updateAry['admin_action_ip'] = getUserIp();
		$adminOnLine->updateData($updateAry, 
								"WHERE admin_id = '". $admin_id ."'");
	}
	unset($adminOnLine);
	$sendData = array();
	$sendData['result'] = true;
	echo json_encode($sendData);
	exit;
	
}

/**
 * 新增使用者
 */
function createAdmin(){
	
	$getHtml = new Template("empty"); 
	
	//取得目前使用者群组
	$nowUser = new WebLogin('admin');
	$nowUser->chkLogin($_SESSION['_admin_acc'], $_SESSION['_admin_pwd'], $_SESSION['_admin_idls4']);
	$nowUser_agid = $nowUser->ag_id;
	unset($nowUser);
	
	//取得群组权限
	$ag = new Mysql(DB_HOST, DB_USER, DB_PWD, DB_NAME);
	$ag->selectTB("admin_group");
	$ag->getData("*",
				 "ORDER BY ag_id ASC");
	if($ag->total_row <= 0){
		$sendAry = array();
		$sendAry['systemErr'] = 'window.location = "/index.php?err=3";';
		echo json_encode($sendAry);
		exit;
	}
	
	$agPowerAry = array();
	$agTitleAry = array();
	do{
		$agPowerAry[$ag->row['ag_id']] = json_decode($ag->row['ag_power'], true);
		$agTitleAry[$ag->row['ag_id']] = $ag->row['ag_title'];
	}while($ag->row = $ag->fetch_assoc());
	
	unset($ag);
	
	//依使用者所属群组，给与新帐号该有的预设权限
	$createPower = $agPowerAry[$nowUser_agid];
	
	$newUser = new WebLogin('admin');
	$newUser->mainNav($createPower); //载入预设权限
	$mainNavHtml = $newUser->getTree(0, 'power-menu', '', false); //指定选单ul的class
	
	$replaceAry = array();
	
	//使用者所属单位，最高权限者可开启所有
	if($nowUser_agid == '1'){
		$group = '<select id="ag_id" name="ag_id" class="select_ag">';
		foreach ($agTitleAry as $key => $value) {
			$group .= '<option value="'. $key .'">'. $value .'</option>';
		}
		$group .= '</select>';
	}else{
		$group = $agTitleAry[$nowUser_agid];
	}
	
	
	$replaceAry['admin_power'] = $mainNavHtml;
	$replaceAry['ag_title'] = $group;
	
	
	//取得样版中的样版资料，作为次样版
	$subTemp = $getHtml->getFile($_SERVER['DOCUMENT_ROOT']."/tpl/system_settings/admin_control/create_admin.html");
	$tableData = $getHtml->regexMatch($subTemp, '<!--__tableData_Start', 'tableData_End__-->');
	$tableData = $getHtml->regexReplace($replaceAry, $tableData, "__", "__");
	//欲取代的内容
	$compilerAry = array();
	
	$compilerAry['__tableContent__'] = 	$tableData;			//自定义取代的内容
	
	
	//重新组合页面
	$Html = $getHtml->compiler($compilerAry);
	
	$sendAry = array();
	$sendAry['result'] = $Html;
	if($nowUser_agid == '1'){
		//群组预设权限(全部)
		$sendAry['agPowAry'] = $agPowerAry;
	}
	//群组预设权限
	$sendAry['adminPowAry'] = $createPower;
	
	echo json_encode($sendAry);
}




/**
 * 修改管理者帐号
 */
function editAdmin(){
	if(isset($_POST['a_id']) && $_POST['a_id'] != ''){
		$a_id = $_POST['a_id'];
	}else{
		$sendAry = array();
		$sendAry['systemErr'] = 'window.location = "/index.php?err=3";';
		echo json_encode($sendAry);
		exit;
	}
	
	
	//取得欲修改的资料
	$chgUser = new Mysql(DB_HOST, DB_USER, DB_PWD, DB_NAME);
	$chgUser->selectTB("admin");
	$chgUser->getData("*",
					  "WHERE admin_id='". $a_id ."'");
	if($chgUser->total_row <= 0){
		$sendAry = array();
		$sendAry['systemErr'] = 'window.location = "/index.php?err=3";';
		echo json_encode($sendAry);
		exit;
	}
	$chgUserDataAry = array();
	$chgUserDataAry = $chgUser->row;
	unset($chgUser);
	
	
	//取得目前使用者群组
	$nowUser = new WebLogin('admin');
	$nowUser->chkLogin($_SESSION['_admin_acc'], $_SESSION['_admin_pwd'], $_SESSION['_admin_idls4']);
	$nowUser_agid = $nowUser->ag_id;
	$nowUser_pow = $nowUser->admin_power;
	unset($nowUser);
	
	
	//取得群组权限
	$ag = new Mysql(DB_HOST, DB_USER, DB_PWD, DB_NAME);
	$ag->selectTB("admin_group");
	$ag->getData("*",
				 "ORDER BY ag_id ASC");
	$agPowerAry = array();
	$agTitleAry = array();
	if($ag->total_row > 0){
		do{
			$agPowerAry[$ag->row['ag_id']] = json_decode($ag->row['ag_power'], true);
			$agTitleAry[$ag->row['ag_id']] = $ag->row['ag_title'];
		}while($ag->row = $ag->fetch_assoc());
	}else{
		die("Can not find the admin group.");
	}
	unset($ag);
	
	//依使用者所属群组，给与新帐号该有的预设权限
	$createPower = $agPowerAry[$nowUser_agid];
	
	$newUser = new WebLogin('admin');
	$newUser->mainNav($createPower); //载入预设权限
	$mainNavHtml = $newUser->getTree(0, 'power-menu', '', false); //指定选单ul的class
	
	
	//使用者所属单位，最高权限者可开启所有
	if($nowUser_agid == '1'){
		$group = '<select id="ag_id" name="ag_id" class="select_ag">';
		foreach ($agTitleAry as $key => $value) {
			if($key == $chgUserDataAry['ag_id']){
				$group .= '<option value="'. $key .'" selected="selected">'. $value .'</option>';
			}else{
				$group .= '<option value="'. $key .'">'. $value .'</option>';
			}
		}
		$group .= '</select>';
	}else{
		$group = $agTitleAry[$nowUser_agid];
	}
	
	if($chgUserDataAry['admin_enable'] == 'y'){
		$chgUserDataAry['admin_enable_y'] = 'checked="checked"';
		$chgUserDataAry['admin_enable_n'] = '';
	}else{
		$chgUserDataAry['admin_enable_y'] = '';
		$chgUserDataAry['admin_enable_n'] = 'checked="checked"';
	}
	
	
	//复写
	$chgUserDataAry['def_power'] = $mainNavHtml;
	$chgUserDataAry['ag_title'] = $group;
	
	
	//样版
	$getHtml = new Template("empty"); 
	//取得样版中的样版资料，作为次样版
	$subTemp = $getHtml->getFile($_SERVER['DOCUMENT_ROOT']."/tpl/system_settings/admin_control/update_admin.html");
	$tableData = $getHtml->regexMatch($subTemp, '<!--__tableData_Start', 'tableData_End__-->');
	$tableData = $getHtml->regexReplace($chgUserDataAry, $tableData, "__", "__");
	//欲取代的内容
	$compilerAry = array();
	
	$compilerAry['__tableContent__'] = 	$tableData;			//自定义取代的内容
	
	//重新组合页面
	$Html = $getHtml->compiler($compilerAry);
	
	$sendAry = array();
	$sendAry['result'] = $Html;
	if($nowUser_agid == '1'){
		//群组预设权限(全部)
		$sendAry['agPowAry'] = $agPowerAry;
	}
	//user权限
	$sendAry['userPowAry'] = json_decode($chgUserDataAry['admin_power'], true);
	//群组预设权限
	$sendAry['adminPowAry'] = $createPower;
	
	echo json_encode($sendAry);
	
}




/**
 * 检视管理者帐号
 */
function viewAdmin(){
	
	if(isset($_POST['a_id']) && $_POST['a_id'] != ''){
		$a_id = $_POST['a_id'];
	}else{
		$sendAry = array();
		$sendAry['systemErr'] = 'window.location = "/index.php?err=3";';
		echo json_encode($sendAry);
		exit;
	}
	
	$admin = new Mysql(DB_HOST, DB_USER, DB_PWD, DB_NAME);
	$admin->selectTB('admin');	//选择资料表
	$admin->getData("*, admin_group.ag_title",
					"LEFT JOIN admin_group ON (admin.ag_id = admin_group.ag_id) WHERE admin_id='". $a_id ."'");
	
	//载入空白样版，单档取代
	$getHtml = new Template("empty");
	
	if($admin->total_row <= 0){
		$sendAry = array();
		$sendAry['systemErr'] = 'window.location = "/index.php?err=3";';
		echo json_encode($sendAry);
		exit;
	}
		
	$powerAry = json_decode($admin->row['admin_power'], true);
	$user = new WebLogin('admin');
	$user->mainNav($powerAry); //载入权限
	$mainNavHtml = $user->getTree(0, 'power-menu', '', false); //指定选单ul的class 
	$admin->row['admin_power'] = $mainNavHtml;
	//帐号停用启用图示
	if($admin->row['admin_enable'] == 'y'){
		$admin->row['admin_enable'] = '<img src="/img/accpet.png" alt="启用" title="启用"/>';
	}else{
		$admin->row['admin_enable'] = '<img src="/img/cross_circle.png" alt="停用" title="停用"/>';
	}
	
	//取得样版中的样版资料，作为次样版
	$subTemp = $getHtml->getFile($_SERVER['DOCUMENT_ROOT']."/tpl/system_settings/admin_control/view_admin.html");
	$tableData = $getHtml->regexMatch($subTemp, '<!--__tableData_Start', 'tableData_End__-->');
	$tableData = $getHtml->regexReplace($admin->row, $tableData, "__", "__");
	//欲取代的内容
	$compilerAry = array();
	
	$compilerAry['__tableContent__'] = 	$tableData;			//自定义取代的内容
	
	
	//重新组合页面
	$Html = $getHtml->compiler($compilerAry);
		
	
	$sendAry = array();
	$sendAry['result'] = $Html;
	
	unset($admin);
	unset($getHtml);
	
	echo json_encode($sendAry);
}




/**
 * 删除管理者帐号
 */
function delAdmin(){
	if(isset($_POST['a_id']) && $_POST['a_id'] != ''){
		$a_id = $_POST['a_id'];
	}else{
		$sendAry = array();
		$sendAry['systemErr'] = 'window.location = "/index.php?err=3";';
		echo json_encode($sendAry);
		exit;
	}
	
	//取得使用者资料
	$admin = new Mysql(DB_HOST, DB_USER, DB_PWD, DB_NAME);
	$admin->selectTB('admin');
	$admin->getData("admin_acc",
					"WHERE admin_id='". $a_id ."'");
	if($admin->total_row <= 0){
		$sendAry = array();
		$sendAry['systemErr'] = 'window.location = "/index.php?err=3";';
		echo json_encode($sendAry);
		exit;
	}
	
	$delUserDataAry = array();
	$delUserDataAry['admin_id'] = $a_id;
	$delUserDataAry['admin_acc'] = $admin->row['admin_acc'];
	
	$getHtml = new Template("empty"); 
	//取得样版中的样版资料，作为次样版
	$subTemp = $getHtml->getFile($_SERVER['DOCUMENT_ROOT']."/tpl/system_settings/admin_control/del_admin.html");
	$tableData = $getHtml->regexReplace($delUserDataAry, $subTemp, "<!--__", "__-->");
	
	$sendAry = array();
	$sendAry['result'] = $tableData;
	
	echo json_encode($sendAry);
	
}




/**
 * 新增群组权限
 */
function createGroup(){
	
	$getHtml = new Template("empty"); 
	
	//取得样版中的样版资料，作为次样版
	$getHtml->getFile($_SERVER['DOCUMENT_ROOT']."/tpl/system_settings/group_control/create_group.html");
	
	//取得所有项目
	$allItem = new Mysql(DB_HOST, DB_USER, DB_PWD, DB_NAME);
	$allItem->selectTB('main_nav');
	$allItem->getData("*","ORDER BY main_id ASC");
	
	if($allItem->total_row <= 0){
		$sendAry = array();
		$sendAry['systemErr'] = 'window.location = "/index.php?err=3";';
		echo json_encode($sendAry);
		exit;
	}
	
	$createPower = array();
	do{
		$createPower[$allItem->row['main_id']] = 'w';
	}while($allItem->row = $allItem->fetch_assoc());
	unset($allItem);
	
	$newUser = new WebLogin('admin');
	$newUser->mainNav($createPower); //载入预设权限
	$mainNavHtml = $newUser->getTree(0, 'power-menu', '', false); //指定选单ul的class
	
	$replaceAry = array();
	$replaceAry['__admin_power__'] = $mainNavHtml;
	
	//重新组合页面
	$Html = $getHtml->compiler($replaceAry);
	
	$sendAry = array();
	$sendAry['result'] = $Html;
//	//群组预设权限(全部)
//	$sendAry['agPowAry'] = $defCreatePower;
	//群组预设权限
	$sendAry['adminPowAry'] = $createPower;
	
	echo json_encode($sendAry);
}




/**
 * 修改群组权限
 */
function editGroup(){
	if(isset($_POST['ag_id']) && $_POST['ag_id'] != ''){
		$ag_id = $_POST['ag_id'];
	}else{
		$sendAry = array();
		$sendAry['systemErr'] = 'window.location = "/index.php?err=3";';
		echo json_encode($sendAry);
		exit;
	}
	
	//取得欲修改的资料
	$chgUser = new Mysql(DB_HOST, DB_USER, DB_PWD, DB_NAME);
	$chgUser->selectTB("admin_group");
	$chgUser->getData("*",
					  "WHERE ag_id='". $ag_id ."'");
	if($chgUser->total_row <= 0){
		$sendAry = array();
		$sendAry['systemErr'] = 'window.location = "/index.php?err=3";';
		echo json_encode($sendAry);
		exit;
	}
	
	$UserDataAry = $chgUser->row;
	unset($chgUser);
	
	
	//取得所有项目
	$allItem = new Mysql(DB_HOST, DB_USER, DB_PWD, DB_NAME);
	$allItem->selectTB('main_nav');
	$allItem->getData("*","ORDER BY main_id ASC");
	
	if($allItem->total_row <= 0){
		$sendAry = array();
		$sendAry['systemErr'] = 'window.location = "/index.php?err=3";';
		echo json_encode($sendAry);
		exit;
	}
	
	$createPower = array();
	do{
		$createPower[$allItem->row['main_id']] = 'w';
	}while($allItem->row = $allItem->fetch_assoc());
	unset($allItem);
	
	
	$newUser = new WebLogin('admin');
	$newUser->mainNav($createPower); //载入预设权限
	$mainNavHtml = $newUser->getTree(0, 'power-menu', '', false); //指定选单ul的class
	
	
	//复写
	$chgUserDataAry = array();
	$chgUserDataAry['__admin_power__'] = $mainNavHtml;
	$chgUserDataAry['__ag_title__'] = $UserDataAry['ag_title'];
	$chgUserDataAry['__updateControl__'] = '<input class="text-input" name="ag_id" id="ag_id" type="hidden" value="'. $ag_id .'" size="2" />';
	
	
	//样版
	$getHtml = new Template("empty"); 
	//取得样版中的样版资料，作为次样版
	$getHtml->getFile($_SERVER['DOCUMENT_ROOT']."/tpl/system_settings/group_control/create_group.html");
	
	//重新组合页面
	$Html = $getHtml->compiler($chgUserDataAry);
	
	$sendAry = array();
	$sendAry['result'] = $Html;
	//user权限
	$sendAry['userPowAry'] = json_decode($UserDataAry['ag_power'], true);
	//群组预设权限
	$sendAry['adminPowAry'] = $createPower;
	
	echo json_encode($sendAry);
	
}



/**
 * 删除管理者群组
 */
function delGroup(){
	if(isset($_POST['ag_id']) && $_POST['ag_id'] != ''){
		$ag_id = $_POST['ag_id'];
	}else{
		$sendAry = array();
		$sendAry['systemErr'] = 'window.location = "/index.php?err=3";';
		echo json_encode($sendAry);
		exit;
	}
	
	//取得群组资料
	$admin = new Mysql(DB_HOST, DB_USER, DB_PWD, DB_NAME);
	$admin->selectTB('admin_group');
	$admin->getData("*",
					"WHERE ag_id='". $ag_id ."'");
	if($admin->total_row <= 0){
		$sendAry = array();
		$sendAry['systemErr'] = 'window.location = "/index.php?err=3";';
		echo json_encode($sendAry);
		exit;
	}
	
	$delUserDataAry = array();
	$delUserDataAry['ag_id'] = $ag_id;
	$delUserDataAry['ag_title'] = $admin->row['ag_title'];
	
	$getHtml = new Template("empty"); 
	//取得样版中的样版资料，作为次样版
	$subTemp = $getHtml->getFile($_SERVER['DOCUMENT_ROOT']."/tpl/system_settings/group_control/del_group.html");
	$tableData = $getHtml->regexReplace($delUserDataAry, $subTemp, "<!--__", "__-->");
	
	$sendAry = array();
	$sendAry['result'] = $tableData;
	
	echo json_encode($sendAry);
	
}


/**
 * 更新基本资料
 */
function updateSelf(){
	
	$admin_id = $_SESSION['_admin_id']; 
	
	//取得使用者资料
	$admin = new Mysql(DB_HOST, DB_USER, DB_PWD, DB_NAME);
	$admin->selectTB('admin');	//选择资料表
	$admin->getData("*, admin_group.ag_title",
					"LEFT JOIN admin_group ON (admin.ag_id = admin_group.ag_id) WHERE admin_id='". $admin_id ."'");
	if($admin->total_row <= 0){
		$sendAry = array();
		$sendAry['systemErr'] = 'window.location = "/index.php?err=3";';
		echo json_encode($sendAry);
		exit;
	}
	
	$getHtml = new Template("empty"); 
	//取得样版中的样版资料，作为次样版
	$subTemp = $getHtml->getFile($_SERVER['DOCUMENT_ROOT']."/tpl/per_info/update_self.html");
	$tableData = $getHtml->regexReplace($admin->row, $subTemp, "<!--__", "__-->");
	
	$sendAry = array();
	$sendAry['result'] = $tableData;
	
	echo json_encode($sendAry);
	
	
}



/**
 * 新增讯息种类
 */
function createNewsType(){

	$getHtml = new Template("empty"); 
	
	//取得样版中的样版资料，作为次样版
	$getHtml->getFile($_SERVER['DOCUMENT_ROOT']."/tpl/system_settings/news_type/create_news_type.html");
	//欲取代的内容
	$compilerAry = array();
	$compilerAry['__requestType__'] = 	'create';			//新增资料
	
	//重新组合页面
	$Html = $getHtml->compiler($compilerAry);
	
	$sendAry = array();
	$sendAry['result'] = $Html;
	
	echo json_encode($sendAry);
}






/**
* 修改讯息种类
*/
function editNewsType(){
	
	if(isset($_POST['data_id']) && $_POST['data_id'] != ''){
		$data_id = $_POST['data_id'];
	}else{
		$sendAry = array();
		$sendAry['systemErr'] = 'alert("查无资料。");';
		echo json_encode($sendAry);
		exit;
	}
	
	$getNt = new Mysql(DB_HOST, DB_USER, DB_PWD, DB_NAME);
	$getNt->selectTB('news_type');
	$getNt->getData("*", 
					"WHERE nt_id='". $data_id ."'");
	if($getNt->total_row <= 0){
		$sendAry = array();
		$sendAry['systemErr'] = 'alert("查无资料。");';
		echo json_encode($sendAry);
		exit;
	}
	
	//欲取代的内容
	$compilerAry = array();
	$compilerAry['__requestType__'] = 	'update';			//新增资料
	$compilerAry['__updaetSystemId__'] = 	'<input type="hidden" name="nt_id" value="'.$data_id .'" />';
	foreach ($getNt->row as $key => $val){
		$compilerAry['__'.$key.'__'] = $val;
	}
	$getHtml = new Template("empty");
	//取得样版中的样版资料，作为次样版
	$getHtml->getFile($_SERVER['DOCUMENT_ROOT']."/tpl/system_settings/news_type/create_news_type.html");
	
	//重新组合页面
	$Html = $getHtml->compiler($compilerAry);

	$sendAry = array();
	$sendAry['result'] = $Html;

	echo json_encode($sendAry);
}


/**
* 删除讯息种类
*/
function delNewsType(){
	if(isset($_POST['data_id']) && $_POST['data_id'] != ''){
		$data_id = $_POST['data_id'];
	}else{
		$sendAry = array();
		$sendAry['systemErr'] = 'alert("查无资料。");';
		echo json_encode($sendAry);
		exit;
	}
	
	$getNt = new Mysql(DB_HOST, DB_USER, DB_PWD, DB_NAME);
	$getNt->selectTB('news_type');
	$getNt->getData("*",
						"WHERE nt_id='". $data_id ."'");
	if($getNt->total_row <= 0){
		$sendAry = array();
		$sendAry['systemErr'] = 'alert("查无资料。");';
		echo json_encode($sendAry);
		exit;
	}
	
	//欲取代的内容
	$compilerAry = array();
	$compilerAry['__requestType__'] = 	'delete';			//删除资料
	$compilerAry['__updaetSystemId__'] = 	'<input type="hidden" name="nt_id" value="'.$data_id .'" />';
	foreach ($getNt->row as $key => $val){
		$compilerAry['__'.$key.'__'] = $val;
	}
	$getHtml = new Template("empty");
	//取得样版中的样版资料，作为次样版
	$getHtml->getFile($_SERVER['DOCUMENT_ROOT']."/tpl/system_settings/news_type/del_news_type.html");
	
	//重新组合页面
	$Html = $getHtml->compiler($compilerAry);
	
	$sendAry = array();
	$sendAry['result'] = $Html;
	
	echo json_encode($sendAry);
	
	
	
}




/**
* 新增危险IP
*/
function createDangerIP(){
	$getHtml = new Template("empty");
	//取得样版中的样版资料，作为次样版
	$subTemp = $getHtml->getFile($_SERVER['DOCUMENT_ROOT']."/tpl/member_settings/danger_ip_list/create_danger_ip.html");

	$getSelectDangerDays = new Mysql(DB_HOST, DB_USER, DB_PWD, DB_NAME);
	$getSelectDangerDays->selectTB("danger_ip");
	$selectOptions = $getSelectDangerDays->enum_select("dan_locked_hours");

	$selectHtml = '<select name="dan_locked_hours" id="dan_locked_hours">';

	for($i=0; $i<count($selectOptions); $i++){

		if($selectOptions[$i] == 0){
			$selectHtml .= '<option value="'. $selectOptions[$i] .'" selected="selected" >永久</option>';
		}else{
				
			$quot = 	floor($selectOptions[$i] / 24);	//商数
			$residue =	($selectOptions[$i] % 24);		//余数
				
			if($quot > 0){
				$quot = $quot."天";
			}else{
				$quot = "";
			}
				
			if($residue > 0){
				$residue = $residue."小时";
			}else{
				$residue = "";
			}
				
			$selectHtml .= '<option value="'. $selectOptions[$i] .'">'. $quot . $residue .'</option>';
				
		}

	}
	$selectHtml .= '</select>';
	unset($getSelectDangerDays);

	$tempAry = array();
	$tempAry['dan_locked_hours'] = $selectHtml;
	$tableData = $getHtml->regexReplace($tempAry, $subTemp, "<!--__", "__-->");


	$sendAry = array();
	$sendAry['result'] = $tableData;
	echo json_encode($sendAry);
	unset($memData);
}


/**
 * 修改危险IP
 */
function dangIpDataEdit(){

	if(isset($_POST['d_id']) && $_POST['d_id'] != ''){
		$dan_id = $_POST['d_id'];
	}else{
		$sendAry = array();
		$sendAry['systemErr'] = 'window.location = "/index.php?err=3";';
		echo json_encode($sendAry);
		exit;
	}

	//取得危险IP资料
	$Data = new Mysql(DB_HOST, DB_USER, DB_PWD, DB_NAME);
	$Data->selectTB('danger_ip');	//选择资料表
	$Data->getData("*",
					"WHERE dan_id='". $dan_id ."'");
	if($Data->total_row <= 0){
		$sendAry = array();
		$sendAry['systemErr'] = 'window.location = "/index.php?err=3";';
		echo json_encode($sendAry);
		exit;
	}

	if($Data->row['dan_locked'] == 'y'){
		$Data->row['dan_locked_y'] = ' checked= "checked" ';
		$Data->row['dan_locked_n'] = '';
	}else{
		$Data->row['dan_locked_y'] = '';
		$Data->row['dan_locked_n'] = ' checked= "checked" ';
	}


	$selectOptions = $Data->enum_select("dan_locked_hours");

	$selectHtml = '<select name="dan_locked_hours" id="dan_locked_hours">';

	for($i=0; $i<count($selectOptions); $i++){

		if($Data->row['dan_locked_hours'] == $selectOptions[$i]){
			$selected = 'selected="selected"';
		}else{
			$selected = '';
		}

		if($selectOptions[$i] == 0){
			$selectHtml .= '<option value="'. $selectOptions[$i] .'" '. $selected .' >永久</option>';
		}else{
			$quot = 	floor($selectOptions[$i] / 24);	//商数
			$residue =	($selectOptions[$i] % 24);		//余数
				
			if($quot > 0){
				$quot = $quot."天";
			}else{
				$quot = "";
			}
				
			if($residue > 0){
				$residue = $residue."小时";
			}else{
				$residue = "";
			}
				
			$selectHtml .= '<option value="'. $selectOptions[$i] .'" '. $selected .'>'. $quot . $residue .'</option>';
		}

	}
	$selectHtml .= '</select>';
	$Data->row['dan_locked_hours'] = $selectHtml;

	$getHtml = new Template("empty");
	//取得样版中的样版资料，作为次样版
	$subTemp = $getHtml->getFile($_SERVER['DOCUMENT_ROOT']."/tpl/member_settings/danger_ip_list/update_danger_ip.html");
	$tableData = $getHtml->regexReplace($Data->row, $subTemp, "<!--__", "__-->");

	$sendAry = array();
	$sendAry['result'] = $tableData;
	echo json_encode($sendAry);
	unset($Data);
}




/**
 * 删除危险IP
 */
function dangIpDataDel(){
	if(isset($_POST['d_id']) && $_POST['d_id'] != ''){
		$dan_id = $_POST['d_id'];
	}else{
		$sendAry = array();
		$sendAry['systemErr'] = 'window.location = "/index.php?err=3";';
		echo json_encode($sendAry);
		exit;
	}

	//取得使用者资料
	$data = new Mysql(DB_HOST, DB_USER, DB_PWD, DB_NAME);
	$data->selectTB('danger_ip');
	$data->getData("*",
					"WHERE dan_id='". $dan_id ."'");
	if($data->total_row <= 0){
		$sendAry = array();
		$sendAry['systemErr'] = 'window.location = "/index.php?err=3";';
		echo json_encode($sendAry);
		exit;
	}

	$getHtml = new Template("empty");
	//取得样版中的样版资料，作为次样版
	$subTemp = $getHtml->getFile($_SERVER['DOCUMENT_ROOT']."/tpl/member_settings/danger_ip_list/del_danger_ip.html");
	$tableData = $getHtml->regexReplace($data->row, $subTemp, "<!--__", "__-->");

	$sendAry = array();
	$sendAry['result'] = $tableData;

	echo json_encode($sendAry);

}



?>