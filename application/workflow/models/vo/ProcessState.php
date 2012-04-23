<?php

require_once ('Zend/Db/Table/Row/Abstract.php');
require_once 'user/models/UserUtil.php';
require_once 'workflow/models/vo/Process.php';
class ProcessState extends Zend_Db_Table_Row_Abstract{
	/**
	 * get the id
	 * @return int
	 */
	public function getId()
	{
		return $this->id;
	}
	/**
	 * get the process_id
	 * @return int
	 */
	public function getProcessId()
	{
		return $this->process_id;
	}
	/**
	 * get the actual name of a state, this should probably be a generated code
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}
	/**
	 * get the display name of the state
	 * @return string
	 */
	public function getDisplayName()
	{
		return Precurio_Utils::isNull($this->display_name) ?  $this->name : $this->display_name;
	}
	/**
	 * Returns the maximum number of hours this state should take
	 * @return int
	 */
	public function getDuration()
	{
		return $this->duration;
	}
	/**
	 * Returns the positon of the state in the overall process flow
	 * @return int
	 */
	public function getPosition()
	{
		return $this->position;
	}
	
	/**
	 * Determines whether this state is departmental or not, 
	 * a departmental state can only be approved by a user in the same department.
	 * @return Boolean
	 */
	public function isDepartmental()
	{
		return $this->departmental;
	}
	/**
	 * Determines whether this state is location based or not, 
	 * a location based state can only be approved by a user in the same location.
	 * @return Boolean
	 */
	public function isLocational()
	{
		return $this->locational;
	}
	
	public function isStartState()
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::WORKFLOW,'rowClass'=>'Process'));
		$process = $table->fetchRow($table->select()->where('id = ?',$this->getProcessId()));
		$startState = $process->getStartState(UserUtil::getUser(Precurio_Session::getCurrentUserId()));
		return $startState == $this->getId();		
	}
	
	public function isEndState()
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::WORKFLOW_STATES,'rowClass'=>'ProcessState'));
		$states = $table->fetchAll($table->select()->where('process_id = ?',$this->getProcessId())
															->where('position = ?',($this->getPosition()+1)));
		return $states->count() == 0;
	}
	/** 
	 * Gets the names of visible fields for this state
	 * @return Array
	 */
	public function getFields()
	{
		$fields = array();
		
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::STATE_FIELDS));
		$rows = $table->fetchAll($table->select()->where('state_id = ?',$this->getId()));
		foreach($rows as $row)
		{
			$fields[] = $row['field_name'];
		}
		return $fields;
	}
	
	/**
	 * Indicates if a user can change the approval level for this state.
	 * @return boolean
	 */
	public function userCanChangeApprover()
	{
		return $this->is_approval && $this->allow_approver_change;
	}
	
	/**
	 * Returns the next state after this one. Note that this function also exists in ProcessManager
	 * but the difference is, this one always returns one state. This one is actually only used 
	 * for the 'User can change approver' scenerio
	 * @return ProcessState
	 */
	public function getNextState()
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::WORKFLOW_STATES,'rowClass'=>'ProcessState'));
		$state = $table->fetchRow($table->select()->where('process_id = ?',$this->getProcessId())
															->where('position = ?',($this->getPosition()+1)));
															
		return $state;	
	}
	
}

?>