<?php

/**
 * TelephoneController
 * 
 * @author
 * @version 
 */

require_once 'Zend/Controller/Action.php';
require_once('employee/models/Employees.php'); 
require_once('visitor/models/TelMessages.php');
require_once('visitor/models/VisitorUtil.php');

class Visitor_TelephoneController extends Zend_Controller_Action 
{
	
	public function indexAction()
	{
	$this->_helper->layout->disableLayout();
	}
	public function registerAction()
	{
		$this->_helper->layout->disableLayout();
				
		
		$ns = new Zend_Session_Namespace('temp');		
						
		$ns->id = 0;
		$ns->selectedUsers = array();
		$this->view->form = $this->getTelephoneMessageForm();
	}
	
	public function viewAction()
	{
		$this->_helper->layout->disableLayout();
		$this->view->msg_id = $this->getRequest()->getParam('id');
		$this->view->mine = $this->getRequest()->getParam('mine'); // Specifies tab user is coming from
	}
	
	public function submitAction()
	{
		$this->_helper->layout->disableLayout();
		$this->_helper->viewRenderer->setNoRender();
		
		$values = $this->getRequest()->getParams();
		if (!$this->getRequest()->isPost()) 
		{
			return $this->_forward('index');
		}
		
		$timestamp = Precurio_Date::now()->getTimestamp();
		
		$values['logged_by'] = Precurio_Session::getCurrentUserId();
		$values['logged_for'] = $values['logged_for'][0];
		
		$date = new Precurio_Date();
		$date->setMonth($values['log_month']);
		$date->setDay($values['log_day']);
		$date->setYear($values['log_year']);
		$date->setHour($values['log_hour']);
		$date->setMinute($values['log_minute']);
		
		unset($values['log_month']);
		unset($values['log_day']);
		unset($values['log_year']);
		unset($values['log_hour']);
		unset($values['log_minute']);
		unset($values['user_id']);
		unset($values['full_name']);
		unset($values['company']);
		unset($values['owners_name']);
		
		$values['date_logged'] = $date->getTimestamp();
		
		$telMsgs = new TelMessages();
		
		if(Precurio_Utils::isNull($values['id']))
		{
			$result = $telMsgs->addTelephoneMessage($values);
		}
		else
		{
			$result = $telMsgs->updateTelephoneMessage($values['id'],$values); 
		}
		
		
		echo $result;
	}
	
	
	function getTelephoneMessageForm()
	{
		$employees = new Employees();
		$logDate = $message == null ? Zend_Date::now() : new Zend_Date($message->date_recorded);
		
		$form = new Zend_Form();
		$form->setMethod('post');
		
		// Get staff receptionist reports to, if more than 1, get the first user, if none, return the current user(self)
		$boss = VisitorUtil::getBoss(Precurio_Session::getCurrentUserId());
		
		$owners_name = $boss->getFullName();
		$logged_for = $boss->getId();		
		
		$form->addElement('hidden', 'logged_for', array(
				'validators' => array(
				),
				'required' => false,
				'value' => $logged_for,
				));
		
		$form->addElement('text', 'owners_name', array(
				'validators' => array(
				),
				'required' => false,
				'value' => $owners_name,
				'disabled' => 'disabled',
				));		
						
		$form->addElement('text', 'full_name', array(
				'validators' => array(
				),
				
				'required' => false,
				));	
				
		$form->addElement('text', 'company', array(
				'validators' => array(
				),
				
				'required' => false,
				));	
				
		$form->addElement('select', 'log_year', array(
				'required' => true,
				'class' => 'oneThirdSelect',
				'multiOptions'=> Precurio_FormElement::getOptionsArray(Precurio_Date::getAllYears(),'value','label'),
				'value'=>$logDate->get(Precurio_Date::YEAR)		
				));
				
		$form->addElement('select', 'log_month', array(
				'required' => true,
				'class' => 'oneThirdSelect',
				'multiOptions'=> Precurio_FormElement::getOptionsArray(Precurio_Date::getAllMonths(),'value','label'),
		        'value'=>$logDate->get(Precurio_Date::MONTH_SHORT)
				));
		$form->addElement('select', 'log_day', array(
				'required' => true,

				'class' => 'oneThirdSelect',
				'multiOptions'=> Precurio_FormElement::getOptionsArray(Precurio_Date::getAllDays(),'value','label'),
				'value'=>$logDate->get(Precurio_Date::DAY)
				));
				
		$form->addElement('select', 'log_hour', array(
				'required' => true,
				'class' => 'oneThirdSelect',
				'multiOptions'=> Precurio_FormElement::getOptionsArray(Precurio_Date::getAllHours(),'value','label'),
				'value'=>$logDate->get(Precurio_Date::HOUR)
				));	
				
				
		$form->addElement('select', 'log_minute', array(
				'required' => true,
				'class' => 'oneThirdSelect',
				'multiOptions'=> Precurio_FormElement::getOptionsArray(Precurio_Date::getAllMinutes(),'value','label'),
				'value'=>$logDate->get(Precurio_Date::MINUTE)
				));
				
		
		
		$form->addElement('textarea', 'content', array(
				'validators' => array(
				),
				'rows'=>3,
				'required' => false,
				
				));	
		
		$form->setElementDecorators(array('ViewHelper','FormElements','Errors'));
		return $form;
	}

}
?>