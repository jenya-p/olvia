<?php 

namespace Admin\Model\Content;

use Common\Db\Select;
use Common\Db\Table;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Expression;
use Common\CRUDListModel;
use Common\Db\Multilingual;
use Common\Db\Historical;
use Common\Db\MultilingualTrait;
use Common\Db\HistoricalTrait;
use Zend\Db\Sql\Join;

class DivisionDb extends Table implements CRUDListModel, Multilingual, Historical {

	use MultilingualTrait, HistoricalTrait;

	protected $table = 'content_division';

    public function __construct(Adapter $adapter) {
        $this->adapter = $adapter;
        $this->langFields(['title', 'seo_title', 'seo_description', 'seo_keywords']);
        $this->history('content_division');
    }
    
    /**
     * @param array $filter
     * @return Select
     */
    public function getSelect($filter){    
    	$select = new Select(['cd' => $this->table]);
    	
    	if(!empty($filter['query'])){
    		$select->where->expression('LOWER(cd.title_ru) like ?', mb_strtolower($filter['query']."%"));
    	}    
    	return $select;
    }
    
    public function getTotals($filter){    	
    	$select = $this->getSelect($filter);
    	$select->reset(Select::COLUMNS)
    		->columns(['count' => new Expression('count(cd.id)')]);
    	return $select->fetchRow();
    }
    
    public function getItems($filter, $p = 1, $ipp = 100){
    	$select = $this->getSelect($filter);
    	$select->join(['c' => 'content'], 'c.division_id = cd.id', ['article_count' => new Expression('count(c.id)')], Join::JOIN_LEFT);
    	
    	$select->limit($ipp)->offset(($p-1)*$ipp);
    	$select->group('cd.id');
    	
    	$sort = $filter['sort'];
    	if(!empty($sort) && is_array($sort) && count($sort) == 2){
    		if($sort[0] == 'article_count'){
    			$select->order(new Expression('count(c.id) '.$sort[1]));
    		} else {
    			$select->order('cd.'.$sort[0]. ' '.$sort[1]);
    		}    		
    	} else {
    		$select->order('cd.priority desc');
    		$select->order('cd.id asc');
    	}
    	    	
    	$items = $select->fetchAll();
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

    
    public function getLastArticles($divisionId, $limit = 20){    	
    	$select = new Select(['c' => 'content']);
    	$select->columns(['id', 'title' => 'title_ru']);
    	$select->where->expression('division_id = ?', $divisionId);
    	$select->limit($limit);
    	$select->order('c.created desc');
    	return $select->fetchAll();
    }
    
    
    public function getArticlesCount($divisionId){
    	$select = new Select(['c' => 'content']);
    	$select->columns(['count' => new Expression('count(*)')]);
    	$select->where->expression('division_id = ?', $divisionId);
    	return $select->fetchOne();
    }
    
    
    public function getOptions(){
    	$select = new Select(['d' => $this->table]);
    	$select->columns(['id', 'title' => 'title_'.$this->lang()]);
    	$select->order('title_'.$this->lang().' ASC');
    	return $select->fetchPairs();
    }
    
    public function saveHistory(array $newValues = null, array $oldValues = null, $id = null) {    
    	$historyWriter = $this->getHistoryWriter($newValues, $oldValues, $id);
    	$historyWriter->setSkipDataFor(['seo_description', 'seo_keywords']);
    	$historyWriter->writeAll();
    }
    
    public function readHistory($id) {
    	$historyReader = $this->getHistoryReader($id);
    	return $historyReader->getRecordsByDate();
    }

    public function getStat($id){
    	$historyReader = $this->getHistoryReader($id);
    	$stat = $historyReader->getStat();
    	return $stat;
    }
    
}
