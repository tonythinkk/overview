<?php

define( "dbUser", "mysqlUserName" );
define( "dbPass", "mysqlUserPass" );
define( "dbConn", "mysql:host=localhost;dbname=dbNameHere" );

$conn = connect();
$nowTimeStamp = date("Y-m-d H:i:s");

$nowCrypt	= new Crypter('3nmgdash3','39347737');

$nowSession	= new Session;

$nowPHPass	= new PHPassLib\Application\Context;
$nowPHPass->addConfig("bcrypt", array("rounds" => 14));

$nowUiD	= ($nowSession::r("userIdentity")!= null) ? $nowSession::r("userIdentity")	: null;	// User # Identifier
$nowUeM	= ($nowSession::r("userEmail")	 != null) ? $nowSession::r("userEmail")		: null;	// User Email Address
$nowUsR	= ($nowSession::r("userRole")	 != null) ? $nowSession::r("userRole")		: null;	// User Role
$nowUiP	= ($nowSession::r("userIP")		 != null) ? $nowSession::r("userIP")		: null;	// User Ip Address
$nowUtC	= ($nowSession::r("userTimezone")!= null) ? $nowSession::r("userTimezone")	: null;	// User TimeZone
$nowUrM	= ($nowSession::r("userReminder")!= null) ? $nowSession::r("userReminder")	: false;	// User Reminder

// connection to database
function connect()
{
	try {
		$conn = new PDO(dbConn, dbUser, dbPass);
		$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		return $conn;
	} catch (Exception $e) {
		return false;
	}
}

// regular mysql queries
function regular_query($query, $bindings, $conn)
{
	$stmt = $conn->prepare($query);
	$stmt->execute($bindings);
	$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
	return $results ? $results : false;
}

// return count for mysql query
function count_query($query, $bindings, $conn)
{
	$stmt = $conn->prepare($query);
	$stmt->execute($bindings);
	$results = $stmt->fetchColumn();
	return $results ? $results : false;
}

// mysql query to delete
function delete_query($query, $bindings, $conn)
{
	$stmt = $conn->prepare($query);
	$stmt->execute($bindings);
}

// mysql query to insert or update
function insert_query($query, $bindings, $conn)
{
	$stmt = $conn->prepare($query);
	$stmt->execute($bindings);
}

// checks to see if User is logged in
function userLoginCheck($conn)
{
	global $nowSession, $nowPHPass;
	$userCheck = $nowSession::r("userCreds");
	if ( !empty($userCheck) ) {

		$usr_Identity = $nowSession::r("userIdentity");
		$usr_Credentials = $nowSession::r("userCreds");

		if ( count_query("SELECT count(*) FROM db_user WHERE id = :id LIMIT 1", ["id" => $usr_Identity], $conn) == true ) {
			$ftcUserPass = regular_query("SELECT usr_password FROM db_user WHERE id = :id LIMIT 1", ["id" => $usr_Identity], $conn);
			if ( $nowPHPass->verify($usr_Credentials, $ftcUserPass[0]["usr_password"]) ) {
				return true; // The password was correct
			} else {
				return false; // The password was not correct
			}
		} else {
			return false; // User doesn't exist
		}
	} else {
		return false; // Not logged session Data
	}
}

// log user in with credientials
function userLogin($uEmail, $uPass, $conn)
{
	$ftcUserInfo = regular_query("SELECT id, usr_email, usr_password, usr_role FROM db_user WHERE usr_email = :usr_email LIMIT 1", ["usr_email" => $uEmail], $conn);

	if ( count_query("SELECT count(*) FROM db_user WHERE usr_email = :usr_email LIMIT 1", ["usr_email" => $uEmail], $conn) == true ) {

		global $nowSession, $nowPHPass; // $nowLocation
		if ( $nowPHPass->verify($uPass, $ftcUserInfo[0]["usr_password"]) ) { // Check if the password in the database matches the password the user submitted.
			$nowSession::w("userIdentity", (int)$ftcUserInfo[0]["id"]);
			$nowSession::w("userEmail", $ftcUserInfo[0]["usr_email"]);
			$nowSession::w("userCreds", $uPass);	// Password not encrypted : bad practice?
			$nowSession::w("userRole", (int)$ftcUserInfo[0]["usr_role"]);
			$nowSession::w("userAgent", $_SERVER["HTTP_USER_AGENT"]);
			$nowSession::w("userReminder", false);
			//$nowSession::w("userIP", $nowLocation->ipAddress);
			//$nowSession::w("userTimezone", $nowLocation->timeZone);
			return true; // Login successful.
		} else {
			return false; // Password is not correct
		}
	} else {
		return false; // No user exists.
	}
}

