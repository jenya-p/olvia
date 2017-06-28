<?php

namespace Application\Controller;

use Application\Model\Content\TagDb;
use Application\Model\Courses\CourseDb;
use Application\Model\Courses\EventDb;
use Application\Model\Courses\TarifsDb;
use Application\Model\MasterDb;
use Common\SiteController;
use Common\Traits\LoggerAware;
use Common\Traits\LoggerTrait;
use ZfAnnotation\Annotation\Controller;
use ZfAnnotation\Annotation\Route;
use Zend\View\Model\JsonModel;
use Zend\Mvc\Console\View\ViewModel;
use Common\Db\Select;
use Application\Model\Orders\OrdersDb;
use Application\Model\Content\ReviewDb;

/**
 * @Controller
 */
class CoursesController extends SiteController implements LoggerAware{

	use LoggerTrait;

	const ORDER_STATUS_EXPIRED 	=	'expired';
	const ORDER_STATUS_AVAILABLE =	'available';
	const ORDER_STATUS_PAIED 	=	'paied';
	const ORDER_STATUS_NEED_PAY =	'need_pay';
	const ORDER_STATUS_FREE 	=	'free';
	
	
	const ARTICLE_COUNT_IN_DIVISION = 6;

	/** @var EventDb */
	var $eventDb;

	/** @var CourseDb */
	var $courseDb;

	/** @var TagDb */
	var $tagDb;
	
	/** @var MasterDb */
	var $masterDb;
	
	/** @var TarifsDb */
	var $tarifsDb;
	
	/** @var OrdersDb */
	var $ordersDb;
	
	public function init(){
		$this->eventDb = $this->serv(EventDb::class);
		$this->courseDb = $this->serv(CourseDb::class);
		$this->masterDb = $this->serv(MasterDb::class);		
		$this->tarifsDb = $this->serv(TarifsDb::class);
		$this->ordersDb = $this->serv(OrdersDb::class);
		$this->tagDb = $this->serv(TagDb::class);
	}


	/**
	 * 	@Route(name="course-view",route="/course/:alias")
	 */
	public function courseViewAction() {

		/* @var $reviewDb ReviewDb */
		$reviewDb = $this->serv(ReviewDb::class);
		
		$alias = $this->params('alias', null);
		
		$item = $this->courseDb->getByAlias($alias);
		if($item == false){
			return $this->notFoundAction();
		}
		
		$item['masters'] = $this->masterDb->getCourseMasters($item['id']);
		$item['tags'] = $this->tagDb->getCourseTags($item['id']);
		
		$ret = ['item' => $item];
		$this->layout()->admin_url = $this->url()->fromRoute('private/course-edit', ['id' => $item['id']]);
		
		$filter = ['course_id' => $item['id']];		
		
		// Serch for normal events and shedule
		$sheduleBounds = $this->eventDb->getSheduleBounds($filter, false);

		if(!empty($sheduleBounds)){
			$bounds = $this->getBoundsFromQuery($sheduleBounds);
			$shedule = $this->eventDb->getShedule($filter, $bounds, $this->identity()->id);			
		}
		
		if(!empty($shedule)){
			if(isset($shedule[0]['tarifs'])){
				$tarifs = $shedule[0]['tarifs'];
			}
			$bounds['from'] = max($bounds['from'], $shedule[0]['date']);
		}
		
		if(!empty($shedule)){
			$ret['shedule'] = $shedule;
			$ret['shedule_bounds'] = $sheduleBounds;
			$ret['tarifs'] = $tarifs;
			$ret['calendar_date'] = $bounds['from'];
			
		} else {

			// Search for announcements
			$announces = $this->eventDb->getCourseAnnouncements($item['id']);
			if(!empty($announces)){
				$ret['announce'] = $announce = $announces[0];
				$ret['anounce_masters'] = $this->masterDb->getEventMasters($announce['id']);
			} else {

				// Search similar alternate courses
				$similar = $this->courseDb->getActualSimilarCourse($item['id']);
				if(!empty($similar)){
					$similar['tags'] = $this->tagDb->getCourseTags($similar['id']);
					$similar['masters'] = $this->masterDb->getCourseMasters($similar['id']);
					$ret['similar'] = $similar;
				
				}
			}				
		}
				
		$reviewFilter= [
				'subject' => [
						'entity' => 'course',
						'item_id' => $item['id']
				]];		
		$ret['review_items'] =	$reviewDb->getItems($reviewFilter, 1, 2);
		$ret['review_totals'] =	$reviewDb->getTotals($reviewFilter);
		
		return $ret;
	}
		

