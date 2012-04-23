<?php
/**
 *
 * @author Brain
 * @version 
 */
require_once 'Zend/Loader/PluginLoader.php';
require_once 'Zend/Controller/Action/Helper/Abstract.php';
require_once 'adLDAP.php';
require_once ('user/models/vo/Location.php');
require_once ('user/models/vo/Department.php');
require_once ('user/models/vo/Group.php');
require_once ('user/models/vo/User.php');
require_once ('user/models/UserUtil.php');
/**
 * Active_Directory_Auth Action Helper 
 * 
 * @uses actionHelper Precurio_Helper
 */
class Precurio_Helper_LdapAuth extends Zend_Controller_Action_Helper_Abstract {
	/**
	 * @var Zend_Loader_PluginLoader
	 */
	public $pluginLoader;
	/**
	 * @var Zend_db active connection to database
	 */
	protected $_db;
	protected $_authAdapter;
	
	
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
	public function init(){
		//create a new db connection if you don't want to authenticate from application database
		$this->_db = Zend_Registry::get('db');
		
	}
	public function __construct() {
		$this->pluginLoader = new Zend_Loader_PluginLoader ( );
	}
	public function validate($username,$password)
	{
		$config = Zend_Registry::get('config');
		$ldap_config = $config->ldap;
		$ldapServers = $ldap_config->ldap;
		$ldapServers = $ldapServers->toArray();
		$this->_authAdapter = new Zend_Auth_Adapter_Ldap($ldapServers,$username,$password);
		$result = $this->_authAdapter->authenticate();
		$messages = $result->getMessages();//log messages when precurio logging is impelemented
		$log = Zend_Registry::get('log');
		foreach($messages as $i=>$message)
		{
			if($i== 1)continue;
			$message = str_replace("\n", "\n ", $message);
			$log->info("Ldap: $i: $message");
		}
		if($result->isValid())
		{
			$server1 = $ldapServers['server1'];
			$email = $this->getUserEmail($username);
			
			$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::USERS,'rowClass'=>'User'));
		    $userVo = $table->fetchRow($table->select()->where('active = ? ',1)
															->where('email = ? ',$email));
			try
			{
				if($userVo == null)//this is a new user
				{
					
					$userVo = $this->importUser($username,$password,$server1);		
				}
				//always update group membership	
				$this->updateGroupMemberships($userVo,$server1);
				//always update password too.	
				//we are only doing this so that at any point in time, 
				//IT can decide to switch from active directory authentication to database authentication
				$userVo->updatePassword($password);											
				
				$this->_user = new stdClass();
				$this->_user->id = $userVo->user_id;
				$this->_user->identity = $username;
				$this->_user->credential = $password;
			}
			catch(Exception $e)
			{
				$log->err($e);
				$this->_errorCode = 1234;
				return false;
			}
			
				
		}
		$this->_errorCode = $result->getCode();
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
			case 1234:
				$this->_errorMsg = $this->translate("There was a problem retrieving your detail from the directory");
				break;
		}
		return $this->_errorMsg;
	}
	/**
	 * Imports user details from active directory
	 * @param $username string
	 * @param $password string
	 * @param $config Array of ldap server properties
	 * @return User
	 */
	private function importUser($username,$password,$config)
	{
		$data = array();
		$date_created = Precurio_Date::now()->getTimestamp();
		//first insert into auth table.
		//naturally this table might never be used, but just incase something happens to AD server
		//yes i know, they won't even be able to use their systems talk less enter the portal,
		//but what the hell, just in case. plus i think i need a $user_id :). 
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::AUTH));
		$data['identity'] = $username;
		$data['credential'] = md5($password);
		$data['date_created'] = $date_created;
		$row = $table->createRow($data);
		$user_id = $row->save();
		
		try
		{
			$data = array();
			$data['user_id'] = $user_id;
			$temp = Zend_Registry::get('config');
			$adLdap = new adLDAP(array(
			'account_suffix'=>$temp->domain,
			'base_dn'=>$config['baseDn'],
			'ad_username'=>$config['username'],
			'ad_password'=>$config['password'],
			'use_ssl'=>$config['useSsl'],
			'domain_controllers'=>array($config['host'])
			));
			
			$res = $adLdap->user_info($username,array("givenName","sn","mail","company","department","location","title","mobile","telephoneNumber"));
			
			$data['email'] = $res[0]['mail'][0];
			if(Precurio_Utils::isNull($data['email']))//this should not happen normally
				$data['email'] = stripos($username,'@') === false ? $username.$temp->email_domain : $username;
			
			$data['username'] = stripos($username,'@') === false ? $username : substr($username,0,strpos($username,'@'));
			$data['password'] = md5($password);
			$data['first_name'] = $res[0]['givenname'][0];
			if(Precurio_Utils::isNull($data['first_name']))
				$data['first_name'] = $username;
			$data['last_name'] = $res[0]['last_name'][0];
			$data['work_phone'] = $res[0]['telephonenumber'][0];
			$data['mobile_phone'] = $res[0]['mobile'][0];
			$data['job_title'] = $res[0]['title'][0];
			$data['company'] = $res[0]['company'][0];
			
			if(isset($res[0]['location']))
			{
				$data['location_id'] = Location::getLocationId($res[0]['location'][0]);
				if($data['location_id'] == 0)unset($data['location_id']);
			}
			
			if(isset($res[0]['department']))
			{
				$data['department_id'] = Department::getDepartmentId($res[0]['department'][0]);
				if($data['department_id'] == 0)unset($data['department_id']);
			}
			
			$userVo = UserUtil::createUser($data);

			return $userVo;	
		}
		catch(Exception $e)
		{
			$log = Zend_Registry::get('log');
			$log->err($e);
		}
		return null;
	}
	/**
	 * @param $user User
	 * @param $config array
	 * @return void
	 */
	private function updateGroupMemberships($user,$config)
	{
		if($user == null)throw new Exception();
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::GROUPS,'rowClass'=>'Group'));
		$groups = $table->fetchAll();
		try
		{
			$temp = Zend_Registry::get('config');
			$adLdap = new adLDAP(array(
			'account_suffix'=>$temp->domain,
			'base_dn'=>$config['baseDn'],
			'ad_username'=>$config['username'],
			'ad_password'=>$config['password'],
			'use_ssl'=>$config['useSsl'],
			'domain_controllers'=>array($config['host'])
			));
			$adUserGroups = $adLdap->user_groups($user->getUsername(),false);
			$userGroups = $this->getGroupNames($user->id);
			
			$adNotDb = array_diff($adUserGroups,$userGroups);
			$dbNotAd = array_diff($userGroups,$adUserGroups);
			
			$this->userIsNoMoreMember($user->id,$dbNotAd);
			$this->userIsNowMember($user->id,$adNotDb); 
			
		}
		catch (Exception $e)
		{
			$log = Zend_Registry::get('log');
			$log->err($e);
		}
	}
	private function getGroupNames($user_id)
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::GROUPS));
		$select = $table->select();
		$select->setIntegrityCheck(false);
		$select->setTable($table); 
		$select = $select->from(array('a' => PrecurioTableConstants::GROUPS),array('title'))
						->join(array('b' => PrecurioTableConstants::USER_GROUPS),'a.id = b.group_id',array())
						->where('b.user_id = ? ',$user_id)
						->order('a.id DESC');
		
		$all = $table->fetchAll($select)->toArray();
		return Precurio_Utils::getSecondLevelArray($all,'title');
	}
	
	/**
	 * Removes user from memberships of groups
	 * @param $user_id int
	 * @param $groups array of group names to which user no longer belongs
	 * @return void
	 */
	private function userIsNoMoreMember($user_id,$groups)
	{
		if(count($groups) == 0)return;
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::USER_GROUPS));
		$where = "";
		foreach($groups as $groupName)
		{
			$id = Group::getGroupIdFromName($groupName);
			if($id == 0)continue;//group does not exist, this can only happen if group was deleted from database
			$where = "user_id = {$user_id} AND group_id = {$id}";
			$table->delete($where);
		}
		return;
	}
	/**
	 * Adds user to memberships of groups
	 * @param $user_id int 
	 * @param $groups array of group names
	 * @return void
	 */
	private function userIsNowMember($user_id,$groups)
	{
		if(count($groups) == 0)return;
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::USER_GROUPS));
		$data = array();
		$data['user_id'] = $user_id;
		$data['date_created'] = Precurio_Date::now()->getTimestamp();
		foreach($groups as $groupName)
		{
			$id = Group::getGroupIdFromName($groupName);
			if($id == 0)//this is a new group, add it to the database
			{
					$log = Zend_Registry::get('log');
					$log->warn("New group '$groupName' imported from active directory, please edit group information locally.");
					$groupTable = new Zend_Db_Table(array('name'=>PrecurioTableConstants::GROUPS));
					$id = $groupTable->insert(array(
						'title'=>$groupName,
						'description'=>'',
						'parent_id'=>0,
						'is_location'=>0,
						'is_role'=>0,
						'is_department'=>0,
						'active'=>1,
						'date_created'=>Precurio_Date::now()->getTimestamp()
					));
			}
			$data['group_id'] = $id;
			$row = $table->createRow($data);
			$row->save();
		}
		return;
	}
	public function getUser()
	{
		if($this->_errorCode != Zend_Auth_Result::SUCCESS)
			return null;
		return $this->_user;
	}
	
	/**
	 * This will get the user email from the active directory
	 * @param string $username
	 * @return string
	 */
	private function getUserEmail($username)
	{
		$config = Zend_Registry::get('config');
		$ldap_config = $config->ldap;
		$ldapServers = $ldap_config->ldap;
		$ldapServers = $ldapServers->toArray();
		$server1 = $ldapServers['server1'];
		
		
		$adLdap = new adLDAP(array(
		'account_suffix'=>$config->domain,
		'base_dn'=>$server1['baseDn'],
		'ad_username'=>$server1['username'],
		'ad_password'=>$server1['password'],
		'use_ssl'=>$server1['useSsl'],
		'domain_controllers'=>array($server1['host'])
		));
		
		$userinfo = $adLdap->user_info($username);
		return $userinfo[0]["mail"][0];
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

