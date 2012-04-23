<?php

/**
 * AppointmentController
 * 
 * @author
 * @version 
 */

require_once 'Zend/Controller/Action.php';
require_once('visitor/models/Appointments.php'); 
require_once('visitor/models/Visitors.php'); 
require_once('visitor/models/VisitorUtil.php'); 
require_once('visitor/models/vo/Appointment.php');
require_once ('employee/models/Employees.php');

class Visitor_AppointmentController extends Zend_Controller_Action 
{
	
	var $user;
	
	public function init()
	{		
		$this->user = UserUtil::getUser(0);		
	}
	
	public function indexAction()
	{
		$this->_forward('view');
	}
	
	public function viewAction()
	{
		$this->_helper->layout->disableLayout();
		$id = $this->view->appointment_id =$this->getRequest()->getParam('id');		
		$this->view->mine = $this->getRequest()->getParam('mine'); // to specify what list the user is coming from - if his listing or all listings
		$this->view->type = $this->getRequest()->getParam('type');
		$this->view->user = $this->user;

		$ns = new Zend_Session_Namespace('temp');
		$ns->id = $this->getRequest()->getParam('id');
		$ns->callback = $this->getRequest()->getBaseUrl()."/visitor/appointment/participants/a_id/{$id}";//needed for the user select dialog
		$this->view->callback = $ns->callback;
		$ns->selectedUsers = array();//clear the user select dialog.
		$ns->selectLabel = 'New Participants'; // translate later
		
	}	
	
	public function registerAction()
	{
		$this->_helper->layout->disableLayout();
				
		$this->view->form = $this->getAppointmentForm();
		$this->view->contact_id = $this->getRequest()->getParam('contact_id');
		
		$ns = new Zend_Session_Namespace('temp');		
		$ns->id = 0;
		$ns->selectedUsers = array();//clear the user select dialog.
		
	}
	
	public function selecthostAction()
	{
		$this->_helper->layout->disableLayout();
		$this->_helper->viewRenderer->setNoRender();
		
		$ns = new Zend_Session_Namespace('temp');
				
		$ns->callback = $this->getRequest()->getBaseUrl()."/visitor/ajax/appointmenthost";//needed for the user select dialog
		$ns->selectLabel = 'Selected Host (Only 1 host allowed)'; // translate later		
	}
	
	public function addcontactAction()
	{
		
		$this->_helper->layout->disableLayout();
		$host = $this->getRequest()->getParam('host');
				
		$this->view->appointment_id = $this->getRequest()->getParam('a_id');
		$this->view->host = $host;
		$this->view->type = $this->getRequest()->getParam('type');				
	}
	
	public function selectparticipantsAction()
	{
		$this->_helper->layout->disableLayout();
		$this->_helper->viewRenderer->setNoRender();
		
		$ns = new Zend_Session_Namespace('temp');		
		
		if( ! Precurio_Utils::isNull($this->getRequest()->getParam('edit')) ) 
		$ns->callback = $this->getRequest()->getBaseUrl()."/visitor/appointment/participants";//needed for the user select dialog		
		else
		$ns->callback = $this->getRequest()->getBaseUrl()."/visitor/ajax/appointmentparticipants";//needed for the user select dialog		
		$ns->selectLabel = 'Selected Participants'; // translate later		
	}
	
	
	
	public function editAction()
	{
		$this->_helper->layout->disableLayout();
				
		$appointment_id = $this->getRequest()->getParam('id');
		$this->view->appointment_id = $appointment_id;
		
		$appointments = new Appointments();
		$appointment = $appointments->getAppointment($appointment_id);
		$this->view->form = $this->getAppointmentForm($appointment);
		$this->render('register');
	}
	
	public function deleteAction()
	{
		$this->_helper->layout->disableLayout();
		$this->_helper->viewRenderer->setNoRender();
		$appointment_id = $this->getRequest()->getParam('id');
				
		$appointments = new Appointments();
		echo $appointments->deleteAppointment($appointment_id);		
	}
	
	public function endAction()
	{
		$this->_helper->layout->disableLayout();
		$this->_helper->viewRenderer->setNoRender();
		$appointment_id = $this->getRequest()->getParam('id');
				
		$appointments = new Appointments();
		$data['status'] = 0;
		$appointments->updateAppointment($appointment_id, $data);
		echo "Appointment has been ended";
	}
	
