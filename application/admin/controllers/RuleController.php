<?php
require_once ('user/models/UserUtil.php');
require_once ('admin/models/Rules.php');
require_once 'Zend/Controller/Action.php';

class Admin_RuleController extends Zend_Controller_Action{
	
	public function indexAction()
	{
		$this->_redirect('/admin/role/index');
	}
	public function editAction()
	{
		$role_id = $this->getRequest()->getParam('r_id',0);
		$resourceId = $this->getRequest()->getParam('r',0);
		$allPrivileges = $this->getRequest()->getParam('a',0);
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::RULES,'rowsetClass'=>'Rules'));
		$rules = $table->fetchAll($table->select()->where('role_id = ?',$role_id)->where('resource = ?',$resourceId));
		if($rules->count() == 0)
		{
			$row = $table->createRow(array(
				'role_id'=>$role_id,
				'resource'=>$resourceId,
				'privilege'=>'null',
				'allow'=>$allPrivileges,
				'created_by'=>Precurio_Session::getCurrentUserId(),
				'date_created'=>Precurio_Date::now()->getTimestamp()
				));
			$row->save();	
			$rules = $table->fetchAll($table->select()->where('role_id = ?',$role_id)->where('resource = ?',$resourceId));
		}
		
		$this->view->form = $this->getForm($rules);
		$this->view->pageTitle = $this->getPageTitle()." : ".$this->translate("Edit");
		$this->renderScript('form.phtml');
	}
	
	public function submitAction()
	{
		$this->_helper->viewRenderer->setNoRender();
		$params = $this->getRequest()->getParams();
		$role_id = $params['role_id'];
		$resourceId = $params['resource'];
		
		$privileges = $this->getAllPrivileges();
		$arr = array();//should hold strings of available privileges
		foreach($privileges as $row)
		{
			$arr[] = $row->privilege;
		}
		$arr = array_flip($arr);
		$privileges = array_intersect_key($params,$arr);
		//$privileges should now hold the results of the privilege settings submitted
		
		
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::RULES,'rowsetClass'=>'Rules'));
		$rules = $table->fetchAll($table->select()->where('role_id = ?',$role_id)->where('resource = ?',$resourceId));
		
		foreach($privileges as $privilege=>$allow)
		{
			$rules->setPrivilege($privilege,$allow);
		}
		//we are done with submit. lets get the group id for the role, so we can redirect
		
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::ROLES));
		$row = $table->fetchRow($table->select()->where('id = ?',$role_id));
		$this->_redirect('/admin/role/view/id/'.$row->group_id);
		
	}
	function getAllPrivileges()
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::PRIVILEGES));
		return $table->fetchAll();
	}
	/**
	 * @param Rules $item 
	 * @param boolean $viewMode
	 * @return Zend_Form
	 */
	function getForm($item, $viewMode = false) 
	{
		$form = new Zend_Form();
		$form->setAction($this->getRequest()->getBaseUrl().'/admin/rule/submit')
			->setMethod('post')
			->setAttrib('id','form')
			->setAttrib('name','addForm')
			->setAttrib('enctype', 'multipart/form-data');
			
		$privileges = $this->getAllPrivileges();
			
		$form->addElement('hidden', 'role_id', array(
				'validators' => array(
				),

				'required' => false,
				'value'=>$item->getRoleId()
				));

		$form->addElement('hidden', 'resource', array(
				'validators' => array(
				),
				'required' => false,
				'value'=>$item->getResourceId()
				));

				
		$form->addElement('text', 'role_name', array(
				'validators' => array(
				),
				'required' => true,
				'label'=>$this->translate('Role'),
				'value'=>$item->getRoleName()
				));
				
		$form->addElement('text', 'resource_name', array(
				'validators' => array(
				),
				'required' => true,
				'label'=>$this->translate('Resource'),
				'value'=>$item->getResourceName()
				));
		foreach($privileges as $privilege)
		{
			$form->addElement('checkbox', $privilege->privilege , array(
				'validators' => array(
				),
				'required' => false,
				'label'=>$privilege->privilege,
				'value'=>$item->givenPrivilege($privilege->privilege) //calling this function already ensures the rule will exist with such privilege.
				));
		}

		
			
		if(!$viewMode)
			$form->addElement('submit', 'submit', array(
					'class'=>'standout',
					'label'=>$this->translate('Submit'),
					));
		
			
		return $form;
	
	}
	
	function getPageTitle() {
	 return $this->translate("Access Control");
	}
	public function preDispatch()
	{
		$this->_helper->layout()->setLayout(PrecurioLayoutConstants::MAIN_ADMIN);
	}
	public function translate($str)
	{
		return $this->view->translate($str);
	}
}

?>