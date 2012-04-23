<?php

/**
 * RequestController
 * 
 * @author
 * @version 
 */

require_once 'Zend/Controller/Action.php';
require_once 'user/models/UserUtil.php';
require_once ('admin/controllers/BaseController.php');
require_once ('workflow/models/vo/Process.php');
class Admin_RequestController extends Admin_BaseController  {
	function generateHeader() {
		return array('',$this->translate('Name'),$this->translate('Type'),$this->translate('Start Level'));
	}
	
	function generateList($searchText) {
		
		$state_id = $this->getRequest()->getParam('id');
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::WORKFLOW_STATES,'rowClass'=>'ProcessState'));
		$state =  $table->fetchRow($table->select()->where('id= ? ',$state_id));
		
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::WORKFLOW,'rowClass'=>'Process'));
		$process = $table->fetchRow($table->select()->where('id = ?',$state->process_id));
		
		$this->view->process = $process;
		$this->view->state = $state;

		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::WORKFLOW_REQUEST_ACCESS));
		$items =  $table->fetchAll($table->select()->where('process_id= ? ',$state->process_id));
		
		$groups = array();
		$users = array();
		//the two arrays below simply hold the primary keys for the respective request record ids
		//this is later used in deleting.
		$user_item_ids = array();
		$group_item_ids = array();
		//just to get the positions
		$user_item_positions = array();
		$group_item_positions = array();
		foreach($items as $item)
		{
			if(Precurio_Utils::isNull($item['group_id']))
			{
				$users[] = UserUtil::getUser($item['user_id']);
				$user_item_ids[] = $item['id'];
				$user_item_positions[] = $item['start_position'];
			}
			else
			{
				$groups[] = UserUtil::getGroup($item['group_id']);
				$group_item_ids[] = $item['id'];
				$group_item_positions[] = $item['start_position'];
			}	
		}		
		
		
		$arr = array();
		$i = 1;
		
		$count = 0;
		foreach($groups as $item)
		{
			if(!Precurio_Utils::isNull($searchText))
			{
				if(stripos($item->title,$searchText)===FALSE)
				{
					continue;
				}
				
			}
			
			$arr[] = array($i++,'full_name'=>$item->title,'type'=>$item->getType(),'start_position'=>$group_item_positions[$count],'id'=>$group_item_ids[$count]);
			$count++;
		}
		
		$count = 0 ; //reset counter
		foreach($users as $item)
		{
			if(!Precurio_Utils::isNull($searchText))
			{
				if(stripos($item->getFullName(),$searchText)===FALSE)
				{
					continue;
				}
				
			}
			
			$arr[] = array($i++,'full_name'=>$item->getFullName(),'type'=>'User','start_position'=>$user_item_positions[$count],'id'=>$user_item_ids[$count]);
			$count++;
		}
		return $arr;
	
	}
	//NOTICE : $formType here is a boolean variable that determines whether we want a add user form
	//or a add group form. TRUE means User form, FALSE means its a group form
	function getForm($item = null, $formType = false) {
		$form = new Zend_Form();
		$form->setAction($this->getRequest()->getBaseUrl().'/admin/request/submit')
			->setMethod('post')
			->setAttrib('id','form')
			->setAttrib('name','addForm')
			->setAttrib('enctype', 'multipart/form-data');
			
		$userUtil = new UserUtil();
			
				
		$form->addElement('hidden', 'process_id', array(
				'validators' => array(
				),
				'required' => false,
				'value'=>$item['process_id'],
				));
		$form->addElement('hidden', 'state_id', array(
				'validators' => array(
				),
				'required' => false,
				'value'=>$item['state_id'],
				));
				
		$form->addElement('hidden', 'date_created', array(
				'validators' => array(
				),
				'required' => false,
				'value'=>Precurio_Date::now()->getTimestamp(),
				));
				
		if($formType == 0)
		{
			$group_id = new Zend_Form_Element_Select('group_id');
			$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::GROUPS));
			$select = $table->select()->where('active=1')->order('title');
			$groups = $table->fetchAll($select);
			foreach($groups as $group)
			{
				$group_id->addMultiOption($group->id,$group->title);
			}
			$group_id->setLabel($this->translate('Select Group'));
			$group_id->setRequired(true);
			$form->addElement($group_id);
		}
		if($formType == 1)
		{
			$user_id = new Zend_Form_Element_Select('user_id');
			$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::USERS,'rowClass'=>'User'));
			$select = $table->select()->where('active=1')->order('first_name asc');
			$users = $table->fetchAll($select);
			foreach($users as $user)
			{
				$user_id->addMultiOption($user->getId(),$user->getFullName());
			}
			$user_id->setLabel($this->translate('User'));
			$user_id->setRequired(true);
			$form->addElement($user_id);
		}
		
		$start_position = new Zend_Form_Element_Select('start_position');
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::WORKFLOW_STATES));
		$select = $table->select()->where('process_id = ?',$item['process_id'])->order('position asc');
		$states = $table->fetchAll($select);
		foreach($states as $state)
		{
			$start_position->addMultiOption($state->position,$state->display_name);
		}
		$start_position->setLabel($this->translate('Start Level'));
		$start_position->setRequired(true);
		$form->addElement($start_position);
		
		$form->addElement('submit', 'submit', array(
					'class'=>'standout',
					'label'=>$this->translate('Submit'),
					));
		
			
		return $form;
	}
	
	function getPageTitle() {
		return $this->translate("Workflow Request Access");
	}
	
	function getTableName() {
		return PrecurioTableConstants::WORKFLOW_REQUEST_ACCESS;
	}
	public function addAction()
	{
		$state_id = $this->getRequest()->getParam("id");
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::WORKFLOW_STATES,'rowClass'=>'ProcessState'));
		$state =  $table->fetchRow($table->select()->where('id= ? ',$state_id));
		
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::WORKFLOW,'rowClass'=>'Process'));
		$process = $table->fetchRow($table->select()->where('id = ?',$state->process_id));
		
		$this->view->process = $process;
		$this->view->state = $state;
		
		$item = array('process_id'=>$process->id,'state_id'=>$state_id);
		
		$this->view->form = $this->getForm($item,$this->getRequest()->getParam("t"));
		$this->view->pageTitle = " : ".$this->view->translate($this->getPageTitle()."Add new");
	}
	
	public function deleteAction()
	{
		$ids = $this->getRequest()->getParam("ids");
		$table = new Zend_Db_Table(array('name'=>$this->getTableName()));
		$items = $table->find($ids);
		
		$state_id = $items->getRow(0)->state_id;
		foreach($items as $obj)
		{
			$obj->delete();
		}
		
		return $this->_redirect('/admin/request/index/id/'.$state_id);
	}
	public function submitAction()
	{
		$params = $this->getRequest()->getParams();
		if (!$this->getRequest()->isPost()) {
			return $this->_redirect('/admin/request/index/id/'.$params['state_id']);
		}
		$form = $this->getForm($params,isset($params['user_id']));
		if (!$form->isValid($_POST))
		{
			// Failed validation; redisplay form
			$this->view->form = $form;
			return $this->_redirect('/admin/request/index/id/'.$params['state_id']);
		}
		$values = $form->getValues();
		
		$table = new Zend_Db_Table(array('name'=>$this->getTableName()));
		$data = $table->createRow($values);
		$id = $data->save();
		return $this->_redirect('/admin/request/index/id/'.$params['state_id']);
	}
}
?>