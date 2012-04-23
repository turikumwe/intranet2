<?php
/**
 *
 * @author Brain
 * @version 
 */
require_once 'Zend/View/Interface.php';

/**
 * ChatHelper helper
 *
 * @uses viewHelper Chat_View_Helper
 */
class Chat_View_Helper_ChatHelper {
	
	/**
	 * @var Zend_View_Interface 
	 */
	public $view;
	
	/**
	 *  
	 */
	public function chatHelper() {
		return $this;
	}
	public function getConfig()
	{
		$config = Zend_Registry::get('config');
		$liveConfig = $config->live;
		$content = 'var domain='+$liveConfig->domain + ';
						var service=' + $liveConfig->service + ';
						var username="mayor";';
		return $content;
	}
	/**
	 * Sets the view field 
	 * @param $view Zend_View_Interface
	 */
	public function setView(Zend_View_Interface $view) {
		$this->view = $view;
	}
}
