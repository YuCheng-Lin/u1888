<?php

//----------------------------------開發完畢後，請刪除
//計算執行時間
$stime=explode(" ",microtime());
$ss=$stime[0]+$stime[1];
//----------------------------------開發完畢後，請刪除

include_once $_SERVER['DOCUMENT_ROOT'].'/class/Template.class.php';

//載入基礎樣版
$html = new Template($_SERVER['DOCUMENT_ROOT']."/tpl/public_temp_v2.html");

$javascriptImport 	= '';
$cssImport 			= '';

$mainContent		= $html->getFile($_SERVER['DOCUMENT_ROOT']."/tpl/item_temp.html");



//-----------------------------------------將欲取代的內容與樣版重新組合-------------------------------------------------
//欲取代的內容
$compilerAry = array();

//載入本頁樣版
$compilerAry['__MainContent__'] = 				$mainContent;		//取次樣版
$compilerAry['__cssImport__'] = 				$cssImport;				//引用css
$compilerAry['__javascriptImport__'] = 			$javascriptImport;		//引用javascript
$compilerAry['__updateAdmin__'] =	 			"";		//資料更新按鈕
// $compilerAry['__updateBrowser__'] =	 			'<div style="color: red; text-align: center; padding: 0pt; margin: 0pt; font-size: 17px; font-weight: bold;">強烈建議升級您的瀏覽器</div>';		//資料更新按鈕

//----------------------------------開發完畢後，請刪除
//計算執行時間
$mtime=explode(" ",microtime());
$es=$mtime[0]+$mtime[1];
$mtime=$es-$ss;	//總耗時
//----------------------------------開發完畢後，請刪除
$compilerAry['__mtime__'] = 					'<div style="text-align:right;color:#255c88;">系統執行耗時:'. $mtime .'</div>';			//系統提示



//-----------------------------------------將欲取代的內容與樣版重新組合-------------------------------------------------

//重新組合頁面
$cleanHtml = $html->compiler($compilerAry);
echo $cleanHtml;
unset($html);


?>