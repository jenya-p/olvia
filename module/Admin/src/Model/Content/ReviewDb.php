<?
namespace Admin\Model\Content;

use Common\CRUDListModel;
use Common\Db\Select;
use Common\Db\Table;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Expression;
use Common\Db\Multilingual;
use Common\Db\MultilingualTrait;
use Common\Db\HistoricalTrait;
use Common\Db\Historical;
use Admin\Model\HistoryWriter;
use Common\Traits\ServiceManagerAware;
use Common\Traits\ServiceManagerTrait;

class ReviewDb extends Table implements CRUDListModel, Multilingual, Historical, ServiceManagerAware {
	
	use MultilingualTrait, HistoricalTrait, ServiceManagerTrait;
		
	protected $table = 'content_reviews';

	public function __construct(Adapter $adapter) {
		$this->adapter = $adapter;
		$this->langFields(['name', 'body']);
		$this->history('review');
	}


	// CRUD list implementation
	/**
	 * @param array $filter
	 * @return Select
	 */
	public function getSelect($filter){
	
		$select = new Select(['r' => 'content_reviews']);		

		if(!empty($filter['query'])){
			$select->where->expression('concat(" ", LOWER(r.name_'.$this->lang.')) like ?', "% ".mb_strtolower($filter['query']."%"));
		}
			
		return $select;
	}
	
	public function getTotals($filter){		
		$select = $this->getSelect($filter);
		$select->reset(Select::COLUMNS)
			->columns(['count' => new Expression('count(r.id)')]);
		return $select->fetchRow();		
	}
	
	public function getItems($filter, $p = 1, $ipp = 100){
		$select = $this->getSelect($filter);
		$select->limit($ipp)->offset(($p-1)*$ipp);
		
		$sort = $filter['sort'];
		if(!empty($sort) && is_array($sort) && count($sort) == 2){
			$select->order('r.'.$sort[0]. ' '.$sort[1]);
		} else {
			$select->order('r.id desc');
		}
		
		
		$items = $select->fetchAll();
		foreach ($items as &$item){
			$this->buildItem($item);
		}
		return $items;
	}
		
	public function buildItem(&$item){
		parent::buildItem($item);
		/*@var $reviewRefDb ReviewRefsDb */
		$reviewRefDb = $this->serv(ReviewRefsDb::class);
		$item['refs'] = $reviewRefDb->getRefs($item['id']);
	}
	
	public function deleteOne($id){
		$res = parent::deleteOne($id);
		if($res){
			/*@var $reviewRefDb ReviewRefsDb */
			$reviewRefDb = $this->serv(ReviewRefsDb::class);
			$reviewRefDb->removeAllRefs($id);
		}
		return $res;
	}

    public function saveHistory(array $newValues = null, array $oldValues = null, $id = null) {
    	$historyWriter = $this->getHistoryWriter($newValues, $oldValues, $id);
    	// $historyWriter->setSkipDataFor(['body_ru' ,'body_en']);
    	$historyWriter->writeAll();
    }
    
    public function readHistory($id) {
    	$historyReader = $this->getHistoryReader($id);
    	return $historyReader->getRecordsByDate();
    }
    
    // Misc
    public function getStat($id){
    	$historyReader = $this->getHistoryReader($id);
    	$stat = $historyReader->getStat();
    	return $stat;
    }
    
}