	public function contactsAction()
	{
		$appointments = new Appointments();
		
		$this->_helper->layout->disableLayout();
		$this->_helper->viewRenderer->setNoRender();
				
		switch ( $this->getRequest()->getParam('f') )
		{
			case 'add':
				$a_id = $this->getRequest()->getParam('a_id');
				$appointment = $appointments->getAppointment($a_id);				
				
				if(! Precurio_Utils::isNull($this->getRequest()->getParam('newcontact')) )
				{
					$contact = $this->getRequest()->getParam('newcontact');
					$contact = explode(',', $contact);
					$data['full_name'] = $contact[0];
					$data['user_id'] = Precurio_Session::getCurrentUserId();
					$data['company'] = $contact[1];
					$data['shared'] = Precurio_Utils::isNull(count($appointment->getParticipants())) ? 0 : 1;
					
					$contact_id = VisitorUtil::addUserContact($data);
					$appointments->addAppointmentContact($a_id, $contact_id);
				}
				else
				{
					$appointments->addAppointmentContact($a_id, $this->getRequest()->getParam('contact_id'));
				}
									
				break;
			
			case 'remove':			
				$appointments->removeAppointmentContact($this->getRequest()->getParam('id'));
				break;
		}
	}
	
	public function participantsAction()
	{
		$appointments = new Appointments();		
				
		$this->_helper->layout->disableLayout();
		$this->_helper->viewRenderer->setNoRender();			
		
		$str = $this->getRequest()->getParam('users');		
		$users = explode(",",$str);
		$a_id = $users[0];
		
		array_shift($users);
		foreach($users as $user_id)			
			$appointments->addUserAppointment($user_id, $a_id);
				
		echo "loadPage('{$this->getRequest()->getBaseUrl()}/visitor/appointment/view/id/{$a_id}')";   
	}
	
	public function loginAction()
	{
		$this->_helper->layout->disableLayout();
		$visitors = new Visitors();

		$visitor = $visitors->getVisit($this->getRequest()->getParam('v_id'));
		
		$this->view->visitors_name = $this->getRequest()->getParam('vname');
		$this->view->form = $this->getLoginForm($visitor);
	}
	
	public function logoutAction()
	{
		$this->_helper->layout->disableLayout();
				
		$this->view->visitors_name = $this->getRequest()->getParam('vname');
		$this->view->form = $this->getLogoutForm($this->getRequest()->getParam('v_id'));		
	}
	
	public function loginsubmitAction()
	{
		$this->_helper->layout->disableLayout();
		$this->_helper->viewRenderer->setNoRender();
		
		$values = $this->getRequest()->getParams();
		
		if (!$this->getRequest()->isPost()) {
			return $this->_forward('index');
		}
		
						
		$dob = new Precurio_Date();
		$date = new Precurio_Date();
				
		$dob->setMonth($values['dob_month']);
		$dob->setDay($values['dob_day']);
		$dob->setYear($values['dob_year']);
		
		$date->setHour($values['timein_hour']);
		$date->setMinute($values['timein_minute']);
		
		// data for the visitor table
		$visitor['DOB'] = $dob->getTimestamp();
		$visitor['car_reg_number'] = $values['car_reg_number'];
		$visitor['emergency_contact'] = $values['emergency_contact'];
		
		// data for the visitor_appointment table
		$v_app['time_in'] = $date->getTimestamp();
		$v_app['visitor_note'] = $values['visitor_note'];
		$v_app['visitor_tag'] = $values['visitor_tag'];
				
		$visitors = new Visitors();		
		echo $visitors->logVisitorIn($values['v_id'], $visitor, $v_app);
	}
	
	public function logoutsubmitAction()
	{
		$this->_helper->layout->disableLayout();
		$this->_helper->viewRenderer->setNoRender();
		
				
		if (!$this->getRequest()->isPost()) {
			return $this->_forward('index');
		}		
		
		$values = $this->getRequest()->getParams();
		
		$date = new Precurio_Date();		
		
		$date->setHour($values['timeout_hour']);
		$date->setMinute($values['timeout_minute']);
				
		$data['time_out'] = $date->getTimeStamp();
				
		$visitors = new Visitors();		
		echo $visitors->logVisitorOut($values['v_id'], $data);
	}
	
	public function removeparticipantAction()
	{
		$id = $this->getRequest()->getParam('id');
		
		$this->_helper->layout->disableLayout();
		$this->_helper->viewRenderer->setNoRender();
		
		$appointments = new Appointments();
		
		echo $appointments->removeParticipant($id);
	}
	
