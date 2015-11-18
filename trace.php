<?php 
include_once $_SERVER['DOCUMENT_ROOT'].'/class/language.class.php';
$lang = new Language();
$lang = $lang->getLanguage('en');
echo '<table>';
foreach ($lang as $key => $value) {
    echo '<tr>';
    echo '<td>'.$key.'</td>';
    echo '<td>'.$value.'</td>';
    echo '</tr>';
}
echo '</table>';
exit;


echo date('Y-m-d H:i:s', strtotime('-5 min'));
exit;



include_once $_SERVER['DOCUMENT_ROOT'].'/class/PDO_DB.class.php';

$db = new PDO_DB('202.150.211.182:1433', DB_USER, DB_PWD, DB_NAME);
// $db = new PDO_DB(DB_HOST, DB_USER, DB_PWD, DB_NAME);
$db->selectTB('A_Member');
$db->getData('*');
$result = $db->execute();
echo '<pre>';
print_r($result);
print_r($db->row);
exit;




$nowTime = '2015-02-19 09:23:44';
$nowTime = empty($nowTime) ? date('Y-m-d H:i:s') : d(strtotime($nowTime)).' 12:00:00';
$setTime = dateCheck($nowTime);
// $setTime = dateCheck($setTime.' 12:00:00');

$result = array();
if(!empty($setTime)){
    // $result[] = d(strtotime('-4 week Monday '.$setTime));
    // $result[] = d(strtotime('-3 week Monday '.$setTime));
    // $result[] = d(strtotime('-2 week Monday '.$setTime));
    $result[] = d(strtotime('-1 week Monday '.$setTime));
    $result[] = d(strtotime('Monday this week '.$setTime));
}

$startDate = d(strtotime('-1 week Monday '.$setTime));
$endDate   = d(strtotime('Monday this week '.$setTime));

echo 'lastDate:'.d(strtotime('-1 week Monday '.$startDate)),'<br>';
echo 'startDate:'.$startDate,'<br>';
echo 'endDate:'.$endDate,'<br>';

// echo '<pre>';
// print_r($result);
exit;



function d($strtotime){
    return date('Y-m-d', $strtotime);
}
function dateCheck($nowTime){
    $setTime = '';
    //如果今天是星期一當天
    if(d(strtotime($nowTime)) == d(strtotime('Monday this week '.$nowTime))
     && $nowTime < date('Y-m-d 10:00:00', strtotime('Monday this week '.$nowTime))
        ){
        $setTime = d(strtotime('Monday '.$nowTime));
    }
    if(d(strtotime($nowTime)) == d(strtotime('Monday this week '.$nowTime))
     && $nowTime >= date('Y-m-d 10:00:00', strtotime('Monday this week '.$nowTime))
        ){
        $setTime = d(strtotime('+1 week Monday '.$nowTime));
    }
    //如果今天是星期日必須設定時間為
    if(d(strtotime($nowTime)) == d(strtotime('Sunday last week '.$nowTime))){
        $setTime = d(strtotime('Monday this week '.$nowTime));
    }
    if(empty($setTime)){
        $setTime = d(strtotime('Monday '.$nowTime));
    }
    return $setTime;
}



include_once $_SERVER['DOCUMENT_ROOT'].'/class/Template.class.php';
$html = new Template('empty',false);
// echo $setTime = $html->dateCheck('2015-01-19 10:59:59'),'<br>';
$setTime = $html->dateCheck(date('Y-m-d H:i:s'));
echo $html->d(strtotime('Monday -1 week '.$setTime)),'<br>';
echo $html->d(strtotime('Monday this week '.$setTime));
exit;



$command = '2@1005@mem012@#';
$byte_array = array();
for ($i=0; $i < strlen($command); $i++) { 
    // echo ,"<br>";
    // $byte_array[] = substr($command, $i, 1);
    $byte_array[] = unpack('C*', substr($command, $i, 1))[1];
    if($i*2 % 2 == 0){
        // echo 0,"<br>";
        $byte_array[] = 0;
    }
}
// echo $str;
// $byte_array = unpack('C*', $command);
// exit;
// echo cshapConnnection('2@1005@mem012@#');
// $command = str_split($str);
echo '<pre>';
print_r($byte_array);
$output = pack("i*", $byte_array);
print_r($output);
// $output = join("", $byte_array);
$socket = fsockopen("113.196.38.47", 18007) or die("Error creating socket");
fwrite($socket, $output);
$get = fgets($socket, 128);
// while (!feof($socket)) {
//     }
fclose($socket);
echo $get;
// socket_close($socket);

