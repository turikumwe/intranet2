<?php
/**
 *
 * @author Brain
 * @version 
 */
require_once 'Zend/Loader/PluginLoader.php';
require_once 'Zend/Controller/Action/Helper/Abstract.php';

/**
 * Database_Auth Action Helper 
 * 
 * @uses actionHelper Precurio_Helper
 */
class Precurio_Helper_DatabaseAuth extends Zend_Controller_Action_Helper_Abstract {
	/**
	 * @var Zend_Loader_PluginLoader
	 */
	public $pluginLoader;
	
	/**
	 * @var active connection to database
	 */
	protected $_db;
	protected $_authAdapter;
	
	private $_usernameField = 'identity';
	private $_passwordField = 'credential';
	private $_tableName = PrecurioTableConstants::AUTH;
	
	private $_errorCode;
	private $_errorMsg;
	/**
	 * @var User - user being authenticated, null is authentication fails
	 */
	private $_user; 
	
	/**
	 * Constructor: initialize plugin loader
	 * 
	 * @return void
	 */
	public function __construct() { 
		$this->pluginLoader = new Zend_Loader_PluginLoader ( );
	}
	public function init(){
		//create a new db connection if you don't want to authenticate from application database
		$this->_db = Zend_Registry::get('db');
		$this->_authAdapter = new Zend_Auth_Adapter_DbTable($this->_db,
								$this->_tableName,
								$this->_usernameField,
								$this->_passwordField,
								'MD5(?)');
		
	}
	public function validate ($username,$password){
		$this->init();
		$this->_authAdapter->setIdentity($username)
						->setCredential($password);
		$result = $this->_authAdapter->authenticate();
		if($result->isValid())
		{
			$this->_user = $this->_authAdapter->getResultRowObject();
			$this->_user->credential = $password;
		}
		else
		{
			//try again. this time if the user entered full email address, use the username past. if the user entered the username part, use the full email address
			$config = Zend_Registry::get('config');
			$username = stripos($username,'@') === false ? $username.$config->email_domain : substr($username,0,strpos($username,'@'));
			$this->_authAdapter->setIdentity($username)
						->setCredential($password);
			$result = $this->_authAdapter->authenticate();
			if($result->isValid())
			{
				$this->_user = $this->_authAdapter->getResultRowObject();
				$this->_user->credential = $password;
			}
		}
		
		$this->_errorCode = $result->getCode();
		if($result->isValid())
		{
			$user = UserUtil::getUser($this->_user->id);
			//if UserUtil::getUser() returns null, the the user has been deactivated by the admin
			if(Precurio_Utils::isNull($user))
			{
				$this->_errorCode = Zend_Auth_Result::FAILURE_IDENTITY_NOT_FOUND;
				return false;
			}
		}
		return $result->isValid();
		
	}
	public function getCode()
	{
		return $this->_errorCode;
	}
	public function getErrorMessage()
	{
		switch($this->_errorCode)
		{
			case Zend_Auth_Result::FAILURE_CREDENTIAL_INVALID:
				$this->_errorMsg = $this->translate("Invalid Password");
				break;
			case Zend_Auth_Result::FAILURE_IDENTITY_NOT_FOUND:
				$this->_errorMsg = $this->translate("Invalid user");
				break;
			case Zend_Auth_Result::FAILURE_IDENTITY_AMBIGUOUS:
				$this->_errorMsg = $this->translate("Invalid user");
				break;
			case Zend_Auth_Result::FAILURE_UNCATEGORIZED:
				$this->_errorMsg = $this->translate("Login Attempt Failed, please try again");
				break;
			case Zend_Auth_Result::FAILURE:
				$this->_errorMsg = $this->translate("Login Attempt Failed, please try again");
				break;
		}
		return $this->_errorMsg;
	}
	public function getUser()
	{
		if($this->_errorCode != Zend_Auth_Result::SUCCESS)
			return null;
		return $this->_user;
	}
	/**
	 * Strategy pattern: call helper as broker method
	 */
	public function direct($username,$password) {
		return $this->validate($username,$password);
	}
	public function translate($str)
	{
		$tr = Zend_Registry::get('Zend_Translate');
		return $tr->translate($str);
	}
}