	public function submitAction()
	{
		$this->_helper->layout->disableLayout();
		$this->_helper->viewRenderer->setNoRender();	
		
		if (!$this->getRequest()->isPost()) {
			return $this->_forward('index');
		}
		$form = $this->getAppointmentForm();
		if (!$form->isValid($_POST))
		{
			echo $this->view->translate(PrecurioStrings::ERROR_PERFORMING_OPERATION);
			return;
		}
		
		$values = $this->getRequest()->getParams();
				
		$date = new Precurio_Date();
		$date->setMonth($values['expected_month']);
		$date->setDay($values['expected_day']);
		$date->setYear($values['expected_year']);
		$date->setHour($values['expected_hour']);
		$date->setMinute($values['expected_minute']);
		
				
		$appointment['appointment_date'] = $date->getTimestamp();
		$appointment['creator'] = Precurio_Session::getCurrentUserId();
		$appointment['date_created'] = Precurio_Date::now()->getTimestamp();
		$appointment['host'] = $values['host'];
		$appointment['purpose'] = $values['purpose'];
		$appointment['purpose_detail'] = $values['purpose_detail'];
		$appointment['title'] = $values['title'];
		$appointment['participants'] = isset($values['participants']) ? $values['participants'] : '';
		$appointment['newcontacts'] = isset($values['newcontacts']) ? $values['newcontacts'] : '';
		$appointment['contacts'] = isset($values['contacts']) ? $values['contacts'] : '';		
		
		$appointments = new Appointments();
		
		if(!isset($values['id']))
		{
			$result = $appointments->addAppointment($appointment);			
			
		}
		else
		{
			$result = $appointments->updateAppointment($values['id'],$appointment); 
		}
		
		
		echo $result;
	}
	