	/**
	 * 	@Route(name="course-calendar-part",route="/course-calendar-part/:id[/:month]")
	 */
	public function calendarPartAction(){
		$id = $this->params('id', null);
		
		$item = $this->courseDb->get($id);
		if($item == false){
			return $this->notFoundAction();
		}		
		$filter = ['course_id' => $item['id']];		
		$sheduleBounds = $this->eventDb->getSheduleBounds($filter, false);
		if(!empty($sheduleBounds)){
			$bounds = $this->getBoundsFromQuery($sheduleBounds);
			$shedule = $this->eventDb->getShedule($filter, $bounds, $this->identity()->id);
		}
		if(!empty($shedule) && isset($shedule[0]['tarifs'])){
			$tarifs = $shedule[0]['tarifs'];
		}
		
		$this->layout()->admin_url = $this->url()->fromRoute('private/course-edit', ['id' => $item['id']]);
		
		
		$vm = new ViewModel([
				'item' => $item,
				'shedule' => $shedule,
				'shedule_bounds' => $sheduleBounds,
				'tarifs' => $tarifs, 
				'calendar_date' => $bounds['from'], 
		]);
		$vm->setTemplate('application/courses/course-view.order.calendar.phtml');
		$vm->setTerminal(true);
		return $vm;
		
	}
	
	
	/**
	 * 	@Route(name="course-shedule-part",route="/course-shedule-part/:id[/:month]")
	 */
	public function shedulePartAction(){
		$id = $this->params('id', null);
		$month = $this->params('month', date('y-m'));
	
		$item = $this->courseDb->get($id);
		if($item == false){
			return $this->notFoundAction();
		}
		$filter = ['course_id' => $item['id']];
		$sheduleBounds = $this->eventDb->getSheduleBounds($filter, false);
		if(!empty($sheduleBounds)){
			$bounds = $this->getBoundsFromQuery($sheduleBounds);
			$shedule = $this->eventDb->getShedule($filter, $bounds, $this->identity()->id);
		}
	
		$this->layout()->admin_url = $this->url()->fromRoute('private/course-edit', ['id' => $item['id']]);
	
	
		$vm = new ViewModel([
				'item' => $item,
				'shedule' => $shedule,
				'shedule_bounds' => $sheduleBounds,
				'calendar_date' => $bounds['from'],
		]);
		$vm->setTemplate('application/courses/course-view.shedule.phtml');
		$vm->setTerminal(true);
		return $vm;
	
	}
	
	
	
	/**
	 * 	@Route(name="course-tarifs-part",route="/course-tarifs-part/:id")
	 */
	public function tarifsPartAction(){
		$dateId = $this->params('id', null);
		
		$date = $this->eventDb->getDate($dateId);		

		if(empty($date)){			
			return $this->notFoundAction();
		}
		$date = $date[0];
		
		$this->eventDb->buildSheduleRecord($date, $this->identity()->id);

		$vm = new ViewModel([
			'tarifs' => $date['tarifs'],
		]);
		$vm->setTemplate('application/courses/course-view.order.tarifs.phtml');
		$vm->setTerminal(true);
		return $vm;
	
	}
	
		
	public function getBoundsFromQuery($sheduleBounds = null){
		$month = $this->params('month', null);
		
		if(empty($month)){
			$calendarDate = strtotime(date('y-m').'-1');
			if(!empty($sheduleBounds['start'])){
				$from = strtotime(date('d.m', $sheduleBounds['start']).'-01');
				$calendarDate = max($calendarDate, $from);
			}
		} else {
			$calendarDate = strtotime($month.'-01');
		}
		
		$bounds = [
			'from' => $calendarDate,
			'to' => strtotime('+ 1 month', $calendarDate),
			'month' => $month
		];
		if(!empty($sheduleBounds['end'])){
			$bounds['to'] = min($bounds['to'], $sheduleBounds['end']);
		}
		
		return $bounds;
	}
	
	
}
