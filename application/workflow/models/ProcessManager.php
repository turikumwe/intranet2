<?php

require_once 'task/models/vo/Task.php';
require_once 'workflow/models/vo/ProcessState.php';
require_once 'workflow/models/vo/UserProcess.php';
require_once 'user/models/vo/User.php';
require_once 'user/models/UserUtil.php';
require_once 'task/models/Tasks.php';
class ProcessManager 
{
	/**
	 * @var ProcessState
	 */
	private $currentState;
	private $form_id;
	
	private $approver_id; //will be set in the 'user can change approver' scenerio
	
	/**
	 * @param $form_id int - will be 0 for a new form
	 * @param $approver_id int - The user that will approve the next process. 
	 * Only set in the  'user can change approver' scenerio 
	 * @return unknown_type
	 */
	public function __construct($form_id,$approver_id = 0)
	{
		$this->form_id = $form_id;
		$this->approver_id = $approver_id;
	}
	public function newProcess($startState)
	{
		$this->currentState = $startState;
		$userProcess = $this->createApprovalProcess($startState,0);
		$this->createNextStep($userProcess);
	}
	/**
	 * @param $userProcess UserProcess
	 * @return void
	 */
	public function rejectProcess($userProcess)
	{
		//first mark task as complete
		$this->completeProcessTask($userProcess);
		//then mark user process as complete
		$userProcess->completed = Task::STATUS_COMPLETE;
		$userProcess->rejected = 1;
		$userProcess->save();
		
		$this->processComplete($userProcess);
		
		Precurio_Activity::newActivity($userProcess->getOwner()->getId(),Precurio_Activity::WORKFLOW_REJECTED,$userProcess->getId(),'/workflow/view/'.$userProcess->getId(),$userProcess->getUserId())	;
	}
	/**
	 * @param $userProcess UserProcess
	 * @param $currentState ProcessState
	 * @return void
	 */
	public function approveProcess($userProcess,$currentState)
	{
		$this->currentState = $currentState;
	
		
		//first mark task as complete
		$this->completeProcessTask($userProcess);
		//then mark user process as complete
		$userProcess->completed = Task::STATUS_COMPLETE;
		$userProcess->save();
		
		Precurio_Activity::newActivity($userProcess->getOwner()->getId(),Precurio_Activity::WORKFLOW_APPROVED,$userProcess->getId(),'/workflow/view/'.$userProcess->getId(),$userProcess->getUserId())	;
		
		//then create next step. but before then, make sure no other approval is pending.
		//NOTE: Since our workflows are designed to operate serially/synchronously, another pending approval
		//existing , means the current process has a twin parallel process which must be approved, before going to the next state.
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::USER_PROCESS,'rowClass'=>'UserProcess'));
		$row = $table->fetchRow($table->select()->where('form_id = ?',$userProcess->getFormId())->where('task_id <> 0')->where('completed <> ?',Task::STATUS_COMPLETE));
		
		if($row == null)$this->createNextStep($userProcess);
		
	}
	/**
	 * This function simply marks a process task has completed
	 * @param $userProcess UserProcess
	 * @return void
	 */
	private function completeProcessTask($userProcess)
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::TASK));
		$task = $table->find($userProcess->getTaskId())->current();
		$task->status = Task::STATUS_COMPLETE;	
		$task->save();
	}
	/**
	 * This will handle every thing involved with the next step, after the current state.
	 * @param $userProcess UserProcess
	 * @return void
	 */
	private function createNextStep($userProcess)
	{
		//get next state(s)
		$states = $this->getNextStates();
		
		if($states->count() == 0)//there is no next state. process is complete
		{
			$this->processComplete($userProcess);
		}
		
		foreach($states as $state)
		{
			$task_id = $this->createApprovalTask($state,$userProcess);
			$this->createApprovalProcess($state,$task_id);
		}
		
	}
	/**
	 * Fetch the state(s) after the current state. Based on position property of the state.
	 * @return Zend_Db_Table_Rowset
	 */
	private function getNextStates()
	{
		$currentState = $this->currentState;
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::WORKFLOW_STATES,'rowClass'=>'ProcessState'));
		$states = $table->fetchAll($table->select()->where('process_id = ?',$currentState->getProcessId())
															->where('position = ?',($currentState->getPosition()+1)));
															
		return $states;													
	}
	/**
	 * Create a task for the user approving the next state.
	 * @param $nextState ProcessState
	 * @param $userProcess UserProcess
	 * @return void
	 */
	private function createApprovalTask($nextState,$userProcess)
	{
		$currentState = $this->currentState;

		$start = Precurio_Date::now()->getTimestamp();
		$end = $start + ($nextState->getDuration() * 60 * 60 );
		
		$tr = Zend_Registry::get('Zend_Translate');
		$data = array(
				'type'=>Task::TYPE_WORKFLOW,
				'creator_group_id'=>0,
				'creator_user_id'=>Precurio_Session::getCurrentUserId(),
				'user_id'=>$this->getGoodApproverId($nextState),
				'title'=>$tr->translate(PrecurioStrings::WORKFLOWAPPROVAL).' - '.$userProcess->getFormCode().' : '.$userProcess->getDisplayName().' ('.$userProcess->getOwner()->getFullName().')',
				'duration'=>$nextState->getDuration(),
				'start_time'=>$start,
				'end_time'=>$end,
				'description'=>'',
				'status'=>Task::STATUS_OPEN,
				'is_transferable'=>0,
				'date_created'=>$start,
				'active'=>1
				);
		$tasks = new Tasks();
		$task_id = $tasks->addTask($data,true);
		return $task_id;
	}
	/**
	 * Creates a user approval process for the next state
	 * @param $nextState ProcessState
	 * @param $task_id int
	 * @return UserProcess
	 */
	private function createApprovalProcess($nextState,$task_id)
	{
		//first get user that was assigned the task. this is the same as the user approving
		//the process for $nextState.
		//if there is no task, then it is a new request, not an approval process
		if($task_id == 0)
		{
			$user_id = Precurio_Session::getCurrentUserId();
		}
		else
		{
			// note that, it is one approval process to a task. if their should be 2 parallel approval
			//process, then it is 2 different task. 
			$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::TASK_USERS));
			$row = $table->fetchRow($table->select()->where('task_id = ?',$task_id));
			if($row == null)
			{
				$tr = Zend_Registry::get('Zend_Translate');
				throw new Precurio_Exception($tr->translate(PrecurioStrings::APPLICATION_LOGIC_ERROR),Precurio_Exception::EXCEPTION_APPLICATION_ERROR,1003);
			}
			$user_id = $row->user_id;
		}
		
		
		$data = array(
			'user_id'=>$user_id,
			'process_id'=>$nextState->getProcessId(),
			'state_id'=>$nextState->getId(),
			'form_id'=>$this->form_id,
			'task_id'=>$task_id,
			'date_created'=>Precurio_Date::now()->getTimestamp(),
			'completed'=>($task_id == 0 ? Task::STATUS_ONHOLD : Task::STATUS_OPEN),//yea, only a request process gets it completed status "on hold"
			'active'=>1
		);
		
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::USER_PROCESS));
		$row = $table->createRow($data);
		$user_process_id = $row->save();
		
		//now we need to update the task description, since we now have a user_process_id
		if($task_id != 0)
		{
			$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::TASK));
			$task = $table->find($task_id)->current();
			
			$baseUrl  =  Zend_Controller_Front::getInstance()->getRequest()->getBaseUrl();
			$description = "<a href='$baseUrl/workflow/view/{$user_process_id}' class='contentTask'> click here to view form </a>";
			$task->description = $description;
			$task->save();
		}
		
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::USER_PROCESS,'rowClass'=>'UserProcess'));
		return $table->find($user_process_id)->current();
		
	}
	/**
	 * Return the user_id of the User approving for state $state.
	 * Serves as a "good" validator for getApproverId()
	 * This function should have been private, but we need it in ProcessForm
	 * for the 'user can change approver' scenerio
	 * @param $state ProcessState
	 * @return int 
	 */
	public function getGoodApproverId($state)
	{
		if(!Precurio_Utils::isNull($this->approver_id))//an approver has been selected by the user
		{
			if($this->getNextStates()->count() == 1)//the 'user can change approver' scenerio is only valid for a serial workflow
			{
				return $this->approver_id;
			}
			
		}
		try
		{
			do
			{
				$user_id = $this->getApproverId($state);
				$user = UserUtil::getUser($user_id);
			}while(!$user->isActive());
		}
		catch(Exception $e)
		{
			if($this->currentState->isStartState())
			{
				$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::USER_PROCESS,'rowClass'=>'UserProcess'));
				$newProcess = $table->fetchRow($table->select()->where('user_id = ?',Precurio_Session::getCurrentUserId())
															->where('completed = ?',Task::STATUS_ONHOLD)
															->where('form_id = ?',$this->form_id)
															->where('process_id = ?',$this->currentState->getProcessId()));
				if($newProcess)$newProcess->delete();											
			}
			if($this->form_id == 0)//this is means, the user is just initiating the process, and the next approval state can be chosen. simply let the current user approver himself instead of throwing error
			{
				return Precurio_Session::getCurrentUserId();
			}
			$log = Zend_Registry::get('log');
			$log->err('No good approver found for form '.$this->form_id.' belonging to process '.$this->currentState->getProcessId());
			throw $e;
		}
		
		
		
		$outObj = $user->getOutOfOffice();
		if($outObj != null)
		{
			$user = $outObj->getProxy();
		}
		return $user->getId();
	}
	/**
	 * Return the user_id of the User approving for state $state, this function should never 
	 * be called directly, except throug getGoodApproverId();
	 * @param $state ProcessState
	 * @return int
	 */
	private function getApproverId($state)
	{
		$tr = Zend_Registry::get('Zend_Translate');
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::WORKFLOW_APPROVAL_ACCESS));
		$accessList = $table->fetchAll($table->select()->where('process_id = ?',$state->getProcessId())
															->where('state_id = ?',($state->getId())));
														
		if($accessList->count() == 0)
		{
			throw new Precurio_Exception($tr->translate(PrecurioStrings::INVALIDPROCESSCONFIG),Precurio_Exception::EXCEPTION_INVALID_PROCESS_CONFIG,2001);
		}
		
		$userList = $this->getUsersFromAccessList($accessList);
		
		$currentUser = UserUtil::getUser(Precurio_Session::getCurrentUserId());
		if($state->isDepartmental())
		{
			$userList = $this->filterUsersByDepartment($userList,$currentUser->getDepartmentId());
		}
		
		if($state->isLocational())
		{
			$userList = $this->filterUsersByLocation($userList,$currentUser->getLocationId());
		}
		
		if(count($userList) == 0)
		{
			throw new Precurio_Exception($tr->translate(PrecurioStrings::INVALIDPROCESSCONFIG),Precurio_Exception::EXCEPTION_INVALID_PROCESS_CONFIG,2003);
		}
		
		$pos = rand(0,count($userList)-1);//randomly select someone from the list.
		
		return $userList[$pos]->getId();;
	}
	/**
	 * @param $users Array of User objects to filter
	 * @param $dept_id int the department Id, not the group_id of the department
	 * @return Array of user objects that passes the filter
	 */
	private function filterUsersByDepartment($users,$dept_id)
	{
		$filter = array();
		foreach($users as $user)
		{
			if($user->getDepartmentId() == $dept_id)
				$filter[] = $user;
		}
		return $filter;
	}
	/**
	 * @param $users Array of User objects to filter
	 * @param $location_id int the location Id, not the group_id of the location
	 * @return Array of user objects that passes the filter
	 */
	private function filterUsersByLocation($users,$location_id)
	{
		$filter = array();
		foreach($users as $user)
		{
			if($user->getLocationId() == $location_id)
				$filter[] = $user;
		}
		return $filter;
	}
	/**
	 * @param $accessList Zend_Db_Table_Rowset , containing row items from workflow_approval_access
	 * @return Array of User Objects
	 */
	private function getUsersFromAccessList($accessList)
	{
		$userList = array();
		foreach($accessList as $access)
		{
			if($access->user_id == 0)
			{
				$users = Group::getUsers($access->group_id);
				foreach($users as $user)
				{
					$userList[] = $user;
				}
			}
			else
			{
				$userList[] = UserUtil::getUser($access->user_id);
			}
		}
		return $userList;
	}
	/**
	 * This function only gets called, if there is no more state after currentState
	 * i.e. process is complete.
	 * @param $userProcess UserProcess
	 * @param $approved Boolean Determines if the process complete was an approval or a rejection
	 * @return void
	 */
	private function processComplete($userProcess,$approved = true)
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::USER_PROCESS,'rowClass'=>'UserProcess'));
		$process = $table->fetchRow($table->select()->where('task_id = 0')
															->where('form_id = ?',$userProcess->getFormId())
															->where('process_id = ?',$userProcess->getProcessId()));
		$process->completed = Task::STATUS_COMPLETE;
		if(!$approved)$process->rejected = 1;
		$process->save();
		
		//TODO notify user process is complete.
	}
	
	/**
	 * A setter function for current state.
	 * @param $currentState ProcessState
	 * @return void
	 */
	public function setCurrentState($currentState)
	{
		$this->currentState = $currentState;
	}
	/**
	 * Returns all pending requests and approval associated with the current user
	 * To get only request,run task_id == 0 test on each object, and otherwise for approvals
	 * Note that this function returns a rowset of UserProcess Objects.
	 * @return Zend_Db_Table_Rowset
	 */
	public static function getMyProcesses()
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::USER_PROCESS,'rowClass'=>'UserProcess'));
		$processes = $table->fetchAll($table->select()->where('user_id = ?',Precurio_Session::getCurrentUserId())
															->order('id desc'));
		return $processes;
		
	}
	/**
	 * Returns all the workflows the current user can initiate, sorted by department
	 * @return Array of Process objects
	 */
	public static function getAllWorkflows()
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::WORKFLOW,'rowClass'=>'Process'));
		$rowSet = $table->fetchAll($table->select()->where('active=1')->order('department_id'));
		
		//now determine the ones i have access to
		$currentUser = UserUtil::getUser(Precurio_Session::getCurrentUserId());
		$temp = array();
		foreach($rowSet as $row)
		{
			if($row->userCanRequest($currentUser))
			{
				$temp[] = $row;
			}
			
		}
		return $temp;
	}
	/**
	 * @param $id int
	 * @return UserProcess
	 */
	public static function getUserProcess($id)
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::USER_PROCESS,'rowClass'=>'UserProcess'));
		$row = $table->fetchRow($table->select()->where('id = ?',$id));
		return $row;
	}
}

?>