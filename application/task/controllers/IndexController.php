<?php

/**
 * IndexController
 * 
 * @author
 * @version 
 */

require_once 'Zend/Controller/Action.php';
require_once('task/models/Tasks.php'); 
require_once('task/models/vo/Task.php');
class Task_IndexController extends Zend_Controller_Action {
	/**
	 * The default action - show the home page
	 */
	public function indexAction() {
		$params = $this->getRequest()->getParams();
		if(isset($params['type']))
			$this->view->type = $params['type'];
		else
			$this->view->type = Tasks::TYPE_ALL;
		if(isset($params['id']))
			$this->view->id = $params['id'];
	}
	public function viewAction()
	{
		$this->_helper->layout->disableLayout();
		$this->view->task_id = $this->getRequest()->getParam('id');
		$this->view->type = $this->getRequest()->getParam('type');
	}
	public function addAction()
	{
		$this->_helper->layout->disableLayout();
		$this->view->form = $this->getForm();
	}
	public function editAction()
	{
		$this->_helper->layout->disableLayout();

		$task_id = $this->getRequest()->getParam('id');
		$this->view->task_id = $task_id;
		$task = Tasks::getTask($task_id);
		$this->view->form = $this->getForm($task);
		$this->render('add');
	}
	public function transferAction()
	{
		$this->_helper->layout->disableLayout();

		$task_id = $this->getRequest()->getParam('id');
		
		$task = Tasks::getTask($task_id);
		
		$this->view->form = $this->getTransferForm($task_id);
	
	}
	public function deleteAction()
	{
		$id = $this->getRequest()->getParam('id');
		$this->_helper->layout->disableLayout();
		$this->_helper->viewRenderer->setNoRender();
		$tasks = new Tasks();
		$msg = $tasks->deleteTask($id);
		$dict = new Precurio_Search();
		$dict->unIndexTask($id);
		echo $msg;
	}
	public function downloadAction()
	{
		$this->_helper->layout->disableLayout();
		$this->view->task_id = $this->getRequest()->getParam('id');
		$this->view->type = $this->getRequest()->getParam('type');
		$this->getResponse()->setHeader('Content-Description','File Transfer');
		$this->getResponse()->setHeader('Content-Disposition',"attachment; filename=Mytask.doc");
		$this->getResponse()->setHeader('Content-Type','application/msowrd');
		$this->getResponse()->setHeader('Content-Transfer-Encoding','binary');
		$this->render('view');
	}
	public function submittransferAction()
	{
		$this->_helper->layout->disableLayout();
		$this->_helper->viewRenderer->setNoRender();
		$params = $this->getRequest()->getParams();
		if (!$this->getRequest()->isPost()) {
			return $this->_forward('index');
		}
		$form = $this->getTransferForm();
		if (!$form->isValid($_POST))
		{
			echo $this->view->translate(PrecurioStrings::ERROR_PERFORMING_OPERATION);
			return;
		}
		$timestamp = Precurio_Date::now()->getTimestamp();
		$values = $form->getValues();
		$values['date_created'] = $timestamp;
		$values['from_user_id'] = Precurio_Session::getCurrentUserId();
		$values['to_user_id'] = $values['to_user_id'][0];
		$task_id = $values['task_id'];
		
		$tasks = new Tasks();
		$result = $tasks->transferTask($task_id,$values);
		echo $result;
	}
	public function submitAction()
	{
		$this->_helper->layout->disableLayout();
		$this->_helper->viewRenderer->setNoRender();
		
		$params = $this->getRequest()->getParams();
		if (!$this->getRequest()->isPost()) {
			return $this->_forward('index');
		}
		$form = $this->getForm();
		if (!$form->isValid($_POST))
		{
			echo $this->view->translate(PrecurioStrings::ERROR_PERFORMING_OPERATION);
			return;
		}
		$timestamp = Precurio_Date::now()->getTimestamp();
		$values = $form->getValues();
		$values['creator_user_id'] = Precurio_Session::getCurrentUserId();
		$values['date_created'] = $timestamp;
		$values['start_time'] = $timestamp;
		
		$date = new Precurio_Date();
		$date->setMonth($values['end_month']);
		$date->setDay($values['end_day']);
		$date->setYear($values['end_year']);
		$date->setHour($values['end_hour']);
		$date->setMinute($values['end_minute']);
		unset($values['end_month']);
		unset($values['end_day']);
		unset($values['end_year']);
		unset($values['end_hour']);
		unset($values['end_minute']);
		$values['end_time'] = $date->getTimestamp();
		$values['user_id'] = $values['user_id'][0];
		$tasks = new Tasks();
		if(Precurio_Utils::isNull($values['id']))
		{
			$result = $tasks->addTask($values);
		}
		else
		{
			$result = $tasks->updateTask($values['id'],$values); 
		}
		
		
		echo $result;
		
	}
	public function statusAction()
	{
		$id = $this->getRequest()->getParam('id');
		$status = $this->getRequest()->getParam('s');
		$this->_helper->layout->disableLayout();
		$this->_helper->viewRenderer->setNoRender();
		$tasks = new Tasks();
		$msg = $tasks->updateTask($id,array('status'=>$status));
		echo $msg;
	}
	
