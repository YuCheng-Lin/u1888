<?php
echo "start"."<br>";

/*
LS_WEB_REMOTE_CONTROL_USER_AWARDS	= 1018,	///< 指定中大獎
封包欄位              4
@封包代號             1018
@指定中獎的唯一索引ID 5533699
@玩家唯一識別碼       20905
@指定金額             5000
@#                    結束符號#
*/

try {
	$msg = "4@1016@1018@20905@200000@#";//
	$socket = @fsockopen("192.168.1.121", 19007);
	if(!$socket){
		throw new Exception('socket die');
	}
	$msg_array = str_split($msg,1);
	$output = "";
	foreach($msg_array as $index => $value)
	{
		//$output = $output.pack("c2", ord($value), 0);
		$output .= pack("c2", ord($value), 0);
	}

	$tmp = fwrite($socket, $output, strlen($output));
	if(strlen($output) != $tmp){
		throw new Exception('response error');
	}

	echo 'output：'.$output;
	strlen( $output);
	echo '<br>';
	echo 'tmp：'.$tmp;
} catch (Exception $e) {
	echo $e->getMessage();
}

exit;






