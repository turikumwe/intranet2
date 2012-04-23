<?php

require_once ('user/models/vo/User.php');

class UserReport extends User {
	
	public function getNumOfEvents()
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::EVENT));
		$items = $table->fetchAll($table->select()->where('user_id= ? ',$this->getId()));
		return $items->count();
	}
	
	public function getNumOfTasks()
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::TASK_USERS));
		$items = $table->fetchAll($table->select()->where('user_id= ? ',$this->getId()));
		return $items->count();
	}
	
	public function getNumOfContacts()
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::CONTACTS));
		$items = $table->fetchAll($table->select()->where('user_id= ? ',$this->getId()));
		return $items->count();
	}
	public function getNumOfProcesses()
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::USER_PROCESS));
		$items = $table->fetchAll($table->select()->where('user_id= ? ',$this->getId()));
		return $items->count();
	}
	public function getPercentageContent()
	{
		return @round($this->getNumOfContents()  * 100 / $this->getTotal(PrecurioTableConstants::CONTENT),2);
	}
	public function getPercentageComment()
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::COMMENTS));
		$total =  $table->fetchAll($table->select())->count();
		return @round($this->getNumOfComments()  * 100 / $total,2);
	}
	public function getPercentageEvent()
	{
		return @round($this->getNumOfEvents()  * 100 / $this->getTotal(PrecurioTableConstants::EVENT),2);
	}
	public function getPercentageTask()
	{
		return @round($this->getNumOfTasks()  * 100 / $this->getTotal(PrecurioTableConstants::TASK),2);
	}
	public function getPercentageContact()
	{
		return @round($this->getNumOfContacts()  * 100 / $this->getTotal(PrecurioTableConstants::CONTACTS),2);
	}
	public function getPercentageProcess()
	{
		return @round($this->getNumOfProcesses()  * 100 / $this->getTotal(PrecurioTableConstants::USER_PROCESS),2);
	}
	public function getTotal($table)
	{
		$table = new Zend_Db_Table(array('name'=>$table));
		return $table->fetchAll($table->select()->where('active = 1 '))->count();
	}

}

?>