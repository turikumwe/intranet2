<?php

require_once ('Zend/Db/Table/Row/Abstract.php');
require_once 'user/models/vo/User.php';
require_once 'user/models/vo/Location.php';
require_once 'user/models/vo/Department.php';
require_once 'user/models/vo/Role.php';
require_once 'user/models/UserUtil.php';
class Group  extends Zend_Db_Table_Row_Abstract {
	/**
	 * if the group name contains spaces, it replaces the spaces with $seperator
	 * @param $seperator string, character to seperate with instead of spaces.
	 * @return unknown_type
	 */
	public function getUnspacedTitle($seperator="-")
	{
		$title = preg_replace("/\s+/", $seperator, $this->title); 
		return strtolower($title);
	}
	/**
	 * Returns the name of the group
	 * @return string
	 */
	public function getTitle()
	{
		return $this->title;
	}
	/**
	 * Checks if this group has any content
	 * @return boolean
	 */
	public function hasContent()
	{
		$myContent = new MyContents();
		return (boolean)count($myContent->getGroupContent($this->id,false));
	}
	public function getType()
	{
		$type = "Group, ";
		if($this->is_location)$type .= 'Location, '	;
		if($this->is_department)$type .= 'Department, ';
		if($this->is_role)$type .= 'Role, ';
			
		$type = substr($type,0,strlen($type)-2);
		return $type;
	}
	
	/**
	 * Returns members of this group of type "group" 
	 * @return Zend_Db_Table_Rowset
	 */
	public function getGroupMembers()
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::GROUPS,'rowClass'=>'Group'));
		$rows = $table->fetchAll($table->select()->where('parent_id = ?',$this->id)
												->where('active = 1'));
		return $rows;
	}
	
	/**
	 * Returns members of this group of type "User" 
	 * @return Zend_Db_Table_Rowset
	 */
	public function getUserMembers()
	{
		return self::getUsers($this->id);
	}
	/**
	 * Adds a user to the member list of a group, please note that groups cannot be "directly"
	 * added to member list of another group, this can only be done through the "parent" property
	 * of the child group, we did this to force sanity on group structures.
	 * @param $user_id int ID of the user to add to group
	 * @return null
	 */
	public function  addUserMember($user_id)
	{
		UserUtil::addUserToGroup($user_id,$this->id);
	}
	
	/**
	 * Removes a user from group membership
	 * @param $user_id int ID of the user to add to group
	 * @return null
	 */
	public function removeUserMember($user_id)
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::USER_GROUPS));
		$row = $table->fetchRow($table->select()->where('user_id = ?',$user_id)
												->where('group_id = ?',$this->id));
		if($row)
		{
			$row->delete(); //delete user to group relationship
		}
		
		//now handle location or department issues
		//first is location
		if($this->is_location)
		{
			$user = UserUtil::getUser($user_id);
			$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::LOCATIONS,'rowClass'=>'Location'));
			$location = $table->fetchRow($table->select()->where('id= ? ',$user->getLocationId()));
			if ($location != null)//if user has set a valid location
			{
				if($location->getGroupId() == $this->id)//if location group is the same as group from which user has been removed
				{
					$user->location_id = 0;
					$user->save();
				}
			}
			
		}
		//now do the same for department
		if($this->is_department)
		{
			$user = UserUtil::getUser($user_id);
			$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::DEPARTMENTS,'rowClass'=>'Department'));
			$department = $table->fetchRow($table->select()->where('id= ? ',$user->getDepartmentId()));
			if ($department != null)//if user has set a valid department
			{
				if($department->getGroupId() == $this->id)//if department group is the same as group from which user has been removed
				{
					$user->department_id = 0;
					$user->save();
				}
			}
			
		}
	}
	/**
	 * Removes a group from group membership
	 * @param $group_id int 
	 * @return null
	 */
	public function removeGroupMember($group_id)
	{
		$group = $this->getGroupMember($group_id);
		
		if($group)
		{
			$group->removeParent();
		}
	}	
	/**
	 * @param $group_id int
	 * @return Group
	 */
	public function getGroupMember($group_id)
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::GROUPS,'rowClass'=>'Group'));
		$row = $table->fetchRow($table->select()->where('parent_id = ?',$this->id)
												->where('id = ?',$group_id));
		return $row;
	}
	public function removeParent()
	{
		$this->parent_id = 0;
		$this->save();
	}
	public function getRole()
	{
		if($this->is_role)
		{
			$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::ROLES,'rowClass'=>'Role'));
			$item = $table->fetchRow($table->select()->where('group_id = ?',$this->id));
			return $item;
		}
		return null;
	}
	public function getLocation()
	{
		if($this->is_location)
		{
			$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::LOCATIONS,'rowClass'=>'Location'));
			$item = $table->fetchRow($table->select()->where('group_id = ?',$this->id));
			return $item;
		}
		return null;
	}
	public function getDepartment()
	{
		if($this->is_department)
		{
			$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::DEPARTMENTS,'rowClass'=>'Department'));
			$item = $table->fetchRow($table->select()->where('group_id = ?',$this->id));
			return $item;
		}
		return null;
	}
	public function deactivateRole()
	{
		if($this->is_role)
		{
			$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::ROLES));
			$item = $table->fetchRow($table->select()->where('group_id = ?',$this->id));
			$item->active = 0;
			$item->save();
		}
	}
	public function deactivateLocation()
	{
		if($this->is_location)
		{
			$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::LOCATIONS));
			$item = $table->fetchRow($table->select()->where('group_id = ?',$this->id));
			$item->active = 0;
			$item->save();
		}
	}
	public function deactivateDepartment()
	{
		if($this->is_department)
		{
			$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::DEPARTMENTS));
			$item = $table->fetchRow($table->select()->where('group_id = ?',$this->id));
			$item->active = 0;
			$item->save();
		}
	}
	public function deactivate()
	{
		$this->active = 0;
		$this->save();
		
		$this->deactivateRole();
		$this->deactivateLocation();
		$this->deactivateDepartment();
	}

	public function getGroupId()
	{
		return $this->id;
	}	
	
	/**
	 * Determines if a user is a member of the group
	 * @param boolean
	 */
	public function containsMember($user_id)
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::USER_GROUPS));
		$row = $table->fetchRow($table->select()->where('user_id = ?',$user_id)->where('group_id = ?',$this->getGroupId()));
		return !empty($row);
	}
	
	public static function getGroupIdFromName($name)
	{
		//first find a group with the location name.
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::GROUPS));
		$group = $table->fetchAll($table->select()->where('title = ?',$name));
		$log = Zend_Registry::get('log');
		if($group->count() > 1)
		{
			$log->warn("There is more than one group with the name {$name}");
		}
		if($group->count() == 0)
		{
			$log->err("There no group with the name {$name}, group name search failed");
			return 0;
		}
		$group = $group->current();
		return $group->id;
			
	}
	/**
	 * Get all users belonging to a group
	 * @return Zend_Db_Table_Rowset
	 */
	public static function getUsers($group_id)
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::USERS,'rowClass'=>'User'));
		$select = $table->select(false);
		$select->setIntegrityCheck(false);
		$select->setTable($table); 
		$select = $select->distinct()
						->from(array('a' => PrecurioTableConstants::USERS))
						->join(array('b' => PrecurioTableConstants::USER_GROUPS),'a.user_id = b.user_id',array())
						->where('b.group_id = ? ',$group_id)
						->where('a.active = 1')
						->where('a.out_of_office = 0');
		$users = $table->fetchAll($select);
		return $users;
	}
	
	
}

?>