<?php

/**
 * PastController
 * 
 * @author
 * @version 
 */

require_once 'Zend/Controller/Action.php';
require_once ('event/models/Events.php');
require_once ('event/models/EventGroup.php');
require_once 'user/models/UserUtil.php';
class Event_PastController extends Zend_Controller_Action {
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
			if($endDate->getTimestamp() > Precurio_Date::now()->getTimestamp())
				continue;//the event hasnt really passed. it isongoing
			if(isset($eventGroups[$event->start_date]))
				$eventGroups[$event->start_date]->push($event);
			else
				$eventGroups[$event->start_date] = new EventGroup($event);
		}
		$this->view->cpage = $params['cpage'];
		$this->view->eventGroups = $eventGroups;
		
//		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::LOCATIONS,'rowClass'=>'Location'));
//		$location = $table->fetchRow($table->select()->where('id= ? ',$location_id));
//		$this->view->nav = 'Location -> '.$location->getTitle().($work == null ? '' : ($work == 0 ? ' : Event Type -> Personal' : ' : Event Type -> Work') );
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
		$this->view->page = 'past';
		$ns = new Zend_Session_Namespace('temp');
		$ns->page = 'past';//content controller needs this
	}

}
?>