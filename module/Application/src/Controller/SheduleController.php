<?php

namespace Application\Controller;

use Application\Model\Content\TagDb;
use Application\Model\Courses\CourseDb;
use Application\Model\Courses\EventDb;
use Application\Model\MasterDb;
use Common\SiteController;
use Common\Traits\LoggerAware;
use Common\Traits\LoggerTrait;
use ZfAnnotation\Annotation\Controller;
use ZfAnnotation\Annotation\Route;
use Zend\View\Model\ViewModel;
use Common\Db\Select;
use Zend\Db\Sql\Join;
use Zend\Http\Response;

/**
 * @Controller
 */
class SheduleController extends SiteController implements LoggerAware{

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
	
	
	var $filter = null;
	var $tag = null;
	var $audience = null;
	var $master = null;
	
	/** @var ViewModel */
	var $vm = null;
	
	public function init(){
		$this->eventDb = $this->serv(EventDb::class);
		$this->courseDb = $this->serv(CourseDb::class);
		$this->masterDb = $this->serv(MasterDb::class);
		$this->tagDb = $this->serv(TagDb::class);
	}

	/**
	 * 	@Route(name="shedule",route="/shedule-:type[/tag-:tag][/tag2-:tag2][/master-:master][/date-:date]",
	 * 		constraints={
	 * 			"tag": 		"[a-zA-z0-9-_]+",
	 * 			"master": 	"[a-zA-z0-9-_]+",
	 * 			"tag2": 	"[a-zA-z0-9-_]+",
	 * 			"date": 	"[0-9-_]+",
	 * 			"type": 	"(list|calendar|blocks)"
	 * 		},
	 * 		defaults={"type":"list"}) 
	 * */
	public function sheduleAction() {		
		
		$filter = $this->parseFilterFromQuery();
		
		if($filter instanceof Response){
			return $filter;			
		}
				
		$ret = $this->getCommonData();
		$ret['type'] = $type = $this->params('type');
		
		$this->vm = new ViewModel($ret);
		
		
		$masterOptions = $this->masterDb->options();
		$this->vm->setVariable('masterOptions', $masterOptions);
				
		if($type == 'blocks'){		
			$this->blocks();
			$this->vm->setTemplate('/application/shedule/shedule-blocks.phtml');
		} else if($type == 'calendar'){
			$this->calendar();
			$this->vm->setTemplate('/application/shedule/shedule-calendar.phtml');
		} else {
			$this->list();
			$this->vm->setTemplate('/application/shedule/shedule-list.phtml');
		}
		
		return $this->vm;
	}

	//////////////  BLOCKS
	private function blocks(){
		$sheduleBounds = $this->eventDb->getSheduleBounds($this->filter);
		if(!empty($sheduleBounds)){
			$bounds = $this->getMonthBoundsFromQuery();
			$dates = $this->eventDb->getShedule($this->filter, $bounds, $this->identity()->id);
		}
		
		$items = [];
		$courses = [];
		foreach ($dates as &$date){
			$courseId = $date['course']['id'];
			if(!array_key_exists($courseId, $items)){
				$items[$courseId] = [];
				
				$courses[$courseId] = $date['course'];
				$courses[$courseId]['tags'] = $this->tagDb->getCourseTags($courseId);
				$courses[$courseId]['masters'] = $this->masterDb->getCourseMasters($courseId);
				
			}
			$items[$courseId][] = $date;
		}

		$this->vm->setVariable('items', $items);
		$this->vm->setVariable('courses', $courses);
		$this->vm->setVariable('sheduleBounds', $sheduleBounds);
		$this->vm->setVariable('currentDate', $bounds['from']);
	}
	
	
	//////////////  			LIST
	
	
	private function list(){
		
		$sheduleBounds = $this->eventDb->getSheduleBounds($this->filter);
		if(!empty($sheduleBounds)){
			$bounds = $this->getWeekBoundsFromQuery();
			$dates = $this->eventDb->getShedule($this->filter, $bounds, $this->identity()->id);
		}
		
		$items = [];
		foreach ($dates as &$date){
			$dateStr = date('d-m-Y', $date['date']);
			if(!array_key_exists($dateStr, $items)){
				$items[$dateStr] = [];
			}
			$items[$dateStr][] = $date;			
		}
		
		$this->vm->setVariable('items', $items);
		$this->vm->setVariable('sheduleBounds', $sheduleBounds);
		$this->vm->setVariable('currentDate', $bounds['from']);
	}
	
