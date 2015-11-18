<?php
echo "start"."<br>";
$socket = fsockopen("192.168.1.121", 19007) or die("Error creating socket");

/*
LS_WEB_REMOTE_CONTROL_USER_BONUS= 1016,	//< 指定彩金中獎
封包欄位              4
@封包代號             1016
@指定中獎的唯一索引ID 5533699
@玩家唯一識別碼       20905
@指定金額             5000
@#                    結束符號#
*/
$msg = "4@1016@5533699@20905@5000@#";//

$msg_array=str_split($msg,1);
$output = "";
foreach($msg_array as $index => $value)
{
	//$output = $output.pack("c2", ord($value), 0);
	$output .= pack("c2", ord($value), 0);
}

echo 'output：'.$output;
// strlen( $output);
echo '<br>';

$tmp = fwrite($socket, $output, strlen( $output));

echo $tmp;
exit;







