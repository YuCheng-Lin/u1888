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
$ag_id            = $html->WebLogin->ag_id;
$updateAdminBtn   = '';
$cssImport        = array();
$cssImport[]      = '<link rel="stylesheet" href="css/style.css">';
$javascriptImport = array();
$updBtn           = '<button data-whatever="'.$html->lang['UpdateData'].'" data-toggle="tooltip" data-placement="top" title="'.$html->lang['UpdateData'].'" data-target="#dialogModal" data-fn="updRecord" data-id="%s" class="dialogModal btn btn-xs btn-default pull-left" type="button"><span class="glyphicon glyphicon-cog" aria-hidden="true"></span></button>';
$updMemBtn        = '<button data-whatever="'.$html->lang['UpdateData'].'" data-toggle="tooltip" data-placement="top" title="'.$html->lang['UpdateData'].'" data-target="#dialogModal" data-fn="updMemRecord" data-id="%s" class="dialogModal btn btn-xs btn-default pull-left" type="button"><span class="glyphicon glyphicon-cog" aria-hidden="true"></span></button>';
// $javascriptImport[] = '<script type="text/javascript" src="js/custom.js"> </script>';
$javascriptImport[] = '<script type="text/javascript">$(function(){
	$("#select").on("change", function(){
		var select = $(this).find("option:selected");
		$("#date_timepicker_start").val(select.data("start"));
		$("#date_timepicker_end").val(select.data("end"));
	});
});</script>';
//管理者本页权限
switch($html->WebLogin->nowPagePower){
	case 'w':
		break;
	case 'r':
		$updBtn    = '--';
		$updMemBtn = '--';
		break;
	default:
		header("location: /index.php?err=6");
		exit;
}

//取得样版中的样版资料，作为次样版
$tableRowData = $html->getFile();
$indexAgent   = $html->getFile($_SERVER['DOCUMENT_ROOT'].'/tpl/reportData/pointsTrans/topAgent.html');

//20150720-加入子帳號判斷
$subAdminData = [];
if($html->WebLogin->ag_id == '4'){
	$db = new PDO_DB(DB_HOST, DB_USER, DB_PWD, DB_NAME);
	$db->selectTB('Admin');
	$db->getData("*", "WHERE admin_id = '".$html->WebLogin->subAdmin."'");
	$db->execute();
	if($db->total_row <= 0){
		$admin_acc = $html->WebLogin->admin_acc;//找不到子帳號，預設自己
	}
	if($db->total_row > 0){
		$admin_acc    = $db->row['admin_acc'];
		$subAdminData = $db->row;
	}
}else{
	$admin_acc = $html->WebLogin->admin_acc;
}
if(!empty($_GET['acc'])){//搜寻代理帐号
	$admin_acc = trim($_GET['acc']);
	$admin_acc = stripslashes($admin_acc);
	$admin_acc = str_replace("'", "''", $admin_acc);
	$admin_acc = stripslashes($admin_acc);
}

// 20150806-no使用期數
$dateAry = $html->dateArray();
$count   = count($dateAry['start']);
$options = '<option value="select"></option>';
$select  = empty($_GET['select']) ? $count-1 : $_GET['select'];
// $today   = date('Y-m-d');
// $lastday = date('Y-m-d', strtotime('-1 day'));
// for ($i=$count-1; $i > 0; $i--) {
// 	$options .= '<option';
// 	if($i == $select){
// 		$options .= ' selected="selected"';
// 	}
// 	$options .= ' value="'.($i).'" data-start="'.$dateAry['start'][$i-1].'" data-end="'.$dateAry['end'][$i].'">'.(sprintf($html->lang['GameOption'], $i)).'：'.$dateAry['start'][$i-1].' '.$html->StartTime.' ～ '.$dateAry['end'][$i].' '.$html->EndTime.'</option>';
// }
// $options .= '<option value="today"'.($select=='today' ? ' selected="selected"' : '').'>'.$html->lang['DateToday'].'：'.$today.' '.$html->StartTime.' ～ '.$today.' '.$html->EndTime.'</option>';
// $options .= '<option value="lastday"'.($select=='lastday' ? ' selected="selected"' : '').'>'.$html->lang['DateLastDay'].'：'.$lastday.' '.$html->StartTime.' ～ '.$lastday.' '.$html->EndTime.'</option>';

