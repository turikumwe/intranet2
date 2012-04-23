<?php
require_once ('user/models/UserUtil.php');
require_once ('admin/controllers/BaseController.php');
class Admin_UserController extends Admin_BaseController {
	
	function generateHeader() {
		return array(" ",$this->translate("First Name"),$this->translate("Last Name"),$this->translate("Job Title"),$this->translate("Department"),$this->translate("Location"));
	}
	
	public function generateList($searchText)
	{
		$searchText = strtolower($searchText);
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::USERS,'rowClass'=>'User'));
		$users = $table->fetchAll();
		
		$arr = array();
		$i = 1;
		foreach($users as $user)
		{
			if(!Precurio_Utils::isNull($searchText))
			{
				if(stripos($user->getFullName(),$searchText)===FALSE)
				{
					if(strtolower($user->getLocation()) != $searchText)
					{
						if(strtolower($user->getDepartment()) != $searchText)
						{
							if(strtolower($user->getJobTitle()) != $searchText)
							{
								continue;
							}
						}
					}
				}
				
			}
			
			$arr[] = array($i++,'first_name'=>$user->first_name,'last_name'=>$user->last_name,'job_title'=>$user->job_title,'department'=>$user->getDepartment(),'location'=>$user->getLocation(),'id'=>$user->getId());
		}
		return $arr;
	}
	
	function getForm($user = null, $viewMode = false) 
	{
		
	}
	
	function getPageTitle() {
		return $this->translate("User Management");
	}
	
	function getTableName() {
		return PrecurioTableConstants::USERS;
	}
	public function addAction()
	{
		$this->view->form = $this->getAddForm();
		$this->view->pageTitle = $this->getPageTitle()." : ".$this->translate("Add new");
		$this->renderScript('form.phtml');
	}
	public function viewAction()
	{
		$id = $this->getRequest()->getParam('id',0);
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::USERS,'rowClass'=>'User'));
		$item = $table->fetchRow($table->select()->where('user_id = ? ',$id));
		
		if($item == null)return $this->_forward('index');
		
		$this->view->user = $item;
		$this->view->pageTitle = $this->getPageTitle();
		$this->view->cpage = $this->getRequest()->getParam('cpage',1);
		$this->view->searchText = $this->getRequest()->getParam('search','');
		
		
	}
	public function resetAction()
	{
		$this->_helper->layout->disableLayout();
		$this->view->user_id = $this->getRequest()->getParam('u_id',-1);//we use -1 so it doesnt defualt to reseting the current user's password
	}
	public function editAction()
	{
		$id = $this->getRequest()->getParam('id',0);
		
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::USERS,'rowClass'=>'User'));
		$item = $table->fetchRow($table->select()->where('user_id = ? ',$id));
		
		if($item == null)return $this->_forward('index');
		
		$this->view->edtop = 1;
		$this->view->user = $item;
		$this->view->pageTitle = $this->view->translate($this->getPageTitle());
		$this->view->cpage = $this->getRequest()->getParam('cpage',1);
		$this->view->searchText = $this->getRequest()->getParam('search','');
		$this->render('view');
	}
	
	public function deleteAction()
	{
		$ids = $this->getRequest()->getParam("ids");
		$table = new Zend_Db_Table(array('name'=>$this->getTableName()));
		$items = $table->find($ids);
		
		foreach($items as $obj)
		{
			$obj->active = 0;
			$obj->save();
		}
		
		return $this->_redirect('/admin/user/index/');
	}
	public function submitresetAction()
	{
		$this->_helper->viewRenderer->setNoRender();
		$this->_helper->layout->disableLayout();
		$params = $this->getRequest()->getParams();
   		$user = UserUtil::getUser($params['user_id']);
    	if($params['np'] !== $params['np2'])
    	{
    		echo($this->view->translate('Passwords do not match'));
    		return;
    	}
    	if( strlen($params['np']) < 6)
    	{
    		echo($this->view->translate('Password must be atleast 6 characters'));
    		return;
    	}
    	$user->updatePassword($params['np']);	
    	echo('You have successfuly reset the password');
	}
	public function submitAction()
	{
		$params = $this->getRequest()->getParams();
		if (!$this->getRequest()->isPost()) {
			return $this->_forward('index');
		}

		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::USERS,'rowClass'=>'User'));
		$user = $table->fetchRow($table->select()->where('user_id = ? ',$params['id']));
		$user->active = empty($params['active']) ? 0 : 1;
		$user->update($params);
		
		$this->_redirect('/admin/user/edit/id/'.$params['id']);
	}
	public function submitnewAction()
	{
		$params = $this->getRequest()->getParams();
		if (!$this->getRequest()->isPost()) {
			return $this->_forward('index');
		}
	
		$form = $this->getAddForm();
		if (!$form->isValid($_POST))
		{
			// Failed validation; redisplay form
			$this->view->form = $form;
			return $this->renderScript('form.phtml');
		}
		
		$values = $form->getValues();
		
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::AUTH));
		$row = $table->createRow(array(
			'identity'=>$values['email'],
			'credential'=>md5($values['password']),
			'date_created'=>$values['date_created']
		));
		$user_id = $row->save();
		
		$config = Zend_Registry::get('config');
		
		$data = array(
			'user_id'=>$user_id,
			'first_name'=>$values['first_name'], 
			'last_name'=>$values['last_name'],
			'email'=>$values['email'],
			'username'=>(stripos($values['email'],'@') === false ? $values['email'] : substr($values['email'],0,strpos($values['email'],'@'))),
			'password'=>md5($values['password']),
			'location_id'=>$values['location_id'],
			'department_id'=>$values['department_id'],
			'company'=>isset($config->company_name) ? $config->company_name : '',
			'date_created'=>$values['date_created'],
			'active'=>1,
		    'out_of_office'=>0
		);
		$user = UserUtil::createUser($data);
		
		$loginDetails = "<br/>
		Login : ".$values['email']." <br/>
		Password : ".$values['password']." <br/>";
		Precurio_Activity::newActivity($user_id,Precurio_Activity::NEW_USER,$user_id,$loginDetails,$user_id)	;			
		
		return $this->_redirect('/admin/user/edit/id/'.$user_id);
		
	}
	function getAddForm()
	{
		$userUtil = new UserUtil();
		
		$form = new Zend_Form();
		$form->setAction($this->getRequest()->getBaseUrl().'/admin/user/submitnew')
			->setMethod('post')
			->setAttrib('id','form')
			->setAttrib('name','addForm')
			->setAttrib('enctype', 'multipart/form-data');
			
		
			
		$form->addElement('hidden', 'user_id', array(
				'validators' => array(
				),
				'decorators'=>array(
				'ViewHelper',
				'FormElements'
				),
				'required' => false,
				));

		$form->addElement('hidden', 'date_created', array(
				'validators' => array(
				),
				'decorators'=>array(
				'ViewHelper',
				'FormElements'
				),
				'required' => false,
				'value'=>Precurio_Date::now()->getTimestamp() 
				));

		$form->addElement('text', 'first_name', array(
				'validators' => array(
				),
				'required' => true,
				'label'=>$this->translate('First name'),
				));

		$form->addElement('text', 'last_name', array(
				'validators' => array(
				),
				'required' => true,
				'label'=>$this->translate('Last name'),
				));
					
		$form->addElement('text', 'email', array(
				'validators' => array(
				),
				'required' => true,
				'label'=>$this->translate('Email'),
				));
				
		$form->addElement('text', 'password', array(
				'validators' => array(
				),
				'required' => false,
				'label'=>$this->translate('Password'),
				));
		
		$form->addElement('select', 'department_id', array(
				'validators' => array(
				),
				'required' => false,
				'label'=>$this->translate('Department'),
				'multiOptions'=> Precurio_FormElement::getOptionsArray($userUtil->getDepartments(),'id','title',true),
				));
		$form->addElement('select', 'location_id', array(
				'validators' => array(
				),
				'required' => false,
				'label'=>$this->translate('Location'),
				'multiOptions'=> Precurio_FormElement::getOptionsArray($userUtil->getLocations(),'id','title',true),
				));

		$form->addElement('submit', 'submit', array(
					'class'=>'standout',
					'label'=>$this->translate('Submit'),
					));
		
			
		return $form;
	}
}

?>