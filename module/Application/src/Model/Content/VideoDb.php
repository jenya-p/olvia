<?php 

namespace Application\Model\Content;

use Common\Db\Multilingual;
use Common\Db\MultilingualTrait;
use Common\Db\Select;
use Common\Db\Table;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Update;
use Zend\Db\Sql\Sql;
use Admin\Model\Content\VideoDb as AdminVideoDb;
use Zend\Db\Sql\Join;

class VideoDb extends Table implements Multilingual {

	use MultilingualTrait;
	
	protected $table = 'content_videos';		

    public function __construct(Adapter $adapter) {
        $this->adapter = $adapter;
        $this->langFields(['title', 'body', 'seo_title', 'seo_description', 'seo_keywords']);
    }
    
	public function getByAlias($alias){
		$select = new Select(['v' => $this->table]);

		$select->join(['u' => 'users_masters'], 		'u.id = v.author', 		['author_name' => 'name_'.$this->lang(), 'author_alias' => 'alias'], 		Join::JOIN_LEFT);
		
		$select->where->expression('v.alias = ?', $alias);
		$select->where->expression('v.status = ?', 1);
		return $this->fetchRow($select);		
	}
	
	public function incViews($id){
		$id = $this->id($id);
		$this->getAdapter()->sql('UPDATE '.$this->table.' SET views = views + 1 WHERE id = :id', ['id' => $id]);
	}

	public function buildHtml(&$video){
		if($video['source'] == AdminVideoDb::SOURCE_HTML){
			return ; 
		} else if($video['source'] == AdminVideoDb::SOURCE_YOUTUBE){
			$video['html'] = '<iframe allowfullscreen="" frameborder="0" height="800" src="https://www.youtube.com/embed/'.$video['code'].'" width="1180"></iframe>';
		} else if($video['source'] == AdminVideoDb::SOURCE_VIMEO){
			$video['html'] = '<iframe src="https://player.vimeo.com/video/'.$video['code'].'" width="1180" height="800" frameborder="0" webkitallowfullscreen mozallowfullscreen allowfullscreen></iframe>';
		}
	}
	
	public function getVideos($albumId = null, $count = null){
		$select = new Select($this->table);
		$select->where->expression('status = ?', 1);
		if($albumId != null){
			$select->where->expression('videoalbum_id = ?', $albumId);
			$select->order('priority DESC')->order('created DESC');
		} else {
			$select->order('created DESC');
		}
		
		if($count !== null){
			$select->limit($count);
		}
		
		return $this->fetchAll($select);
		
	}
	
	
	public function getLatest($count = null){
		$select = new Select($this->table);
		$select->where->expression('status = ?', 1);
		$select->order('created DESC');
		
		if($count !== null){
			$select->limit($count);
		}
		
		return $this->fetchAll($select);
	}

	public function getTop($count = null){
		$select = new Select($this->table);
		$select->where
			->equalTo('status', 1)->
			and->equalTo('top', 1);
		$select->order('created DESC');
		
		if($count !== null){
			$select->limit($count);
		}
		
		return $this->fetchAll($select);
	}
	
}
