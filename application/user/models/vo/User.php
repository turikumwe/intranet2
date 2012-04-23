<?php

require_once ('Zend/Db/Table/Row/Abstract.php');
require_once ('cms/models/vo/Content.php');
require_once ('cms/models/vo/Category.php');
require_once ('user/models/UserSetting.php');
require_once ('user/models/UserUtil.php');
class User extends Zend_Db_Table_Row_Abstract {
	public $full_name;
	public $fieldWeights = array(
		'first_name'=>'5',
		'last_name'=>'5',
		'work_phone'=>'5',
		'mobile_phone'=>'10',
		'birth_day'=>'5',
		'birth_month'=>'5',
		'gender'=>'5',
		'profile_picture_id'=>'20',
		'location_id'=>'5',
		'job_title'=>'10',
		'department_id'=>'5',
		'job_description'=>'10',
		'skills'=>'10',
	);
	public function init()
	{
		$this->id = $this->user_id;
		//full_name property is used by contacts module,
		// where user objects also acts as contacts as in Co-workers.
		$this->full_name = $this->getFullName();
	}
	/**Returns the user id.
	 * @return int
	 */
	public function getId()
	{
		return $this->id;
	}
	/**
	 * @return string
	 */
	public function getEmail()
	{
		return $this->email;
	}
	/**
	 * First name
	 * @return string
	 */
	public function getFirstName($thirdPerson = false)
	{
		if($thirdPerson)return $this->first_name."'s";
		return $this->first_name;
	}
	/**Last name
	 * @return string
	 */
	public function getLastName()
	{
		return $this->last_name;
	}
	public function getWorkPhone()
	{
		return $this->work_phone;
	}
	public function getMobilePhone()
	{
		return $this->mobile_phone;
	}
	public function getBirthDay()
	{
		return $this->birth_day;
	}
	public function getBirthMonth()
	{
		return $this->birth_month;
	}
	public function getGender()
	{
		return $this->gender;
	}
	public function getLocationId()
	{
		return $this->location_id;
	}
	public function getJobTitle()
	{
		return $this->job_title;
	}
	public function getDepartmentId()
	{
		return $this->department_id;
	}
	public function getJobDescription()
	{
		return $this->job_description;
	}
	public function getSkills()
	{
		return $this->skills;
	}
	public function getAddress()
	{
		return $this->address;
	}
	public function getProfilePictureId()
	{
		return $this->profile_picture_id;
	}
	public function getDateCreated()
	{
		return $this->date_created;
	}
	public function getDateJoined()
	{
		$date = new Precurio_Date($this->date_created);
		return $date->get(Zend_Date::DATETIME);
	}
	public function getLastUpdated()
	{
		return $this->last_updated;
	}
	public function getPercentageComplete()
	{
		$this->setPercentageComplete($this->calculateWeight()); 
		return $this->percentage_complete;
	}
	public function setPercentageComplete($value)
	{
		$this->percentage_complete = $value;
	}
	public function isActive()
	{
		return $this->active;
	}
	public function isOutOfOffice()
	{
		return $this->out_of_office;
	}
	public function isGuest()
	{
		return $this->isAnonymous();
	}
	public function isAnonymous()
	{
		return (strtolower(substr($this->getUsername(),0,5)) == 'guest');
	}	
	/**
	 * Determines if the user is blocking any particular user
	 * @param int $user_id (id of user that may be blocked by this user)
	 * @return boolean
	 */
	public function isBlocked($user_id)
	{
		$blockedList = $this->getSettings()->getBlockedUsers();
		return (array_search($user_id, $blockedList) !== false);
	}
	/**
	 * Gets the out of office object, returns null if user is not out of office
	 * Serves as a proxy to outOfOffice()
	 * @return OutOfOffice
	 */
	public function getOutOfOffice()
	{
		return $this->outOfOffice();
	}
	
