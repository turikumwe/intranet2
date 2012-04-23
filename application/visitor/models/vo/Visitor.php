<?php
require_once ('Zend/Db/Table/Row/Abstract.php');
require_once('visitor/models/VisitorStrings.php');
		
class Visitor extends Zend_Db_Table_Row_Abstract
{
	var $expectedTime;
	
	/**
	 * Returns the full name of the host
	 * @return string
	 */
	public function getToSee()
	{
		return ucfirst($this->first_name).' '.ucfirst($this->last_name);
	}
	
	/**
	 * Returns the host
	 * @return User
	 */
	public function getHost()
	{
		return UserUtil::getUser($this->getUserId());
	}
	
	public function getUserId()
	{
		return $this->user_id;
	}
	
	public function getTimeIn()
	{
		if( Precurio_Utils::isNull($this->time_in))
		return '';
		
		$date = new Precurio_Date($this->time_in);
		return $date->get(Precurio_Date::TIME_SHORT);
	}
	
	public function getTimeOut()
	{
		if( Precurio_Utils::isNull($this->time_out))
		return '';
		$date = new Precurio_Date($this->time_out);
		return $date->get(Precurio_Date::TIME_SHORT);
	}
	
	public function getVisitDate()
	{
		$date = new Precurio_Date($this->appointment_date);
		if($date->isYesterday()) return 'Yesterday';
		if($date->isToday()) return 'Today';
		
		return $date->get(Precurio_Date::DATE_SHORT);
	}
	
	public function getVisitTime()
	{
		$date = new Precurio_Date($this->appointment_date);
		return $date->get(Precurio_Date::TIME_SHORT);
	}
	
	public function isIn()
	{
		return ! $this->absent() && ! $this->isOut();
	}
	
	public function isOut()
	{
		return ! Precurio_Utils::isNull($this->time_out);
	}
	
	public function absent()
	{
		return Precurio_Utils::isNull($this->time_in) && Precurio_Utils::isNull($this->time_out);
	}
	
	public function isExpected()
	{
		$visits = $this->getVisits();
		
		$timestamp = Precurio_Date::now()->getTimestamp();
		
		foreach($visits as $visit)
		{
			if( Precurio_Utils::isNull($visit->time_in) && ($visit->appointment_date > $timestamp) )
			{
				$this->expectedTime = ' (expected - '. $visit->getVisitDate().'  '. $visit->getVisitTime() .')';
				return true;
			}			
		}
		
		return false;
	}
	
	public function getExpectedTime()
	{
		return $this->expectedTime;
	}
	
	public function getVisits($type=VisitorStrings::USER)
	{
		$user_id = Precurio_Session::getCurrentUserId();
		$visitor_id = $this->id;
		
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::VISITOR_APPOINTMENT, 'rowClass'=>'Visitor'));
		$select = $table->select(false);
		
		$select->setTable($table)->setIntegrityCheck(false);			
			
		$select = $select->from(array('a' => PrecurioTableConstants::VISITOR_APPOINTMENT))
			->join(array('b' => PrecurioTableConstants::VISITOR),'a.visitor_id = b.id', array('DOB', 'car_reg_number', 'emergency_contact'))	
			->join(array('d' => PrecurioTableConstants::APPOINTMENT),'a.appointment_id = d.id', array('host', 'purpose', 'title', 'purpose_detail', 'appointment_date'));
			
			
		switch($type)
		{
			case VisitorStrings::USER:
			$select = $select->where("a.active = 1 AND a.visitor_id = $visitor_id");
				break;
			case VisitorStrings::RECEPTIONIST:
			$select = $select->where("a.active = 1 AND a.visitor_id = $visitor_id AND a.logged_by = $user_id");
				break;
			case VisitorStrings::ADMIN:
			$select = $select->where("a.active = 1 AND a.visitor_id = $visitor_id");
				break;
		}
		
		return $table->fetchAll($select);
	}
	
}

?>	