$startDate = $dateAry['start'][$count-2];//預設查詢最新期
// if(!empty($dateAry['start'][$select-1])){
// 	$startDate = $dateAry['start'][$select-1];
// }
$endDate = $dateAry['end'][$count-1];
// if(!empty($dateAry['end'][$select])){
// 	$endDate = $dateAry['end'][$select];
// }
// if($select == 'today'){
// 	$startDate = $today;
// 	$endDate   = $today;
// }
// if($select == 'lastday'){
// 	$startDate = $lastday;
// 	$endDate   = $lastday;
// }
// $startDateTime = $startDate.' '.$html->StartTime;
// $endDateTime   = $endDate.' '.$html->EndTime;

// 20150806-使用日期區間
$limitDate    = date( "Y-m-d", mktime(0,0,0,date("m")-2,date("d"),date("Y")));//限制搜尋僅前二個月
$thisMonFirst = date('Y-m-01');
$nowDate      = date('Y-m-d');
// $startDate    = $thisMonFirst;
if(!empty($_GET['date_timepicker_start'])){
	$startDate = $_GET['date_timepicker_start'];
}
// $endDate = $nowDate;
if(!empty($_GET['date_timepicker_end'])){
	$endDate = $_GET['date_timepicker_end'];
}
//如果超過預設限制搜尋大小
if($startDate < $limitDate){
	$startDate = $thisMonFirst;
	$endDate   = $nowDate;
}

for ($i=$count-1; $i > 0; $i--) {
	$options .= '<option';
	if($dateAry['start'][$i-1] == $startDate && $dateAry['end'][$i] == $endDate){
		$options .= ' selected="selected"';
	}
	$options .= ' value="'.($i).'" data-start="'.$dateAry['start'][$i-1].'" data-end="'.$dateAry['end'][$i].'">'.(sprintf($html->lang['GameOption'], $i)).'：'.$dateAry['start'][$i-1].' '.$html->StartTime.' ～ '.$dateAry['end'][$i].' '.$html->EndTime.'</option>';
}

//回主帐号连结
$goBackMine = '';
if(($html->WebLogin->ag_id != '4' && $admin_acc != $html->WebLogin->admin_acc) || ($html->WebLogin->ag_id == '4' && !empty($subAdminData['admin_acc']) && $admin_acc != $subAdminData['admin_acc'])){
	$goBackMine = '<a href="'.$_SERVER['SCRIPT_NAME'].'" class="btn btn-default pull-right">'.$html->lang['BackMainAcc'].'</a>';
}

//取得管理表资料
$notice     = '';
$admin_list = new PDO_DB(DB_HOST, DB_USER, DB_PWD, DB_NAME);

