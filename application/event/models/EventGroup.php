<?php

class EventGroup {
	public $events;
	public $name;
	function __construct($event)
	{
		$this->name = $event->getDate();
		$this->events = array();
		$this->push($event);
	}
	
	public function push($event)
	{
		array_push($this->events,$event);
	}

}

?>