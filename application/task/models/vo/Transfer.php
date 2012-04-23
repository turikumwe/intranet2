<?php

require_once ('Zend/Db/Table/Row/Abstract.php');

class Transfer extends Zend_Db_Table_Row_Abstract {

	
	public function getTo()
	{
		if($this->to_user_id == Precurio_Session::getCurrentUserId())
			return 'You';
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::USERS));
		$user = $table->fetchRow($table->select()->where('user_id= ? ',$this->to_user_id));
		return $user == null ? '' : $user->first_name.' '.$user->last_name;
	}
	public function getFrom()
	{
		if($this->from_user_id == Precurio_Session::getCurrentUserId())
			return 'You';
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::USERS));
		$user = $table->fetchRow($table->select()->where('user_id= ? ',$this->from_user_id));
		return $user == null ? '' : $user->first_name.' '.$user->last_name;
	}
	public function getDate()
	{
		$date = new Precurio_Date($this->date_created);	
		return $date->get(Precurio_Date::DATE_LONG);
	}
}

?>