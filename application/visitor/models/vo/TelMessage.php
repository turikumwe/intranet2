<?php
require_once ('Zend/Db/Table/Row/Abstract.php');

class TelMessage extends Zend_Db_Table_Row_Abstract
{
	public function getMessageAttendant()
	{
		return ucfirst($this->attendant_lname).' '.ucfirst($this->attendant_fname);
	}
	
	public function getMessageTarget()
	{
		return ucfirst($this->target_lname).' '.ucfirst($this->target_fname);
	}
	
	public function getLogDate()
	{
		$date = new Precurio_Date($this->date_logged);
		if($date->isYesterday()) return 'Yesterday';
		if($date->isToday()) return 'Today';		
		return $date->get(Precurio_Date::DATE_LONG);
	}
	
	public function getLogTime()
	{
		$date = new Precurio_Date($this->date_logged);
		return $date->get(Precurio_Date::TIME_SHORT);
	}
	
	public function isMine()
	{
		return $this->logged_for == Precurio_Session::getCurrentUserId();
	}
}

?>

