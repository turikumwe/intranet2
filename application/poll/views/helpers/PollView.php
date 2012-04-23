<?php
require_once ('poll/models/vo/Poll.php');
/**
 *
 * @author Brain
 * @version 
 */
require_once 'Zend/View/Interface.php';

/**
 * PollView helper
 *
 * @uses viewHelper Zend_View_Helper
 */
class Poll_View_Helper_PollView {
	
	/**
	 * @var Zend_View_Interface 
	 */
	public $view;
	
	/**
	 *  
	 */
	public function pollView() {
		return $this;
	}
	public function getActivePoll()
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::POLL,'rowClass'=>'Poll'));
		$activePolls = $table->fetchAll($table->select()->where('active = 1')
														->where('end_date < ?',Precurio_Date::now()->getTimestamp()));
		$user_id = Precurio_Session::getCurrentUserId();
		foreach($activePolls as $poll)
		{
			if(!$poll->userHasVoted($user_id))
				break;
		}
		return $poll;
	}

	/**
	 * Sets the view field 
	 * @param $view Zend_View_Interface
	 */
	public function setView(Zend_View_Interface $view) {
		$this->view = $view;
	}
}
