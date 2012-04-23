<?php

class Report {
	
/**
     * Simplify the datagrid creation process
     * @return Bvb_Grid_Deploy_Table
     */
    static function grid ($id = 'myGrid')
    {
    	$baseUrl = Zend_Controller_Front::getInstance()->getRequest()->getBaseUrl();
    	$root = Zend_Registry::get('root');
        $config = new Zend_Config_Ini($root.'/application/configs/bvb.ini',null,array('allowModifications'=>true));
        $config->deploy->table->imagesUrl =  $baseUrl.$config->deploy->table->imagesUrl;

        $grid = Bvb_Grid::factory('Table', $config, $id);
        $grid->setEscapeOutput(false);
        $grid->setExport(array('pdf', 'word','excel','print'));
        $grid->setPagination((int) 10);
        $grid->addFormatterDir('Precurio/Grid/Formatter','Precurio_Grid_Formatter');
        #$grid->setCache(array('use' => array('form'=>false,'db'=>false), 'instance' => Zend_Registry::get('cache'), 'tag' => 'grid'));
        return $grid;
    }
    /**
     * Simplify the datagrid creation process
     * @return Bvb_Grid_Deploy_Ofc
     */
    static function chart($id='myChart')
    {
    	//$this->getRequest()->setParam('_exportTo', 'ofc');
		
		$root = Zend_Registry::get('root');
        $config = new Zend_Config_Ini($root.'/application/configs/bvb.ini');
        $chart = Bvb_Grid::factory('Ofc', $config, $id);
        $chart->setExport(array('ofc'));
        return $chart;
    }

}

?>