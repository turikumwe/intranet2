<?php
/**
 *
 * @author Brain
 * @version 
 */
require_once 'Zend/View/Interface.php';
require_once 'user/models/UserUtil.php';
require_once ('task/models/Tasks.php');
require_once ('event/models/Events.php');
require_once ('forum/models/vo/Topic.php');

/**
 * MyPage helper
 *
 * @uses viewHelper User_View_Helper
 */
class User_View_Helper_MyPage {
	
	/**
	 * @var Zend_View_Interface 
	 */
	public $view;
	
	/**
	 *  
	 */
	public function myPage() {
		return $this;
	}
	public function getImportantStuffs($limit = 10)
	{
		//there are all ordered by the most recent
		$tasks = $this->getTasks();
		$events = $this->getEvents();
		$status = $this->getStatus();
		$appointments = $this->getAppointments();
		$telMessages = $this->getTelephoneMessages();
		$topics = $this->getUserActiveTopics();
		
		$temp = array();
		
		$temp = array_merge($tasks,$events,$status, $telMessages, $appointments,$topics);
		usort($temp,'self::sortFn');
		$temp = array_reverse($temp);
		return $temp;
		
	}
	public static function sortFn($x,$y)
	{
		$propx = self::getSortProperty($x);
		$propy = self::getSortProperty($y);
		$xdate = new Precurio_Date($x->{$propx});
		$ydate = new Precurio_Date($y->{$propy});
		if ($xdate->getTimestamp() == $ydate->getTimestamp() )
		  return 0;
		 else if ( $xdate->getTimestamp() > $ydate->getTimestamp() )
		  return -1;
		 else
		  return 1;
	}
	public static function getSortProperty($obj)
	{
		if(is_a($obj,'Task'))return 'end_time';
		if(is_a($obj,'Event'))return 'start_timestamp';
		if(is_a($obj,'TelMessage'))return 'date_logged';
		if(is_a($obj,'Appointment'))return 'appointment_date';
		return 'date_created';//status will use this
	}
	private function getTasks()
	{
		$tasks = new Tasks();
		$tasks = $tasks->getAllTasks();
		$temp = array();
		foreach($tasks as $task)
		{
			if(!$task->isComplete())
				$temp[] = $task;
		}
		return $temp;
	}
	private function getEvents()
	{
		$events = Events::getEvents(Precurio_Session::getCurrentUserId(),null,null,false);
		$temp = array();
		foreach($events as $event)
		{
			$temp[] = $event;
		}
		return $temp;
	}
	private function getStatus()
	{
		$userFn = new UserUtil();
		$status = $userFn->getRecentStatus(2);
		$temp = array();
		foreach($status as $obj)
		{
			$temp[] = $obj;
		}
		return $temp;
	}
	
	private function getTelephoneMessages()
	{
		$config = Zend_Registry::get('config');
		if($config->module->mod_visitor <> 1)return array();
		$telMessages = new TelMessages();
		$telMessages = $telMessages->getTelephoneMessages(true);
		$temp = array();
		foreach($telMessages as $telMessage)
		{
			$temp[] = $telMessage;
		}
		return $temp;
	}
	
	private function getUserActiveTopics()
	{
		$userId = Precurio_Session::getCurrentUserId();
		
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::FORUM_TOPICS, 'rowClass'=>'Topic'));
		$select = $table->select(false);
		
		$select->setTable($table)->setIntegrityCheck(false);			
			
		$select = $select->distinct()->from(array('a' => PrecurioTableConstants::FORUM_TOPICS))
			->join(array('b' => PrecurioTableConstants::FORUM_POSTS), 'a.id = b.topic_id', array())	
												
			->where("a.active = 1 AND b.user_id = {$userId}");
			
		$topics = $table->fetchAll($select);
		$temp = array();
		foreach($topics as $topic)
			if( $topic->hasNewPost() )
				$temp[] = $topic;
				
		return $temp;
	}
	
	private function getAppointments()
	{
		$config = Zend_Registry::get('config');
		if($config->module->mod_visitor <> 1)return array();
		$appointments = new Appointments();
		$appointments = $appointments->getUpcomingAppointments(true);
		$temp = array();
		foreach($appointments as $appointment)
		{
			$temp[] = $appointment;
		}
		return $temp;
	}
	/**
	 * Sets the view field 
	 * @param $view Zend_View_Interface
	 */
	public function setView(Zend_View_Interface $view) {
		$this->view = $view;
	}
	
}
