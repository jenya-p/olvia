<?
namespace Admin\Model\Courses;

use Common\CRUDListModel;
use Common\Db\Historical;
use Common\Db\HistoricalTrait;
use Common\Db\Multilingual;
use Common\Db\MultilingualTrait;
use Common\Db\Select;
use Common\Db\Table;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Expression;
use Admin\Model\Content\TagDb;
use Common\Traits\ServiceManagerTrait;
use Common\Traits\ServiceManagerAware;
use Common\Db\OptionsModel;

class CourseDb extends Table implements CRUDListModel, Multilingual, Historical, ServiceManagerAware, OptionsModel {

	use MultilingualTrait, HistoricalTrait, ServiceManagerTrait;
	
	protected $table = 'courses';

	public function __construct(Adapter $adapter) {
		$this->adapter = $adapter;
		$this->langFields(['title', 'summary', 'body', 'seo_title', 'seo_description', 'seo_keywords']);
		$this->history('course');
	}


	// CRUD list implementation
	/**
	 * @param array $filter
	 * @return Select
	 */
	public function getSelect($filter){
	
		$select = new Select(['c' => 'courses']);		

		if(!empty($filter['query'])){
			$select->where->expression('concat(" ", LOWER(c.title_'.$this->lang.')) like ?', "% ".mb_strtolower($filter['query']."%"));
		}
			
		return $select;
	}
	
	public function getTotals($filter){
		
		$select = $this->getSelect($filter);
		$select->reset(Select::COLUMNS)
			->columns(['count' => new Expression('count(c.id)')]);
		return $select->fetchRow();
		
	}
	
	public function getItems($filter, $p = 1, $ipp = 100){
		$select = $this->getSelect($filter);
		$select->limit($ipp)->offset(($p-1)*$ipp);
		$select->order('c.id asc');
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
    	return parent::insert($insert);    	
    }	

	
		
	// History Model implementation
	public function saveHistory(array $newValues = null, array $oldValues = null, $id = null) {	
		$historyWriter = $this->getHistoryWriter($newValues, $oldValues, $id);	
		$historyWriter->setSkipDataFor(['summary_ru', 'body_ru', 'seo_description_ru', 'seo_keywords_ru','summary_en', 'body_en', 'seo_description_en', 'seo_keywords_en', 'image']);							
		$historyWriter->writeAll();
	}
	
	

	public function saveTagHistory($newTagIds, $oldTagsIds, $id){
		$historyWriter = $this->getHistoryWriter(null, null, $id);
		foreach ($newTagIds as $nId){
			if(!in_array($nId, $oldTagsIds)){
				$historyWriter->write('add_tag', $nId);
			}
		}
		foreach ($oldTagsIds as $oId){
			if(!in_array($oId, $newTagIds)){
				$historyWriter->write('remove_tag', null, $oId);
			}
		}
	}
	
	
	public function readHistory($id) {
		$historyReader = $this->getHistoryReader($id);
		$tagDb = $this->serv(TagDb::class);
		$tagDict = [$tagDb, 'optionName'];
		$historyReader->addDictionary('add_tag', $tagDict);
		$historyReader->addDictionary('remove_tag', $tagDict);
		return $historyReader->getRecordsByDate();
	}

	// Misc	
	public function getStat($id){
		$historyReader = $this->getHistoryReader($id);
		$stat = $historyReader->getStat();		
		return $stat;
	}
		
	public function getDescription($id){
		$price = $this->get($id);
		if(!empty($price)){
			return $price['title'].' (id = '.$id.')';
		} else if(!empty($id)){
			return '<i>не найдено</i> (id = '.$id.')';
		} else {
			return null;
		}
	}
	
	// Options Model implementation
	public function options() {
		return null;
	}

	public function option($key) {
		$course = $this->get($key);
		if(!empty($course)){
			return [
				'label' => $course['title'],
				'alias' => $course['alias'],
				'status' => $course['status']
			];			
		} else {
			null;
		}
	}
	
	public function optionName($key){
		$course = $this->option($key);
		if(!empty($course)){
			return $course['label'];
		} else {
			return null;
		}
	}


}
