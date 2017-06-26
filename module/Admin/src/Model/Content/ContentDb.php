<?php 

namespace Admin\Model\Content;

use Common\CRUDListModel;
use Common\Db\Multilingual;
use Common\Db\MultilingualTrait;
use Common\Db\Select;
use Common\Db\Table;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Expression;
use Common\Db\Historical;
use Common\Db\HistoricalTrait;
use Zend\Db\Sql\Join;
use Common\Traits\ServiceManagerAware;
use Common\Traits\ServiceManagerTrait;
use Admin\Model\Courses\CourseDb;
use Zend\Db\Sql\Sql;

class ContentDb extends Table implements CRUDListModel, Multilingual, Historical, ServiceManagerAware {

	use MultilingualTrait, HistoricalTrait, ServiceManagerTrait;
	
	
	protected $table = 'content';		

    public function __construct(Adapter $adapter) {
        $this->adapter = $adapter;
        $this->langFields(['title', 'intro', 'body', 'seo_title', 'seo_description', 'seo_keywords']);
        $this->history('content');
    }
    
    /**
     * @param array $filter
     * @return Select
     */
    public function getSelect($filter){    
    	$select = new Select(['c' => $this->table]);
    	
    	if(!empty($filter['query'])){
    		$select->where->expression('LOWER(c.title_ru) like ?', mb_strtolower($filter['query']."%"));
    	}    
    	
    	if(!empty($filter['division'])){
    		$select->where->expression('division_id = ?', $filter['division']);
    	}
    	
    	return $select;
    }
    
    public function getTotals($filter){    	
    	$select = $this->getSelect($filter);
    	
    	$select->reset(Select::COLUMNS)
    		->columns(['count' => new Expression('count(c.id)')]);
    	
    	return $this->getAdapter()->fetchRow($select);
    }
    
    
    public function getItems($filter, $p = 1, $ipp = 100){

    	$select = $this->getSelect($filter);
    	$select->join(['u' => 'users_accounts'], 'u.id = c.author', ['author_displayname' => 'displayname'], Join::JOIN_LEFT);
    	$select->join(['d' => 'content_division'], 'd.id = c.division_id', ['division_title' => 'title_'.$this->lang()], Join::JOIN_LEFT);
    	$select->limit($ipp)->offset(($p-1)*$ipp);
    	
    	$sort = $filter['sort'];
    	if(!empty($sort) && is_array($sort) && count($sort) == 2){
    		$select->order($sort[0]. ' '.$sort[1]);
    	} else {
    		$select->order('c.id desc');
    	}

    	$items = $this->fetchAll($select);
    	
    	foreach ($items as &$item){
    		$this->buildItem($item);    		
    	}
    	
    	return $items;
    }
    
    public function buildItem(&$item){    	 
    	return parent::buildItem($item);
    }
    
    public function insert($insert){
    	if(empty($insert['created'])){
    		$insert['created'] = time();
    	}
    	return parent::insert($insert);
    }
  
	public function saveHistory(array $newValues = null, array $oldValues = null, $id = null) {	

		$historyWriter = $this->getHistoryWriter($newValues, $oldValues, $id);
		if($historyWriter->hasUpdated('body_ru')){
			$historyWriter->write('body_ru');
		}
		if($historyWriter->hasUpdated('body_en')){
			$historyWriter->write('body_en');
		}
		if($historyWriter->hasUpdated('intro_ru')){
			$historyWriter->write('intro_ru');
		}
		if($historyWriter->hasUpdated('intro_en')){
			$historyWriter->write('intro_en');
		}
		$historyWriter->writeAll();
	}
	
	public function saveTagHistory($newTagIds, $oldTagsIds, $id){
		$historyWriter = $this->getHistoryWriter(null, null, $id);
		$historyWriter->writeArrayDiff($attr,$newTagIds,$oldTagsIds);		
	}

	public function readHistory($id) {
		$historyReader = $this->getHistoryReader($id);
		
		$tagDb = $this->serv(TagDb::class);
		$tagDict = [$tagDb, 'optionName'];		
		$historyReader->addDictionary('add_tag', $tagDict);
		$historyReader->addDictionary('remove_tag', $tagDict);
		
		$courseDb = $this->serv(CourseDb::class);
		$dict = [$courseDb, 'optionName'];
		$historyReader->addDictionary('add_course', $dict);
		$historyReader->addDictionary('remove_course', $dict);
		
		return $historyReader->getRecordsByDate();
	}

	public function getStat($id){
		$historyReader = $this->getHistoryReader($id);
		$stat = $historyReader->getStat();
		$item = $this->get($id);
		$stat['views'] = $item['views'];
		return $stat;
	}
	
	
	var $courseIdsCache = [];
	
	public function getArticleCourseIds($id){
		if(!array_key_exists($id, $this->courseIdsCache)){
			
			$select = new Select(['c' => 'courses']);
			$select->reset(Select::COLUMNS)
				->columns(['id']);
			$select->join(['c2c' => 'content_content2course'], 'c2c.course_id = c.id', [], Join::JOIN_INNER);
			$select->where->equalTo('c2c.content_id', $id);
			$select
				->order('c.status desc')
				->order('c.priority desc')
				->order('c.id asc');
			
			$result = $select->fetchColumn();
			$this->courseIdsCache[$id] = $result;
			
		}
		return  $this->courseIdsCache[$id];
	}
	
	public function saveArticleCourseIds($articleId, $courseIds){
		$oldValues = $this->getArticleCourseIds($articleId);

		$historyWriter = $this->getHistoryWriter(null, null, $articleId);
		$historyWriter->writeArrayDiff('course', $courseIds, $oldValues);
				
		$sql = new Sql( $this->getAdapter() );
		$delete = $sql->delete('content_content2course');
		$delete->where->equalTo('content_id', $articleId);		
		$sql->prepareStatementForSqlObject($delete)->execute();
		
		$courseIds = array_unique($courseIds);
		$priority = 0;
		foreach ($courseIds as $courseId){
			$insert = [
					'content_id' => $articleId,
					'course_id' => $courseId,
			];
			$this->getAdapter()->insert('content_content2course', $insert);
		}
	}
	
	
}
