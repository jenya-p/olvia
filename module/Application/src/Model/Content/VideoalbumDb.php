<?php 

namespace Application\Model\Content;

use Common\Db\Multilingual;
use Common\Db\MultilingualTrait;
use Common\Db\Select;
use Common\Db\Table;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Update;
use Zend\Db\Sql\Sql;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Join;

class VideoalbumDb extends Table implements Multilingual {

	use MultilingualTrait;
	
	protected $table = 'content_videoalbums';		

    public function __construct(Adapter $adapter) {
        $this->adapter = $adapter;
        $this->langFields(['title', 'seo_title', 'seo_description', 'seo_keywords']);
    }
    
	public function getByAlias($alias){
		$select = new Select($this->table);
		$select->where->expression('alias = ?', $alias);
		$select->where->expression('status = ?', 1);
		
		return $this->fetchRow($select);		
	}
	
	public function getAlbums(){
		$select = new Select(['va' => $this->table]);
		$select->where->expression('va.status = ?', 1);
		
		$select->join(['v' => 'content_videos'], 'v.videoalbum_id = va.id', ['video_count' => new Expression('count(v.id)')], Join::JOIN_INNER);
		$select->group('va.id');
		
		$select->order('priority DESC')->order('id ASC');
		return $this->fetchAll($select);
	}
	
	public function incViews($id){
		$id = $this->id($id);
		$this->getAdapter()->sql('UPDATE '.$this->table.' SET views = views + 1 WHERE id = :id', ['id' => $id]);
	}
	
}
