<?php
require_once ('employee/models/Employees.php');
require_once ('cms/models/MyContents.php');
require_once ('visitor/models/VisitorUtil.php');
/**
 * IndexController - The default controller class
 * 
 * @author
 * @version 
 */

class User_IndexController extends Zend_Controller_Action 
{

    public function indexAction() 
    {
    	$this->_helper->viewRenderer->setNoRender();
		$this->_helper->layout->disableLayout();
    	$this->_forward('view','profile');
    	return;
    }
    public function selectAction()
    {
    	$this->_helper->layout->disableLayout();
    	$ns = new Zend_Session_Namespace('temp');
    	
    	$c_id = $this->getRequest()->getParam('c_id',0);
    	if($c_id != 0)
    	{
    		$ns->id = $c_id;
    		$content = MyContents::getContent($c_id) ;
    		$ns->selectedUsers = $content->getSharedUsers();//array
    		
    		$dict = new Precurio_Search();
  			$dict->indexContent($c_id);
    	}
    	if(!isset($ns->callback))
    	{
    		$this->_helper->viewRenderer->setNoRender();
    		echo $this->view->translate("Invalid callback selected");
    		return;
    	}
    	if(!isset($ns->selectLabel))$ns->selectLabel = $this->view->translate("Selected");
    	$this->view->callback = $ns->callback;
    	$this->view->id = $ns->id;
    	$this->view->selectedUsers = $ns->selectedUsers;
    	$this->view->selectLabel = $ns->selectLabel;
    	$employees  = new Employees();
    	$this->view->employees = $employees->getAll(true);
    }
    public function autocompleteAction()
    {
    	$this->_helper->layout->disableLayout();
    	$this->_helper->viewRenderer->setNoRender();
    	$part = $this->getRequest()->getParam('part');
    	
    	//TODO please cache the db query below.
    	$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::USERS,'rowClass'=>'User'));
		$select = $table->select()->where('active=1')->order('first_name desc');
		$users = $table->fetchAll($select);
		$results = array();
		foreach($users as $user)
		{
			if(stripos($user->getFullName(),$part)!== FALSE)
				$results[].= $user->getFullName();
		}
		echo Zend_Json_Encoder::encode($results);
		
		
		
    }
    public function setstyleAction()
    {
    	$this->_helper->layout->disableLayout();
		$this->_helper->viewRenderer->setNoRender();
    	Precurio_Session::setStyle($this->getRequest()->getParam('style'));
    }
    
    
	public function selectreceptionistAction()
    {
		$this->_helper->layout->disableLayout();
    	$ns = new Zend_Session_Namespace('temp');
    	    	
    				
    	$this->view->callback = $this->getRequest()->getBaseUrl()."/visitor/ajax/selectreceptionist/";
    	$this->view->id = 0;
    	$this->view->selectedUsers = array();
    	$this->view->selectLabel = 'Selected Receptionist(You can only select 1)'; // translate later   	 	
    	$this->view->employees = VisitorUtil::getReceptionists();
		$this->render('select');
    }
    
	public function updateusernamesAction()
	{
		$this->_helper->layout->disableLayout();
		$this->_helper->viewRenderer->setNoRender();
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::USERS));
		$users = $table->fetchAll();
		foreach($users as $user)
		{
			$user->username = substr($user->email,0,strpos($user->email,'@'));
			$user->save();
		}
		echo "Done!";
		
	}
  
}
