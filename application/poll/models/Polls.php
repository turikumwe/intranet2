<?php
require_once ('poll/models/vo/Poll.php');
class Polls {
	
	public static function getPolls()
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::POLL,'rowClass'=>'Poll'));
		$activePolls = $table->fetchAll($table->select()->where('active = 1'));
		return $activePolls;
	}
	/**
	 * Returns an active poll
	 * @return Poll
	 */
	public static function getActivePoll()
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::POLL,'rowClass'=>'Poll'));
		$activePolls = $table->fetchAll($table->select()->where('active = 1')
														->where('end_date > ?',Precurio_Date::now()->getTimestamp()));
		$poll = null;
		$user_id = Precurio_Session::getCurrentUserId();
		foreach($activePolls as $poll)
		{
			if(!$poll->userHasVoted($user_id))
				break;
		}
		return $poll;
	}
	/**
	 * Returns poll with specified id.
	 * @param $id int
	 * @return Poll
	 */
	public static function getPoll($id)
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::POLL,'rowClass'=>'Poll'));
		return $table->find($id)->current();
	}
}

?>