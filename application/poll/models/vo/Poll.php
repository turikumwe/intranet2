<?php

require_once ('Zend/Db/Table/Row/Abstract.php');
require_once ('poll/models/vo/PollOption.php');
class Poll extends Zend_Db_Table_Row_Abstract {
	public function getQuestion()
	{
		return $this->title;
	}
	public function userHasVoted($user_id)
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::USER_VOTES));
		$vote = $table->fetchRow($table->select()->where('poll_id = ?',$this->id)
											->where('user_id = ?',$user_id));
		return $vote != null;
	}
	public function newVote($option_id)
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::POLL_OPTIONS,'rowClass'=>'PollOption'));
		$option = $table->find($option_id)->current();
		$option->num_votes += 1;
		$option->save();
		
		
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::USER_VOTES));
		$data['poll_id'] = $this->id;
		$data['poll_option_id'] = $option_id;
		$data['user_id'] = Precurio_Session::getCurrentUserId();
		$data['date_created'] = Precurio_Date::now()->getTimestamp();
		$row = $table->createRow($data);
		$row->save();
	}
	private $options;
	public function getOptions()
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::POLL_OPTIONS,'rowClass'=>'PollOption'));
		$this->options = $table->fetchAll($table->select()->where('poll_id = ?',$this->id));
		if($this->options->count() <2)
			return $this->options;

		$temp = array();
		foreach($this->options as $option)
		{
			$temp[] = $option;
			
		}
		
		if($this->randomise_options)
		{
			
			shuffle($temp);
		}
		return $temp;
	}
	
	public function getPercentageOfTotal($option)
	{
		return @round(($option->num_votes/$this->getTotalVotes()) * 100); 
	}
	private $total;
	public function getTotalVotes()
	{
		if(!Precurio_Utils::isNull($this->total))
			return $this->total;
		$options = $this->getOptions();
		$total = 0;
		foreach($options as $option)
		{
			$total += $option->num_votes;
		}
		$this->total = $total;
		return $this->total;
	}
	public function getExpiringTime()
	{
		$tr = Zend_Registry::get('Zend_Translate');
		if(Precurio_Utils::isNull($this->end_date))
			return $tr->translate("Never");
		return new Precurio_Date($this->end_date); 
	}
	
}

?>