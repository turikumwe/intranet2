<?php

require_once ('Zend/Db/Table/Row/Abstract.php');

class Department extends Zend_Db_Table_Row_Abstract {
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
	public function getColourCode()
	{
		if(Precurio_Utils::isNull($this->colour_code))
		{
			$this->colour_code = $this->generateColourCode();
			$this->save();
		}
		return $this->colour_code;
	}
	private function generateColourCode()
	{
		$availableColours = array('red','green','orange','purple','blue');
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::DEPARTMENTS));
		$departments = $table->fetchAll($table->select()->where('colour_code <> ?',''));
		$usedColours  = array();
		foreach($departments as $dept)
		{
			$usedColours[] = $dept->colour_code;
		}
		$unUsedColours = array_diff($availableColours,$usedColours);
		if(count($unUsedColours))
		 	return $unUsedColours[0];
		return "grey";
		
	}
	public static function getDepartmentId($name)
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::DEPARTMENTS));
		$department = $table->fetchRow($table->select()->where('title = ?',$name));
		if($department != null)
			return $department->id;
		
		//ok there is no department with such name, attempt automatic detection ;)	
		$log = Zend_Registry::get('log');
		$log->info("Using auto-department detection for department {name}");
		//first find a group with the department name.
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::GROUPS,'rowClass'=>'Group'));
		$group = $table->fetchAll($table->select()->where('title = ?',$name));
		if($group->count() > 1)
		{
			$log->warn("There is more than one group with the name {$name}");
		}
		if($group->count() == 0)
		{
			$log->err("There no group with the name {$name}, automatic department detection failed.");
			return 0;
		}
		try
		{
			$group = $group->current();
			$data = array();
			$data['title'] = $group->title;
			$data['group_id'] = $group->id;
			$data['date_created'] = Precurio_Date::now()->getTimestamp();
			$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::DEPARTMENTS));
			$department = $table->createRow($data);
			
			return $department->save();
		}
		catch(Exception $e)
		{
			$log->err($e);
			return 0;
		}
	}

}

?>