exit;

function connnection(){
    /* Permitir al script esperar para conexiones. */
    set_time_limit(0);

    /* Activar el volcado de salida implícito, así veremos lo que estamo obteniendo
    * mientras llega. */
    ob_implicit_flush();

    $address = '127.0.0.1';
    $port = 10000;

    if (($sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP)) === false) {
        echo "socket_create() falló: razón: " . socket_strerror(socket_last_error()) . "\n";
    }

    if (socket_bind($sock, $address, $port) === false) {
        echo "socket_bind() falló: razón: " . socket_strerror(socket_last_error($sock)) . "\n";
    }

    if (socket_listen($sock, 5) === false) {
        echo "socket_listen() falló: razón: " . socket_strerror(socket_last_error($sock)) . "\n";
    }

    //clients array
    $clients = array();

    do {
        $read = array();
        $read[] = $sock;
       
        $read = array_merge($read,$clients);
       
        // Set up a blocking call to socket_select
        if(socket_select($read,$write = NULL, $except = NULL, $tv_sec = 5) < 1)
        {
            //    SocketServer::debug("Problem blocking socket_select?");
            continue;
        }
       
        // Handle new Connections
        if (in_array($sock, $read)) {       
           
            if (($msgsock = socket_accept($sock)) === false) {
                echo "socket_accept() falló: razón: " . socket_strerror(socket_last_error($sock)) . "\n";
                break;
            }
            $clients[] = $msgsock;
            $key = array_keys($clients, $msgsock);
            /* Enviar instrucciones. */
            $msg = "\nBienvenido al Servidor De Prueba de PHP. \n" .
            "Usted es el cliente numero: {$key[0]}\n" .
            "Para salir, escriba 'quit'. Para cerrar el servidor escriba 'shutdown'.\n";
            socket_write($msgsock, $msg, strlen($msg));
           
        }
       
        // Handle Input
        foreach ($clients as $key => $client) { // for each client       
            if (in_array($client, $read)) {
                if (false === ($buf = socket_read($client, 2048, PHP_NORMAL_READ))) {
                    echo "socket_read() falló: razón: " . socket_strerror(socket_last_error($client)) . "\n";
                    break 2;
                }
                if (!$buf = trim($buf)) {
                    continue;
                }
                if ($buf == 'quit') {
                    unset($clients[$key]);
                    socket_close($client);
                    break;
                }
                if ($buf == 'shutdown') {
                    socket_close($client);
                    break 2;
                }
                $talkback = "Cliente {$key}: Usted dijo '$buf'.\n";
                socket_write($client, $talkback, strlen($talkback));
                echo "$buf\n";
            }
           
        }       
    } while (true);

    socket_close($sock);
}


// function cshapConnnection($command,$ip='113.196.38.47',$port='18007',$back=true){
// 	$get = "";
//     //server IP, server Port, errno, errstr, timeout seconds
//     $fp = fsockopen($ip, $port, $errno, $errstr, 5);
    
//     if (!$fp) {
//         return "Server error("$errno:$errstr")";
//         //echo "<script>alert('$errno:$errstr');</script>";
//     }
//     fwrite($fp, $command."\n");
//     if($back){
//         while (!feof($fp)) {
//             $get.= fgets($fp, 128);
//         }
//     }
//     fclose($fp);
//     return $get;
// }


include_once 'class/mainNav.class.php';
$mainNav = new mainNav(array("1"=>"w","2"=>"w","3"=>"w","4"=>"w","5"=>"w"));
echo $mainNav;
exit;

$env_var = getenv('OPENSHIFT_ENV_VAR');
echo '<pre>';
print_r($env_var);
print_r($_SERVER);
exit;
?>