//代理帐号搜寻
if(!empty($admin_acc)){
	$breadcrumb[] = '<li class="active">'.$admin_acc.'</li>';
	$admin_list->selectTB('Admin');	//选择资料表
	$admin_list->getData("*", "WHERE admin_acc = '".$admin_acc."'");
	$admin_list->execute();
	$mergeAry = array();

	//代理资料表
	if($admin_list->total_row > 0){
		try{
			$admin_id  = $admin_list->row['admin_id'];
			$admin_list->row['commissionRate'] = $admin_list->row['ag_id']=='2'? $admin_list->row['commissionRate'] : 0;
			$admin_list->row['ReturnRate']     = $admin_list->row['ag_id']=='2'? $admin_list->row['ReturnRate'] : 0;

			$db     = new PDO_DB(DB_HOST, DB_USER, DB_PWD, DB_NAME);
			$Points = new Points($html->WebLogin);
			$Points->produceLink($admin_list->row);
			if(!$Points->allowSearch){
				throw new Exception("not allow to search");
			}

			$Points->getRangeReport($admin_list->row, $startDate, $endDate);
			//查詢帳號的資料
			$mergeAry             = $Points->chkAdminData;
			$mergeAry['ag_title'] = $html->lang['Group'.$mergeAry['ag_id']];
			// $mergeAry             += $Points->chkAdminData;
			$mergeAry['Total']    = ($Points->is_Negative($Points->chkAdminData['total'])) ? '<span class="text-danger">'.$Points->chkAdminData['total'].'</span>' : $Points->chkAdminData['total'];
			$mergeAry['UpTotal']  = $Points->chkAdminData['upTotal'];
			$mergeAry['UpTotal']  = ($Points->is_Negative($mergeAry['UpTotal'])) ? '<span class="text-danger">'.$mergeAry['UpTotal'].'</span>' : $mergeAry['UpTotal'];
			$mergeAry['btn']      = '--';
			$mergeAry['note']     = empty($mergeAry['note']) ? '--' : $mergeAry['note'];
			if(!empty($mergeAry['AdminWinLose']) && $mergeAry['AdminWinLose']){
				try {
					if(empty($mergeAry['note'])){
						throw new Exception('no note');
					}
					if(empty($mergeAry['id'])){
						throw new Exception('no id');
					}
					$mergeAry['btn'] = sprintf($updBtn, $mergeAry['id']);
				} catch (Exception $e) {
					$mergeAry['btn']  = '--';
				}
			}
			//麵包屑
			$breadcrumb = $Points->breadcrumb;

			//下线总数 > 0 搜寻下线代理
			$adminLog = '';
			if(!empty($Points->downAgentData[$admin_id]) && count($Points->downAgentData[$admin_id]) > 0){
				$agentTemp    = $html->getFile($_SERVER['DOCUMENT_ROOT'].'/tpl/reportData/pointsTrans/agentTemp.html');
				$agentLogTemp = $html->regexMatch($agentTemp, '<!--__downAgentStart', 'downAgentEnd__-->');
				foreach ($Points->downAgentData[$admin_id] as $value) {
					$db->selectTB('Admin');
					$db->getData("*", "WHERE admin_id = '".$value['admin_id']."'");
					$db->execute();
					
					$value['admin_acc'] = $db->row['admin_acc'];

					$value['startDate'] = $startDate;
					$value['endDate']   = $endDate;
					$value['ag_title']  = $html->lang['Group'.$value['ag_id']];
					$value['Total']     = ($Points->is_Negative($value['Total'])) ? '<span class="text-danger">'.$value['Total'].'</span>' : $value['Total'];
					$value['UpTotal']   = ($value['upCommissionTotal'] + $value['upReturnTotal']);
					$value['UpTotal']   = ($Points->is_Negative($value['UpTotal'])) ? '<span class="text-danger">'.$value['UpTotal'].'</span>' : $value['UpTotal'];
					$value['btn']       = '--';
					$value['note']      = empty($value['note']) ? '--' : $value['note'];
					// $value['select']    = $select;//連結使用
					if(!empty($value['AdminWinLose']) && $value['AdminWinLose']){
						try {
							if(empty($value['id'])){
								throw new Exception('no id');
							}
							$value['btn'] = sprintf($updBtn, $value['id']);
						} catch (Exception $e) {
							$value['btn']  = '--';
						}
					}
					// 20150721-拿掉過濾輸贏為0的帳號
					// if($value['WinLoseTotal'] != 0){
						$adminLog .= $html->regexReplace($value, $agentLogTemp);
					// }
				}
				if(!empty($adminLog)){
					$agentTemp = $html->regexReplace(array("downAgent"=>$adminLog), $agentTemp, '<!--__', '__-->');
					$mergeAry['agentTemp'] = $agentTemp;
				}
			}
			//下线总数 > 0 搜寻下线会员
			$memLog = '';
			if(!empty($Points->downMemData[$admin_id]) && count($Points->downMemData[$admin_id]) > 0){
				$memTemp = $html->getFile($_SERVER['DOCUMENT_ROOT'].'/tpl/reportData/pointsTrans/memTemp.html');
				$agentLogTemp = $html->regexMatch($memTemp, '<!--__downAgentStart', 'downAgentEnd__-->');
				foreach ($Points->downMemData[$admin_id] as $value) {
						$db->selectTB('A_Member');
						$db->getData("*", "WHERE MemberID = '".$value['MemberId']."'");
						$db->execute();
						
						$value['MemberAccount'] = $db->row['MemberAccount'];
					// 20150721-拿掉過濾輸贏為0的帳號
					// if($value['BetTotal'] > 0){
						$value['ag_title'] = $html->lang['GroupMem'];
						// $value['Total']    = ($Points->is_Negative($value['Total'])) ? '<span class="text-danger">'.$value['Total'].'</span>' : $value['Total'];
						$value['btn']      = '--';
						$value['note']     = empty($value['note']) ? '--' : $value['note'];
						if(!empty($value['MemWinLose']) && $value['MemWinLose']){
							try {
								if(empty($value['id'])){
									throw new Exception('no id');
								}
								$value['btn'] = sprintf($updMemBtn, $value['id']);
							} catch (Exception $e) {
								$value['btn']  = '--';
							}
						}
						//20150708-需求＋上該期投注紀錄的超連結
						$value['MemberAccount'] = '<a href="/reportData/betLog/betLog.php?mem='.$value['MemberAccount'].'&game=all&date_timepicker_start='.$startDate.'&date_timepicker_end='.$endDate.'">'.$value['MemberAccount'].'</a>';

						$memLog .= $html->regexReplace($value, $agentLogTemp);
					// }
				}
				if(!empty($memLog)){
					$memTemp = $html->regexReplace(array("downMember"=>$memLog), $memTemp, '<!--__', '__-->');
					$mergeAry['memTemp'] = $memTemp;
				}
			}
			if($admin_list->row['upAdmin_id'] != NULL){
				$indexAgent = $html->getFile($_SERVER['DOCUMENT_ROOT'].'/tpl/reportData/pointsTrans/indexAgent.html');
			}
			$mergeAry['indexAgent'] = $html->regexReplace($mergeAry, $indexAgent, '<!--__', '__-->');
			unset($Points);
		}catch(Exception $e){
			$notice = $html->__systemConfig['__notAllowSearch__'];
		}
		$tableRowData = $html->regexReplace($mergeAry, $tableRowData, "<!--__", "__-->");
	}
}