	/**
	 * Returns the total number of contents this user has on the intranet
	 * @return int
	 */
	public function getNumOfContents()
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::CONTENT));
		$contents = $table->fetchAll($table->select()->where('user_id= ? ',$this->getId()));
		return $contents->count();
	}
	/**
	 * Returns the total number of comments this user has on the intranet
	 * @return int
	 */
	public function getNumOfComments()
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::COMMENTS));
		$comments = $table->fetchAll($table->select()->where('user_id= ? ',$this->getId()));
		return $comments->count();
	}
	/**
	 * Returns the % activity of this user on the intranet
	 * @return int
	 */
	public function getPercentageActivity()
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::ACTIVITY));
		$total = $table->fetchAll()->count();
		if($total == 0)return 0;//to prevent a division by zero below.
		$mine = $table->fetchAll($table->select()->where('user_id= ? ',$this->getId()))->count();
		return number_format(($mine * 100 /$total),1);
	}
	public function getActivityPosition()
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::ACTIVITY));
		$select  = $table->select(false);
		$select->setIntegrityCheck(false);
		$select = $select->from(array('a' => PrecurioTableConstants::ACTIVITY),array('num'=>'count(a.id)','user_id'))
						->group('a.user_id')
						->order('num DESC')
						->limit(20);
		
		$rows = $table->fetchAll($select);
		$me = $this->getId();
		$pos = 0;
		foreach ($rows as $row)
		{
			$pos++;
			if($row->user_id == $me)
				return $pos;
		}
		return 0;
	}
	/**
	 * Gets the user settings object
	 * @return UserSetting
	 */
	public function getSettings()
	{
		return new UserSetting($this->getId());
	}
	public function getAllPortalActivites($limit = 10)
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::ACTIVITY, 'rowClass'=>'UserActivity'));
		$select  = $table->select(false);
		$select->setIntegrityCheck(false);
		$select = $select->from(array('a' => PrecurioTableConstants::ACTIVITY))
						->join(array('b' => PrecurioTableConstants::USERS),'a.user_id = b.user_id',array('first_name','last_name','profile_picture_id','gender'))
						->order('a.id DESC')
						->limit(100);
		
		$activities = $table->fetchAll($select);
		
		$user_id = $this->getId();
		$temp = array();
		foreach($activities as $activity)
		{
			//first check if user blocked me
			$activityOwner = UserUtil::getUser($activity->user_id);
			if($activityOwner->isBlocked($user_id))continue;
			//then check if i have access to the activity
			if($activity->canSee($user_id))
				$temp[] = $activity;
				
			if(count($temp) >= $limit)break;
		}
		return $temp;
	}
	/**
	 * @return Zend_Db_Table_Rowset -  Collection of Group  objects the user belongs to.
	 */
	public function getGroups()
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::GROUPS, 'rowClass'=>'Group'));
		$select = $table->select();
		$select->setIntegrityCheck(false);
		$select->setTable($table); 
		$select = $select->from(array('a' => PrecurioTableConstants::GROUPS))
						->join(array('b' => PrecurioTableConstants::USER_GROUPS),'a.id = b.group_id',array('date_joined'=>'date_created'))
						->where('b.user_id = ? ',$this->getId())
						->where('a.active=1')
						->order('a.id DESC');
		
		$all = $table->fetchAll($select);
		return $all;
	}
	
	public function getRoles()
	{
		$groups  = $this->getGroups();
		$roles = array();
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::ROLES,'rowClass'=>'Role'));
		
		
		foreach($groups as $group)
		{
			if($group->is_role)
			{
				$role = $table->fetchRow($table->select()->where('group_id = ?',$group->id));
				$roles[] = $role;
			}
		}
		return $roles;
	}
	
	/**
	 * Returns the user's date of birth  e.g. "15th, April"
	 * @return String
	 */
	public function getDOB()
	{
		try
		{
			$date =  new Precurio_Date();
			$date->setMonth($this->getBirthMonth());
			$date->setDay($this->getBirthDay());
			$str = $date->get(Precurio_Date::DAY). $date->get(Precurio_Date::DAY_SUFFIX). ', '. $date->get(Precurio_Date::MONTH_NAME);	
		}
		catch(Exception $e)
		{
			$str = "";
		}
		
		
		return $str;
	}
	/**
	 * Returns all currently active contents created by the user.
	 * @return Zend_Db_Table_Rowset_Abstract
	 */
	public function getMyContents()
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::CONTENT, 'rowClass'=>'Content'));
		$select = $table->select();
		$select->setIntegrityCheck(false);
		$select->setTable($table); 
		$select = $select->from(array('a' => PrecurioTableConstants::CONTENT))
						->join(array('b' => PrecurioTableConstants::USERS),'a.user_id = b.user_id',array('first_name','last_name','profile_picture_id'))
						->where('a.user_id= ? ',$this->getId())
						->where('a.is_photo= 0')
						->where('a.active=1')
						->order('id DESC');
		
		$all = $table->fetchAll($select);
		return $all;
	}
	/**
	 * Returns all currently active contents that has been shared with the user.
	 * @return Zend_Db_Table_Rowset_Abstract
	 */
	public function getSharedContents()
	{
				
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::SHARED_CONTENTS, 'rowClass'=>'Content'));
		$select = $table->select();
		$select->setIntegrityCheck(false);
		$select->setTable($table); 
		$select = $select->from(array('b' => PrecurioTableConstants::CONTENT))
						->join(array('a' => PrecurioTableConstants::SHARED_CONTENTS),'a.content_id = b.id',array('user_id'))
						->join(array('c' => PrecurioTableConstants::USERS),'a.user_id = c.user_id',array('first_name','last_name','profile_picture_id'))
						->where('a.sharer_id= ? ',$this->getId())
						->where('b.active=1')
						->order('id DESC');
		
		$all = $table->fetchAll($select);
		return $all;
		
		
	}
	public function getPhotos()
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::CONTENT, 'rowClass'=>'Content'));
		$photos = $table->fetchAll($table->select()->where('user_id= ? ',$this->getId())
														->where('is_photo = ? ',1)
														->where('active=1')
														->order('id DESC'));
		return $photos;
	}
	
	public function getRecentActivity()
	{
		$recentDate = new Precurio_Date();
		$recentDate->sub(2,Precurio_Date::DAY);//i.e. last 2 days
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::ACTIVITY, 'rowClass'=>'UserActivity'));
		$select  = $table->select(false);
		$select->setIntegrityCheck(false);
		$select = $select->from(array('a' => PrecurioTableConstants::ACTIVITY))
						->join(array('b' => PrecurioTableConstants::USERS),'a.user_id = b.user_id',array('first_name','last_name','profile_picture_id','gender'))
						->where('a.user_id = ? ',$this->getId())
						->order('id DESC')
						->limit(10);
		
		$activities = $table->fetchAll($select);
		
		$user_id = Precurio_Session::getCurrentUserId();
		
		foreach($activities as $activity)
		{
			//first check if user blocked me
			$activityOwner = UserUtil::getUser($activity->user_id);
			if($activityOwner->isBlocked($user_id))continue;
			//then check if i have access to the activity
			if($activity->canSee($user_id))
				$temp[] = $activity;
		}
		
		return $temp;
													
	}
	public function getRecentChange($includeName=false)
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::ACTIVITY));
		$row = $table->fetchRow($table->select()->where('user_id= ? ',$this->getId())
												->where('type <= ?', Precurio_Activity::CHANGE_DEPARTMENT)
												->order('id DESC'));
		if($row == null)return "";

		$format = Precurio_Activity::getMessageFormat($row->type);
		
		$name = $includeName ? $this->getFirstName() : '';
		$format  = strip_tags($format);
		return sprintf($format,$name,$this->getGenderPronoun());
	}
	
	private function getGenderPronoun($thirdPerson = false)
	{
		$translate = Zend_Registry::get('Zend_Translate');
		if(strtolower($this->gender) == 'male' )
		{
			if($thirdPerson)
				return $translate->translate('him');
			else
				return $translate->translate('his');
		}
		return $translate->translate('her');
	}
	/**
	 * Gets the out of office object, returns null if user is not out of office
	 * @return OutOfOffice
	 */
	public function outOfOffice()
	{
		if(!$this->out_of_office)return null;//dont waste the cpu's time
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::OUT_OF_OFFICE,'rowClass'=>'OutOfOffice'));
		$obj = $table->fetchRow($table->select()->where('user_id= ? ',$this->getId())
													->where('active= ? ',1)
													->order('id DESC'));
		return $obj;
	}
	public function getLocation()
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::LOCATIONS,'rowClass'=>'Location'));
		$location = $table->fetchRow($table->select()->where('id= ? ',$this->getLocationId()));
		return $location == null ? '' : $location->getTitle();
	}
	/**
	 * Returns path to user profile picture
	 * @return string
	 */
	public function getProfilePicture()
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::PROFILE_PICS));
		$pic = $table->fetchRow($table->select()->where('id= ? ',$this->getProfilePictureId()));
		return $pic == null ? '/uploads/profile_pic.jpg' : $pic->image_path;
	}
	
	/**
	 * Returns all categorys the user has access to (i.e. private, public, shared)
	 * @return array
	 */
	public function getCategorys()
	{
		$public = Category::getPublicCategorys();
		$private = Category::getPrivateCategorys($this->getId());
		$shared = Category::getSharedCategorys($this->getId());
		$arr = array($public,$private,$shared);
		$result = array();
		foreach($arr as $item)
		{
			foreach($item as $category)
			{
				$result[] = $category;
			}
		}
		return $result;
	}
	
	/**
	 * Strips the username from the email address
	 * @return String
	 */
	public function getUsername()
	{
		return $this->username;
	}
	/**
	 * Accepts the image path, inserts in into the DB and returns the last insert id
	 * @param String $imagePath Path to the new profile picture 
	 * @return int profile_pic_id
	 */
	public function newProfilePic($imagePath)
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::PROFILE_PICS));
		$insertId = $table->insert(array('image_path'=>$imagePath,'user_id'=>$this->getId()));	
		return $insertId;
	}
	/**
	 * Called by getPercentageComplete only when it percentage_complete is zero.
	 * @return int
	 */
	private function calculateWeight()
	{
		$totalWeight = 0;
		foreach($this->fieldWeights as $field=>$weight)
		{
			if(!Precurio_Utils::isNull($this[$field]))
			{
				$totalWeight += $weight;
			}
		}
		return $totalWeight;
	}
	public function getDepartment()
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::DEPARTMENTS,'rowClass'=>'Department'));
		$department = $table->fetchRow($table->select()->where('id= ? ',$this->getDepartmentId()));
		return $department ==  null ? '' :$department->getTitle();
	}
	public function getFullName()
	{
		return $this->first_name .' '. $this->last_name; 
	}
	/**
	 * checks if a user has a recpetionist; If yes, returns the receptionist_id
	 * else returns 0
	 * @return int receptionist_id
	 */
	public function getReceptionist()
	{
		if( Precurio_Utils::isNull($this->receptionist_id) )
		return 0;
		else return $this->receptionist_id;
	}
	
	public function isReceptionist()
	{		
		$receptionists = Visitor_Util::getReceptionists();
		
		return array_key_exists($this->getId(), $receptionists);
														
	}
	public function update($params)
	{
		$url = '/user/profile/view/'.$this->getId();
		$currentWeight = $this->getPercentageComplete();
		foreach($this->fieldWeights as $field=>$weight)
		{
			if(!isset($params[$field]))continue;//parameter does not exist
			if($this[$field] == $params[$field])continue;//new value is same as previous value
			if(Precurio_Utils::isNull($this[$field]))
			{
				//i.e previous value is  null and you are updating with a real value
				if(!Precurio_Utils::isNull($params[$field]))
				{
					$currentWeight += $weight;
					if($field == 'profile_picture_id')//send add profile picture activity.
						Precurio_Activity::newActivity($this->getId(),Precurio_Activity::ADD_PROFILE_PICTURE,$this->getId(),$url);
					if($field == 'location_id')$this->locationChanged($this['location_id'],$params['location_id']);
					if($field == 'department_id')$this->departmentChanged($this['department_id'],$params['department_id']);
				} 
			}
			else
			{
				//i.e previous value is not null and you are updating with a null value
				//this should hardly ever happen
				if(Precurio_Utils::isNull($params[$field]))
					$currentWeight -= $weight;	
				else//you are changing it. points wont apply, but that is a portal update
				{
					
					$activityType = "";
					switch($field)
					{
						case 'profile_picture_id':
							$activityType = Precurio_Activity::CHANGE_PROFILE_PICTURE;
							break;
						case 'work_phone':
						case 'mobile_phone':
							$activityType = Precurio_Activity::CHANGE_PHONE_NUMBER;
							break;
						case 'location_id':
							$activityType = Precurio_Activity::CHANGE_LOCATION;
							$this->locationChanged($this['location_id'],$params['location_id']);
							break;	
						case 'job_title':
							$activityType = Precurio_Activity::CHANGE_JOB_TITLE;
							break;
						case 'department_id':
							$activityType = Precurio_Activity::CHANGE_DEPARTMENT;
							$this->departmentChanged($this['department_id'],$params['department_id']);
							break;
						case 'email'://this is one wont be broadcasted.
							$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::AUTH));
							$row = $table->fetchRow($table->select()->where('id = ?',$this->user_id));
							$row->identity = $params['email'];
							$row->save();
							break;
					}
					if($activityType != "")
						Precurio_Activity::newActivity($this->getId(),$activityType,$this->getId(),$url);
				}
				
			}
		}
		$this->setPercentageComplete($currentWeight);
		$param['percentage_complete'] = $currentWeight;//include into update list
		
		//now perform the update
		foreach ($params as $param=>$value)
		{
			if(isset($this[$param]))
				$this[$param] = $value;
		}
		
		$this->save();
		$dict = new Precurio_Search();
		$dict->indexEmployee($this);
	}
	
	public function toCSVString()
	{
		$data = array($this->getFullName(),$this->company,$this->email,$this->job_title, 
		'',$this->mobile_phone,$this->work_phone,'',''
		); 
		array_walk($data,'self::pdStr');
		return implode(",",$data);
		
	}
	public function __toString()
	{
		return $this->getFullName();
	}
	private function pdStr(&$value,$key)
	{
		$value = ltrim($value);
		if($value[0] == '+' && stripos($value,' ')===FALSE)
		{
			str_ireplace('-',' ',$value);
			$i = stripos($value,'-');
			$value[$i] = ' ';
		}
		$value = '"'.$value.'"';	
	}
	
	private function locationChanged($old_location_id,$new_location_id)
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::LOCATIONS));
		$old_location = $table->find($old_location_id)->current();
		if($old_location)$old_group_id = $old_location['group_id'];
		
		$new_location = $table->find($new_location_id)->current();
		$new_group_id = $new_location['group_id'];
		
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::USER_GROUPS));
		if($old_location)
			$row = $table->fetchRow($table->select()->where('user_id = ?',$this->user_id)->where('group_id = ?',$old_group_id));
		if($row)$row->delete();
		
		UserUtil::addUserToGroup($this->user_id,$new_group_id);
		
	}
	
	private function departmentChanged($old_department_id,$new_department_id)
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::DEPARTMENTS));
		$old_department = $table->find($old_department_id)->current();
		if($old_department)$old_group_id = $old_department['group_id'];
		
		$new_department = $table->find($new_department_id)->current();
		$new_group_id = $new_department['group_id'];
		
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::USER_GROUPS));
		if($old_department)
			$row = $table->fetchRow($table->select()->where('user_id = ?',$this->user_id)->where('group_id = ?',$old_group_id));
		if($row)$row->delete();
		
		UserUtil::addUserToGroup($this->user_id,$new_group_id);
	}
	/**
	 * This does not actually create a user, it simple creates a user object.
	 * For object testing purposes only.
	 * @return User
	 */
	public static function createNew()
	{
		$user = new User(array(
		'table'=>new Zend_Db_Table(array('name'=>PrecurioTableConstants::USERS,'rowClass'=>'User')),
		'data'=>array(
			'id'=>0,
			'user_id'=>0,
			'first_name'=>'',
			'last_name'=>'',
			'work_phone'=>'',
			'mobile_phone'=>'',
			'birth_day'=>'',
			'birth_month'=>'',
			'gender'=>'',
			'profile_picture_id'=>'',
			'location_id'=>'',
			'job_title'=>'',
			'department_id'=>'',
			'job_description'=>'',
			'skills'=>''
		)
		));
		return $user;
	}
	public function updatePassword($password)
	{
		$this->password = md5($password);
		$this->save();
		
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::AUTH));
		$row = $table->find($this->user_id)->current();
		$row->credential = md5($password);
		$row->save();
		
		if(Bootstrap::usesDatabase())
		{
			Precurio_Activity::newActivity($this->user_id,Precurio_Activity::RESET_PASSWORD,$this->user_id,$password,$this->user_id)	;			
		}
		return;
	}
}

?>