<?php

/**
 * UserController
 * 
 * @author
 * @version 
 */

require_once 'Zend/Controller/Action.php';
require_once 'report/models/Report.php';
require_once 'report/models/AnalyticsReport.php';

class Report_UserController extends Zend_Controller_Action {
	public function translate($str)
	{
		return $this->view->translate($str);
	}
	/**
	 * The default action - show the home page
	 */
	public function indexAction() {
		$grid = Report::grid('user');
		
		$analytics = new AnalyticsReport();
		
		$data = $analytics->getUserList();
				
		$grid->setSource(new Bvb_Grid_Source_Array($data));
		
		$grid->updateColumn('date_created',array('title'=>$this->translate('Date Joined')));
		
		$grid->updateColumn('user_id',array('hidden'=>true));
		
		$grid->updateColumn('date_created',array('format'=>'date'));
		$grid->updateColumn('user_id',array('format'=>'User'));
		$grid->updateColumn('user_id',array('position'=>'1'));
		$grid->updateColumn('total',array('position'=>'2'));
		
		$grid->updateColumn('user_id',array('search'=>false));
		$grid->updateColumn('number_of_contacts',array('search'=>false));
		$grid->updateColumn('number_of_tasks',array('search'=>false));
		$grid->updateColumn('date_created',array('search'=>false));
		
		$baseUrl = $this->getRequest()->getBaseUrl();
		$grid->updateColumn('user',array('decorator'=>"<a href='$baseUrl/report/user/view/id/{{user_id}}'>{{user}}</a>"));
		
        $this->view->grid = $grid->deploy();
	}
	
	public function viewAction()
	{
		$user_id = $this->getRequest()->getParam('id');
		$analyticReport = new AnalyticsReport();
		$user = $analyticReport->getUserReport($user_id);
		$data = $analyticReport->getUserActivity($user_id);
		if(empty($data))
		{
			echo("<div id='noRecords' align='center'>".$this->view->translate(PrecurioStrings::NOTENOUGHREPORTDATA) ."</div>");
			return;
		}
		
		$chart = Report::chart('chart1');
        $chart->setChartType('line');
   

        $chart->setSource(new Bvb_Grid_Source_Array($data));
		
        $chart->setXLabels('day');
        $chart->addValues('total', array('set_colour' => '#44DD55', 'set_key' => $this->translate('Activity')));
		$chart->setTitle($this->translate('User Activity Chart').' ('.$this->translate('in the last 30 days').')');
        
        
        $chart->setChartDimensions(700,300);
        $chart->setMultiple(false);
        
        
        
        $data2 = array(
        	array('label'=> 'contents','value'=>$user->getNumOfContents()),
        	array('label'=> 'comments','value'=>$user->getNumOfComments()),
        	array('label'=> 'contacts','value'=>$user->getNumOfContacts()),
        	array('label'=> 'tasks','value'=>$user->getNumOfTasks()),
        	array('label'=> 'events','value'=>$user->getNumOfEvents())
        );
        
        $chart2 = Report::chart('chart2');
        $chart2->setChartType('pie');
   

        $chart2->setSource(new Bvb_Grid_Source_Array($data2));
		
        $chart2->setXLabels('label');
        $chart2->addValues('value', array('set_colours' => array('#22FF44','#889BFA','#EE9B0A','#A40DC5','#9FB2B7'), 'set_key' => $this->translate('Value'),'set_animate'=>true));
		$chart2->setTitle($this->translate("User Inventory"));
        
        
        $chart2->setChartDimensions(400,300);
        $chart2->setMultiple(false);
        
        
        $this->view->user = $user;
		if(Precurio_Session::getLicense()->isPro)$this->view->chart = $chart->deploy();
		$this->view->chart2 = $chart2->deploy();
	}
	public function preDispatch()
	{
		$this->_helper->layout()->setLayout(PrecurioLayoutConstants::MAIN_REPORT);
		
	}
}
?>