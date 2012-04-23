<?php

require_once ('news/models/vo/News.php');
class Article extends News {

	public function isPromotional()
	{
		if(Precurio_Utils::isNull($this->getImagePath()))
			$this->deactivate();//a promotional article must have an immage
		return $this->is_promotional ;
	}
	
	public function isFeatured()
	{
		if(Precurio_Utils::isNull($this->getImagePath()))
			$this->deactivate();//a promotional article must have an immage
		return $this->is_featured;
	}
	public function __call($method, $args) 
	{
     	return call_user_func_array(array($this->content, $method), $args);
    }
	
}

?>