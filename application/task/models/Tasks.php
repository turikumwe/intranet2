<?php
require_once ('task/models/vo/Task.php');
class Tasks {
	const TYPE_ALL = 0;
	const TYPE_TO = 1;
	const TYPE_BY = 2;
	const TYPE_PENDING = 3;
	const TYPE_COMPLETE = 4;
	
	private $tasksToMe;
	private $tasksByMe;
	private $pendingTasks;
	private $completedTasks;
	private $allTasks;
	
	public function getCount($type)
	{
		
			switch ($type)
			{
				case self::TYPE_TO:
					$count = count($this->getTasksByOwner(false));
					break;
				case self::TYPE_BY:
					$count = count($this->getTasksByOwner(true));
					break;
				case self::TYPE_PENDING:
					$count = count($this->getTasksByStatus(false));
					break;
				case self::TYPE_COMPLETE:
					$count = count($this->getTasksByStatus(true));
					break;
				case self::TYPE_ALL:
					$count = count($this->getAllTasks());
					break;
				
			}
			return $count;	
		}
		
		public function getTaskSummary($type, $page = 1)
		{
			switch ($type)
			{
				case self::TYPE_TO:
					$tasks = $this->merge($this->getTasksByOwner(false));
					break;
				case self::TYPE_BY:
					$tasks = $this->merge($this->getTasksByOwner(true));
					break;
				case self::TYPE_PENDING:
					$tasks = $this->merge($this->getTasksByStatus(false));
					break;
				case self::TYPE_COMPLETE:
					$tasks = $this->merge($this->getTasksByStatus(true));
					break;
				case self::TYPE_ALL:
					$tasks = $this->merge($this->getAllTasks());
					break;
				
			}
			
			
			$per_page = 5;
			$start = ($page-1)*$per_page;
//			if(!is_array($tasks))
//				$tasks = $tasks->toArray();
			$tasks = array_splice($tasks,$start,$per_page);
			return $tasks;
			
		}
		private function merge($a,$b=array())
		{
			$temp = array();
			foreach($a as $obj)
			{
				array_push($temp,$obj);
			}
			foreach($b as $obj)
			{
				array_push($temp,$obj);
			}
			return $temp;
		}
	private function sortFn($x, $y)
	{
		 if ( $x->end_time == $y->end_time )
		  return 0;
		 else if ( $x->end_time < $y->end_time )
		  return -1;
		 else
		  return 1;
	}
		
		public function getTaskDetails($id,$type="")
		{
			return $this->getTask($id);	
		}
		
