<?php

/**
 * MyController
 * 
 * @author
 * @version 
 */

require_once 'Zend/Controller/Action.php';

require_once ('event/models/Events.php');
require_once ('event/models/vo/Event.php');
require_once ('event/models/EventGroup.php');
require_once ('user/models/UserUtil.php');
class Event_MyController extends Zend_Controller_Action {
	/**
	 * The default action - show the home page
	 */
	public function indexAction() 
	{
		$this->_forward('view');
	}
	private function getForm($event = null)
	{
		$startDate = $event == null ? Precurio_Date::now() : new Precurio_Date($event->start_date);
		$endDate = $event == null ? Precurio_Date::now() : new Precurio_Date($event->end_date);
		$userUtil = new UserUtil();
		$form = new Zend_Form();
		$form->setAction($this->getRequest()->getBaseUrl().'/event/my/submit')
			->setMethod('post')
			->setAttrib('id','addForm')
			->setAttrib('name','addForm')
			->setAttrib('enctype', 'multipart/form-data');
			
		$form->addElement('hidden', 'id', array(
				'validators' => array(
				),
				'required' => false,
				'value'=>$event['id'],
				));
			
		$form->addElement('text', 'title', array(
				'validators' => array(
				),
				'required' => true,
				'value'=>$event['title']
				));
		$form->addElement('text', 'summary', array(
				'validators' => array(
				),
				'required' => true,
				'value'=>$event['summary']
				));
		$form->addElement('text', 'host', array(
				'validators' => array(
				),
				'required' => true,
				'value'=>$event['host']
				));
	
		$form->addElement('select', 'location_id', array(
				'required' => true,
				'multiOptions'=> Precurio_FormElement::getOptionsArray($userUtil->getLocations(),'id','title'),
				'value'=>$event['location_id']
				));
				
		$form->addElement('text', 'venue', array(
				'validators' => array(
				),
				'required' => true,
				'value'=>$event['venue']
				));

		$form->addElement('select', 'start_month', array(
				'required' => true,
				'class' => 'oneThirdSelect',
				'multiOptions'=> Precurio_FormElement::getOptionsArray(Precurio_Date::getAllMonths(),'value','label'),
		        'value'=>$startDate->get(Precurio_Date::MONTH_SHORT)
				));
		$form->addElement('select', 'start_day', array(
				'required' => true,
				'class' => 'oneThirdSelect',
				'multiOptions'=> Precurio_FormElement::getOptionsArray(Precurio_Date::getAllDays(),'value','label'),
				'value'=>$startDate->get(Precurio_Date::DAY)
				));
		$form->addElement('select', 'start_year', array(
				'required' => true,
				'class' => 'oneThirdSelect',
				'multiOptions'=> Precurio_FormElement::getOptionsArray(Precurio_Date::getAllYears(),'value','label'),
				'value'=>$startDate->get(Precurio_Date::YEAR)		
				));
				
		$form->addElement('select', 'end_month', array(
				'required' => true,
				'class' => 'oneThirdSelect',
				'multiOptions'=> Precurio_FormElement::getOptionsArray(Precurio_Date::getAllMonths(),'value','label'),
		        'value'=>$endDate->get(Precurio_Date::MONTH_SHORT)
				));
		$form->addElement('select', 'end_day', array(
				'required' => true,
				'class' => 'oneThirdSelect',
				'multiOptions'=> Precurio_FormElement::getOptionsArray(Precurio_Date::getAllDays(),'value','label'),
				'value'=>$endDate->get(Precurio_Date::DAY)
				));
		$form->addElement('select', 'end_year', array(
				'required' => true,
				'class' => 'oneThirdSelect',
				'multiOptions'=> Precurio_FormElement::getOptionsArray(Precurio_Date::getAllYears(),'value','label'),
				'value'=>$endDate->get(Precurio_Date::YEAR)		
				));
		$form->addElement('text', 'start_time', array(
				'validators' => array(
				),
				'required' => true,
				'value'=>$event['start_time']
				));		
		$form->addElement('textarea', 'description', array(
				'validators' => array(
				),
				'filters'=>array(
				'htmlEntities'
				),
				'rows'=>10,
				'required' => true,
				'value'=>$event['description']
				));
		$is_open = new Zend_Form_Element_Select('is_open');
		$is_open->addMultiOption(1,'Open');
		$is_open->addMultiOption(0,'Closed');
		$is_open->setValue($event == null ? 1 : $event['is_open']);
		//$is_open->setAttrib('class','oneThirdSelect'); 
		$form->addElement($is_open);
		 		
		$work_related = new Zend_Form_Element_Select('work_related');
		$work_related->addMultiOption(1,'Yes');
		$work_related->addMultiOption(0,'No, Personal');
		$work_related->setValue($event == null ? 1 : $event['work_related']);
		//$work_related->setAttrib('class','oneThirdSelect'); 
		$form->addElement($work_related);
		
		$form->addElement('checkbox', 'restrict_content_access', array(
				'validators' => array(
				),
				'required' => true,
				'value'=>$event['restrict_content_access']
				));	
		$form->addElement('checkbox', 'disable_content_access', array(
				'validators' => array(
				),
				'required' => true,
				'value'=>$event['disable_content_access']
				));	
		$form->addElement('checkbox', 'open_guest_list', array(
				'validators' => array(
				),
				'required' => true,
				'value'=>true,
				));	
			
		
		
		$form->setElementDecorators(array('ViewHelper','FormElements','Errors'));
		
		
		$logo = new Zend_Form_Element_File('logo');
		$root = Zend_Registry::get('root');
		$logo->setDestination($root.'/public/uploads/')
			->addValidator('Count', false, 1) // ensure only 1 file
			->addValidator('Size', false, 2048000) // limit to 2MB
			->addValidator('Extension' ,false, 'jpg,png,gif'); // only JPEG, PNG, and GIFs
		$logo->removeDecorator('HtmlTag');
		$logo->removeDecorator('Label');
		$logo->setValue($event['logo']);
		$form->addElement($logo);
		
		
		$form->addElement('submit', 'submit', array(
				'class'=>'standout',
				'label'=>$this->view->translate('Submit'),
				));
		return $form;
	}
	public function newAction()
	{
		$this->view->form = $this->getForm();
	}
	public function editAction()
	{
		$event_id = $this->getRequest()->getParam('e_id');
		$event = Events::getEvent($event_id);
		if($event==null)return $this->_forward('details');
		$this->view->form = $this->getForm($event);
		$this->render('new');
	}
	public function deleteAction()
	{
		$this->_helper->layout->disableLayout();
		$this->_helper->viewRenderer->setNoRender();
		$event_id = $this->getRequest()->getParam('e_id');
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::EVENT));
		$event = $table->find($event_id)->current();
		
		if($event==null)
		{
			$this->_redirect('/event/my/index');
			return;
		}
		if($event->user_id != Precurio_Session::getCurrentUserId())
		{
			$this->_redirect('/event/my/index');
			return;
		}
		
		
		$event->active = 0;
		$event->save();	
		
		$dict = new Precurio_Search();
		$dict->unIndexEvent($event_id);
	}
	public function submitAction()
	{

		$params = $this->getRequest()->getParams();
		if (!$this->getRequest()->isPost()) 
		{
			$this->_redirect('/event/my/index');
			return;
		}
		$form = $this->getForm();
		if (!$form->isValid($_POST))
		{
			// Failed validation; redisplay form
			$this->view->form = $form;
			return $this->render('new');
		}
		$values = $form->getValues();
		
		$values['start_date'] = $values['start_day'].'-'.$values['start_month'].'-'.$values['start_year'];
		$values['end_date'] = $values['end_day'].'-'.$values['end_month'].'-'.$values['end_year'];
		$values['user_id'] = Precurio_Session::getCurrentUserId();
		$start_date = new Precurio_Date($values['start_date'],null,'de');
		$values['start_timestamp'] = $start_date->getTimestamp();
		$values['date_created'] = Precurio_Date::now()->getTimestamp();
		$values['logo'] = $this->getRequest()->getBaseUrl()."/uploads/".($values['logo'] == null ? 'questionmark.jpg' : $values['logo']);
		
		if(Precurio_Utils::isNull($values['id']))
		{
			$id = Events::createEvent($values);
		}
		else
		{
			$id = $values['id'];
			Events::updateEvent($values);
		}
		
		
		
		$dict = new Precurio_Search();
		$dict->indexEvent($id);
		return Precurio_Utils::isNull($values['id']) ? $this->_forward('view') : $this->_forward('details','my','event',array('e_id'=>$id));
		
	}
	public function viewAction()
	{
		$params = $this->getRequest()->getParams();
		$currentUser = UserUtil::getUser(Precurio_Session::getCurrentUserId());;
		
		$location_id = empty($params['l_id']) ? null : $params['l_id'];
		
		if(!isset($params['w']))
			$params['w'] = null;
		$work = $params['w'];
		
		if(!isset($params['cpage']))
			$params['cpage'] = 1;
		
		$events = Events::getMyEvents($currentUser->getId(),$location_id,$work);
		$eventGroups = array();
		foreach($events as $event)
		{
			$index = new Precurio_Date($event->start_timestamp);
			$index = $index->get(Precurio_Date::DATE_SHORT);
			if(isset($eventGroups[$index]))
				$eventGroups[$index]->push($event);
			else
				$eventGroups[$index] = new EventGroup($event);
		}
		$this->view->cpage = $params['cpage'];
		$this->view->eventGroups = $eventGroups;
		$this->view->w = $work;
		$this->view->l_id = $location_id; 
		
	}
	public function detailsAction()
	{
		$event_id = $this->getRequest()->getParam('e_id');
		$this->view->event = Events::getEvent($event_id);
		//the only reason am doing this is because i can figure how to pass the event_id from facebox
		//to be used for the invitees action
		try
		{
			$event = Events::getEvent($event_id);
			$user_ids = $event == null ? array() : $event->getInvitees();
			
			//Precurio_Utils::debug($user_ids);
			
			$ns = new Zend_Session_Namespace('temp');
			$ns->id = $event_id;
			$ns->selectedUsers = $user_ids;//array
			$ns->selectLabel = $this->view->translate("Currently Invited");
		}
		catch(Exception $e)
		{
			$log = Zend_Registry::get('log');
			$log->err($e);
		}
		
	}
	public function uploadAction()
	{
		$this->view->event_id = $this->getRequest()->getParam('id');
		$this->_helper->layout->disableLayout();
	}
	public function inviteesAction()
	{
		$this->_helper->layout->disableLayout();
		$this->_helper->viewRenderer->setNoRender();
		$selected_ids = $this->getRequest()->getParam('users');
		$selected_ids = explode(",",$selected_ids);
		$event_id = array_shift($selected_ids);
		
		$event = Events::getEvent($event_id);
		$event->setInvitees($selected_ids);
		
		$user_ids = $event->getInvitees();
		$ns = new Zend_Session_Namespace('temp');
		$ns->selectedUsers = $user_ids;//array
	}
	public function preDispatch()
	{
		$this->_helper->layout()->setLayout(PrecurioLayoutConstants::MAIN_C);
		$this->view->page = 'my';
		$ns = new Zend_Session_Namespace('temp');
		$ns->page = 'my';//content controller needs this
		$ns->callback = $this->getRequest()->getBaseUrl()."/event/my/invitees";//needed for the user select dialog
		$ns->selectedUsers = array();//clear the user select dialog.
	}

}
?>