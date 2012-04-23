<?php

/**
 * ContentController
 * 
 * @author
 * @version 
 */

require_once 'Zend/Controller/Action.php';
require_once 'report/models/Report.php';
require_once 'report/models/AnalyticsReport.php';
require_once 'cms/models/vo/Content.php';

class Report_ContentController extends Zend_Controller_Action {
	
	public function translate($str)
	{
		return $this->view->translate($str);
	}
	/**
	 * The default action - show the home page
	 */
	public function indexAction() {
		return $this->_redirect('/report/content/top');
	}
	public function topAction()
	{
		$now = time();
		$threeMonthsAgo = $now - (60 * 60 * 24 * 7 * 13); //3months == 13 weeks
		
		
		$chart = Report::chart();
        $chart->setExport(array('ofc'));

        $chart->setChartType('bar_filled');
       
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::CONTENT));
		
		$select = $table->select(true);
		$select->setIntegrityCheck(false);
		
		$select = $select->distinct()
				->where('date_created > ?',$threeMonthsAgo)
				->where('active=1')
				->order('num_of_hits DESC')
				->order('rating DESC')
				->limit(20);
		if($table->fetchAll($select)->count() < 5)
		{
			echo("<div id='noRecords' align='center'>".$this->view->translate(PrecurioStrings::NOTENOUGHREPORTDATA)."</div>");
			return;//no top contents, then definitely no most search content
		}
		
        $chart->setSource(new Bvb_Grid_Source_Zend_Select($select));
        
        $chart->setXLabels('title');
        $chart->addValues('num_of_hits', array('set_colour' => '#00FF00', 'set_key' => $this->translate('Number of Views'),'set_tooltip'=>$this->translate('Score')." = #val#"));
		$chart->setTitle($this->translate('Top Contents').' ('.$this->translate('within the last 3 months').')');
        
        $count = $table->fetchAll($select)->count();
        $x  =  $count * 100;
        if($x>900)$x=900; //900 is the maximum value
        if($x<300)$x=300; //300 is the minimum value
        
        $chart->setChartDimensions($x,350);
        $chart->setMultiple(false);
		$this->view->chart = $chart->deploy();
		

/**********************************************************************************/

		
		$grid = Report::grid('top');
		$analytics = new AnalyticsReport();
		
		$data = $analytics->getMostSearchedContent();
		
		if(empty($data))
		{
			echo("<div id='noRecords' align='center'>".$this->view->translate(PrecurioStrings::NOTENOUGHREPORTDATA) ."</div>");
			return;
		}
		
		$grid->setSource(new Bvb_Grid_Source_Array($data));
		
		$grid->updateColumn('title',array('title'=>$this->translate('Content Title')));
		$grid->updateColumn('user',array('title'=>$this->translate('Created By')));
		$grid->updateColumn('value',array('title'=>$this->translate('Number of Search Hits')));
		$grid->updateColumn('id',array('hidden'=>true));
		$grid->updateColumn('view_id',array('hidden'=>true));
		
		$baseUrl = $this->getRequest()->getBaseUrl();
		$grid->updateColumn('title',array('decorator'=>"<a href='$baseUrl/cms/view/details/c_id/{{id}}'>{{title}}</a>"));
		
        $this->view->grid = $grid->deploy();
		
			
	}
	
	public function userAction()
	{
		
	}
	public function activityAction()
	{
	}
	public function preDispatch()
	{
		$this->_helper->layout()->setLayout(PrecurioLayoutConstants::MAIN_REPORT);
		$this->view->page = $this->getRequest()->getActionName();
		
	}
	public function postDispatch()
	{
		$this->render('index');
	}

}
?>