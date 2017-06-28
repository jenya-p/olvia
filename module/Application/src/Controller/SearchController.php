<?php

namespace Application\Controller;

use Application\Model\MasterDb;
use Application\Model\Content\ContentDb;
use Application\Model\Content\TagDb;
use Application\Model\Courses\CourseDb;
use Common\SiteController;
use Common\Traits\LoggerAware;
use Common\Traits\LoggerTrait;
use Application\Model\Courses\EventDb;
use ZfAnnotation\Annotation\Controller;
use ZfAnnotation\Annotation\Route;
use Application\Model\SearchDb;
use Application\Model\Content\DivisionDb;
use Zend\View\Model\JsonModel;

/**
 * @Controller
 */
class SearchController extends SiteController implements LoggerAware{

	use LoggerTrait;
	
	
	/** @var SearchDb */
	var $searchDb = null;	
	/** @var MasterDb */
	var $masterDb = null;
	/** @var CourseDb */
	var $courseDb = null;
	/** @var ContentDb */
	var $contentDb = null;
	/** @var TagDb */
	var $tagDb = null;	
	/** @var EventDb */
	var $eventDb;
	/** @var DivisionDb */
	var $divisionDb;
	
	public function init(){
		$this->searchDb = $this->serv(SearchDb::class);
		$this->masterDb = $this->serv(MasterDb::class);
		$this->courseDb = $this->serv(CourseDb::class);
		$this->contentDb = $this->serv(ContentDb::class);
		$this->tagDb = $this->serv(TagDb::class);
		$this->eventDb =  $this->serv(EventDb::class);
		$this->divisionDb =  $this->serv(DivisionDb::class);
		
	}
	
	/**
	 * 	@Route(name="search-index",route="/search", type="Literal")
	 */
	public function searchAction(){
		$query = $this->params()->fromQuery('query', null);
					
		$searchResults = $this->searchDb->search($query);
		
		foreach ($searchResults as &$sItem){
			$this->buildItem($sItem);
		}
		
		return [
			'search_results' => $searchResults,
			'query' => $query,
		];
	}
	
	
	/**
	 * 	@Route(name="search-suggestion",route="/search-suggestion", type="Literal")
	 */
	public function suggestionAction(){
		$query = $this->params()->fromQuery('query', null);
		
		$searchResults = $this->searchDb->search($query);
		
		foreach ($searchResults as $item) {
			$suggestion = [
				'value' => $item['ru_1'],
				'entity' => $item['entity'],					
			];
			if ($entity == 'course'){
				$item = $this->courseDb->get($itemId);
				$suggestion['url'] = $this->url()->fromRoute('course-view', 	['alias' => $item['alias']]);
				
			} else if ($entity == 'master'){
				$item =	$this->masterDb->get($itemId);
				$suggestion['url'] = $this->url()->fromRoute('master-view', 	['alias' => $item['alias']]);
				
			} else if ($entity == 'content'){				
				$item = $this->contentDb->get($itemId);
				$suggestion['url'] = $this->url()->fromRoute('content-article', ['alias' => $item['alias']]);
				
			} 					
			$suggestions[] = $suggestion;
		}
		
		
		return new JsonModel([
				"query" => $query,
				"suggestions" => $suggestions]);
	}
	
	
	
	private function buildItem(&$sItem){
		
		$itemId = $sItem['item_id'];
		$entity= $sItem['entity'];
		if ($entity == 'course'){
			
			$item = $this->courseDb->get($itemId);
			$item['masters'] = $this->masterDb->getCourseMasters($itemId);
			$item['tags'] = $this->tagDb->getCourseTags($itemId);
			$item['dates'] = $this->eventDb->getCourseDates($itemId);
			if(empty($item['dates'])){
				$item['announcements'] = $this->eventDb->getCourseAnnouncements($itemId);
			}			
			$sItem['item'] = $item;
			
		} else if ($entity == 'master'){
			$item =	$this->masterDb->get($itemId);
			$item['courses'] = $this->courseDb->getMasterCourses($itemId);
			
			$sItem['item'] = $item;
			
		} else if ($entity == 'content'){
			
			$item = $this->contentDb->get($itemId);
			
			$item['tags'] = $this->tagDb->getContentTags($itemId);
			if(!empty($item['division_id'])){
				$item['division'] = $this->divisionDb->get($item['division_id']);
			}
			$sItem['item'] = $item;
			
		} 		
		
	}
	
	
}
