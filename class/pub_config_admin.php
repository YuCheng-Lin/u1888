<?php
header("Content-Type:text/html; charset=utf-8");
date_default_timezone_set("Asia/Taipei");

$global_vars = array(
	"DB_NAME"          => "Game_Main",
	"DB_ANALYSIS_NAME" => "Game_Analysis",
	"DB_WINLOSE_NAME"  => "Game_WinloseLog",
	"LANGUAGE_DIR"     => $_SERVER['DOCUMENT_ROOT']."/class/languages"
);	
//自行測試
$global_vars_v2['yulin'] = array(
	"GAME_HOST"        => "192.168.1.121",
	"GAME_PORT"        => "19007",
	"DB_HOST"          => "localhost",
	"DB_USER"          => "sa",
	"DB_PWD"           => "zz791219",
	"DEFAULT_LANGUAGE" => "zh",
	"ENV"              => "local",
	"DEBUG"            => true,
	"CURRENCY"         => 'MYR',//平台幣別
	"MAXMEMMONEY"      => '2000000',//轉給遊戲會員點數上限
	// "DB_HOST"       => "202.150.211.182,19999",
	// "DB_PWD"        => "1q2w3e4rVVV",
);
//本機測試
$global_vars_v2['local'] = array(
	"GAME_HOST"        => "192.168.1.121",
	"GAME_PORT"        => "19007",
	"DB_HOST"          => "localhost",
	"DB_USER"          => "sa",
	"DB_PWD"           => "1q2w3e4rvvv",
	"DEFAULT_LANGUAGE" => "zh",
	"ENV"              => "local",
	"EMERGENCY"        => "y",//緊急開關系統是否開放
	"DEBUG"            => true,
	"CURRENCY"         => 'THB',//平台幣別
	"LIMITADMINID"     => '4091',//限制開設90%帳號編號
	"LIMITRATE"        => '90',//限制開設%數
	"MAXMEMMONEY"      => '2000000',//轉給遊戲會員點數上限
);
//台中泰國測試機
$global_vars_v2['test'] = array(
	"GAME_HOST"        => "127.0.0.1",
	"GAME_PORT"        => "19007",
	"DB_HOST"          => "localhost",
	"DB_USER"          => "sa",
	"DB_PWD"           => "1q2w3e4rVVV",
	"DEFAULT_LANGUAGE" => "zh",
	"ENV"              => "dev",
	"EMERGENCY"        => "y",//緊急開關系統是否開放
	"DEBUG"            => true,
	"CURRENCY"         => 'THB',//平台幣別
	"LIMITADMINID"     => '0',//限制開設90%帳號編號
	"LIMITRATE"        => '90',//限制開設%數
	"MAXMEMMONEY"      => '2000000',//轉給遊戲會員點數上限
);
//泰國正式机
$global_vars_v2['online-tai'] = array(
	"GAME_HOST"        => "127.0.0.1",
	"GAME_PORT"        => "19007",
	"DB_HOST"          => "localhost",
	"DB_USER"          => "sa",
	"DB_PWD"           => "1q2w3e4rVVV",
	"DEFAULT_LANGUAGE" => "en",
	"ENV"              => "online",
	"EMERGENCY"        => "y",//緊急開關系統是否開放
	"DEBUG"            => false,
	"CURRENCY"         => 'THB',//平台幣別
	"LIMITADMINID"     => '0',//限制開設90%帳號編號
	"LIMITRATE"        => '90',//限制開設%數
	"MAXMEMMONEY"      => '2000000',//轉給遊戲會員點數上限
);

while (list($key, $value) = each($global_vars)) {
	define($key, $value);
}

switch ($_SERVER['HTTP_HOST']) {
	case 'webta.gametest.com':
		foreach ($global_vars_v2['yulin'] as $key => $val){
			define($key, $val);
		}
		break;
	case 'b.u1888tai':
		foreach ($global_vars_v2['local'] as $key => $val){
			define($key, $val);
		}
		break;
	case 'srv.u1888t.com':
		foreach ($global_vars_v2['test'] as $key => $val){
			define($key, $val);
		}
		break;
	case 'srv.bbgames365.com':
		foreach ($global_vars_v2['online-tai'] as $key => $val){
			define($key, $val);
		}
		ini_set('allow_call_time_pass_reference', 'Off');
		ini_set('display_errors', 'Off');
		ini_set('display_startup_errors', 'Off');
		ini_set('error_reporting', '~E_ALL & ~E_DEPRECATED & ~E_STRICT');
		ini_set('html_errors', 'Off');
		ini_set('log_errors', 'On');
		ini_set('output_buffering', 4096);
		break;
	default:
		die('Not Allow Host Name');
}
?>