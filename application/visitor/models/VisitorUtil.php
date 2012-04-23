<?php

require_once('contact/models/vo/Contact.php');
require_once('admin/models/Rules.php');
require_once('user/models/vo/Role.php');
require_once('user/models/vo/Group.php');
require_once('user/models/UserUtil.php');

/**
 * This is a  utility class for the visitor manager module
 * @author Kayfun
 *
 */
class VisitorUtil
{
	public static function getContacts()
	{
		return array_merge( self::getUserContacts()->toArray(),self::getSharedContacts()->toArray() );		
	}
	
	public  static function getUserContacts()
	{			
		$user_id = Precurio_Session::getCurrentUserId();
		
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::CONTACTS,'rowClass'=>'Contact'));
		return $table->fetchAll($table->select()
				->where('user_id= ? ',$user_id)
				->where('active= 1 ')
				->order('full_name ASC'));		
	}	
	
	public static function getSharedContacts()
	{
		$user_id = Precurio_Session::getCurrentUserId();
		
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::CONTACTS,'rowClass'=>'Contact'));
		return $table->fetchAll($table->select()
				->where("shared = 1 AND user_id != $user_id")
				->where('active= 1 ')
				->order('full_name ASC'));
	}
			
		
	public static function addUserContact($contact)
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::CONTACTS));
				
		$row = $table->createRow($contact);
		$id = $row->save();	
		return $id;
	}
	
	/* Function returns a receptionist's boss -- if receptionist is to more than one user, the first user found is returned */
	public static function getBoss($user_id)
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::USERS,'rowClass'=>'User'));
		
		$staff = $table->fetchRow($table->select()->where('receptionist_id = ? ',$user_id));

		// If for any reason, receptionist is to no user, return current user
		if( count($staff) < 1 ) 
		return UserUtil::getUser(Precurio_Session::getCurrentUserId());	
														  
		return $staff;
	}
	
	public static function setReceptionist($receptionist_id)
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::USERS,'rowClass'=>'User'));
		$user_id = Precurio_Session::getCurrentUserId();	
		$tr = Zend_Registry::get('Zend_Translate');
		try
		{
			
			$data['receptionist_id'] = $receptionist_id;
			$user = $table->find($user_id)->current();
			
			$user->setFromArray($data);
			$user->save();				
			
			$msg = $tr->translate(PrecurioStrings::EDITSUCESS);			
		}
		catch (Exception $e)
		{
			$msg = $tr->translate(PrecurioStrings::ERROR_PERFORMING_OPERATION);
		}
		return $msg;
	}
	
	
	
	function isAllowed($resource)//this function has a clone in report/views/scripts/index/index.phtml
	{
		try
		{
			$ignoreAcl = Zend_Registry::get('ignoreAcl');//this is set in performAcl()::Initializer.php
			if($ignoreAcl)return true;
		}
		catch(Exception $e)
		{
 		
		}
 	
 	
		$resource = new Precurio_Resource($resource);

		$userRoles = UserUtil::getUser(Precurio_Session::getCurrentUserId())->getRoles();
    
	
		$acl = Precurio_Acl::getInstance();
		$acl->initialise(array($resource),$userRoles);
    
		if(!$acl->has($resource))return true;
		foreach($userRoles as $role)
		{
			if (!$acl->hasRole($role)) 
			{
				continue;									
			}
			if ($acl->isAllowed($role, $resource,'view'))
			{
				return true;//if the user belongs to a role that is allowed access, then he can
				//access the resource even if he belongs to roles that have been denied access
			}
		}
 
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::ROLES));
		if($table->fetchAll()->count() > 1)//if there is more than one available role, then set error
		{
			//ok, the administrator has created atleast 2 roles, let's check if he has assign rules to them
			$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::RULES));
			if($table->fetchAll()->count() > 0)//atleast, a rule has been set on one of the roles, then throw acess error
			{
				return false;
			}
			else
			{
				return true;
			}
		}//else, allow access to all resources since there aren't enough roles or rules to successfully implement the role based acess control.
		else
		{
			return true;
		}	
		  	
		return false;
	}
	
		
	public static function getReceptionists()
	{
		$receptionists = array();
		
		
		$groupIds = self::getReceptionistGroups();

		foreach($groupIds as $groupId)
		{
			$group = UserUtil::getGroup($groupId);
				
			$groupMembers = Group::getUsers($groupId);		
			
			foreach($groupMembers as $member)
				$receptionists[$member->getId()] = $member;  // key to prevent duplicate entries in the case that user belongs to different groups that allows a receptionist
		}
				
		return $receptionists;		
	}
	
	
	/**
	 * Returns all the groups that can act as a receptionist.
	 * These are groups that have been given the 'share' privilege to 'visitors' resource
	 * @return array
	 */
	public static function getReceptionistGroups()
	{
		$group_ids = array();
		
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::RULES,'rowsetClass'=>'Rules'));
		$result = $table->fetchAll($table->select()->where('resource = ?','visitor_index')
					->where("allow=1"));
		
		foreach($result as $rule) 
		{
			$roleId = $rule->role_id;
			if(Precurio_Utils::isNull($rule->privilege) || $rule->privilege == 'share')
			{
				$rtable = new Zend_Db_Table(array('name'=>PrecurioTableConstants::ROLES,'rowClass'=>'Role'));
				$item = $rtable->fetchRow($rtable->select()->where('id = ?',$roleId));
			
				if($item->active)
				$group_ids[] = $item->group_id;
			}		
			
		}
		
		return $group_ids;
	}
	
	/**
	 * checks if a user has a recpetionist; If yes, returns the receptionist_id
	 * else returns 0
	 * @return int receptionist_id
	 */
	public static function getReceptionist($user_id)
	{
		$user = UserUtil::getUser($user_id);
		
		if( Precurio_Utils::isNull($user->receptionist_id) )
		return 0;
		else return $user->receptionist_id;
	}
	
	public static function isReceptionist($user_id)
	{		
		$receptionists = self::getReceptionists();
		
		return array_key_exists($user_id, $receptionists);
														
	}
	
	/**
	 * Fetches all vistors
	 * @return Zend_Db_Table_Rowset
	 */
	public static function getAllVisitors()
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::VISITOR,'rowClass'=>'Visitor'));
		
		$select = $table->select(false);
		$select->setTable($table)->setIntegrityCheck(false);
		
		$select = $select->from(array('a' => PrecurioTableConstants::VISITOR))
			->join(array('b' => PrecurioTableConstants::CONTACTS),'a.contact_id = b.id',array('full_name', 'company','user_id','address','work_phone','mobile_phone','fax','email','website','job_title'))
			->join(array('c' => PrecurioTableConstants::USERS),'b.user_id = c.user_id', array('first_name', 'last_name'))
			->where("a.active =1 AND b.active = 1");
			
		$visitors = $table->fetchAll($select);
		return $visitors;
	}

}

?>