<?php

require_once ROOT_DIR.'/vendor/autoload.php';
require_once ROOT_DIR.'/library/_main.php';
require_once ROOT_DIR.'/library/_vars.php';

( $conn == false || $nowSession->r('userCreds') == null ) && headerLocation("index.php", true);

$nowTemplates = new League\Plates\Engine(ROOT_DIR."/load/templates/");