<?php
require_once 'task/models/vo/Task.php';
class WorkflowReport {
	
	private $user_id;
	public function __construct($user_id=0)
	{
		$this->user_id = $user_id == 0 ? Precurio_Session::getCurrentUserId() : $user_id;	
	}
	public function getNumOfCompleted()
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::USER_PROCESS));
		$select = $table->select(true)->distinct()
				->where('user_id = ?',$this->user_id)
				->where('completed = ?', Task::STATUS_COMPLETE);
		return $table->fetchAll($select)->count();
	}
	public function getNumOfPending()
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::USER_PROCESS));
		$select = $table->select(true)->distinct()
				->where('user_id = ?',$this->user_id)
				->where('completed = ?', Task::STATUS_ONHOLD);
		return $table->fetchAll($select)->count();
	}
	public function getNumOfDue()
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::USER_PROCESS));
		$select = $table->select(false);
		$select->setIntegrityCheck(false);
		$select = $select->distinct()
				->from(array('a'=>PrecurioTableConstants::USER_PROCESS))
				->join(array('b' => PrecurioTableConstants::TASK),'a.task_id = b.id',array('end_time'))
				->where('a.user_id = ?',$this->user_id)
				->where('a.completed <> ?', Task::STATUS_COMPLETE)
				->where('b.end_time < unix_timestamp()');
		return $table->fetchAll($select)->count();
	}
	public function getNumOfApproved()
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::USER_PROCESS));
		$select = $table->select(true)->distinct()
				->where('user_id = ?',$this->user_id)
				->where('completed = ?', Task::STATUS_COMPLETE)
				->where('task_id <> 0')
				->where('rejected = 0');
		return $table->fetchAll($select)->count();
	}
	public function getNumOfDenied()
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::USER_PROCESS));
		$select = $table->select(true)->distinct()
				->where('user_id = ?',$this->user_id)
				->where('completed = ?', Task::STATUS_COMPLETE)
				->where('task_id <> 0')
				->where('rejected = 1');
		return $table->fetchAll($select)->count();
	}
}

?>