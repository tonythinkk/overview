<?php

require_once 'path.php';
require_once ROOT_DIR.'/vendor/autoload.php';
require_once ROOT_DIR.'/library/_main.php';

interface Controller
{
	/**
	 * @param  array  $input  The request parameters/data
	 * @return mixed  The (userialized) return data
	 */
	public function execute($input);
}

try {
	$requestMade = $_POST['liveCall'];
	$className = $requestMade . 'Controller';
	$fileName  = ROOT_DIR . '/library/controllers/' . $requestMade . '.php';
	require_once $fileName;

	$controller = new $className;
	$result = $controller->execute($_POST["liveData"]);
	header('Content-Type: application/json');
	echo $result;
} catch(Exception $e) {
	//send to email with $e
	header('404 Not Found');
	exit;
}

?>