function headerLocation($location, $exit)
{
	header("Location: ". $location);
	($exit === true) ? exit() : null;
}

function explodeThis($delimiter, $array)
{
	$newArray = explode($delimiter, $array);
	foreach ($newArray as $key => $value) {
		if (empty($value)) {
			unset($newArray[$key]);
		}
	}
	return $newArray;
}

function rootDomain($url)
{
	// Check if the url begins with http:// www. or both
	// If so, replace it
	if (preg_match("/^(http:\/\/|www.)/i", $url))
	{
		$domain = preg_replace("/^(http:\/\/)*(www.)*/is", "", $url);
	}
	else
	{
		$domain = $url;
	}

	// Now all thats left is the domain and the extension
	// Only return the needed first part without the extension
	$domain = explode(".", $domain);

	return $domain[0];
}

if ( !class_exists("CustomException") ) { class CustomException extends Exception {} }
class SessionHandlerException extends CustomException {}
class SessionDisabledException extends SessionHandlerException {}
class InvalidArgumentTypeException extends SessionHandlerException {}
class ExpiredSessionException extends SessionHandlerException {}

class Session
{
	/**
	 * Session Age.
	 *
	 * The number of seconds of inactivity before a session expires.
	 *
	 * @var integer
	 */
	protected static $SESSION_AGE = 1800;

	/**
	 * Writes a value to the current session data.
	 *
	 * @param string $key String identifier.
	 * @param mixed $value Single value or array of values to be written.
	 * @return mixed Value or array of values written.
	 * @throws InvalidArgumentTypeException Session key is not a string value.
	 */
	public static function write($key, $value)
	{
		if ( !is_string($key) )
			throw new InvalidArgumentTypeException("Session key must be string value");
		self::_init();
		$_SESSION[$key] = $value;
		self::_age();
		return $value;
	}

	/**
	 * Alias for {@link Session::write()}.
	 *
	 * @see Session::write()
	 * @param string $key String identifier.
	 * @param mixed $value Single value or array of values to be written.
	 * @return mixed Value or array of values written.
	 * @throws InvalidArgumentTypeException Session key is not a string value.
	 */
	public static function w($key, $value)
	{
		return self::write($key, $value);
	}

	/**
	 * Reads a specific value from the current session data.
	 *
	 * @param string $key String identifier.
	 * @param boolean $child Optional child identifier for accessing array elements.
	 * @return mixed Returns a string value upon success.  Returns false upon failure.
	 * @throws InvalidArgumentTypeException Session key is not a string value.
	 */
	public static function read($key, $child = false)
	{
		if ( !is_string($key) )
			throw new InvalidArgumentTypeException("Session key must be string value");
		self::_init();
		if (isset($_SESSION[$key]))
		{
			self::_age();

			if (false == $child)
			{
				return $_SESSION[$key];
			}
			else
			{
				if (isset($_SESSION[$key][$child]))
				{
					return $_SESSION[$key][$child];
				}
			}
		}
		return false;
	}

	/**
	 * Alias for {@link Session::read()}.
	 *
	 * @see Session::read()
	 * @param string $key String identifier.
	 * @param boolean $child Optional child identifier for accessing array elements.
	 * @return mixed Returns a string value upon success.  Returns false upon failure.
	 * @throws InvalidArgumentTypeException Session key is not a string value.
	 */
	public static function r($key, $child = false)
	{
		return self::read($key, $child);
	}

	/**
	 * Deletes a value from the current session data.
	 *
	 * @param string $key String identifying the array key to delete.
	 * @return void
	 * @throws InvalidArgumentTypeException Session key is not a string value.
	 */
	public static function delete($key)
	{
		if ( !is_string($key) )
			throw new InvalidArgumentTypeException("Session key must be string value");
		self::_init();
		unset($_SESSION[$key]);
		self::_age();
	}

	/**
	 * Alias for {@link Session::delete()}.
	 *
	 * @see Session::delete()
	 * @param string $key String identifying the key to delete from session data.
	 * @return void
	 * @throws InvalidArgumentTypeException Session key is not a string value.
	 */
	public static function d($key)
	{
		self::delete($key);
	}

