<?php

class loginUserController implements Controller
{
	public function execute($input)
	{
		global $conn;
		$logMeInData = array();
		parse_str($input, $logMeInData);
		$attmptEmail = htmlentities($logMeInData["username"]);
		$attmptPassw = htmlentities($logMeInData["password"]);
		$attmptHiddn = htmlentities($logMeInData["userwhom"]);

		(!filter_var($logMeInData["username"], FILTER_VALIDATE_EMAIL)) && $attmptEmail = htmlentities($logMeInData["username"]."@domain.com");

		if ( ltrim($attmptHiddn) == null && ltrim($attmptEmail) != null && ltrim($attmptPassw) != null ) {

			$returnData = ( userLogin($attmptEmail, $attmptPassw, $conn) == true ) ? "logmein" : "loginretry";

		} else {
			$returnData = "fields empty";
		}

		return json_encode(array('returnedData' => $returnData) );
	}
}