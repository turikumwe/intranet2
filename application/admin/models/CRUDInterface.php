<?php

interface CRUDInterface 
{
	function generateList($searchText);
	
	function getPageTitle();
	
	function getTableName();
	
	function generateHeader();
	
	function addAction();
	
	function editAction();
	
	function deleteAction();
	
	function viewAction();
	
	function submitAction();
	function translate($str);
	function getForm($item = null,$viewMode = false);
	
	/**
	 * This function is called after the submit function, can be overriden to perform 
	 * specific post-insert tasks
	 * @param $params Array, Parameters that were passed to the submit function
	 * @return null
	 */
	function postSubmit($params);
	
	/**
	 * This function is called before the submit function, can be overriden to perform 
	 * specific pre-insert tasks
	 * @param $params Array, Parameters that will be passed to the submit function
	 * @return null
	 */
	function preSubmit(&$params);
	
}

?>