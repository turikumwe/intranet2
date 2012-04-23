<?php

/**
 * OngoingController
 * 
 * @author
 * @version 
 */

require_once 'Zend/Controller/Action.php';
require_once ('event/models/Events.php');
require_once ('event/models/EventGroup.php');
require_once 'user/models/UserUtil.php';
class Event_OngoingController extends Zend_Controller_Action {
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
		
		$events = Events::getEvents($currentUser->getId(),$location_id,$work,true);
		$eventGroups = array();
		foreach($events as $event)
		{
			$endDate = new Precurio_Date($event->end_date);
			if($endDate->getTimestamp() < Precurio_Date::now()->getTimestamp())
				continue;//the event has really passed. no more ongoing
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
	public function uploadAction()
	{
		$this->view->event_id = $this->getRequest()->getParam('id');
		$this->_helper->layout->disableLayout();
	}
	public function preDispatch()
	{
		$this->_helper->layout()->setLayout(PrecurioLayoutConstants::MAIN_C);
		$this->view->page = 'ongoing';
		$ns = new Zend_Session_Namespace('temp');
		$ns->page = 'ongoing';//content controller needs this
	}

}
?>