<?php

require_once ('cms/models/vo/Content.php');

class Advert extends Content {
	
	public function isWelcomeAdvert()
	{
		return $this->is_welcome_advert ;
	}
	public function canslide()
	{
		return $this->can_slide;
	}
	public function isActive()
	{
		//all adverts must have an image
		return $this->active && $this->content->isActive() && !Precurio_Utils::isNull($this->getImagePath()) ;	
	}
	public function __call($method, $args)
	{
     	return call_user_func_array(array($this->content, $method), $args);
    }
    
}

?>