<?php
/**
 * AjaxController
 * 
 * @author
 * @version 
 */
 
require_once('visitor/models/VisitorUtil.php');
require_once('contact/models/Contacts.php');
class Visitor_AjaxController extends Zend_Controller_Action 
{

	public function appointmentparticipantsAction()
	{
		$this->_helper->layout->disableLayout();
		$this->_helper->viewRenderer->setNoRender();
			
		$data = '';
		
		
		$str = $this->getRequest()->getParam('users');
		
		$users = explode(",",$str);
		
		array_shift($users);
		foreach($users as $user_id)	
		{
			$user = UserUtil::getUser($user_id);
			$data.= "{".$user->id.','.$user->getFullName().'}/';			
		}
		
		echo "fillParticipantTable('{$data}')";   
	}
	
	public function appointmenthostAction()
	{
		$this->_helper->layout->disableLayout();
		$this->_helper->viewRenderer->setNoRender();
		
		
		$data = '';
		
		
		$str = $this->getRequest()->getParam('users');
		
		$users = explode(",",$str);
		
		array_shift($users);
		
		if(count($users) > 1)		
		echo "alert('You cant select more than 1 host')";// translate	
		else
		{
			$user = UserUtil::getUser($users[0]);
			$data.= $user->id.','.$user->getFullName();
			echo "setHost('{$data}')";
		}
		
	}
	
	public function selectreceptionistAction()
	{
		$this->_helper->layout->disableLayout();
		$this->_helper->viewRenderer->setNoRender();
		
		
		$data = '';
		
		
		$str = $this->getRequest()->getParam('users');
		
		$users = explode(",",$str);
		
		array_shift($users);
		
		if(count($users) > 1)		
		echo "alert('You cant select more than 1 receptionist')";// trenslate	
		else
		{
			$user = VisitorUtil::setReceptionist($users[0]);
			
			echo "alert('Your receptionist has been registered'); location.reload();"; //translate
		}
	}
	
	public function addreceptionistAction()
	{
		$this->_helper->layout->disableLayout();
		$this->_helper->viewRenderer->setNoRender();
		
		
		$data = '';
		
		
		$str = $this->getRequest()->getParam('users');
		
		$users = explode(",",$str);
		
		$g_id = $users[0];
		
			
		array_shift($users);
		
		foreach($users as $user_id)		
			$user = UserUtil::addUserToGroup($user_id, $g_id);					
	
		echo "alert('receptionists has been registered'); location.reload();"; 
	}
	
	public function selectcontactAction()
	{
		$this->_helper->layout->disableLayout();
		$this->_helper->viewRenderer->setNoRender();
		
		
		$data = '';
		
		
		$str = $this->getRequest()->getParam('users');
		
		$users = explode(",",$str);
		
		array_shift($users);
		
		if(count($users) > 1)		
		echo "alert('You cant select more than 1 contact')";		
		else
		{
			$user = UserUtil::getUser($users[0]);
			$data.= $user->id.','.$user->getFullName();
			echo "setContact('{$data}')";
		}
	}
	
	public function autocompletecontactAction()
	{
		$this->_helper->layout->disableLayout();
		$this->_helper->viewRenderer->setNoRender();
		
		
		$query = $this->getRequest()->getParam('q');
							
		$contacts = VisitorUtil::getContacts();		
		
		foreach($contacts as $contact)
		{
			if(strpos(strtolower($contact['full_name']), strtolower($query)) !== false)
			echo "{$contact['full_name']}|{$contact['id']}|{$contact['company']}\n";			
		}
	}

}

?>