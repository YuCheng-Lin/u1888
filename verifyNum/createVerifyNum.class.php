<?php
class createGif {
	

	/**
	 * 圖檔名稱
	 * @var String
	 */
	var $fileName = '';
	/**
	 * 圖檔變換數量
	 * @var Int
	 */
	var $fileAmount = 0;
	/**
	 * 圖檔文字內容
	 * @var String
	 */
	var $fileCont = '';
	/**
	 * 圖檔變換速度(百分秒)
	 * @var Int
	 */
	var $fileSpeed = 100;
	/**
	 * 圖檔大小(寬)
	 * @var Int
	 */
	var $fileSizeX = 0;
	/**
	 * 圖檔大小(高)
	 * @var Int
	 */
	var $fileSizeY = 0;
	/**
	 * 暫存圖檔名稱
	 * @var String
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
		
		//如果沒有指定檔案名稱，就用"g"_initName當檔案名稱
		if($file_name != ''){
			$this->fileName = $file_name;
		}else{
			$this->fileName = "g_".$this->initName;
		}
		
		$this->fileBgPath = $file_bg_path;
		$this->bgName = "bg_".$this->initName;
		
		$BgSize = GetImageSize($this->fileBgPath);
		$this->randX = rand(0, $BgSize[0] - $this->fileSizeX);
		$this->randY = rand(0, $BgSize[1] - $this->fileSizeY);
		exec("convert ".$this->fileBgPath." -crop ".$this->fileSizeX."x".$this->fileSizeY."+".$this->randX."+".$this->randY." +repage ".$this->bgName.".gif");
	}
	
	
	
	/**
	 *建立暫存圖檔
	 * 
	 * @param integer $g 第幾張圖
	 */
	function createInitGif($g){
		
		//建立圖形
		exec("convert ".$this->bgName.".gif ".$this->initName."_".$g.".gif");			
		
		$cont = $this->fileCont;
		
		for($i=0 ; $i < strlen($cont);$i++){
			$this->compound(substr($cont,$i,1), $i, $g);
		}
	}
	
	
	/**
	 * 圖檔合成
	 * 
	 * @param String $init_cont 單字字串
	 * @param integer $cont_num 第$cont_num個字元
	 * $param integer $init_num 第幾張圖
	 */
	function compound($init_cont, $cont_num, $init_num){
		//10種字型檔(ttf)
		$font_family = rand(0,9);
		//字型大小
		$font_size = rand(20,30);
		//文字旋轉角
		$font_rotation = rand(-5,5);
		//座標(x軸)、間距
		$font_x = $cont_num*20+10;
		//座標(y軸)
		$font_y = $this->fileSizeY / 4 * 3;
		
		//單張合成
		exec("convert ".$this->initName."_".$init_num.".gif -font ".$font_family.".ttf -pointsize ".$font_size." \
		-draw \"fill rgb(".rand(100,256).",".rand(100,256).",".rand(100,256).") rotate ".$font_rotation." text ".$font_x.",".$font_y." '".$init_cont."' \" ".$this->initName."_".$init_num.".gif");
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
				
		exec("convert -delay ".$this->fileSpeed." -dispose Background ".$addpic."-loop 0 ".$this->fileName.".gif");
		
		for($i=0 ; $i < $this->fileAmount; $i++){
			unlink($this->initName."_".$i.".gif");
		}
		readfile($this->fileName.".gif");
		unlink($this->fileName.".gif");
		unlink($this->bgName.".gif");
	}
}
?>