	public function remindAction()
	{
		$this->_helper->layout->disableLayout();
		$this->_helper->viewRenderer->setNoRender();
		try
		{
			$user_id = Precurio_Session::getCurrentUserId();
		}
		catch (Exception $e)
		{
			$str = Zend_Json::encode(array());
			echo $str;
			return;
		}
		
		$remind = array();
		$now = Precurio_Date::now()->getTimestamp();
		$min45 = 45 * 60;
		$min15 = 15 * 60;
		
		$tasks = new Tasks();
		$pendingTasks = $tasks->getTasksByStatus(false);
		foreach ($pendingTasks as $task)
		{
			$date = new Precurio_Date($task->end_time);
			//if it is not today and later, please skip
			if(!($date->isToday() && $date->isLater(Precurio_Date::now())))
				continue;
			
			$obj = array();
			$obj['title'] = $task->title;
			$obj['id'] = $task->id;
			$obj['when'] = $date->get(Precurio_Date::TIMES); 
			
			$diff = $date->getTimestamp() - $now;//number of seconds till task.
			
			if($diff > 3600)//if it is still more than an hr ahead, ignore.
			{
				continue;
			}
			else if($diff > $min45)
			{
				$obj['time'] = $diff - $min45;//number of seconds till its 45minutes to task
				$obj['type'] = '45';
			}
			else if($diff > $min15)
			{
				$obj['time'] = $diff - $min15;//number of seconds till its 15minutes to task
				$obj['type'] = '15';
			}
			else
			{
				$obj['time'] = $diff;//number of seconds to task
				$obj['type'] = '0';
			}
			
			$remind[] = $obj;
			
		}
		$str = Zend_Json::encode($remind);
		echo $str;
		return;
	}
	
