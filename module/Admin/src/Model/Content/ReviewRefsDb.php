<?
namespace Admin\Model\Content;

use Common\Db\Select;
use Common\Db\Table;
use Common\Traits\ServiceManagerAware;
use Common\Traits\ServiceManagerTrait;
use Admin\Model\Users\MasterDb;
use Common\Db\OptionsModel;
use Common\Utils;
use Zend\Db\Sql\Expression;
use Admin\Model\Courses\CourseDb;

class ReviewRefsDb extends Table implements ServiceManagerAware {
	
	use ServiceManagerTrait;
	const ENTITY_MASTER = 'master';
	const ENTITY_COURSE = 'course';
	
	protected $table = 'content_review_refs';
	
	public function getRefs($reviewId){
		$select = new Select($this->table);
		$select->where
			->equalTo('review_id', $reviewId);
		$items = $select->fetchAll();
		foreach ($items as &$item){			
			$this->buildItem($item);
		}
		return $items;
	}
	
	
	public function buildItem(&$item){
		$item['entity_name'] = $this->getEntityName($item['entity']);
		switch ($item['entity']) {
			case self::ENTITY_MASTER: return $this->buildMasterRef($item);
			case self::ENTITY_COURSE: return $this->buildCourseRef($item);
			default: return $item;
		}
	}
	
	public function buildMasterRef(&$item){
		/** @var $masterDb MasterDb */
		$masterDb = $this->serv(MasterDb::class);
		$master = $masterDb->get($item['item_id']);
		if($master){
			Utils::arrayMergePrefixed($master, 'item_', $item);
		}		
		return $item;
	}
	
	
	public function buildCourseRef(&$item){
		$courseDb = $this->serv(CourseDb::class);
		$course = $courseDb->get($item['item_id']);
		if($course){
			Utils::arrayMergePrefixed(['title' => $course['title']], 'item_', $item);
		}		
		return $item;
	}
	
	
	
	public function getEntityName($entity){
		switch ($entity) {
			case self::ENTITY_MASTER: return 'Специалист';
			case self::ENTITY_COURSE: return 'Курс';
			default: return '';
		}
	}
	
	public function addRef($reviewId, $entity, $itemId){
		if( ! $this->hasRef($reviewId, $entity, $itemId)){
			$this->getAdapter()->insert($this->table, [
					'review_id' => $reviewId,
					'entity' => $entity,
					'item_id' => $itemId
			]);
			if($entity == self::ENTITY_MASTER){
				$hw = $this->serv('HistoryWriter');
				$hw->reset('master', null, null, $itemId);
				$hw->write('add_review', $reviewId);
			}
		}
	}
	
	public function removeRef($reviewId, $entity, $itemId){
		if($this->hasRef($reviewId, $entity, $itemId)){
			$this->delete([
					'review_id' => $reviewId,
					'entity' => $entity,
					'item_id' => $itemId ]);
			if($entity == self::ENTITY_MASTER){
				$hw = $this->serv('HistoryWriter');
				$hw->reset('master', null, null, $itemId);
				$hw->write('remove_review', null, $reviewId);
			}
		}
	}
	
	public function removeAllRefs($reviewId){
		$select = new Select($this->table);
		$select->where
			->equalTo('review_id', $reviewId);
		$refs = $select->fetchAll();
		foreach ($refs as $ref){
			$this->removeRef($reviewId, $ref['entity'], $ref['item_id']);
		}
	}
	
	
	public function hasRef($reviewId, $entity, $itemId){
		$select = new Select($this->table);
		$select->where
					->equalTo('review_id', $reviewId)
			->and	->equalTo('entity', $entity)
			->and	->equalTo('item_id', $itemId);
		$select->columns(['count' => new Expression('count(*)')]);
		$count = $select->fetchOne();
		return $count > 0;
	}

}
