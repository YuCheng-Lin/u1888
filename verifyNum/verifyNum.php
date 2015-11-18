<?php

//載入圖形驗證
include_once 'createVerifyNum.class.php';

// 產生n位隨機數
function randNum($n){
	$verifyNum = $n;
	$Ary = array('1','2','3','4','5','6','7','8','9','1','2','3','4','5','6','7','8','9','A','B','C','D','E','F','G','H','I','J','K','L','M','N','P','Q','R','S','T','U','V','W','X','Y','Z');
	$Cont = '';
	srand ((double) microtime() * 10000000);
	$rand_keys = array_rand($Ary, $n);
	for($i=0; $i<count($rand_keys); $i++){
		$Cont .= $Ary[$rand_keys[$i]];
	}
	return $Cont;
}



if(!isset($_GET['type']) && $_GET['type'] == ''){
	exit;
}

if(!isset($_SESSION)){
	session_start();
}

switch(trim($_GET['type'])){
	case 'register':
		
		$verifyCont = randNum(4);
		$_SESSION['verifyNum'] = $verifyCont;
		$gif = new createGif($verifyCont);
		$gif->result();
		unset($gif);
		exit;
		
	break;
	
	default:
		exit;
	break;
}



//./configure --prefix=/opt --with-quantum-depth=16 --disable-dependency-tracking --with-x=yes --x-includes=/usr/X11R6/include --x-libraries=/usr/X11R6/lib/ --without-perl


//export MAGICK_HOME="$HOME/Library/ImageMagick-6.6.7-3"  
//export PATH="$MAGICK_HOME/bin:$PATH"  
//export DYLD_LIBRARY_PATH="$MAGICK_HOME/lib"  


?>