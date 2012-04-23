<?php

require_once ('Zend/Db/Table/Row/Abstract.php');
require_once ('Zend/Acl/Role/Interface.php');
class Role extends Zend_Db_Table_Row_Abstract  implements Zend_Acl_Role_Interface{
	/**
	 * @var Group
	 */
	private $_group;

	public function getId()
	{
		return $this->id;
	}	
	public function getGroupId()
	{
		return $this->group_id;
	}	
	public function getTitle()
	{
		return $this->title;
	}	
	public function getDescription()
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::GROUPS));
		$group = $table->fetchRow($table->select()->where('id = ?',$this->group_id));
		return $group->description;
	}
	
	public function getRoleId()
	{
		return $this->getId();
	}
	
	/**
	 * @return Role
	 */
	public function getParent()
	{
		//select the role whose group_id is this role's group's parent_id
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::ROLES,'rowClass'=>'Role'));
		$role = $table->fetchRow($table->select()->where('group_id = ?',$this->getParentId()));
		return $role;
	}
	
	public function getParentId()
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::GROUPS));
		$group = $table->fetchRow($table->select()->where('id = ?',$this->getGroupId()));
		return $group->parent_id;
	}
		
	public static function getRoleIdFromName($name)
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::ROLES));
		$role = $table->fetchRow($table->select()->where('title = ?',$name));
		if($role != null)
			return $role->id;
		
		//ok there is no role with such name, attempt automatic detection ;)	
		$log = Zend_Registry::get('log');
		$log->info("Using auto-role detection for role {name}");
		//first find a group with the role name.
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::GROUPS,'rowClass'=>'Group'));
		$group = $table->fetchAll($table->select()->where('title = ?',$name));
		if($group->count() > 1)
		{
			$log->warn("There is more than one group with the name {$name}");
		}
		if($group->count() == 0)
		{
			$log->err("There no group with the name {$name}, automatic role detection failed.");
			return 0;
		}
		try
		{
			$group = $group->current();
			$data = array();
			$data['title'] = $group->title;
			$data['group_id'] = $group->id;
			$data['date_created'] = Precurio_Date::now()->getTimestamp();
			$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::ROLES));
			$role = $table->createRow($data);
			
			return $role->save();
		}
		catch(Exception $e)
		{
			$log->err($e);
			return 0;
		}
	}
	
}

?>