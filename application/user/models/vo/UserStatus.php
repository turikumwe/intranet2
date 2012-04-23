<?php

require_once ('Zend/Db/Table/Row/Abstract.php');

class UserStatus extends Zend_Db_Table_Row_Abstract {

	public function getMessage()
	{
		return $this->message;
	}
	public function getFullName()
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::USERS,'rowClass'=>'User'));
		$user = $table->fetchRow($table->select()->where('user_id= ? ',$this->user_id));
		return $user->getFullName();
	}
}

?>