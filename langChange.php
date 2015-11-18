<?php
include_once 'class/Template.class.php';
$html    = new Template('empty', false);
$referer = $_SERVER['HTTP_REFERER'];
header('location: '.$referer);
exit;
?>