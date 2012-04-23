<?php

require_once ('Zend/Db/Table/Row/Abstract.php');
require_once 'user/models/vo/Department.php';
require_once 'workflow/models/vo/ProcessState.php';
class Process extends Zend_Db_Table_Row_Abstract {
	const WORKFLOW_TABLES_PREFIX = "wf_";//all tables that are dynamically created for workflow must be prefixed with this
	/**
	 * get the process_id
	 * @return int
	 */
	public function getId()
	{
		return $this->id;
	}
	/**
	 * get the real name of the process, corresponds to the actual file/schema name
	 * @return string
	 */
	public function getName()
	{
		return $this->process;
	}
	/**
	 * gets the code to use on forms, like they use for filing purpose
	 * @return string
	 */
	public function getCode()
	{
		if(Precurio_Utils::isNull($this->code))
		{
			$c = "";
			$str = str_word_count($this->getDisplayName(),1);
			foreach($str as $s)$c .= strtoupper($s{0});
			$this->code=$c;
		}
		return $this->code;
	}
	/**
	 * get the table name of the process, corresponds to the actual table name
	 * @return string
	 */
	public function getTableName()
	{
		return self::WORKFLOW_TABLES_PREFIX.$this->process;
	}
	/**
	 * get the display name of the process
	 * @return string
	 */
	public function getDisplayName()
	{
		return $this->display_name;
	}
	
	public function getColourCode()
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::DEPARTMENTS,'rowClass'=>'Department'));
		$department = $table->fetchRow($table->select()->where('id= ? ',$this->department_id));
		return $department ==  null ? 'grey' : $department->getColourCode();
	}
	public function getDepartmentName()
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::DEPARTMENTS,'rowClass'=>'Department'));
		$department = $table->fetchRow($table->select()->where('id= ? ',$this->department_id));
		return $department ==  null ? 'General' : $department->getTitle();
	}
	
	/**
	 * Returns the start state id of the current process for the current user
	 * @param User $user i.e the User Value Object
	 * @return int state ID
	 */
	public function getStartState($user)
	{
		$stateID = -1;
		$db = Zend_Registry::get('db');
		$startPosition = $this->userCanInitiateProcess($user);
		$select = $db->select()
			->from(PrecurioTableConstants::WORKFLOW_STATES,'id')
			->where('process_id=?',$this->getId())
			->Where('position=?',$startPosition);
		$stateID = $db->fetchOne($select);
		return $stateID;
	}
	
	/**
	 * Determines whether a user can make a request on this process
	 * @param $user User
	 * @return Boolean
	 */
	public function userCanRequest($user)
	{
		try
		{
			$id = $this->userCanInitiateProcess($user);
		}
		catch (Exception $e)
		{
			return false;
		}
		return true ;
	}
	
	public function getStates()
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::WORKFLOW_STATES,'rowClass'=>'ProcessState'));
		return $table->fetchAll($table->select()->where('process_id= ? ',$this->id)->order('position ASC'));
		
	}
	/**
	 * this function throws an exception if user cannot initiate process.
	 * @param User $user - the user object
	 * @return int - start position of the process
	 */
	private function userCanInitiateProcess($user)
	{
		$tr = Zend_Registry::get('Zend_Translate');
		$accessArr = $this->getAccessArray(false);
		
		$userID = $user->getId();
		
		if(count($accessArr) == 0)//no one has been given access
			throw new Precurio_Exception($tr->translate(PrecurioStrings::CANNOTACCESSRESOURCE),Precurio_Exception::EXCEPTION_NO_ACCESS);
		
		$startPosition = -1;
		//first see if it is tied to the user
		foreach($accessArr as $access)
		{
			if($access['user_id'] == $userID)
				$startPosition = $access['start_position'];
			//remember we can't perform an else here, because that will result in a logic bug, think about it
		}
		if($startPosition == -1)//ok, the user has not been given access.
			$allowedGroups = Precurio_Utils::getSecondLevelArray($accessArr,'group_id');
		else
			return $startPosition;
			
		//now lets see if his/her group has been given access.	
		//Note:there is no need to check if allowedGroups is empty, since it can never 
		//be empty if accessArr is not empty.worst case scenerio, it contains only zeros
		$userGroups = $this->getUserGroups($userID);
		
		$userAllowedGroups = array_intersect($userGroups,$allowedGroups);
		if(count($userAllowedGroups) == 0)//user does not belong to a group that has been given access
			throw new Precurio_Exception($tr->translate(PrecurioStrings::CANNOTACCESSRESOURCE),Precurio_Exception::EXCEPTION_NO_ACCESS);
		
		//just in case you have more than one user allowed groups, use the most recent one.
		$allowedUserGroup = array_pop($userAllowedGroups);
			
		$rowIndex = array_search($allowedUserGroup,$allowedGroups);
		$startPosition = $accessArr[$rowIndex]['start_position'];
	
		return $startPosition;
		
	}
	/**
	 * Returns an associate array of allowed user and group access to the workflow
	 * @param Boolean $isApprovalAccess Indicates if you are requesting an approval access or a request access
	 * @return Array (Associative)
	 */
	private function getAccessArray($isApprovalAccess=false)
	{
		$db = Zend_Registry::get('db');
		$processID = $this->getId();
		if($isApprovalAccess)
		{
			$select = $db->select()
				->from(PrecurioTableConstants::WORKFLOW_APPROVAL_ACCESS,
				array('state_id','user_id','group_id'))
				->where('process_id=?',$processID);
		}
		
		else
		{
			$select = $db->select()
				->from(PrecurioTableConstants::WORKFLOW_REQUEST_ACCESS,
						array('user_id','group_id','start_position'))
				->where('process_id=?',$processID);
		}		

		
		$stmt = $select->query();
		$accessArr = $stmt->fetchAll();
		return $accessArr;
	}
	/**
	 * Returns an array of field schema.
	 * @return array
	 */
	public function getFields()
	{
		$root = Zend_Registry::get('root');
		$tableSchema = simplexml_load_file($root.'/application/workflow/schemas/'.$this->getName().'.xml');
		$converter = new Precurio_XmlToArray();
		$tableSchema = $converter->GetXMLTree($tableSchema->asXML());
		$fields =  $tableSchema['process']['fields']['field'];
		return $fields;
	}
	/**
	 * Gets all the active groups the user belongs to.
	 * @param int $user_id The Id of the user
	 * @return Array a numeric array.
	 */
	private function getUserGroups($userID)//this function should be moved to the UserVo
	{
		$db = Zend_Registry::get('db');
		
		$select = $db->select()
					->from(PrecurioTableConstants::USER_GROUPS,'group_id')
					->join(PrecurioTableConstants::GROUPS,'user_groups.group_id = groups.id','')
					->where('user_id= ?',$userID)
					->where ('active= ?',true);
					
		$stmt = $select->query();
		
		$stmt->setFetchMode(Zend_Db::FETCH_NUM);
		
		$userGroups = $stmt->fetchAll();
		$userGroups = Precurio_Utils::getSecondLevelArray($userGroups,0);
		return $userGroups;
	}
}

?>