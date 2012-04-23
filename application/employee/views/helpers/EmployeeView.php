<?php
/**
 *
 * @author Brain
 * @version 
 */
require_once 'Zend/View/Interface.php';
require_once ('employee/models/Employees.php');
require_once 'user/models/UserUtil.php';
/**
 * EmployeeView helper
 *
 * @uses viewHelper Employee_View_Helper
 */
class Employee_View_Helper_EmployeeView {
	const ITEMS_PER_PAGE = 10;
	public $translate;
	/**
	 * Employees model
	 * @var Employees
	 */
	private $employees;
	/**
	 * @var Zend_View_Interface 
	 */
	public $view;
	
	/**
	 *  
	 */
	public function employeeView() {
		$this->translate = Zend_Registry::get('Zend_Translate'); 
		return $this;
	}
	public function getRecentProfileChanges()
	{
		$this->employees = new Employees();
		
		$all = $this->employees->getRecentProfileChanges();
		
		$content = array();
		foreach($all as $employee)
		{
			
			$content[] = $this->wrap($employee,false,true);
			
		}
		$paginator = Zend_Paginator::factory($content);
		$paginator->setCurrentPageNumber($this->view->pageNumber);
		$paginator->setItemCountPerPage(self::ITEMS_PER_PAGE);
		$this->view->paginator = $paginator;
		return $content;
	}
	public function getOutOfOffice()
	{
		$this->employees = new Employees();
		
		$all = $this->employees->filter(array('out_of_office'=>'1'));
		
		$content = array();
		foreach($all as $employee)
		{
			
			$content[] = $this->wrap($employee,true);
			
		}
		$paginator = Zend_Paginator::factory($content);
		$paginator->setCurrentPageNumber($this->view->pageNumber);
		$paginator->setItemCountPerPage(self::ITEMS_PER_PAGE);
		$this->view->paginator = $paginator;
		return $content;
	}
	public function getBirthDays()
	{
		$month = $this->view->month;
		$this->employees = new Employees();
		$all = $this->employees->filter(array('birth_month'=>$month));
		
		$content = array();
		foreach($all as $employee)
		{
			
			$content[] = $this->wrap($employee);
			
		}
		$paginator = Zend_Paginator::factory($content);
		$paginator->setCurrentPageNumber($this->view->pageNumber);
		$paginator->setItemCountPerPage(self::ITEMS_PER_PAGE);
		$this->view->paginator = $paginator;
		return $content;
	}
	public function getLocations($selected_id = 0)
	{
		$userUtils = new UserUtil();
		$locations = $userUtils->getLocations();
		$content = "";
		$ns = new Zend_Session_Namespace('temp');
		$department_id = $ns->department_id;
		$baseUrl = Zend_Controller_Front::getInstance()->getRequest()->getBaseUrl();
		foreach($locations as $location)
		{
			$str = "<a href='$baseUrl/employee/staff/find/location_id/{$location->getId()}/department_id/{$department_id}'>".$location->getTitle().'</a><br />';
			if($location->id == $selected_id)
				$str = '<span style="font-weight: bold">'.$str.'</span>';
			$content .= $str;
		}
		return $content;
	}
	public function getDepartments($selected_id = 0)
	{
		$userUtils = new UserUtil();
		$departments = $userUtils->getDepartments();
		$content = "";
		$ns = new Zend_Session_Namespace('temp');
		$location_id = $ns->location_id;
		$baseUrl = Zend_Controller_Front::getInstance()->getRequest()->getBaseUrl();
		foreach($departments as $department)
		{
			$str = "<a href='$baseUrl/employee/staff/find/location_id/{$location_id}/department_id/{$department->getId()}'>".$department->getTitle().'</a><br />';
			if($department->id == $selected_id)
				$str = '<span style="font-weight: bold">'.$str.'</span>';
			$content .= $str;
		}
		return $content;
	}
	public function getAllAsList()
	{
		$this->employees = new Employees();
		$all = $this->employees->getAll();
		
		$content = "";
		$baseUrl = Zend_Controller_Front::getInstance()->getRequest()->getBaseUrl();
		foreach($all as $employee)
		{
			
			$content .= "<li><a href='$baseUrl/user/profile/view/{$employee->getId()}'>".$employee->getFullName()."</a></li>";
			
		}
		return $content;
	}
	public function getAll()
	{
		$requestParams = Zend_Controller_Front::getInstance()->getRequest()->getParams();
		$this->employees = new Employees();
	
		$all = $this->employees->filter($requestParams);
		
		$content = array();
		foreach($all as $employee)
		{
			
			$content[] = $this->wrap($employee);
			
		}
		$paginator = Zend_Paginator::factory($content);
		$paginator->setCurrentPageNumber($this->view->pageNumber);
		$paginator->setItemCountPerPage(self::ITEMS_PER_PAGE);
		$this->view->paginator = $paginator;
		return $content;
	}
	/**
	 * @param $user User
	 * @param $out Boolean- flag to indicate if out of office
	 * @return string
	 */
	private function wrap($user,$out=false,$recent=false)
	{
		if($out)$outObj = $user->outOfOffice();
		if($out && Precurio_Utils::isNull($outObj))return "";//only happens if there something weird goes wrong with the relationship between user table and out of office table
		if($recent && $user->getRecentChange()== "")return "";//also a db relation problem.
		$baseUrl = Zend_Controller_Front::getInstance()->getRequest()->getBaseUrl();
		return "<div  class='listLine contentPad_5'>".
            	 "<img src='".$baseUrl.Precurio_Image::getPath($user->getProfilePicture(),Precurio_Image::IMAGE_ICON)."' width='50' height='50' align='left' />".
            	" <span class='nameText'><a href='$baseUrl/user/profile/view/{$user->getId()}'>{$user->getFullName()}</a></span> ({$user->getLocation()})".
				($recent ? "<span class='textLinks'> </span><span class='midText'>{$user->getRecentChange()}</span>" : '').
				($out ? "<span class='textLinks'> {$this->translate('from')}: </span><span class='midText'>{$outObj->getLeaveDate()}</span>" : ' <br />').
            	($out ? "<span class='textLinks'> {$this->translate('to')}: </span><span class='midText'>{$outObj->getReturnDate()}</span> <br/>" : '').    
            	($out ? "<span class='textLinks '><br />" : " <span class='textLinks'>{$user->getJobTitle()}: <a href='#'>{$user->getDepartment()}</a><br />").
                " <a href='$baseUrl/user/profile/view/{$user->getId()}'><img src='$baseUrl/library/css/{$this->view->themestyle}/images/arrow_small.gif'  id='arrow'/> {$this->translate('View Complete Profile')} </a>".
				" <a href='mailTo:{$user->getEmail()}'><img src='$baseUrl/library/css/{$this->view->themestyle}/images/arrow_small.gif'  id='arrow'/> {$this->translate('Send')} {$user->getFirstName()} {$this->translate('a message')}</a>.".  
				($out ? '' : "<a href='javascript:void(0)' onClick='javascript:chatWith(&quot;{$user->getFirstName()}&quot;)'><img src='$baseUrl/library/css/{$this->view->themestyle}/images/arrow_small.gif'  id='arrow'/>  {$this->translate('Chat with')} {$user->getFirstName()}</a>.    </span><br/> ").         
   		"</div> ";
	}
	/**
	 * Sets the view field 
	 * @param $view Zend_View_Interface
	 */
	public function setView(Zend_View_Interface $view) {
		$this->view = $view;
	}
	public function translate($str)
	{
		return $this->translate->translate($str);
	}
}?>