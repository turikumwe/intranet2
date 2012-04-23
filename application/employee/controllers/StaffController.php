<?php

/**
 * StaffController
 * 
 * @author
 * @version 
 */

require_once 'Zend/Controller/Action.php';
require_once 'user/models/UserUtil.php';
class Employee_StaffController extends Zend_Controller_Action {
	
public function preDispatch()
	{
		$this->_helper->layout()->setLayout(PrecurioLayoutConstants::MAIN_C);
	}
	/**
	 * The default action - show the home page
	 */
	public function indexAction() {
		return $this->_forward('find');
	}
	public function findAction()
	{
		$params = $this->getRequest()->getParams();
		$ns = new Zend_Session_Namespace('temp');
		$user = UserUtil::getUser(Precurio_Session::getCurrentUserId());
		//this will be a bug if there is no location_id with 1.
		$ns->location_id = isset($params['location_id']) ? $params['location_id'] : $user->getLocationId();
		$ns->department_id = isset($params['department_id']) ? $params['department_id'] : $user->getDepartmentId();
		$this->view->pageNumber = $this->getRequest()->getParam('page');
		$this->view->l_id = $ns->location_id;
		$this->view->d_id = $ns->department_id;
	}
	public function listAction()
	{
		
	}
	public function recentAction()
	{
		
	}
	public function outAction()
	{
		
	}
	public function birthdaysAction()
	{
		$month = $this->getRequest()->getParam('month');
		if(Precurio_Utils::isNull($month))
		{
			$date = new Precurio_Date();
			$month = $date->get(Precurio_Date::MONTH);
		}
		$this->view->month = $month;
		$this->view->pageNumber = $this->getRequest()->getParam('page');
	}
}
?>