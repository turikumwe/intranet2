<?php

/**
 * WorkflowController
 * 
 * @author
 * @version 
 */

require_once ('admin/controllers/BaseController.php');
require_once ('workflow/models/vo/Process.php');
class Admin_WorkflowController extends Admin_BaseController {
	
	function generateHeader() {
		return array('',$this->translate('Name'),$this->translate('Description'),$this->translate('Code'),$this->translate('Department')); 
	}

	function generateList($searchText) {
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::WORKFLOW,'rowClass'=>'Process'));
		$items = $table->fetchAll($table->select()->where('active=1'));
		
		$arr = array();
		$i = 1;
		foreach($items as $item)
		{
			if(!Precurio_Utils::isNull($searchText))
			{
				if(stripos($item->display_name,$searchText)===FALSE)
				{
					if(stripos($item->description,$searchText)===FALSE)
					{
						continue;
					}
				}
			}
			
		$arr[] = array($i++,'display_name'=>$item->display_name,'description'=>$item->description,'code'=>$item->code,'department'=>$item->getDepartmentName(),'id'=>$item->id);
		}
		return $arr;
	}
	
	function getForm($item = null, $viewMode = false) 
	{
		$form = new Zend_Form();
		$form->setAction($this->getRequest()->getBaseUrl().'/admin/workflow/submit')
			->setMethod('post')
			->setAttrib('id','form')
			->setAttrib('name','addForm')
			->setAttrib('enctype', 'multipart/form-data');
			
			
		$form->addElement('hidden', 'id', array(
				'validators' => array(
				),

				'required' => false,
				'value'=>$item['id']
				));

		$form->addElement('hidden', 'date_created', array(
				'validators' => array(
				),
				'required' => false,
				'value'=>$item == null ? Precurio_Date::now()->getTimestamp() : $item['date_created'],
				));
		$form->addElement('hidden', 'active', array(
				'validators' => array(
				),
				'required' => false,
				'value'=>1,
				));
				
				
		$form->addElement('text', 'display_name', array(
				'validators' => array(
				),
				'required' => true,
				'label'=>$this->translate('Name'),
				'value'=>$item['display_name']
				));
		$form->addElement('textarea', 'description', array(
				'validators' => array(
				),
				'required' => false,
				'label'=>$this->translate('Description'),
				'value'=>$item['description'],
				'rows'=>4
				));
				
		$form->addElement('text', 'code', array(
				'validators' => array(
				),
				'required' => true,
				'label'=>$this->translate('Code'),
				'value'=>$item['code']
				));
				
		$department_id = new Zend_Form_Element_Select('department_id');
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::DEPARTMENTS));
		$select = $table->select()->where('active=1')->order('title');
		$departments = $table->fetchAll($select);
		$department_id->addMultiOption(0,$this->translate('No Department'));	
		foreach($departments as $department)
		{
			$department_id->addMultiOption($department->id,$department->title);
		}
		$department_id->setValue($item['department_id']);
		$department_id->setLabel($this->translate('Select Department'));
		$form->addElement($department_id);
		
		if(!$viewMode)
			$form->addElement('submit', 'submit', array(
					'class'=>'standout',
					'label'=>$this->translate('Submit'),
					));
		
			
		return $form;
	}
	
	public function editstateAction()
	{
		$id = $this->getRequest()->getParam('id',0);
		
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::WORKFLOW_STATES));
		$item = $table->find($id)->current();
		
		if($item == null)return $this->_forward('index');
		
		$this->view->form = $this->getStateForm($item);
		$this->view->pageTitle = $this->translate("Workflow State").' : '.$this->translate("Edit").' '.$item->display_name;
		$this->renderScript('form.phtml');
	}
	
	public function getStateForm($item=null)
	{
		$form = new Zend_Form();
		$form->setAction($this->getRequest()->getBaseUrl().'/admin/workflow/submitstate')
			->setMethod('post')
			->setAttrib('id','form')
			->setAttrib('name','addForm');
			
			
				
		$form->addElement('hidden', 'id', array(
				'validators' => array(
				),
				'required' => false,
				'value'=>$item['id'],
				));
				
		$form->addElement('hidden', 'process_id', array(
				'validators' => array(
				),
				'required' => false,
				'value'=>$item['process_id'],
				));
				
		$form->addElement('text', 'display_name', array(
				'validators' => array(
				),
				'required' => true,
				'label'=>$this->translate('Name'),
				'value'=>$item['display_name']
				));
				
				
		$form->addElement('text', 'SLA', array(
				'validators' => array(
				),
				'required' => false,
				'label'=>$this->translate('SLA'),
				'value'=>$item['duration']
				));
		$form->addElement('text', 'sla_email', array(
				'validators' => array(
				),
				'required' => false,
				'label'=>$this->translate('Email to alert on SLA breach'),
				'value'=>$item['sla_email']
				));

		if($item['is_approval'])
		{
			$form->addElement('checkbox', 'allow_approver_change', array(
				'validators' => array(
				),
				'required' => false,
				'label'=>$this->translate('Allow approver change?'),
				'value'=> $item['allow_approver_change'],
				));
		}				
		
				
				
		$form->addElement('submit', 'submit', array(
					'class'=>'standout',
					'label'=>$this->translate('Submit'),
					));
		
			
		return $form;
	}
	public function submitstateAction()
	{
		$params = $this->getRequest()->getParams();
		if (!$this->getRequest()->isPost()) {
			return $this->_forward('index');
		}
		
		$form = $this->getStateForm();
		
		if (!$form->isValid($_POST))
		{
			// Failed validation; redisplay form
			$this->view->pageTitle = $this->translate("Workflow State").' : '.$this->translate("Edit").' '.$params['display_name'];
			$this->view->form = $form;
			return $this->renderScript('form.phtml');
		}
		
		$values = $form->getValues();
		
		$this->preSubmit($values);
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::WORKFLOW_STATES));
		$row = $table->find($values['id'])->current();
		$row->setFromArray($values);
		$row->save();
		
		return $this->_redirect('/admin/workflow/view/id/'.$values['process_id']);
	}
	function getPageTitle() {
		return $this->translate('Workflow');
	}
	
	function getTableName() {
		return PrecurioTableConstants::WORKFLOW;
	}
	
	public function viewAction()
	{
		$id = $this->getRequest()->getParam('id',0);
		
		//we are using sessions to remember the last viewed id, because i can't figure how to make Zend_Navigation pass parameters
		$ns = new Zend_Session_Namespace(Precurio_Session::NAMESPACE_WORKFLOW);
		if($id == 0)
		{
			if(isset($ns->id))
				$id = $ns->id; 
		}
		$ns->id = $id;
		
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::WORKFLOW,'rowClass'=>'Process'));
		$item = $table->fetchRow($table->select()->where('id = ?',$id));
		
		if($item == null)return $this->_forward('index');
		
		$this->view->process = $item;
		$this->view->pageTitle = $this->getPageTitle();
	}
	
	public function publishAction()
	{
		Precurio_Session::getLicense()->validate();
		Precurio_Session::getLicense()->proFeature();
		
		$id = $this->getRequest()->getParam('id');
		$formsBuilder = new Precurio_FormsBuilder($id);
		$process_id = $formsBuilder->publish();
		
		
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::WORKFLOW,'rowClass'=>'Process'));
		$this->view->process = $table->fetchRow($table->select()->where('id = ?',$process_id));
		
		$this->view->pageTitle = $this->getPageTitle().' : '.$this->view->translate('Publish');
		
	}

}
?>