	/**
	 * @param $contact Contact
	 * @return Zend_Form
	 */
	private function getForm($task = null)
	{
		
		$endDate = $task == null ? Zend_Date::now() : new Zend_Date($task->end_time);
		$form = new Zend_Form();
		$form->setAction($this->getRequest()->getBaseUrl().'/task/index/submit')
			->setMethod('post');

			
		$form->addElement('hidden', 'id', array(
				'validators' => array(
				),
				'required' => false,
				'value'=>$task['id'],
				));
		$form->addElement('text', 'title', array(
		'validators' => array(
				),
				'required' => true,		
				'value'=>$task['title'],
				));
				
		$form->addElement('select', 'end_month', array(
				'required' => true,
				'class' => 'oneThirdSelect',
				'multiOptions'=> Precurio_FormElement::getOptionsArray(Precurio_Date::getAllMonths(),'value','label'),
		        'value'=>$endDate->get(Precurio_Date::MONTH_SHORT)
				));
		$form->addElement('select', 'end_day', array(
				'required' => true,

				'class' => 'oneThirdSelect',
				'multiOptions'=> Precurio_FormElement::getOptionsArray(Precurio_Date::getAllDays(),'value','label'),
				'value'=>$endDate->get(Precurio_Date::DAY)
				));
		
		$form->addElement('select', 'end_year', array(
				'required' => true,
				'class' => 'oneThirdSelect',
				'multiOptions'=> Precurio_FormElement::getOptionsArray(Precurio_Date::getAllYears(),'value','label'),
				'value'=>$endDate->get(Precurio_Date::YEAR)		
				));
				
		$form->addElement('select', 'end_hour', array(
				'required' => true,
				'class' => 'oneThirdSelect',
				'multiOptions'=> Precurio_FormElement::getOptionsArray(Precurio_Date::getAllHours(),'value','label'),
				'value'=>$endDate->get(Precurio_Date::HOUR)
				));	
				
				
		$form->addElement('select', 'end_minute', array(
				'required' => true,
				'class' => 'oneThirdSelect',
				'multiOptions'=> Precurio_FormElement::getOptionsArray(Precurio_Date::getAllMinutes(),'value','label'),
				'value'=>$endDate->get(Precurio_Date::MINUTE)
				));

		$selectUser = new Zend_Form_Element_Multiselect('user_id');
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::USERS,'rowClass'=>'User'));
		$select = $table->select()->where('active=1')->order('first_name asc');
		$users = $table->fetchAll($select);
		
		$user_id = $task == null ? Precurio_Session::getCurrentUserId(): $task['user_id'];
		foreach($users as $user)
		{
			$selectUser->addMultiOption($user->getId(),$user->getFullName());
		}
		$selectUser->setAttrib('size',10);
		$selectUser->setValue($user_id);
		$selectUser->multiple = '';
		$form->addElement($selectUser);		
				
				
		$form->addElement('textarea', 'description', array(
				'validators' => array(
				),
				'rows'=>3,
				'required' => false,
				'value'=>$task['description'],
				));	
				
				
		$status = new Zend_Form_Element_Select('status');
		$status->addMultiOption(Task::STATUS_OPEN,'Open');
		$status->addMultiOption(Task::STATUS_ONHOLD,'On Hold');
		$status->addMultiOption(Task::STATUS_COMPLETE,'Closed'); 
		$form->addElement($status);
		
		$form->setElementDecorators(array('ViewHelper','FormElements','Errors'));
		return $form;
		
	}
	private function getTransferForm($task_id = 0)
	{
		$form = new Zend_Form();
		$form->setAction($this->getRequest()->getBaseUrl().'/task/index/submittransfer')
			->setMethod('post');

		$form->addElement('hidden', 'task_id', array(
				'validators' => array(
				),
				'required' => false,
				'value'=>$task_id,
				));
				
		$form->addElement('textarea', 'reason', array(
				'validators' => array(
				),
				'rows'=>3,
				'required' => true,
				));	
			
		$selectUser = new Zend_Form_Element_Multiselect('to_user_id');
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::USERS,'rowClass'=>'User'));
		$select = $table->select()->where('active=1')->order('first_name asc');
		$users = $table->fetchAll($select);
		
		$user_id = Precurio_Session::getCurrentUserId();//since only the user assigned the task can transfer
		foreach($users as $user)
		{
			$selectUser->addMultiOption($user->getId(),$user->getFullName());
		}
		$selectUser->setAttrib('size',10);
		$selectUser->setValue($user_id);
		$selectUser->multiple = '';
		$form->addElement($selectUser);	
		
		$form->setElementDecorators(array('ViewHelper','FormElements','Errors'));
		return $form;
	}
	private function getUser($id)
	{
		if(Precurio_Utils::isNull($id))
			$id = Precurio_Session::getCurrentUserId();
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::USERS,'rowClass'=>'User'));
		$select = $table->select()->where('user_id=?',$id);
		$user= $table->fetchRow($select);
		return $user->getFullName();
	//	if(Precurio_Utils::isNull($id))
	//		return 'Myself';
	}
	private function getUserId($full_name)
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::USERS,'rowClass'=>'User'));
		$select = $table->select()->where("concat(first_name,' ',last_name) = ?",$full_name);
		$user= $table->fetchRow($select);
		return $user->getId();
	}
}
?>