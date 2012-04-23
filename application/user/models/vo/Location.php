<?php

require_once ('Zend/Db/Table/Row/Abstract.php');


class Location extends Zend_Db_Table_Row_Abstract 
{
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
	public static function getLocationName($id)
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::LOCATIONS,'rowClass'=>'Location'));
		$location = $table->fetchRow($table->select()->where('id= ? ',$id));
		return $location == null ? '' : $location->getTitle();
	}	
	public static function getLocationId($name)
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::LOCATIONS));
		$location = $table->fetchRow($table->select()->where('title = ?',$name));
		if($location != null)
			return $location->id;
		
		//ok there is no location with such name, attempt automatic detection ;)	
		$log = Zend_Registry::get('log');
		$log->info("Using auto-location detection for {name}");
		//first find a group with the location name.
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::GROUPS,'rowClass'=>'Group'));
		$group = $table->fetchAll($table->select()->where('title = ?',$name));
		if($group->count() > 1)
		{
			$log->warn("There is more than one group with the name {$name}");
		}
		if($group->count() == 0)
		{
			$log->err("There no group with the name {$name}, automatic location detection failed.");
			return 0;
		}
		try
		{
			$group = $group->current();
			$data = array();
			$data['title'] = $group->title;
			$data['group_id'] = $group->id;
			$data['date_created'] = Precurio_Date::now()->getTimestamp();
			$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::LOCATIONS));
			$location = $table->createRow($data);
			
			return $location->save();
		}
		catch(Exception $e)
		{
			$log->err($e);
			return 0;
		}
	}
}

?>