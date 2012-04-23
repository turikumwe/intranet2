<?php

/**
 * IndexController
 * 
 * @author
 * @version 
 */

require_once 'Zend/Controller/Action.php';
require_once('visitor/models/Visitors.php'); 
require_once('visitor/models/Appointments.php');
require_once('visitor/models/vo/Visitor.php');
require_once('visitor/models/VisitorUtil.php');

class Visitor_IndexController extends Zend_Controller_Action 
{
	var $user;
	
	public function init()
	{		
		$this->user = UserUtil::getUser(0);	
	}	
	
	public function indexAction()
	{
		$this->_helper->viewRenderer->setNoRender();
		$this->view->user = $this->user;
		
		$request = $this->getRequest();
		
		// throw an error if no receptionist has been registered
		if( Precurio_Utils::isNull(count(VisitorUtil::getReceptionists(Precurio_Session::getCurrentUserId())))  )
		{	
			$baseUrl = $this->getRequest()->getBaseUrl();
			
			$action = ( VisitorUtil::isAllowed('visitor_index') ) ? "<a href='{$baseUrl}/admin/visitor/addreceptionist'> <br/> click here </a> to add receptionist" : "Contact administrator";
			$error = new Precurio_Exception("Module is not preoperly configured, no receptionist has been registred {$action}",Precurio_Exception::EXCEPTION_IMPROPER_MODULE);
			
			$err = new stdClass();
			$err->exception = $error;
			$request->setModuleName('index');
			$request->setControllerName('error');
			$request->setActionName('error');
			$request->setParam('error_handler',$err);
			$request->setDispatched(false);
		}
		else 
		{
			$params = $this->getRequest()->getParams();
			if(isset($params['id']))
				$this->view->a_id = $params['id'];
			
			$this->render('index');
		}
	}
		
	public function selectreceptionistAction()
	{
		$this->_helper->layout->disableLayout();
		$this->_helper->viewRenderer->setNoRender();
		
		$ns = new Zend_Session_Namespace('temp');		
						
		$ns->id = 0;
		$ns->selectedUsers = array();
		$ns->callback = $this->getRequest()->getBaseUrl()."/visitor/ajax/selectreceptionist";//needed for the user select dialog		
		$ns->selectLabel = 'Selected Receptionist(You can only select 1)'; // translate later	
	}
	
	public function deletevisitorAction()
	{
		$this->_helper->layout->disableLayout();
		$this->_helper->viewRenderer->setNoRender();
		$visitor_id = $this->getRequest()->getParam('id');
		
		$visitors = new Visitors();		
		echo $visitors->deleteVisitor($visitor_id);
	}
	
	
	public function viewAction()
	{
		$this->_helper->layout->disableLayout();
		$this->view->visitor_id = $this->getRequest()->getParam('id');
		$this->view->type = $this->getRequest()->getParam('type');
		$this->render('viewvisitor');	
	}	
	
	public function viewsdiaryAction()
	{
		$this->_helper->layout->disableLayout();
	}
	
	public function getsdiaryAction()
	{
		$this->_helper->layout->disableLayout();
		$this->view->staff_id = $this->getRequest()->getParam('staff_id');
		$this->view->staff_name = $this->getRequest()->getParam('staff_name');
	}	

}
?>