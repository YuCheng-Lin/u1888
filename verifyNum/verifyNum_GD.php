<?php
class createGif {
	
	/**
	 * 建立圖檔
	 */
	var $img = '';
	/**
	 * 圖檔名稱
	 */
	var $fileName = '';
	/**
	 * 圖檔變換數量
	 */
	var $fileAmount = 0;
	/**
	 * 圖檔文字內容
	 */
	var $fileCont = '';
	/**
	 * 圖檔變換速度(百分秒)
	 */
	var $fileSpeed = 100;
	/**
	 * 圖檔大小(寬)
	 */
	var $fileSizeX = 0;
	/**
	 * 圖檔大小(高)
	 */
	var $fileSizeY = 0;
	/**
	 * 暫存圖檔名稱
	 */
	var $initName = '';
	/**
	 * 背景千擾圖(原檔路徑)
	 * @var String
	 */
	var $fileBgPath = '';
	/**
	 * 背景圖圖名稱
	 * @var String
	 */
	var $bgName = '';
	/**
	 * 背景隨機截圖坐標(寬)
	 * @var Int
	 */
	var $randX = 0;
	/**
	 * 背景隨機截圖坐標(高)
	 * @var Int
	 */
	var $randY = 0;
	
	/**
	 * 建構子
	 * 
	 * @param String $file_cont 圖檔內容文字
	 * @param String $file_name 圖檔名稱
	 * @param String $chg_speed 變換速度(秒)
	 * @param integer $file_amount 圖檔變換數量
	 * @param integer $file_x 圖檔寬度
	 * @param integer $file_y 圖檔高度
	 */
	function createGif($file_cont, $file_name='', $chg_speed=1, $file_amount=5, $file_x= 100, $file_y=40, $file_bg_path='bg.jpg'){
		header("Expires: Mon, 9 dec 2002 00:00:00 GMT");
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
		header("Cache-Control: no-store, no-cache, must-revalidate");
		header("Cache-Control: post-check=0, pre-check=0", false);
		header("Pragma: no-cache");
		header ("Content-type: image/gif");
		
		$this->fileCont = $file_cont;
		$this->fileSpeed = $chg_speed * 100;
		$this->fileAmount = $file_amount;
		$this->fileSizeX = $file_x;
		$this->fileSizeY = $file_y;
		$this->initName = date("His");
		
		//如果沒有指定檔案名稱，就用initName+fileCont當檔案名稱
		if($file_name != ''){
			$this->fileName = $file_name;
		}else{
			$this->fileName = "g_".$this->initName;
		}
		
		$this->fileBgPath = $file_bg_path;
		$this->bgName = "bg_".$this->initName;
		
//		$BgSize = GetImageSize($this->fileBgPath);
//		$this->randX = rand(0, $BgSize[0] - $this->fileSizeX);
//		$this->randY = rand(0, $BgSize[1] - $this->fileSizeY);
//		exec("convert ".$this->fileBgPath." -crop ".$this->fileSizeX."x".$this->fileSizeY."+".$this->randX."+".$this->randY." +repage ".$this->bgName.".gif");
	}
	
	
	
	/**
	 *建立暫存圖檔
	 * 
	 * @param integer $g 編號
	 */
	function createInitGif($g){
		
		//建立圖形
		$this->img = imagecreate($this->fileSizeX, $this->fileSizeY);
		//匹配所有黑色
		$black = ImageColorAllocate($this->img, 256, 256, 256);
		//建立背景透明色
		imagecolortransparent($this->img, $black);
			
		$cont = $this->fileCont;
		for($i=0 ; $i <= strlen($cont);$i++){
			$this->compound(substr($cont,$i,1),$i);
		}
		
		Imagegif($this->img, $this->initName."_".$g.".gif");
		exec("composite -gravity center ".$this->initName."_".$g.".gif ".$this->bgName.".gif ".$this->initName."_".$g.".gif");
		
	}
	
	
	/**
	 * 圖檔合成
	 * 
	 * @param integer $i 文字字串
	 * @param integer $j 第j個字元
	 */
	function compound($i, $j){
		//10種字型檔(ttf)
		$font_family = rand(0,9);
		//字型大小
		$font_size = rand(12,25);
		//文字旋轉角
		$font_rotation = rand(-15,15);
		//座標(x軸)、間距
		$font_x = $j*20+10;
		//座標(y軸)
		$font_y = 30;
		//建立文字顏色
		$fontColor = ImageColorAllocate($this->img, rand(0,200), rand(0,200), rand(0,200));
		//使用ttf字形
		//此函式需要GD library和FreeType library
		//套用圖形,文字大小,旋轉角度,x,y,顏色,字型檔路徑,文字
		ImageTTFText($this->img, $font_size, $font_rotation, $font_x, $font_y, $fontColor, $font_family.".ttf", $i);
	}
	
	/**
	 * 產生圖形檔
	 */
	function result(){
		$addpic = '';
		for($i=0 ; $i < $this->fileAmount; $i++){
			$this->createInitGif($i);
			$addpic.="-page +0+0 ".$this->initName."_".$i.".gif ";
		}
		
		Imagegif($this->img, $this->fileName.".gif");
		
		exec("convert -delay ".$this->fileSpeed." -dispose Background ".$addpic."-loop 0 ".$this->fileName.".gif");
		
		for($i=0 ; $i < $this->fileAmount; $i++){
			unlink($this->initName."_".$i.".gif");
		}
		readfile($this->fileName.".gif");
		unlink($this->fileName.".gif");
		unlink($this->bgName.".gif");
	}
}




// 產生n位隨機數
function randNum($n){
	$verifyNum = $n;
	$Ary = array('0','1','2','3','4','5','6','7','8','9','0','1','2','3','4','5','6','7','8','9','A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z');
	$Cont = '';
	srand ((double) microtime() * 10000000);
	$rand_keys = array_rand($Ary, $n);
	for($i=0; $i<count($rand_keys); $i++){
		$Cont .= $Ary[$rand_keys[$i]];
	}
	return $Cont;
}



$verifyCont = randNum(4);
$gif = new createGif($verifyCont);
$gif->result();
session_start();
$_SESSION['verifyNum'] = $verifyCont;


?>