if($admin_list->total_row <= 0){
	$notice = $html->__systemConfig['__notFound__'];
}

//-----------------------------------------将欲取代的内容与样版重新组合-------------------------------------------------
//欲取代的内容
$compilerAry = array();

//载入本页样版
$compilerAry['__MainContent__']              = $tableRowData;//取次样版
$compilerAry['__cssImport__']                = join("\n", $cssImport);//引用css
$compilerAry['__javascriptImport__']         = join("\n", $javascriptImport);//引用javascript
$compilerAry['__updateAdmin__']              = $updateAdminBtn;//按钮
$compilerAry['__notice__']                   = $notice;//系统提示
$compilerAry['__breadcrumb__']               = join("", array_reverse($breadcrumb));
$compilerAry['__goBackMine__']               = $goBackMine;
$compilerAry['__acc__']                      = $admin_acc;

// 20150806-使用日期區間
$compilerAry['__setdate_p1M_from__']  = date( "Y-m-d", mktime(0,0,0,date("m")-1,1,date("Y")));
$compilerAry['__setdate_p1M_to__']    = date( "Y-m-t", mktime(23,59,59,	date("m")-1,date("d"),date("Y")));
$compilerAry['__setdate_nowM_from__'] = date( "Y-m-01");
$compilerAry['__setdate_nowM_to__']   = date( "Y-m-d");
$compilerAry['__setdate_nowW_from__'] = date( "Y-m-d", mktime(0,0,0,date("m"),date("d")-date("w")-6,date("Y")));
$compilerAry['__setdate_nowW_to__']   = date( "Y-m-d", mktime(23,59,59,date("m"),date("d")-date("w"),date("Y")));
$compilerAry['__setdate_YD_from__']   = date( "Y-m-d", mktime(0,0,0,date("m"),date("d")-1,date("Y")));
$compilerAry['__setdate_YD_to__']     = date( "Y-m-d", mktime(23,59,59,date("m"),date("d")-1,date("Y")));
$compilerAry['__setdate_nowD_from__'] = date( "Y-m-d", mktime(0,0,0,date("m"),date("d"),date("Y")));
$compilerAry['__setdate_nowD_to__']   = date( "Y-m-d", mktime(23,59,59,date("m"),date("d"),date("Y")));
$compilerAry['__p3M__']               = date( "Y-m-d", mktime(0,0,0,date("m")-2,date("d"),date("Y")));

$compilerAry['__start_time__']  = $startDate;
$compilerAry['__end_time__']    = $endDate;

// 20150806-不使用期數
$compilerAry['__options__'] = $options;
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
unset($html, $admin_list);
exit;
?>