	function getLoginForm($visitor=null)
	{
		$date = Zend_Date::now();
		
		$form = new Zend_Form();
		$form->setMethod('post');
		
		$dob = $visitor == null ? $date : ( Precurio_Utils::isNull( $visitor->DOB ) ) ? $date : new Zend_Date($visitor->DOB);
		
		$form->addElement('hidden', 'v_id', array(
				'validators' => array(
				),
				'required' => false,
				'value' => $visitor->id,
				));
				
						
		$form->addElement('text', 'visitor_tag', array(
				'validators' => array(
				),
				'required' => false,				
				));
	
		$form->addElement('select', 'timein_hour', array(
				'required' => true,
				'class' => 'oneThirdSelect',
				'multiOptions'=> Precurio_FormElement::getOptionsArray(Precurio_Date::getAllHours(),'value','label'),
				'value'=>$date->get(Precurio_Date::HOUR)
				));	
				
				
		$form->addElement('select', 'timein_minute', array(
				'required' => true,
				'class' => 'oneThirdSelect',
				'multiOptions'=> Precurio_FormElement::getOptionsArray(Precurio_Date::getAllMinutes(),'value','label'),
				'value'=>$date->get(Precurio_Date::MINUTE)
				));
				
		$form->addElement('select', 'dob_year', array(
				'required' => true,
				'class' => 'oneThirdSelect',
				'multiOptions'=> Precurio_FormElement::getOptionsArray(Precurio_Date::getAllYears(),'value','label'),
				'value'=>$dob->get(Precurio_Date::YEAR)		
				));
				
		$form->addElement('select', 'dob_month', array(
				'required' => true,
				'class' => 'oneThirdSelect',
				'multiOptions'=> Precurio_FormElement::getOptionsArray(Precurio_Date::getAllMonths(),'value','label'),
		        'value'=>$dob->get(Precurio_Date::MONTH_SHORT)
				));
		$form->addElement('select', 'dob_day', array(
				'required' => true,

				'class' => 'oneThirdSelect',
				'multiOptions'=> Precurio_FormElement::getOptionsArray(Precurio_Date::getAllDays(),'value','label'),
				'value'=>$dob->get(Precurio_Date::DAY)
				));
				
		$form->addElement('text', 'emergency_contact', array(
				'validators' => array(
				),				
				'required' => true,
				'value'=> $visitor['emergency_contact'],
				));	
				
		$form->addElement('text', 'car_reg_number', array(
				'validators' => array(
				),				
				'required' => true,
				'value'=> $visitor['car_reg_number'],
				));	
				
		$form->addElement('textarea', 'visitor_note', array(
				'validators' => array(
				),
				'rows'=>3,
				'required' => false,
				
				));
				
		
				
				
		$form->setElementDecorators(array('ViewHelper','FormElements','Errors'));
		return $form;
	}

	
	
	
	function getAppointmentForm($appointment=null)
	{
		$employees = new Employees();
		$user_id = Precurio_Session::getCurrentUserId();
		$expectedDate = $appointment == null ? Zend_Date::now() : new Zend_Date($appointment->appointment_date);
		$host = $appointment == null ?  $user_id : $appointment->host;
		$host_name = $appointment == null ?  $this->user->getFullName() : UserUtil::getUser($appointment['host'])->getFullName();
		
		
		$form = new Zend_Form();
		$form->setAction($this->getRequest()->getBaseUrl().'/visitor/appointment/submit')
			->setMethod('post');

		
		
		$form->addElement('hidden', 'host', array(
				'validators' => array(
				),
				'required' => false,
				'value' => $host,
				));
		
		$form->addElement('text', 'host_name', array(
				'validators' => array(
				),
				'required' => false,
				'value' => $host_name,
				'readOnly' => true,
				));
				
		$form->addElement('text', 'title', array(
				'validators' => array(
				),
				'rows'=>3,
				'required' => true,
				'value' => $appointment['title'],				
				));	
				
		
		
		$form->addElement('hidden', 'id', array(
				'validators' => array(
				),
				'required' => false,
				'value' => $appointment['id'],
				));
				
		
				
		$form->addElement('select', 'expected_year', array(
				'required' => true,
				'class' => 'oneThirdSelect',
				'multiOptions'=> Precurio_FormElement::getOptionsArray(Precurio_Date::getAllYears(),'value','label'),
				'value'=>$expectedDate->get(Precurio_Date::YEAR)		
				));
				
		$form->addElement('select', 'expected_month', array(
				'required' => true,
				'class' => 'oneThirdSelect',
				'multiOptions'=> Precurio_FormElement::getOptionsArray(Precurio_Date::getAllMonths(),'value','label'),
		        'value'=>$expectedDate->get(Precurio_Date::MONTH_SHORT)
				));
		$form->addElement('select', 'expected_day', array(
				'required' => true,

				'class' => 'oneThirdSelect',
				'multiOptions'=> Precurio_FormElement::getOptionsArray(Precurio_Date::getAllDays(),'value','label'),
				'value'=>$expectedDate->get(Precurio_Date::DAY)
				));
				
		$form->addElement('select', 'expected_hour', array(
				'required' => true,
				'class' => 'oneThirdSelect',
				'multiOptions'=> Precurio_FormElement::getOptionsArray(Precurio_Date::getAllHours(),'value','label'),
				'value'=>$expectedDate->get(Precurio_Date::HOUR)
				));	
				
				
		$form->addElement('select', 'expected_minute', array(
				'required' => true,
				'class' => 'oneThirdSelect',
				'multiOptions'=> Precurio_FormElement::getOptionsArray(Precurio_Date::getAllMinutes(),'value','label'),
				'value'=>$expectedDate->get(Precurio_Date::MINUTE)
				));
				
		$purpose = new Zend_Form_Element_Select('purpose');
		$purpose->addMultiOption('meeting','Meeting');
		$purpose->addMultiOption('personal','Personal');
		//$status->addMultiOption(Task::STATUS_COMPLETE,'Closed'); 
		$form->addElement($purpose);
		
		$form->addElement('textarea', 'purpose_detail', array(
				'validators' => array(
				),
				'rows'=>3,
				'required' => false,
				'value' => $appointment['purpose_detail'],
				));	
		
		$form->setElementDecorators(array('ViewHelper','FormElements','Errors'));
		return $form;
	}
	
	function getLogoutForm($v_id)
	{
		$date = Zend_Date::now();
		
		$form = new Zend_Form();
		$form->setMethod('post');
		
		$form->addElement('hidden', 'v_id', array(
				'validators' => array(
				),
				'required' => false,
				'value' => $v_id,
				));
	
		$form->addElement('select', 'timeout_hour', array(
				'required' => true,
				'class' => 'oneThirdSelect',
				'multiOptions'=> Precurio_FormElement::getOptionsArray(Precurio_Date::getAllHours(),'value','label'),
				'value'=>$date->get(Precurio_Date::HOUR)
				));	
				
				
		$form->addElement('select', 'timeout_minute', array(
				'required' => true,
				'class' => 'oneThirdSelect',
				'multiOptions'=> Precurio_FormElement::getOptionsArray(Precurio_Date::getAllMinutes(),'value','label'),
				'value'=>$date->get(Precurio_Date::MINUTE)
				));	
				
				
		$form->setElementDecorators(array('ViewHelper','FormElements','Errors'));
		return $form;
	}

	


}
?>