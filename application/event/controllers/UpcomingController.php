<?php

/**
 * UpcomingController
 * 
 * @author
 * @version 
 */

require_once 'Zend/Controller/Action.php';
require_once ('event/models/Events.php');
require_once ('event/models/EventGroup.php');
require_once 'user/models/UserUtil.php';
class Event_UpcomingController extends Zend_Controller_Action {
	/**
	 * The default action - show the home page
	 */
	public function indexAction() 
	{
		$this->_forward('view');
	}
	public function viewAction()
	{
		$params = $this->getRequest()->getParams();
		$currentUser = UserUtil::getUser(Precurio_Session::getCurrentUserId());

		$location_id = empty($params['l_id']) ? null : $params['l_id'];
		
		if(!isset($params['w']))
			$params['w'] = null;
		$work = $params['w'];
		
		if(!isset($params['cpage']))
			$params['cpage'] = 1;
		
		$events = Events::getEvents($currentUser->getId(),$location_id,$work,false);
		$eventGroups = array();
		foreach($events as $event)
		{
			if(isset($eventGroups[$event->start_date]))
				$eventGroups[$event->start_date]->push($event);
			else
				$eventGroups[$event->start_date] = new EventGroup($event);
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
	}
	public function statusAction()
	{
		$this->_helper->layout->disableLayout();
		$this->_helper->viewRenderer->setNoRender();
		
		$event_id = $this->getRequest()->getParam('e_id');
		$status = $this->getRequest()->getParam('s');
		$user_id = Precurio_Session::getCurrentUserId();
		
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::EVENT_STATUS));
		$e_status = $table->fetchRow($table->select()->where('event_id = ?',$event_id)
												->where('user_id = ?',$user_id));
												
		if($e_status)
		{
			$e_status->status = $status;
			$e_status->save();
		}
		else
		{
			$data = array('event_id'=>$event_id,'user_id'=>$user_id,'status'=>$status,'date_created'=>Precurio_Date::now()->getTimestamp());
			$row = $table->createRow($data);
			$row->save();
			
		}
		if($status == Event::ATTENDING)
			echo $this->view->translate(PrecurioStrings::ATTENDINGEVENT);
		if($status == Event::NOTSURE)
			echo $this->view->translate(PrecurioStrings::UNSUREEVENT);
		if($status == Event::NOTATTENDING)
			echo $this->view->translate(PrecurioStrings::NOTATTENDINGEVENT);
			
			 
	}
	public function uploadAction()
	{
		$this->view->event_id = $this->getRequest()->getParam('id');
		$this->_helper->layout->disableLayout();
	}
	public function preDispatch()
	{
		$this->_helper->layout()->setLayout(PrecurioLayoutConstants::MAIN_C);
		$this->view->page = 'upcoming';
		$ns = new Zend_Session_Namespace('temp');
		$ns->page = 'upcoming';//content controller needs this and partial_event
	}
	
}
?>