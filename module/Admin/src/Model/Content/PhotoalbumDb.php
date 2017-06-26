<?
namespace Admin\Model\Content;

use Common\CRUDListModel;
use Common\Db\Historical;
use Common\Db\HistoricalTrait;
use Common\Db\Multilingual;
use Common\Db\MultilingualTrait;
use Common\Db\OptionsModel;
use Common\Db\Select;
use Common\Db\Table;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Join;

class PhotoalbumDb extends Table implements CRUDListModel, Multilingual, Historical, OptionsModel {
	
	use MultilingualTrait, HistoricalTrait;
	
	protected $table = 'content_photoalbums';

	public function __construct(Adapter $adapter) {
		$this->adapter = $adapter;
		$this->langFields(['title', 'body', 'seo_title', 'seo_description', 'seo_keywords']);
		$this->history('photoalbum');
	}


	// CRUD list implementation
	/**
	 * @param array $filter
	 * @return Select
	 */
	public function getSelect($filter){
	
		$select = new Select(['fa' => 'content_photoalbums']);		

		if(!empty($filter['query'])){
			$select->where->expression('LOWER(fa.title_'.$this->lang().') like ?', mb_strtolower($filter['query']."%"));
		}
			
		return $select;
	}
	
	public function getTotals($filter){
		
		$select = $this->getSelect($filter);
		$select->reset(Select::COLUMNS)
			->columns(['count' => new Expression('count(fa.id)')]);
		return $select->fetchRow();
		
	}
	
	public function getItems($filter, $p = 1, $ipp = 100){
		$select = $this->getSelect($filter);
		
		$select
			->join(['f' => 'content_photos'], 'f.photoalbum_id = fa.id', ['photo_count' => new Expression('count(f.id)')], Join::JOIN_LEFT)
			->group('fa.id');
		
		$select->limit($ipp)->offset(($p-1)*$ipp);
		$select->order('fa.priority desc');
		$select->order('fa.id asc');
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

    public function getChildCount($id){
    	$select = new Select(['f' => 'content_photos']);
    	$select->columns(['count' => new Expression('count(*)')]);
    	$select->where->expression('photoalbum_id = ?', $id);
    	return $select->fetchOne();
    }

    // Options Model implementation
    var $options = null;
	public function options() {
		if($this->options === null){
			$select = new Select(['d' => $this->table]);
			$select->columns(['id', 'title' => 'title_ru']);
			$select->order('title_ru ASC');
			$this->options = $select->fetchPairs();
		}
		return $this->options;
	}

	public function option($key) {
		if($this->options === null){
			$this->options();			
		}
		return $this->options[$key];
	}
	
	
	
		
	// History Model implementation
	public function saveHistory(array $newValues = null, array $oldValues = null, $id = null) {
	
		$historyWriter = $this->getHistoryWriter($newValues, $oldValues, $id);
					
		$historyWriter = $this->getHistoryWriter($newValues, $oldValues, $id);
		if($historyWriter->hasUpdated('body_ru')){
			$historyWriter->write('body_ru');
		}
		if($historyWriter->hasUpdated('body_en')){
			$historyWriter->write('body_en');
		}
		
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
