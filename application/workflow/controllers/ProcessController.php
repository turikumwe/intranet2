<?php

/**
 * ProcessController
 * 
 * @author
 * @version 
 */

require_once 'Zend/Controller/Action.php';
require_once 'user/models/UserUtil.php';
require_once 'workflow/models/vo/Process.php';
require_once 'workflow/models/vo/ProcessState.php';
require_once 'workflow/models/vo/UserProcess.php';
require_once 'workflow/models/ProcessForm.php';
require_once 'workflow/models/ProcessManager.php';
class Workflow_ProcessController extends Zend_Controller_Action {
	/**
	 * The default action - show the home page
	 */
	public function indexAction() {
	//	$this->_forward('requests');//default action
	}
	public function viewAction() {
		if($this->getRequest()->isXmlHttpRequest())
		{
			$this->_helper->layout->disableLayout();
		}
		$root = Zend_Registry::get('root');
        $user_process_id = $this->getRequest()->getUserParam('id');
		$userProcess = ProcessManager::getUserProcess($user_process_id);

		if(!$userProcess || (!$userProcess->userCanView(Precurio_Session::getCurrentUserId())))
		{
			throw new Precurio_Exception($this->view->translate(PrecurioStrings::CANNOTACCESSRESOURCE),Precurio_Exception::EXCEPTION_NO_ACCESS);
		}
		
		$process = $this->getProcessObject($userProcess->getProcessId());
		$tableSchema = simplexml_load_file($root.'/application/workflow/schemas/'.$process->getName().'.xml');
		
		$forms = array();
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::USER_PROCESS,'rowClass'=>'UserProcess'));
		$userProcesses = $table->fetchAll($table->select()->where('form_id = ?',$userProcess->getFormId())
										->where('process_id = ?',$userProcess->getProcessId())->order('id asc')
															);					
		foreach ($userProcesses as $userProcess)
		{
			$viewMode = true;
			$processState = $this->getStateObject($userProcess->getStateId());
			
			//we have to make sure a user is not able to view an unapproved process of another user, 
			if($userProcess->getTaskId() <> 0)// if this process is an approval process
			{
				if($userProcess->completed != Task::STATUS_COMPLETE)// and it is not yet complete
				{
					if($userProcess->getUserId() != Precurio_Session::getCurrentUserId())//and it doesn't belong to current user
					{
						continue;//dont show process at all.
					}
					else//and it belongs to current user
					{
						$viewMode = false;// so that the current user has access to approval buttons
					}
				}
			}
			$obj = new ProcessForm($processState,$tableSchema,$userProcess,$userProcess->getUserId() == Precurio_Session::getCurrentUserId(),$viewMode);
			$forms[] = $obj->getForm();
		}
		$this->view->forms = $forms;
		$this->view->header = $obj->getHeader();
	}

	public function newAction() {
		$process_id = $this->getRequest()->getUserParam('id');
		
		$process = $this->getProcessObject($process_id);
		
		if(!$process->userCanRequest(UserUtil::getUser(Precurio_Session::getCurrentUserId())))
		{
			throw new Precurio_Exception($this->view->translate(PrecurioStrings::CANNOTACCESSRESOURCE),Precurio_Exception::EXCEPTION_NO_ACCESS);
		}

		$table = new Zend_Db_Table(array('name'=>$process->getTableName()));

		$tableStruct = $table->info();
		$root = Zend_Registry::get('root');
		if(!file_exists($root.'/application/workflow/schemas/'.$process->getName().'.xml'))
			throw new Zend_Config_Exception();
			
		$tableSchema = simplexml_load_file($root.'/application/workflow/schemas/'.$process->getName().'.xml');
		
		//now get the state id for the process
		//the state id determines what fields will be processed by the processGenerator helper
		$user = UserUtil::getUser(Precurio_Session::getCurrentUserId());
		$state_id = $process->getStartState($user);//access will be check during function execution
		
		$processState = $this->getStateObject($state_id);
		
		//finally, call our process generator view helper
		$obj = new ProcessForm($processState,$tableSchema);
		
		
		$this->view->form = $obj->getForm();
		$this->view->header = $obj->getHeader();
		$this->view->formError = implode('<br/>',$this->_helper->flashMessenger->getMessages());
		$this->render('new');
		
	}
	public function submitAction()
	{
		set_time_limit(0);
		$data = $this->getRequest()->getParams();
		$form = Precurio_Session::getCurrentForm();
		
		if($form->isValid($data) )
		{
			//get form values
			$formValues = $form->getValues();
			$approver_id = 0;
			if(isset($formValues['nextapproverid']))//an approver has been chosen
			{
				$approver_id = $formValues['nextapproverid'];
				unset($formValues['nextapproverid']);
			}
			
			//the next 4 lines try to get the user inputed values from the form values
			//remember, not all form values were directly entered by the user.
			//first we get the state object
			$processState = $this->getStateObject($formValues['state_id']);
			//we then get the fields for that state
			$stateFields = $processState->getFields();
			//we flip it.so that $arr[0]='amount' will become $arr['amount'] = 0
			$stateFields = array_flip($stateFields);
			//we then get any occurances of the form values, in the state fields.
			//that way, we have gotten database specific inputs i.e user input 
			$userInputedValues = array_intersect_key($formValues,$stateFields);

			$db = Zend_Registry::get('db');
			$process = $this->getProcessObject($formValues['process_id']);
			if($formValues['form_id'] == 0)//this is new form i.e start state
			{
				//all workflows have a user_id and date_created
				$userInputedValues['user_id'] = Precurio_Session::getCurrentUserId();
				$userInputedValues['date_created'] = time();
		
				$db->insert($process->getTableName(),$userInputedValues);
			
				$id = $db->lastInsertId();
				
				$manager = new ProcessManager($id,$approver_id);
				$manager->newProcess($processState);
			}
			else
			{
				if(count($userInputedValues))
					$db->update($process->getTableName(),$userInputedValues,'id = '.$formValues['form_id']);
				
				$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::USER_PROCESS,'rowClass'=>'UserProcess'));
				$userProcess = $table->fetchRow($table->select()->where('form_id = ?',$formValues['form_id'])
														->where('user_id = ?',Precurio_Session::getCurrentUserId())
														->where('process_id = ?',$formValues['process_id'])
														->where('state_id = ?',$formValues['state_id']));

				if($userProcess->user_id != Precurio_Session::getCurrentUserId())
				{
					throw new Precurio_Exception($this->view->translate(PrecurioStrings::CANNOTACCESSRESOURCE),Precurio_Exception::EXCEPTION_NO_ACCESS);
				}
				
				$manager = new ProcessManager($formValues['form_id'],$approver_id);
				$manager->approveProcess($userProcess, $processState);
			}
			
			
		}
		else
		{
			$translate = Zend_Registry::get('Zend_Translate');
			$this->_helper->flashMessenger($translate->translate('There was an error submiting your form, please make sure you have properly filled this form'));
			$log = Zend_Registry::get('log');
			$log->err(serialize($form->getErrors()));
			if($form->form_id->value == 0)
			{
				$this->_redirect('/workflow/new/'.$formValues['process_id']);
			}
			
			
		}
		$this->_redirect('/workflow');
	}
	public function submitrejectAction()
	{
		$values = $this->getRequest()->getParams();
		
		$userProcess = ProcessManager::getUserProcess($values['id']);
		if($userProcess == null)
		{
			$this->_redirect('/workflow');
		}
		if($userProcess->getUserId() != Precurio_Session::getCurrentUserId())
		{
			throw new Precurio_Exception($this->view->translate(PrecurioStrings::CANNOTACCESSRESOURCE),Precurio_Exception::EXCEPTION_NO_ACCESS);
		}
		
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::USER_PROCESS_REJECT));
		$table->insert(array(
		'user_id'=>Precurio_Session::getCurrentUserId(),
		'user_process_id'=>$values['id'],
		'comment'=>$values['comment'],
		'date_created'=>Precurio_Date::now()->getTimestamp()
		));
		
		$processManager = new ProcessManager($userProcess->getFormId());
		$processManager->rejectProcess($userProcess);
		
		$this->_redirect('/workflow');
	}
	public function rejectAction()
	{
		$this->_helper->layout()->disableLayout();
		$user_process_id =  $this->getRequest()->getParam('id',0);
		
		if($user_process_id == 0)
			$this->_redirect('/workflow');
		
		$this->view->id = $user_process_id;
		
	}
	
	/**
	 * @param $process_id
	 * @return Process
	 */
	private function getProcessObject($process_id)
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::WORKFLOW,'rowClass'=>'Process'));
		$rowSet = $table->find($process_id);
		if(count($rowSet) == 0)//missing process, i.e process id does not exit
		{
			throw new Precurio_Exception($this->view->translate(PrecurioStrings::MISSINGPROCESS),Precurio_Exception::EXCEPTION_MISSING_PROCESS);
		}
		
		$process = $rowSet[0];
		return $process;
	}
	/**
	 * @param $state_id
	 * @return ProcessState
	 */
	private function getStateObject($state_id)
	{
		
		//now get the state object
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::WORKFLOW_STATES,'rowClass'=>'ProcessState'));
		$rowSet = $table->find($state_id);
		if(count($rowSet) == 0)//state does not exist.
		{
			throw new Precurio_Exception($this->view->translate(PrecurioStrings::INVALIDPROCESSCONFIG),Precurio_Exception::EXCEPTION_INVALID_PROCESS_CONFIG,2000);
		}
		
		$processState = $rowSet[0];
		return $processState;
	}
	public function preDispatch()
	{
		$router = Zend_Controller_Front::getInstance()->getRouter();
        $fake_route = new Zend_Controller_Request_Http();
        $fake_route->setRequestUri('/');
        $router->route($fake_route);
	}
}
?>