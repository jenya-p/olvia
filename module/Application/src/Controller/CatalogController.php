<?php

namespace Application\Controller;

use Application\Model\Courses\CourseDb;
use Application\Model\Courses\EventDb;
use Common\SiteController;
use Common\Traits\LoggerAware;
use Common\Traits\LoggerTrait;
use Zend\View\Model\ViewModel;
use ZfAnnotation\Annotation\Controller;
use ZfAnnotation\Annotation\Route;
use Zend\View\Model\JsonModel;
use Application\Model\Content\TagDb;
use Application\Model\MasterDb;

/**
 * @Controller
 */
class CatalogController extends SiteController implements LoggerAware{

	
	const TYPE_COURSES = 'courses';
	const TYPE_ANNOUNCEMENTS = 'announcements';
	const TYPE_ARCHIVE = 'archive';
	
	use LoggerTrait;

	const ARTICLE_COUNT_IN_DIVISION = 6;

	/** @var EventDb */
	var $eventDb;

	/** @var CourseDb */
	var $courseDb;

	/** @var TagDb */
	var $tagDb;
	
	/** @var MasterDb */
	var $masterDb;
	
	
	const IPP = 16;
	
	public function init(){
		$this->eventDb = $this->serv(EventDb::class);
		$this->courseDb = $this->serv(CourseDb::class);
		$this->masterDb = $this->serv(MasterDb::class);
		$this->tagDb = $this->serv(TagDb::class);
	}


	/**
	 * 	@Route(name="catalog-index",route="/:type[/tag-:tag][/p-:p]", 
	 * 		constraints={
	 * 			"type": "(courses|archive|announcements)", 
	 * 			"tag": "[a-zA-z0-9-_]+", 
	 * 			"p": "[0-9]+"
	 * 		},
	 * 		defaults={"type":"courses"})
	 */
	public function indexAction() {		
		
		$filter = $this->parseFilterFromQuery();
		
		$query = $this->params()->fromQuery();
		
		$tagAlias = $this->params('tag', null);
		if(!empty($tagAlias)){
			$tag = $this->tagDb->getByAlias($tagAlias);
			if(!empty($tag)){				
				$filter['tags'][] = $tag['id'];
				$filter['tags'] = array_unique($filter['tags']);
			} else {
				return $this->redirect()->toRoute('catalog-index', ['type' => $filter['type']]);
			}
			if (count($filter['tags']) > 1){				
				$query ['tag'.$tag['id']] = '';
				return $this->redirect()->toRoute('catalog-index', ['p' => 1,'type' => $filter['type']], ['query' => $query]);
			}
		} else if (count($filter['tags']) == 1){
			$tagId = $filter['tags'][0];
			unset($query ['tag'.$tagId]);
			$tag = $this->tagDb->get($tagId);			
			return $this->redirect()->toRoute('catalog-index', ['tag' => $tag['alias'], 'p' => 1, 'type' => $filter['type']], ['query' => $query]);
		}
		
		$p = $this->params('p', null);
		if(empty($p)) { 
			return $this->redirect()->toRoute('catalog-index', ['p' => 1,'type' => $filter['type']], ['query' => $query], true);
		}
				
		$items = $this->courseDb->getItems($filter, $p,self::IPP );
		$totals = $this->courseDb->getTotals($filter);
		
		foreach ($items as &$item){
			$item['masters'] = $this->masterDb->getCourseMasters($item['id']);
			$item['tags'] = $this->tagDb->getCourseTags($item['id']);
			if($filter['type'] == self::TYPE_ANNOUNCEMENTS){
				$item['announcements'] = $this->eventDb->getCourseAnnouncements($item['id'], $filter);
			} else if($filter['type'] == self::TYPE_COURSES){
				$item['dates'] = $this->eventDb->getCourseDates($item['id'], $filter);
			}			
		}
		
		return new ViewModel([			
			'items' => $items,
			'totals' => $totals,
			'filter' => $filter,			
			'filter_tags' => $this->tagDb->getCouseFilterTags(),
			'query' => $query,
			'tag' => $tag,			
			'page' => $p,
			'pageCount' => ceil($totals['count'] / self::IPP),
		]);
	}

	/**
	 * @Route(name="catalog-search-preview",route="/courses/ajax-count")
	 */
	public function ajaxCountAction() {
				
		$filter = $this->parseFilterFromQuery();
		
		$tagAlias = $this->params('tag', null);
		if(!empty($tagAlias)){
			$tag = $this->tagDb->getByAlias($tagAlias);
			if(!empty($tag)){
				$filter['tags'][] = $tag['id'];
				$filter['tags'] = array_unique($filter['tags']);
			}
		}
		
		return new JsonModel([ 
				"result" => "ok",
				"count" => $this->courseDb->getTotals($filter)['count'] 
		]);
	}
	
	

	public function parseFilterFromQuery() {
		$re = '/^tag(\d{1,7})$/';
		$filter = [ ];
		$tagIds = [ ];
		foreach ( array_keys($this->params()->fromQuery()) as $name) {
			$matches = [ ];
			if (preg_match($re, $name, $matches)) {
				$tagIds [] = $matches[1];
			}
		}
		if(!empty($tagIds)){
			$filter['tags'] = $tagIds;
		}
		
		$dateRange = $this->params()->fromQuery('date_range', null);
		if(!empty($dateRange)){
			$filter['date_range'] = $dateRange;
		}		
		 
		$priceMax = $this->params()->fromQuery('price_max', null);
		if(!empty($priceMax) || $priceMax === '0'){
			$filter['price_max'] = $priceMax;
		}
			
		$query = $this->params()->fromQuery('query', null);
		if(!empty($query)){
			$filter['query'] = $query;
		}
		
		$filter['type'] = $this->params('type', 'courses');
		
		return $filter;
	}
	
	
	private function filterToQuery($filter){
		$query = [];
		if(!empty($filter['query'])){
			$query['query'] = $filter['query'];
		}
		
		if(!empty($filter['price_max'])){
			$query['price_max'] = $filter['price_max'];
		}
		
		if(!empty($filter['date_range'])){
			$query['date_range'] = $filter['date_range'];
		}
		
		foreach ($filter['tags'] as $tagId){
			$query['tag'.$tagId] = '';
		}
		return $query;
	}
	
}
