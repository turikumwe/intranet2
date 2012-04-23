<?php
require_once ('user/models/vo/User.php');
require_once ('user/models/vo/Location.php');
require_once ('user/models/vo/Department.php');
require_once ('user/models/vo/Role.php');
require_once ('user/models/vo/Group.php');
class UserUtil {
	
	public function __construct()
	{
		return $this;
	}
	/**
	 * Fetches all Locations
	 * @return Array
	 */
	public function getLocations()
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::LOCATIONS,'rowClass'=>'Location'));
		$locations = $table->fetchAll($table->select()->where('active=1'));
		return $locations;
	
	}
	/**
	 * Returns user object with id = $user_id
	 * @param $user_id
	 * @return User
	 */
	public static function getUser($user_id=null)
	{
		if(Zend_Registry::isRegistered('Zend_Translate'))
			$tr = Zend_Registry::get('Zend_Translate');
		try
		{
			if(Precurio_Utils::isNull($user_id))
				$user_id = Precurio_Session::getCurrentUserId();
			
				$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::USERS,'rowClass'=>'User'));
			     $userVo = $table->fetchRow($table->select()->where('active = ? ',1)
																->where('user_id = ? ',$user_id));
			if(Precurio_Utils::isNull($userVo))
			{
				$msg = isset($tr) ? $tr->translate(PrecurioStrings::NOSUCHUSER) : PrecurioStrings::NOSUCHUSER;
				throw new Precurio_Exception($msg,Precurio_Exception::EXCEPTION_NO_SUCH_USER);
			}
		}
		catch(Exception $e)
		{
			return null;
		}
		return $userVo;
	}
	/**
	 * @param $group_id int
	 * @return Group
	 */
	public static function getGroup($group_id)
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::GROUPS,'rowClass'=>'Group'));
	    return $table->fetchRow($table->select()->where('id = ? ',$group_id));
	}
	
	public function getFeaturedUser()
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::FEATURED_USER));
		//get the last guy
		$row = $table->fetchRow($table->select()->order('id desc')->limit(1));
		if($row == null)//there is no featured user at all, this only happens for a fresh application
			return $this->setFeaturedUser();
		$date = new Precurio_Date($row->date_created);
		if($date->isToday())
			return $this->getUser($row->user_id);
		//ok, the last featured user was not done today, so set one.
		return $this->setFeaturedUser();
		
	}
	private function setFeaturedUser()//change to private on production
	{
		$db = Zend_Registry::get('db');
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::USERS));
		$select = $table->select(false);
		$select = $select->from(PrecurioTableConstants::USERS,'user_id')
							->where('percentage_complete >= 90' );//user must have almost completed your profile
		$user_ids = $db->fetchCol($select);
		if(count($user_ids) == 0)//no user passed RULE 0
			return null;//only users who have almost completed profile will be featured.
		
		$featured_user_id = 0;
		$rule = rand(1,3); //randomly select a rule.
		
		switch ($rule)
		{
			case 1:
				$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::FEATURED_USER));
				$select = $table->select(false);
				$select->setIntegrityCheck(false);
				$select = $select->from(PrecurioTableConstants::FEATURED_USER,'user_id');
				$featured_user_ids = $db->fetchCol($select);
				//eligible contains users that are eligible to be featured but have not been featured.
				$eligible = array_diff($user_ids,$featured_user_ids);
				
				if(count($eligible) > 0)
				{
					$featured_user_id =  $eligible[array_rand($eligible)];//randomly pick an eligible person
					break;	
				}
				//note that i didnt include a break statement on purpose;
			case 2:
				//ok, there is no eligible person, or this rule was picked.
				//lets select the user with the most activities, but first we make sure there
				//aren't too many users who passed rule 0 i.e have almost completed profile.
				if(count($user_ids) < 100)
				{
					$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::ACTIVITY));
					$max = 0;
					$max_id = 0;
					foreach($user_ids as $user_id)
					{
						$select = $table->select(false);
						$select->setIntegrityCheck(false);
						$count = $db->fetchOne($select->from(PrecurioTableConstants::ACTIVITY,'count(*)')
									->where('user_id = ?',$user_id ));
						if($count >= $max)
						{
							$max = $count;
							$max_id = $user_id;
						}
					}
					//to further increase the complexity of this rule, you can also make sure the most active 
					//user wasn't featured that week, if he was, use another algorightm
					$featured_user_id =  $max_id;
				}
				break;//not necessary, since rule 2 is guaranteed to return a result.
			case 3:
				//this rule simply picks any user at random
				$featured_user_id =  $user_ids[array_rand($user_ids)];
				break;
		}
		//now insert into database so that every user wont have to call this function.
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::FEATURED_USER));
		$data['user_id'] = $featured_user_id;
		$data['date_created'] = Precurio_Date::now()->getTimestamp();
		$row = $table->createRow($data);
		$row->save();
		return $this->getUser($featured_user_id);
	}
	/**
	 * Fetches all Groups
	 * @return Zend_Db_Table_Rowset
	 */
	public function getGroups()
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::GROUPS,'rowClass'=>'Group'));
		$groups = $table->fetchAll($table->select()->where('active=1'));
		return $groups;
	
	}
	/**
	 * Fetches all departments
	 * @return Array
	 */
	public static function getDepartments()
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::DEPARTMENTS,'rowClass'=>'Department'));
		$depts = $table->fetchAll($table->select()->where('active=1'));
		return $depts;
	}
	/**
	 * Fetches all Roles
	 * @return Zend_Db_Table_Rowset
	 */
	public function getRoles()
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::ROLES,'rowClass'=>'Role'));
		$roles = $table->fetchAll($table->select()->where('active=1'));
		return $roles;
	
	}
	public function getRecentStatus($limit = 20, $user_id = 0)
	{
		if($user_id == 0)$user_id = Precurio_Session::getCurrentUserId();
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::STATUS_MESSAGES, 'rowClass'=>'UserStatus'));
		$select = $table->select();
		$select->setTable($table); 
		
		$select = $select->from(array('a' => PrecurioTableConstants::STATUS_MESSAGES))
						->where('a.user_id = ?' ,$user_id)
						->order('id DESC')
						->limit($limit);
		return  $table->fetchAll($select);
	}
	public static function addUserToGroup($user_id,$group_id)
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::USER_GROUPS));
		
		$row = $table->fetchRow($table->select()->where('user_id = ?',$user_id)->where('group_id = ?',$group_id));
		if($row)return;//should probably throw an error here, but i think its better to ignore.
		//ok, user as not already been added, we can now add the user.
		$row = $table->createRow(array(
		'user_id'=>$user_id,
		'group_id'=>$group_id,
		'date_created'=>Precurio_Date::now()->getTimestamp()
		));
		
		$row->save();
	}
	
	/**
	 * This simply inserts the user data into the users table, Please note that
	 * users created through his means cannnot login to the system, calling function
	 * must handle inserting user credentials into authentication table (p2_users).
	 * @param array $data user data
	 * @return User
	 */
	public static function createUser($data,$insertAuth=false)
	{
		$tr = Zend_Registry::get('Zend_Translate');
		if(Precurio_Session::getLicense()->isFull())
			throw new Precurio_Exception($tr->translate(PrecurioStrings::LICENSEFULL),Precurio_Exception::EXCEPTION_LICENSE_FULL);
			
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::USERS));
		$id  = $table->insert($data);
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::USERS,'rowClass'=>'User'));
		return $table->find($id)->current();
	}
	
	public static function activateGuestUser()
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::USERS,'rowClass'=>'User'));
		$guest =  $table->fetchRow($table->select()->where('username = "guest"')->order('id desc'));
		$tr = Zend_Registry::get('Zend_Translate');
		if(empty($guest))
		{
			$config = Zend_Registry::get('config');
			//create guest
			$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::AUTH));
			$user_id =  $table->insert(array(
				'identity' => 'guest',
				'credential' =>md5('guest'),
				'date_created'=>time()
			));
			
			$params = array(
				'user_id'=>$user_id,
				'first_name'=>$tr->translate('Guest'),
				'last_name'=>'',
				'email'=>'guest'.$config->email_domain,
				'username'=>'guest',
				'password'=>md5('guest'),
				'location_id'=>0,
				'department_id'=>0,
				'company'=>isset($config->company_name) ? $config->company_name : '',
				'date_created'=>time(),
				'active'=>1,
			    'out_of_office'=>0
			);
			
			$guest = UserUtil::createUser($params);
		}
		if(!$guest->isActive())
		{
			$guest->active = 1;
			$guest->first_name = $tr->translate('Guest');
			$guest->last_name = '';
			$guest->save();
		}
		return $guest;
	}
}

?>