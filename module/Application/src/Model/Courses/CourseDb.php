<?
namespace Application\Model\Courses;

use Admin\Model\Courses\EventDb as AdminEventDb;
use Application\Controller\CatalogController;
use Common\CRUDListModel;
use Common\Db\Multilingual;
use Common\Db\MultilingualTrait;
use Common\Db\Select;
use Common\Db\Table;
use Common\Traits\ServiceManagerTrait;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Join;
use Application\Model\Content\TagDb;
use Application\Model\MasterDb;
use Common\Traits\Initializable;

class CourseDb extends Table implements CRUDListModel, Multilingual{

	use MultilingualTrait, ServiceManagerTrait;
	
	protected $table = 'courses';

	public function __construct(Adapter $adapter) {
		$this->adapter = $adapter;
		$this->langFields(['title', 'summary', 'body', 'seo_title', 'seo_description', 'seo_keywords']);
	}
	
	public function getMaxPrice(){
		return 40000;
	}
	
	// CRUD list implementation
	/**
	 * @param array $filter
	 * @return Select
	 */
	public function getSelect($filter){
	
		$select = new Select(['c' => 'courses']);
			
		$select->where->equalTo('c.status', 1);
		
		if(!empty($filter['query'])){
			$query = mb_strtolower($filter['query']);
			$select->where->expression('concat(" ", LOWER(c.title_'.$this->lang.')) like ?', "% ".$query."%");
		}
		
		if(!empty($filter['tags'])){
			$select->join(['tr' => 'content_tag_refs'], 'tr.item_id = c.id', [], Join::JOIN_INNER);
			$select->where
				->equalTo('tr.entity', "course")
				->and->in('tr.tag_id', $filter['tags']);
		}
		
		
		if($filter['type'] == CatalogController::TYPE_ANNOUNCEMENTS){			
			// Есть мероприятия с типом анонс
			$ids = $this->getActualAnnouncmentIds();
						
		} else if($filter['type'] == CatalogController::TYPE_ARCHIVE){
			// Нет активных мероприятий
			$ids = $this->getArchiveCoursesIds();
			
		} else {
			// Есть активные мероприятия
			$ids = $this->getActualCoursesIds();
		}
		if(!empty($ids)){
			$select->where->in('c.id', $ids);
		} else {
			$select->where->expression('false',[]); // dummy staff
			return $select;
		}
		
		
		if(!empty($filter['date_range']) || is_numeric($filter['price_max'])){
			$select->join(['e' => 'course_events'], 'e.course_id = c.id', [], Join::JOIN_INNER);
		} 
		
		if(!empty($filter['date_range'])){
			list($dateFrom, $dateTo) = explode('_', $filter['date_range']);
			$select->join(['es' => 'course_event_shedule'], 'es.event_id = e.id', [], Join::JOIN_INNER);
			$select->where->between('es.date', strtotime($dateFrom), strtotime($dateTo));
		}
		
		if(is_numeric($filter['price_max'])){			
			$select->join(['e2t' 	=> 'course_event2tarif'], 'e2t.event_id = e.id', [], Join::JOIN_INNER);
			$select->join(['t' 		=> 'course_tarifs'], 't.id = e2t.tarif_id', [], Join::JOIN_INNER);
			$nest = $select->where->nest; 
			$nest->lessThanOrEqualTo('t.price', $filter['price_max'])
				->and->IsNotNull('e2t.tarif_id');
		}
				
		$select->group('c.id');
		// echo $select->toString(); die;
		return $select;
	}
	
	public function getTotals($filter){
	
		// Юзаем вложенный запрос, из-за группировки
		$select = $this->getSelect($filter);
		$select->reset(Select::COLUMNS)
			->columns(['id']);
		
		$select = new Select(['qry' => $select]);
		$select->reset(Select::COLUMNS)
			->columns(['count' => new Expression('count(*)')]);
			
		return $select->fetchRow();
	
	}
	
	public function getItems($filter, $p = 1, $ipp = 100){
		$select = $this->getSelect($filter);
		
		$select->limit($ipp)->offset(($p-1)*$ipp);
		
		$select->order('c.priority desc')
			->order('c.id asc');
			
		return $this->fetchAll($select);
		
	}
	
	public function buildItem(&$item){
		return parent::buildItem($item);
	}
	
	public function getByAlias($alias){
		$select = new Select(['c' => $this->table]);
		$select->where->expression('c.alias = ?', $alias);
		$select->where->expression('c.status = ?', 1);	
		return $this->fetchRow($select);
	}
	
	public function getActualCoursesIds(){
		return $this->getAdapter()->fetchColumn(
				'select course_id from course_events e 
					left join courses c on c.id = e.course_id 
					where e.type != :announceType and e.expiration_date > :now and c.status = 1 
					group by course_id',[
						'announceType' 	=> 	AdminEventDb::TYPE_ANNOUNCE,
						'now' 			=> 	time()]);
	}
	
	public function getArchiveCoursesIds(){
		return $this->getAdapter()->fetchColumn('select id from courses c where c.id not in (:ids) and c.status = 1',
				['ids' => $this->getActualCoursesIds()]);		
	}
	
	public function getActualAnnouncmentIds(){
		return $this->getAdapter()->fetchColumn(
				'select course_id from course_events e 
				left join courses c on c.id = e.course_id
				where e.type = :announceType and e.expiration_date > :now and c.status = 1 group by course_id',[
						'announceType' 	=> 	AdminEventDb::TYPE_ANNOUNCE,
						'now' 			=> 	time()]);
	}
	

	
	public function getActualSimilarCourse($courseId){
		
		$actuals = $this->getActualCoursesIds(); 
		if(empty($actuals)){
			return null;
		}
		$id = $this->getAdapter()->fetchOne('select r2.item_id
			from content_tag_refs r1
			inner join content_tag_refs r2 on r2.tag_id = r1.tag_id
			inner join courses c on c.id = r2.item_id
			where r1.item_id = :courseId and r2.entity = \'course\' and r1.entity = \'course\'
					and r2.item_id in ('.implode(', ', $actuals).')
					group by r2.item_id
					order by count(r1.tag_id) desc, c.priority desc', ['courseId' => $courseId]);
		
		return $this->get($id);		
	}
	
	
	public function getMasterCourses($masterId){
		$select = new Select(['c' => 'courses']);
		$select->join(['e' => 'course_events'], 'e.course_id = c.id', [], Join::JOIN_INNER);
		$select->join(['e2m' => 'course_event2master'], 'e.id = e2m.event_id', [], Join::JOIN_INNER);
		$select->join(['shed' => 'course_event_shedule'], 'e.id = shed.event_id', [], Join::JOIN_INNER);
		
		$select->where
			->equalTo('c.status', 1)->and
			->equalTo('e.status', 1)->and
			->greaterThan('shed.date', time() - 2*7*24*60*60) // Мастер ведет или вел мероприятия по курсу последние 2 недели 
			->equalTo('e2m.master_id', $masterId);
		
		$select->group('c.id');
			
		return $this->fetchAll($select);
		
	}
	
	
	
	
}
