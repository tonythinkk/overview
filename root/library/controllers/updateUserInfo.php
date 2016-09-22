<?php

class updateUserInfoController implements Controller
{
	public function execute($input)
	{
		global $nowUiD, $nowPHPass, $nowSession, $conn;

		$newUserInfo = array();
		parse_str($input, $newUserInfo);
		$newUsrEmail = htmlentities($newUserInfo["userPersonalEmail"]);
		$newUsrPass1 = htmlentities($newUserInfo["userNewPassword"]);
		$newUsrPass2 = htmlentities($newUserInfo["userConfirmNewPass"]);
		$usrCurPassw = htmlentities($newUserInfo["userCurrentPassword"]);

		$ftcCurrentInfo = regular_query("SELECT * FROM db_user WHERE id = :id", [":id" => $nowUiD], $conn);

		$changedPassword = false;

		if ( $ftcCurrentInfo[0]["usr_email"] !== $newUsrEmail ) {
			insert_query("UPDATE db_user SET usr_email = :usr_email WHERE id = :id", [":usr_email" => $newUsrEmail, ":id" => $nowUiD], $conn);
		}

		if ( $newUsrPass1 != null && $newUsrPass2 != null && $usrCurPassw != null ) {
			if (ltrim($newUsrPass1) === ltrim($newUsrPass2)) {
				if ( $nowPHPass->verify($usrCurPassw, $ftcCurrentInfo[0]["usr_password"]) ) {
					insert_query("UPDATE db_user SET usr_password = :usr_password WHERE id = :id", [":usr_password" => $nowPHPass->hash($newUsrPass1), ":id" => $nowUiD], $conn);
					session_regenerate_id(TRUE);
					$nowSession::destroy();
					$changedPassword = true;
				}
			} else {
				$changedPassword = false;
			}
		}

		return json_encode(array( "newPassword" => ( $changedPassword == true ) ? "changed" : "unchanged" ));
	}
}