	//////////////  									CALENDAR
	
	private function calendar(){
		
		$sheduleBounds = $this->eventDb->getSheduleBounds($this->filter);
		if(!empty($sheduleBounds)){
			$bounds = $this->getMonthBoundsFromQuery();
			$dates = $this->eventDb->getShedule($this->filter, $bounds, $this->identity()->id);
		}
		
		$items = [];
		foreach ($dates as &$date){
			$dateStr = date('d-m-Y', $date['date']);
			if(!array_key_exists($dateStr, $items)){
				$items[$dateStr] = [];
			}
			$items[$dateStr][] = $date;
		}
		
		$this->vm->setVariable('items', $items);
		$this->vm->setVariable('sheduleBounds', $sheduleBounds);
		$this->vm->setVariable('currentDate', $bounds['from']);
	}
	
	
	public function parseFilterFromQuery() {
		
		$this->filter = [];
		
		$tags = [];
		
		$tagId = $this->params('tag2', null);
		if(!empty($tagId)){
			$this->tag2 = $this->tagDb->get($tagId);
			if(empty($this->tag2)){
				return $this->redirect()->toRoute();
			}
			$tags[] = $tagId;
			$this->filter['tag2'] = $tagId;			
		}
		
		
		$tagId = $this->params('tag', null);		
		if(!empty($tagId)){
			$this->tag = $this->tagDb->get($tagId);
			if(empty($this->tag)){
				return $this->redirect()->toRoute();
			}
			$tags[] = $tagId;
			$this->filter['tag'] = $tagId;
		}
		$this->filter['tag_ids'] = $tags;
		
		
		$masterId = $this->params('master', null);
		if(!empty($masterId)){
			$this->master = $this->masterDb->get($masterId);
			if(empty($this->master)){
				return $this->redirect()->toRoute();
			}
			$this->filter['master'] = $masterId;
		}
		
		
	}
	
	
	private function getCommonData(){
		$ret = [
			'filter_tag' => $this->tag ,
			'filter_tag2' => $this->tag2,
			'filter_master' => $this->master
		];
				
		$tagGroups = $this->tagDb->getCouseFilterTags();
		$ret['filter_tag_options'] = $tagGroups;
		
		$anotherGroup = null;
		foreach ($tagGroups as $key => $tg){			
			if($tg['items'][0]['group_id'] == 3){ // Аудитория				
				$ret['filter_tag2_options'] = $tg;
				unset($tagGroups[$key]);
			} 
		}
		
		return $ret;
	}
	
	
	public function getWeekBoundsFromQuery(){
		$date = $this->params('date', null);
		if(empty($date)){
			$date = time();
		} else {
			$date = strtotime($date);
		}
		$d = strtotime('midnight', $date);
		
		$w  = intval(date('w', $date));
		if($w == 0){
			$w = 6;
		} else {
			$w --;
		}
		$d = $d - 60*60*24*$w;
				
		$bounds = [
				'from' => $d,
				'to' => $d + 7*24*60*60
		];
		return $bounds;		
	}
	
	public function getMonthBoundsFromQuery(){
		$month = $this->params('date', date('y-m'));
		$calendarDate = strtotime($month.'-01');
		
		$bounds = [
				'from' => $calendarDate,
				'to' => strtotime('+ 1 month', $calendarDate),
				'month' => $month
		];
		
		return $bounds;
	}
	
}