	/**
	 * Echos current session data.
	 *
	 * @return void
	 */
	public static function dump()
	{
		self::_init();
		echo nl2br(print_r($_SESSION));
	}

	/**
	 * Starts or resumes a session by calling {@link Session::_init()}.
	 *
	 * @see Session::_init()
	 * @return boolean Returns true upon success and false upon failure.
	 * @throws SessionDisabledException Sessions are disabled.
	 */
	public static function start()
	{
		// this function is extraneous
		return self::_init();
	}

	/**
	 * Expires a session if it has been inactive for a specified amount of time.
	 *
	 * @return void
	 * @throws ExpiredSessionException() Throws exception when read or write is attempted on an expired session.
	 */
	private static function _age()
	{
		$last = isset($_SESSION["LAST_ACTIVE"]) ? $_SESSION["LAST_ACTIVE"] : false ;

		if (false !== $last && (time() - $last > self::$SESSION_AGE))
		{
			// Don't end session
			// self::destroy();
			// header("Location: index.php");
			// exit();
			// throw new ExpiredSessionException();
		}
		$_SESSION["LAST_ACTIVE"] = time();
	}

	/**
	 * Returns current session cookie parameters or an empty array.
	 *
	 * @return array Associative array of session cookie parameters.
	 */
	public static function params()
	{
		$r = array();
		if ( "" !== session_id() )
		{
			$r = session_get_cookie_params();
		}
		return $r;
	}

	/**
	 * Closes the current session and releases session file lock.
	 *
	 * @return boolean Returns true upon success and false upon failure.
	 */
	public static function close()
	{
		if ( "" !== session_id() )
		{
			return session_write_close();
		}
		return true;
	}

	/**
	 * Alias for {@link Session::close()}.
	 *
	 * @see Session::close()
	 * @return boolean Returns true upon success and false upon failure.
	 */
	public static function commit()
	{
		return self::close();
	}

	/**
	 * Removes session data and destroys the current session.
	 *
	 * @return void
	 */
	public static function destroy()
	{
		if ( "" !== session_id() )
		{
			$_SESSION = array();

			// If it's desired to kill the session, also delete the session cookie.
			// Note: This will destroy the session, and not just the session data!
			if (ini_get("session.use_cookies")) {
				$params = session_get_cookie_params();
				setcookie(session_name(), "", time() - 42000,
					$params["path"], $params["domain"],
					$params["secure"], $params["httponly"]
				);
			}

			session_destroy();
		}
	}

	/**
	 * Initializes a new session or resumes an existing session.
	 *
	 * @return boolean Returns true upon success and false upon failure.
	 * @throws SessionDisabledException Sessions are disabled.
	 */
	private static function _init()
	{
		if (function_exists("session_status"))
		{
			// PHP 5.4.0+
			if (session_status() == PHP_SESSION_DISABLED)
				throw new SessionDisabledException();
		}

		if ( "" === session_id() )
		{
			return session_start();
		}
		// Helps prevent hijacking by resetting the session ID at every request.
		// Might cause unnecessary file I/O overhead?
		// TODO: create config variable to control regenerate ID behavior
		return session_regenerate_id(false); // only set this true on logout
	}
}

class Crypter
{
	private $key = '';
	private $iv = '';
	function __construct($key,$iv){
	$this->key = $key;
	$this->iv  = $iv;
	}
	protected function getCipher(){
		$cipher = mcrypt_module_open(MCRYPT_BLOWFISH,'','cbc','');
		mcrypt_generic_init($cipher, $this->key, $this->iv);
		return $cipher;
	}
	function encrypt($string){
		$binary = mcrypt_generic($this->getCipher(),$string);
		$string = '';
		for($i = 0; $i < strlen($binary); $i++){
		$string .=  str_pad(ord($binary[$i]),3,'0',STR_PAD_LEFT);
	}
		return $string;
	}
	function decrypt($encrypted){
		//check for missing leading 0's
		$encrypted = str_pad($encrypted, ceil(strlen($encrypted) / 3) * 3,'0', STR_PAD_LEFT);
		$binary = '';
		$values = str_split($encrypted,3);
		foreach($values as $chr){
			$chr = ltrim($chr,'0');
			$binary .= chr($chr);
		}
		return mdecrypt_generic($this->getCipher(),$binary);
	}
}