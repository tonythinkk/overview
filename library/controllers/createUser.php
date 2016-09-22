<?php

class createUserController implements Controller
{
	public function execute($input)
	{
		global $nowSession, $nowPHPass, $conn, $nowCrypt, $usersPath;
		$createUsrData = $infoRay = array();
		$returnError = false;
		parse_str($input, $createUsrData);
		$attmptEmail = htmlentities($createUsrData["username"]);

		$grabUsrRole = (substr($createUsrData["password"], 0, 3));
		switch ($grabUsrRole) {
			case 'xax': $newUsrRole = 1; break;
			case 'xsx': $newUsrRole = 2; break;
			case 'xwx': $newUsrRole = 3; break;
			case 'xcx': $newUsrRole = 4; break;
			default: $returnData = "auth failed"; break;
		}

		if ( in_array($newUsrRole, array(1, 2, 3, 4)) ) {

			$attmptPassw = htmlentities(substr($createUsrData["password"], 3));

			// Check if session isset; if true redirect;
			if ( $nowSession->r('userCreds') != null ) {
				$returnError = true;
			}

			// Check if fields don't have data and role isn't in array; if true redirect;
			if ( ltrim($attmptEmail) == null || ltrim($attmptPassw) == null ) {
				$returnError = true;
			}

			if ( !filter_var($createUsrData["username"], FILTER_VALIDATE_EMAIL) ) {
				$returnError = true;
			}

			// Check if email exists in database; if true redirect;
			if ( count_query("SELECT count(*) FROM db_user WHERE usr_email = :usr_email LIMIT 1", [":usr_email" => $attmptEmail], $conn) == 1 ) {
				$returnError = true;
			}

			if ( $returnError != true ) {
				insert_query("INSERT INTO db_user (usr_password, usr_email, usr_role) VALUES (:one, :two, :three)",
					[":one"		=> (string)$nowPHPass->hash($attmptPassw),
					 ":two"		=> (string)$attmptEmail,
					 ":three"	=> (int)$newUsrRole], $conn);

				$infoRay["userinfo"] = array( "_name" => substr($attmptEmail, 0, strpos($attmptEmail, "@")), "_numbers" => null, "_created" => time(), "_seen" => time(), "_theme" => "bg4.jpg", "_photo" => "load/img/userplaceholder.png" );
				$infoRay["billinfo"] = array( "_state" => null, "_city" => null, "_code" => null);
				$infoRay["userdata"] = array( "_brightlocal" => null, "_logmycalls" => null, "_services" => null );
				$infoRay["websites"] = null;

				$grabSelf = regular_query("SELECT id FROM db_user WHERE usr_email LIKE :usr_email", [":usr_email" => "%".$attmptEmail."%"], $conn);
				file_put_contents($usersPath.$nowCrypt->encrypt($grabSelf[0]["id"]).'.json', json_encode($infoRay));

				$returnData = "successCreate";
			} else {
				$returnData = "retryCreate";
			}
		}
		return json_encode( array('returnedData' => $returnData) );
	}
}