		private function addUserTask($user_id,$task_id,$isTransfer=0,$isProxy=0)
		{
			$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::TASK_USERS));
			$data = array();
			$data['user_id'] = $user_id;
			$data['task_id'] = $task_id;
			$data['is_transfer'] = $isTransfer;
			$data['is_proxy'] = $isProxy;
			$data['date_created'] = Precurio_Date::now()->getTimestamp();
			$row = $table->createRow($data);
			return $row->save();
			
		}
		/**
		 * 
		 * @param $creator_id int user_id of the user requesting content approval
		 * @param $content_id int
		 * @param $type string constant, the type of content.
		 * @return int Id of new task
		 */
		public function addContentTask($creator_id,$content_id,$type,$group_id = 0)
		{
				$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::CONTENT_APPROVAL));
				$select = $table->select();
				
				$field = strtolower($type);
				$select = $select->where("$field = 1")->where('group_id = ?',$group_id);
				
				$rows = $table->fetchAll($select);
				if($rows->count() == 0)//no user to approve
				{
					$obj = new stdClass();
					$obj->creator_id = $creator_id;
					$obj->content_id = $content_id;
					$obj->content_type = $type;
					$log = Zend_Registry::get('log');
					$log->warn('There was no user to assign task : '.serialize($obj));
				}
				//if we have more than one person approving this type of content
				//randomly select someone.
				if($rows->count() > 1)
				{
					$c = $rows->count();
					$i = rand(0,$c-1);
					$rows->seek($i);
				}
				
				$rec = $rows->current();
				//user_id is the ID of the user who will be assigned the task of approving the content.
				//if rec is null, i.e. there was no user to assign task, simply assign approval to the owner of the content/
				$user_id =  $rec== null ? $creator_id : $rec->user_id;
				$start = Precurio_Date::now()->getTimestamp();
				$end = $start + (48 * 60 * 60 );//you've got 48hrs to approve mehn
				
				$translate = Zend_Registry::get('Zend_Translate');
				$description = "<a href='<?php echo $this->baseUrl();?>/cms/view/details/c_id/{$content_id}' class='contentTask'>".$translate->translate('click here to view content')."</a>";
				if($group_id != 0)
					$description = $translate->translate("Your group needs you to approve a content")." <br/>".$description;
				$data = array(
				'type'=>$type,
				'creator_group_id'=>$group_id,
				'creator_user_id'=>$creator_id,
				'user_id'=>$user_id,
				'title'=>$translate->translate(PrecurioStrings::TASKCONTENTAPPROVAL).' : '.$type,
				'duration'=>48,
				'start_time'=>$start,
				'end_time'=>$end,
				'description'=>$description,
				'status'=>Task::STATUS_OPEN,
				'date_created'=>$start,
				'active'=>1
				
				);
				return $this->addTask($data);
				
		}
		
		/**
		 * @param $data Array - task data to insert
		 * @param $returnID Boolean - indicate where you want to return the insert ID, or not (default=false)
		 * @return int|string Returns the new task id, or the operation status if $returnID is false;
		 */
		public function addTask ($data,$returnID = false)
		{
			//TODO determine where the user id is out of office, if yes get proxy
			$tr = Zend_Registry::get('Zend_Translate');
			$user_id = $data['user_id'];
			if(isset($data['id']))unset($data['id']);//you must do this else lastInsertId wont work
			$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::TASK,'rowClass'=>'Task'));
			$msg = "";
			try
			{
				$row = $table->createRow($data);
				$id = $row->save();
				if($this->addUserTask($user_id,$id))
				{
					$url = '/task/index/index/id/'.$id;
					Precurio_Activity::newActivity($data['creator_user_id'],Precurio_Activity::NEW_TASK,$id,$url,$data['user_id'])	;			
					$msg = $tr->translate(PrecurioStrings::ADDSUCESS);
				}
				else
					$msg = $tr->translate(PrecurioStrings::ERROR_PERFORMING_OPERATION);
					
				$dict = new Precurio_Search();
				$dict->indexTask($id);
			}
			catch (Exception $e)
			{
				$msg = $tr->translate(PrecurioStrings::ERROR_PERFORMING_OPERATION);
				$log = Zend_Registry::get('log');
				$log->err($e);
			}
			
						
			return $returnID ? $id : $msg;
			
		}
		public function updateTask($id,$data)
		{
			$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::TASK));
			
			if(isset($data['status']))
			{
				$status = $data['status'];
				
			}
			$tr = Zend_Registry::get('Zend_Translate');
			try
			{
				$task = $table->find($id)->current();
				$this->handleUpdateIssues($task,$data);
				
				if($data['status'] == 3)$data['status']= 1;//reject also means complete. handleUpdateIssues would have handled reject issues
				$task->setFromArray($data);
				$task->save();
				$url = '/task/index/index/id/'.$task->id;
				if($data['status'] == Task::STATUS_COMPLETE)
					Precurio_Activity::newActivity($task->creator_user_id,Precurio_Activity::COMPLETE_TASK,$task->id,$url)	;			
				else
					Precurio_Activity::newActivity($task->creator_user_id,Precurio_Activity::EDIT_TASK,$task->id)	;	
				
				$msg = $tr->translate(PrecurioStrings::EDITSUCESS);
				
				$dict = new Precurio_Search();
				$dict->indexTask($task->id);
			}
			catch (Exception $e)
			{
				$msg = $tr->translate(PrecurioStrings::ERROR_PERFORMING_OPERATION);
				$log = Zend_Registry::get('log');
				$log->err($e);
			}
			return $msg;
		}
		/**
		 * Transfers task to another user
		 * @param $task_id int Id of task to transfer
		 * @param $data Array of transfer parameters
		 * @return String Transfer status
		 */
		public function transferTask($task_id,$data)
		{
			$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::TASK_USERS));
			if(isset($data['id']))unset($data['id']);
			$tr = Zend_Registry::get('Zend_Translate');
			try
			{
				$task = $table->fetchRow($table->select()->where('task_id = ?',$task_id)->where('user_id = ?',$data['from_user_id'])->where('active = 1'));
				$task->active = 0;//deactive task for the user
				$task->save();
				
				$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::TASK_TRANSFERS));
				$newTransfer = $table->createRow($data);
				$newTransfer->save();
				if($this->addUserTask($data['to_user_id'],$task_id,1,0))
				{
					$url = '/task/index/index/id/'.$task_id;
					Precurio_Activity::newActivity($data['from_user_id'],Precurio_Activity::TRANSFER_TASK,$task_id,$url,$data['to_user_id'])	;			
					$msg = $tr->translate(PrecurioStrings::TRANSFERSUCESS);
				}
				else
					$msg = $tr->translate(PrecurioStrings::ERROR_PERFORMING_OPERATION);
				
			}
			catch (Zend_Exception $e)
			{
				$msg = $tr->translate(PrecurioStrings::ERROR_PERFORMING_OPERATION);
				$log = Zend_Registry::get('log');
				$log->err($e);
			}
			return $msg;
		}
		/**
		 * @param $task Task
		 * @param $data Array - Containing new updates to be applied on $task.
		 * @return Boolean indicating whether update issues on task where resolved
		 */
		private function handleUpdateIssues($task,$data)
		{
			if(Precurio_Utils::isNull($task->type))return true;
			//Precurio_Utils::debug($task);
			$user_id = Precurio_Session::getCurrentUserId();
			$baseUrl = Zend_Controller_Front::getInstance()->getRequest()->getBaseUrl();
			switch ($task->type)
			{
				case Task::TYPE_ADVERT:
					if($data['status'] == Task::STATUS_COMPLETE)
					{
						$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::ADVERTS));
						$row = $table->fetchRow($table->select()->where('task_id= ? ',$task->id));
						$url = $baseUrl."/cms/view/details/c_id/".$row->content_id;
						$row->active = 1;
						$row->save();
						Precurio_Activity::newActivity($user_id,Precurio_Activity::CONTENT_APPROVED,$row->content_id,$url,$task->creator_user_id)	;
						Precurio_Activity::newActivity($task->creator_user_id,Precurio_Activity::NEW_CONTENT,$row->content_id,"/cms/view/details/c_id/".$row->content_id);
					}
					elseif($data['status'] == Task::STATUS_REJECT)
					{
						Precurio_Activity::newActivity($user_id,Precurio_Activity::CONTENT_REJECTED,$row->content_id,$url,$task->creator_user_id)	;
					}
					break;
				case Task::TYPE_FEATURED:
					if($data['status'] == Task::STATUS_COMPLETE)
					{
						$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::ARTICLES));
						$row = $table->fetchRow($table->select()->where('task_id= ? ',$task->id));
						$url = $baseUrl."/cms/view/details/c_id/".$row->content_id;
						$row->active = 1;
						$row->save();
						Precurio_Activity::newActivity($user_id,Precurio_Activity::CONTENT_APPROVED,$row->content_id,$url,$task->creator_user_id)	;
						Precurio_Activity::newActivity($task->creator_user_id,Precurio_Activity::NEW_CONTENT,$row->content_id,"/cms/view/details/c_id/".$row->content_id);
					}
					elseif($data['status'] == Task::STATUS_REJECT)
					{
						Precurio_Activity::newActivity($user_id,Precurio_Activity::CONTENT_REJECTED,$row->content_id,$url,$task->creator_user_id)	;
					}
					break;
				case Task::TYPE_NEWS:
					if($data['status'] == Task::STATUS_COMPLETE)
					{
						$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::NEWS));
						$row = $table->fetchRow($table->select()->where('task_id= ? ',$task->id));
						$url = $baseUrl."/cms/view/details/c_id/".$row->content_id;
						$row->active = 1;
						$row->save();
						Precurio_Activity::newActivity($user_id,Precurio_Activity::CONTENT_APPROVED,$row->content_id,$url,$task->creator_user_id)	;
						Precurio_Activity::newActivity($task->creator_user_id,Precurio_Activity::NEW_CONTENT,$row->content_id,"/cms/view/details/c_id/".$row->content_id);
					}
					elseif($data['status'] == Task::STATUS_REJECT)
					{
						Precurio_Activity::newActivity($user_id,Precurio_Activity::CONTENT_REJECTED,$row->content_id,$url,$task->creator_user_id)	;
					}
					break;
				case Task::TYPE_GROUP_CONTENT:
					if($data['status'] == Task::STATUS_COMPLETE)
					{
						$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::GROUP_CONTENTS));
						$row = $table->fetchRow($table->select()->where('task_id= ? ',$task->id));
						$url = $baseUrl."/cms/view/details/c_id/".$row->content_id;
						$row->active = 1;
						$row->save();
						Precurio_Activity::newActivity($user_id,Precurio_Activity::CONTENT_APPROVED,$row->content_id,$url,$task->creator_user_id)	;
						Precurio_Activity::newActivity($task->creator_user_id,Precurio_Activity::NEW_CONTENT,$row->content_id,"/cms/view/details/c_id/".$row->content_id,$task->creator_group_id);
					}
					elseif($data['status'] == Task::STATUS_REJECT)
					{
						Precurio_Activity::newActivity($user_id,Precurio_Activity::CONTENT_REJECTED,$row->content_id,$url,$task->creator_user_id)	;
					}
					break;
				case Task::TYPE_WORKFLOW:
					break;//code below has been marked for deletion. We cannot approve a workflow from task module, because most workflows will need some form of input from the approver.
					$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::USER_PROCESS,'rowClass'=>'UserProcess'));
					$userProcess = $table->fetchRow($table->select()->where('task_id= ? ',$task->id));
					
					$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::WORKFLOW_STATES,'rowClass'=>'ProcessState'));
					$processState = $table->fetchRow($table->select()->where('id= ? ',$userProcess->getStateId()));
					
					$processManager = new ProcessManager($userProcess->getFormId());
					if($data['status'] == Task::STATUS_COMPLETE)
					{
						$processManager->approveProcess($userProcess,$processState);
					}
					elseif($data['status'] == Task::STATUS_REJECT)
					{
						$processManager->rejectProcess($userProcess,$processState);
					}
					break;
					
				default:
					break;
			}
		return true;
		}
		public function deleteTask($id)
		{
			$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::TASK));
			
			$tr = Zend_Registry::get('Zend_Translate');
			try
			{
				$task =  $table->fetchRow($table->select()
								->where('id = ? ',$id));
				$task['active'] = 0; 
				$task->save();
				
				$msg = $tr->translate(PrecurioStrings::DELETESUCCESS);
			}
			catch (Exception $e)
			{
				$msg = $tr->translate(PrecurioStrings::ERROR_PERFORMING_OPERATION);
			}
			
			return ($msg);	
			
		}
		/**
		 * Returns tasks based on ownership
		 * @param $mine Boolean Detemines whether you want your own tasks or not
		 * @return Zend_Db_Table_Row_Abstract
		 */
		public  function getTasksByOwner($mine = false)
		{
			if(!Precurio_Utils::isNull($this->tasksByMe) && $mine)
				return $this->tasksByMe;
			if(!Precurio_Utils::isNull($this->tasksByMe) && (!$mine))
				return $this->tasksToMe;
				
			$user_id = Precurio_Session::getCurrentUserId();
			
			
			$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::TASK, 'rowClass'=>'Task'));
			$select = $table->select();
			$select->setIntegrityCheck(false);
			$select->setTable($table); 
			
			$select = $select->distinct()
							->from(array('a' => PrecurioTableConstants::TASK))
							->join(array('b' => PrecurioTableConstants::TASK_USERS),'a.id = b.task_id',array('user_id','is_proxy','is_transfer','date_assigned'=>'date_created'))
							->join(array('c' => PrecurioTableConstants::USERS),'a.creator_user_id = c.user_id',array('first_name','last_name','profile_picture_id'))
							->where('a.active=1')
							->where('b.active=1')
							->order('id desc');
			if($mine)
			{
				$select = $select->where('a.creator_user_id = ?' ,$user_id);
			}
			else
			{
				$select = $select->where('b.user_id = ?' ,$user_id);
			}
							
			$tasks = $table->fetchAll($select);
			
			if($mine)
				$this->tasksByMe = $tasks;
			else
				$this->tasksToMe = $tasks;
				
			
			return $tasks;
				
			
		}
		public function getTasksByStatus($complete = false)
		{
			if(!Precurio_Utils::isNull($this->completedTasks) && $complete)
				return $this->completedTasks;
			if(!Precurio_Utils::isNull($this->pendingTasks) && (!$complete))
				return $this->pendingTasks;
				
			$user_id = Precurio_Session::getCurrentUserId();
			
			
			$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::TASK, 'rowClass'=>'Task'));
			$select = $table->select();
			$select->setIntegrityCheck(false);
			$select->setTable($table); 
			
			$select = $select->distinct()
							->from(array('a' => PrecurioTableConstants::TASK))
							->join(array('b' => PrecurioTableConstants::TASK_USERS),'a.id = b.task_id',array('user_id','is_proxy','is_transfer','date_assigned'=>'date_created'))
							->join(array('c' => PrecurioTableConstants::USERS),'a.creator_user_id = c.user_id',array('first_name','last_name','profile_picture_id'))
							->where('b.user_id = ?' ,$user_id)
							->where('a.active=1')
							->where('b.active=1')
							->order('id desc');
			if($complete)
			{
				$select = $select->where('a.status = ?' ,Task::STATUS_COMPLETE);
			}
			else
			{
				$select = $select->where('a.status <> ?' ,Task::STATUS_COMPLETE);
			}
							
			$tasks = $table->fetchAll($select);
			
			if($complete)
				$this->completedTasks = $tasks;
			else
				$this->pendingTasks = $tasks;
				
			
			return $tasks;
		}
		public function getAllTasks()
		{
			if(!Precurio_Utils::isNull($this->allTasks) )
				return $this->allTasks;
				
			$user_id = Precurio_Session::getCurrentUserId();
			
			
			$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::TASK, 'rowClass'=>'Task'));
			$select = $table->select(false);
			$select->setIntegrityCheck(false);
			$select->setTable($table); 
			
			$select = $select->distinct()
							->from(array('a' => PrecurioTableConstants::TASK))
							->join(array('b' => PrecurioTableConstants::TASK_USERS),'a.id = b.task_id',array('user_id','is_proxy','is_transfer','date_assigned'=>'date_created'))
							->join(array('c' => PrecurioTableConstants::USERS),'a.creator_user_id = c.user_id',array('first_name','last_name','profile_picture_id'))
							->where('b.user_id = ?' ,$user_id)
							->where('a.active=1')
							->where('b.active=1')
							->orWhere('a.creator_user_id = ?' ,$user_id)
							->where('a.active=1')
							->where('b.active=1');
			$this->allTasks = $table->fetchAll($select);
			return $this->allTasks;
		}
		/**
		 * Returns Task Object with ID = $id
		 * @param $id int
		 * @return Task
		 */
		public static function getTask($id)
		{
			$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::TASK, 'rowClass'=>'Task'));
			$select = $table->select(false);
			$select->setIntegrityCheck(false);
			$select->setTable($table); 
			
			$select = $select->distinct()
							->from(array('a' => PrecurioTableConstants::TASK))
							->join(array('b' => PrecurioTableConstants::TASK_USERS),'a.id = b.task_id',array('user_id','is_proxy','is_transfer','date_assigned'=>'date_created'))
							->join(array('c' => PrecurioTableConstants::USERS),'a.creator_user_id = c.user_id',array('first_name','last_name','profile_picture_id'))
							->where('a.id = ?' ,$id)
							->where('a.active = 1')
							->where('b.active = 1');
			$task = $table->fetchRow($select);
			return $task;
		}
}

?>