<?php

require_once ('Zend/Db/Table/Row/Abstract.php');
require_once('user/models/UserUtil.php');
require_once 'workflow/models/vo/Process.php';

class UserProcess extends Zend_Db_Table_Row_Abstract {
	
	/**
	 * get the id
	 * @return int
	 */
	public function getId()
	{
		return $this->id;
	}
	public function getProcessId()
	{
		return $this->process_id;
	}
	public function getStateId()
	{
		return $this->state_id;
	}
	public function getFormId()
	{
		return $this->form_id;
	}
	public function getUserId()
	{
		return $this->user_id;
	}
	public function getTaskId()
	{
		return $this->task_id;
		
	}
	public function getDate()
	{
		$date = new Precurio_Date($this->date_created);
		return $date->get(Precurio_Date::DATE_SHORT);
	}
	public function getStatus()
	{
		//if you are going to change this status to reflect 'approved' or 'not approved'
		//for every user, then consider the fact that in the case of a rejection, i.e non approval
		// only the rejectee and the process owner will be able to know the particlar form
		//was 'NOT APPROVED', every other user inbetween the process, sees it as 'APPROVED'
		//this behaviour can be changed by make sure, ProcessManager::rejectProcess() updates
		//the 'rejected' flag of all process within a form.
		if($this->task_id == 0)
			return $this->completed == 1 ? ($this->rejected == 1 ? 'NOT APPROVED' :'APPROVED') : 'PENDING';
		else
			return $this->completed == 1 ? 'COMPLETED' : 'PENDING';
	}
	public function getFormCode()
	{
		$process = $this->getProcess();
		return $process->getCode().'-'.$this->getFormId();
	}
	public function getDisplayName()
	{
		$process = $this->getProcess();
		return $process->getDisplayName();
	}
	public function getTableName()
	{
		$process = $this->getProcess();
		return $process->getTableName();
	}
	public function isRejected()
	{
		return $this->rejected;
	}
	/**
	 * @return Process
	 */
	private function getProcess()
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::WORKFLOW,'rowClass'=>'Process'));
		$process = $table->fetchRow($table->select()->where('id = ?',$this->process_id));
		return $process;
	}
	public function getColourCode()
	{
		$process = $this->getProcess();
		return $process->getColourCode();
	}
	/**
	 * Returns the actual owner of the entire process,i.e the person who initiated the process
	 * @return User
	 */
	public function getOwner()
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::USER_PROCESS));
		$user_process = $table->fetchRow($table->select()->where('task_id = 0')->where('process_id = ?',$this->getProcessId())->where('form_id = ?', $this->getFormId()));
		return UserUtil::getUser($user_process->user_id); 
	}
	/**
	 * Determines if a user can view a form, by checking if the user is in the process flow
	 * @param $user_id int user id
	 * @return Boolean
	 */
	public function userCanView($user_id)
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::USER_PROCESS));
		$row = $table->fetchRow($table->select()->where('user_id = ?',$user_id)->where('form_id = ?', $this->getFormId()));
		return $row != null;
		
	}
	/**
	 * Returns the User Object currently handling the next approval level
	 * @return User
	 */
	public function getCurrentApprover()
	{
		//the next approver is the person who has a task that is not completed.
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::USER_PROCESS));
		$user_process = $table->fetchRow($table->select()->where('task_id <> 0')
															->where('completed = 0')
															->where('form_id = ?', $this->getFormId()));
		if($user_process == null)return "N/A";
		
		return UserUtil::getUser($user_process->user_id); 
	}
	
	public function getRejectionComment()
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::USER_PROCESS_REJECT));
		$row = $table->fetchRow($table->select()->where('user_process_id = ?',$this->getId()));
		if($row == null)return '[NO REASON GIVEN]';
		return Precurio_Utils::isNull($row->comment) ? '[NO REASON GIVEN]' : $row->comment;
	}

}

?>