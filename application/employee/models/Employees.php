<?php
require_once ('user/models/vo/User.php');
class Employees {
	/**
	 * @var Zend_Db_Table_Rowset_Abstract
	 */
	private $_allEmployees;
	
	public function getAll($includeYourself = false)
	{
		$current_user_id = Precurio_Session::getCurrentUserId();
		if(!Precurio_Utils::isNull($this->_allEmployees))
			return $this->_allEmployees; //this would have worked if the web was freaking stateful
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::USERS,'rowClass'=>'User'));
		$select = $table->select()->where('active=1')->where("SUBSTRING(username,1,5) <> 'guest'");
		if(!$includeYourself)
			$select = $select->where('user_id <> ?', $current_user_id);
		$this->_allEmployees = $table->fetchAll($select);
		
		return $this->_allEmployees;
	
	}
	public function getRecentProfileChanges()
	{
		$recentDate = new Precurio_Date();
		$recentDate->sub(14,Precurio_Date::HOUR);//i.e. last 2 days
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::USERS,'rowClass'=>'User'));
		$result = $table->fetchAll($table->select()->where('unix_timestamp(last_updated) > ? ',$recentDate->getTimestamp())
													->where('active= ? ',1)
													->order('last_updated DESC'));
		return $result;
	}
	public static function getOutofOffice()
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::USERS,'rowClass'=>'User'));
		$result = $table->fetchAll($table->select()->where('out_of_office= ? ',1)
													->order('last_updated DESC'));
		return $result;
	}
	/**
	 * @param $month int
	 * @param $intelli Boolean, Determines whether it does an intelligent filter, i.e 
	 * it will also pick birthdays from the following month if you are near the end of the 
	 * current month
	 * $param $limit int Limits the number of results returned, ignored if intelli is true
	 * @return Zend_Db_Table_Rowset_Abstract | Array - It only returns an Array if intelli is true;         
	 */
	public static function getBirthdays($month=0,$limit=40000,$intelli = true)
	{
		$date = Precurio_Date::now();
		if($month == 0)
			$month = $date->get(Precurio_Date::MONTH_SHORT);
		$day = $date->get(Precurio_Date::DAY);
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::USERS,'rowClass'=>'User'));
		$result = $table->fetchAll($table->select()->where('birth_month= ? ',$month)
													->where('birth_day> ? ',$day)
													->order('birth_day ASC')
													->limit($limit));
		
		if($intelli && $day > 20)
		{
			$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::USERS,'rowClass'=>'User'));
			$result_2 = $table->fetchAll($table->select()->where('birth_month= ? ',$month + 1)
													->where('birth_day< ? ',10)//first 10 days of the next month
													->order('birth_day ASC'));
			
			$temp = $result;
			$result = array();
			foreach($temp as $obj)
			{	
				$result[] = $obj;
			}
			foreach($result_2 as $obj)
			{	
				$result[] = $obj;
			}
		}
		return $result;
	}
	/**
	 * Filters a source based on filter parameters
	 * @param $filterParam Array in format array('key'=>'value')
	 * @param $source Array
	 * @return Array of Filtered Values
	 */
	public function filter($filterParam,$source = null)
	{
		//please consider optimizing this function if you have more than 500 employees in the database.
		//So instead of fetching all and then filtering, simply build a complex AND query statement.
		if($source == null)$source = $this->getAll();
		$filteredSource = array();
		
		foreach($source as $item)
		{
			$passed = true;
			foreach($filterParam as $param=>$filterValue)
			{
				if(!isset($item[$param]))//if $param does not exist
				{
					continue;
				}
				if($item[$param] == $filterValue)	
					$passed = true;
				else
				{
					$passed = false;
					break;//once there is a false, exit;
				}
			}
			if($passed)$filteredSource[] = $item;
		}
		return $filteredSource;
	}

}

?>