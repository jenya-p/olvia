<?php

namespace Application\Controller;

use Application\Model\MasterDb;
use Common\SiteController;
use Common\Traits\LoggerAware;
use Common\Traits\LoggerTrait;
use Zend\View\Model\ViewModel;
use ZfAnnotation\Annotation\Controller;
use ZfAnnotation\Annotation\Route;
use Admin\Model\Users\MasterPricesDb;
use Application\Model\Content\DiplomDb;
use Application\Model\Content\ReviewDb;
use Common\Db\Select;
use Zend\View\Model\JsonModel;


/**
 * @Controller
 */
class MasterController extends SiteController implements LoggerAware{

	use LoggerTrait;
	
	/** @var MasterDb */
	var $masterDb;
	
	public function init(){
		$this->masterDb = $this->serv(MasterDb::class);
	}
	
	/**
	 * 	@Route(name="master-index",route="/masters", type="Segment")
	 */
	public function indexAction(){
		
		$page = intval($this->params()->fromQuery('p', 1));
		
		$vm = new ViewModel();
		
		$vm->setVariables([
				'items' => $this->masterDb->getItems(null, $page, 25 ),
				'totals' => $this->masterDb->getTotals(null),				
				'page' => $page
		]);
		
		if($this->getRequest()->isXmlHttpRequest()){
			$vm->setTerminal(true);
			$vm->setTemplate('/application/master/index.items.phtml');
		}
		
		return $vm;
	}
	
	
	
	/**
	 * 	@Route(name="master-view",route="/master/:alias", type="Segment")
	 */
	public function viewAction(){
		/* @var $masterPricesDb MasterPricesDb */
		$masterPricesDb = $this->serv(MasterPricesDb::class);
		
		/* @var $diplomDb DiplomDb */
		$diplomDb = $this->serv(DiplomDb::class);
		
		/* @var $reviewDb ReviewDb */
		$reviewDb = $this->serv(ReviewDb::class);
		
		$alias = $this->params('alias', null);
		$item = $this->masterDb->getByAlias($alias);
		
		if(empty($item)){
			return $this->notFoundAction();
		}
		$this->layout()->admin_url = $this->url()->fromRoute('private/master-edit', ['id' => $item['id']]);
		
		$reviewFilter= [
			'subject' => [
				'entity' => 'master',
				'item_id' => $item['id']
			]];
				
		return [			
			'item' => $item,
			'tarifs' => $masterPricesDb->getMasterPrices($item['id']),
			'diplomas' => $diplomDb->getMasterDiplomas($item['id']),
			'review_items' =>	$reviewDb->getItems($reviewFilter, 1, 2),
			'review_totals' =>	$reviewDb->getTotals($reviewFilter),
		];
	}
	
	
	
	/**
	 * @Route(name="master-suggestion",route="/master-suggestion")
	 */
	public function masterSuggestionAction(){
		$query = $this->params()->fromQuery('q');
		if(empty($query)){
			return new JsonModel([]);
		}
		 
		$select = new Select(['m' => 'users_masters']);
		$select->columns(['id', 'value' => 'name_'.$this->masterDb->lang()]);
				 
		$select->where->expression('concat(" ", LOWER(m.name_'.$this->masterDb->lang().')) like ?', "% ".mb_strtolower($query)."%");
		
		$select
			->order('m.name_'.$this->masterDb->lang().' asc')
			->limit(20);
	
		$suggestions = $select->fetchAll();
		 
		return new JsonModel([
				"query" => $query,
				"suggestions" => $suggestions]);
		 
	
	}

	

	
}
