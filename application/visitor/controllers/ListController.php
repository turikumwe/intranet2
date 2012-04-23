<?php

/**
 * ListController
 * 
 * @author
 * @version 
 */

require_once 'Zend/Controller/Action.php';
require_once('visitor/models/Appointments.php');
require_once('visitor/models/Visitors.php'); 
require_once('visitor/models/vo/Appointment.php');

class Visitor_ListController extends Zend_Controller_Action {
	
	var $user;
	
	public function init()
	{
		$this->user = UserUtil::getUser(0);
	}
		
	public function upcomingappointmentAction()
	{
		$this->_helper->layout->disableLayout();
		$this->view->type = Appointments::UPCOMING;
		$this->view->mine = $this->getRequest()->getParam('mine');
		$this->view->page = $this->getRequest()->getParam('page');
		$this->view->user = $this->user;
		$this->render('appointments');
	}
	
	public function pastappointmentAction()
	{
		$this->_helper->layout->disableLayout();
		$this->view->type = Appointments::PAST;
		$this->view->mine = $this->getRequest()->getParam('mine');
		$this->view->page = $this->getRequest()->getParam('page');
		$this->view->user = $this->user;
		$this->render('appointments');
	}
	
	public function currentappointmentAction()
	{
		$this->_helper->layout->disableLayout();
		$this->view->type = Appointments::CURRENT;		
		$this->view->page = $this->getRequest()->getParam('page');
		$this->view->user = $this->user;
		$this->render('appointments');
	}
	
	public function whoisinAction()
	{
		$this->_helper->layout->disableLayout();
		
		$this->view->page = $this->getRequest()->getParam('page');
	}
	
		
	public function visitorAction()
	{
		$this->_helper->layout->disableLayout();		
		$this->view->mine = $this->getRequest()->getParam('mine');
		$this->view->page = $this->getRequest()->getParam('page');
		$this->view->user = $this->user;		
	}
			
	public function telmessagesAction()
	{
		$this->_helper->layout->disableLayout();
		$this->view->mine = $this->getRequest()->getParam('mine');
		$this->view->page = $this->getRequest()->getParam('page');
		$this->view->user = $this->user;
	}
	
	
}
?>