<?php
require_once ('admin/controllers/GroupController.php');
class Admin_LocationController extends Admin_GroupController {
	function generateHeader() {
		return array('',$this->translate('Title'),$this->translate('Description'));
	}

	function generateList($searchText) 
	{
		$userUtil = new UserUtil();
		$items = $userUtil->getGroups();
		
		$arr = array();
		$i = 1;
		foreach($items as $item)
		{
			if(!$item->is_location)continue;
			
			$location = $item->getLocation();
			if(!$location->active)continue;
			
			
			if(!Precurio_Utils::isNull($searchText))
			{
				if(stripos($item->title,$searchText)===FALSE)
				{
					if(stripos($item->description,$searchText)===FALSE)
					{
						continue;
					}
				}
				
			}
			
			$arr[] = array($i++,'title'=>$item->title,'description'=>$item->description,'id'=>$item->id);
		}
		return $arr;
	}
	
	function getForm($item = null, $viewMode = false) {
		$form = parent::getForm($item,$viewMode);
		$form->removeElement('is_location');
		$form->removeElement('is_department');
		$form->removeElement('is_role');
		
		$form->addElement('hidden', 'is_location', array(
				'validators' => array(
				),
				'decorators'=>array(
				'ViewHelper',
				'FormElements'
				),
				'value'=>1,
				));
				
				
		$form->setAction($this->getRequest()->getBaseUrl().'/admin/location/submit');	
		return $form;
	}
	
	function getPageTitle() {
	 return $this->translate("Locations");
	}
	
	function getTableName() {
		return PrecurioTableConstants::GROUPS;
	}


}

?>