<?php

require_once ('Zend/Db/Table/Rowset/Abstract.php');

class Rules extends Zend_Db_Table_Rowset_Abstract {
	
	/**
	 * Simply checks if a rule set contains a privilege access.
	 * If it does, we return the access type i.e allowed or not allowed.
	 * If it doesn't, we create a new privilege with access set to not allowed.
	 * @param $privilege
	 * @return Boolean
	 */
	public function givenPrivilege($privilege)
	{
		$granted = false;
		$found = NULL;
		$nullFound = NULL;
		foreach($this as $row)
		{
			if($row->privilege == $privilege)
			{
				$found = $row;
				break;
			}
			if(Precurio_Utils::isNull($row->privilege))
			{
				$nullFound  = $row;
			}
		}
		
		if(!$found)
		{
			if($nullFound)
			{
				return $nullFound->allow;
			}
			else
			{
				$this->createRule($row->role_id,$row->resource,$privilege);
			}
		}
		else
			return $found->allow;
	}
	
	private function createRule($role_id,$resourceId,$privilege,$allow = 0)
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::RULES));
		$rule = $table->fetchRow($table->select()->where('role_id = ?',$role_id)->where('resource = ?',$resourceId)->where('privilege = ?',$privilege));
		if($rule)
		{
			$rule->allow = $allow;
			return $rule->save();
		}
		else
		{
			$row = $table->createRow(array(
			'role_id'=>$role_id,
			'resource'=>$resourceId,
			'privilege'=>$privilege,
			'allow'=>$allow,
			'created_by'=>Precurio_Session::getCurrentUserId(),
			'date_created'=>Precurio_Date::now()->getTimestamp()
			));
			return $row->save();	
		}
		
			
	}
	public function setPrivilege($privilege,$allow)
	{
		foreach($this as $row)
		{
			if($row->privilege == $privilege)
			{
				$row->allow = $allow;
				$row->save();
				return;
			}
		}
		//ok, not found
		$this->createRule($row->role_id,$row->resource,$privilege,$allow);
		return ;
	}
	public function getRoleId()
	{
		return $this->getRow(0)->role_id;
	}
	public function getResourceId()
	{
		return $this->getRow(0)->resource;
	}
	public function getRoleName()
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::ROLES));
		return $table->find($this->getRoleId())->current()->title;
	}
	public function getResourceName()
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::RESOURCES));
		return $table->fetchRow($table->select()->where('resource = ?',$this->getResourceId()))->display_name;
	}
}

?>