<?php 

namespace Application\Model\Content;

use Common\Db\Multilingual;
use Common\Db\MultilingualTrait;
use Common\Db\Select;
use Common\Db\Table;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Update;
use Zend\Db\Sql\Sql;

class PhotoalbumDb extends Table implements Multilingual {

	use MultilingualTrait;
	
	protected $table = 'content_photoalbums';		

    public function __construct(Adapter $adapter) {
        $this->adapter = $adapter;
        $this->langFields(['title', 'body', 'seo_title', 'seo_description', 'seo_keywords']);
    }
    
	public function getByAlias($alias){
		$select = new Select($this->table);
		$select->where->expression('alias = ?', $alias);
		$select->where->expression('status = ?', 1);
		return $this->fetchRow($select);		
	}
	
	public function incViews($id){
		$id = $this->id($id);
		$this->getAdapter()->sql('UPDATE '.$this->table.' SET views = views + 1 WHERE id = :id', ['id' => $id]);
	}
	
	
	public function getPhotoalbums(){
		$select = new Select($this->table);
		$select->where->expression('status = ?', 1);
		$select->order('priority DESC')->order('created DESC');
		return $this->fetchAll($select);
	}
	
}
