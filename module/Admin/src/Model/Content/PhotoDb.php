<?
namespace Admin\Model\Content;

use Common\CRUDListModel;
use Common\Db\Multilingual;
use Common\Db\MultilingualTrait;
use Common\Db\Select;
use Common\Db\Table;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Where;
use Zend\Db\Sql\Join;

class PhotoDb extends Table implements CRUDListModel, Multilingual {
	
	use MultilingualTrait;
	
	protected $table = 'content_photos';

	public function __construct(Adapter $adapter) {
		$this->adapter = $adapter;
		$this->langFields(['title']);
	}


	// CRUD list implementation
	/**
	 * @param array $filter
	 * @return Select
	 */
	public function getSelect($filter){
	
		$select = new Select(['f' => 'content_photos']);		

		if(!empty($filter['query'])){
			$select->where->expression('LOWER(f.title_ru) like ?', mb_strtolower($filter['query']."%"));
		}
			
		if(!empty($filter['album'])){
			$select->where->equalTo('photoalbum_id', $filter['album']);
		}
		
		return $select;
	}
	
	public function getTotals($filter){
		
		$select = $this->getSelect($filter);
		$select->reset(Select::COLUMNS)
			->columns(['count' => new Expression('count(f.id)')]);
		return $select->fetchRow();
		
	}
	
	public function getItems($filter, $p = 1, $ipp = 100){
		$select = $this->getSelect($filter);
		
		$select->join(['fa' => 'content_photoalbums'], 'fa.id = f.photoalbum_id', ['photoalbum_name' => 'title_'.$this->lang()], Join::JOIN_LEFT);
		
		$select->limit($ipp)->offset(($p-1)*$ipp);
		$select->order('f.priority desc');
		$select->order('f.id asc');
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

	
	// Misc
	public function getStat($id){
		$item = $this->get($id);
		$stat = ['created' => $item['created']];
		return $stat;
	}	

	public function getPhotoalbumPhotos($photoalbumId) {
		$select = new Select(['f' => 'content_photos']);
		$select->where->equalTo('photoalbum_id', $photoalbumId);
		$select->order('priority desc');
		$select->order('id asc');
		return $this->fetchAll($select);
	}
	
	
	public function addPhotosToAlbum($photoIds,$photoalbumId){
		$update = ['photoalbum_id' => $photoalbumId];
		$where = new Where();
		$where->in('id', $photoIds);
		return parent::update($update, $where);
	}
	
}
