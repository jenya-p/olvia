<?
namespace Admin\Model\Content;

use Common\CRUDListModel;
use Common\Db\Historical;
use Common\Db\HistoricalTrait;
use Common\Db\Multilingual;
use Common\Db\MultilingualTrait;
use Common\Db\Select;
use Common\Db\Table;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Where;
use Common\Traits\ServiceManagerAware;
use Common\Traits\ServiceManagerTrait;
use Common\ImageService;
use Zend\Db\Sql\Join;

class VideoDb extends Table implements CRUDListModel, Multilingual, Historical, ServiceManagerAware {
	
	use MultilingualTrait, HistoricalTrait, ServiceManagerTrait;
	
	protected $table = 'content_videos';

	public function __construct(Adapter $adapter) {
		$this->adapter = $adapter;
		$this->langFields(['title', 'body', 'seo_title', 'seo_description', 'seo_keywords']);
		$this->history('video');
	}


	// CRUD list implementation
	/**
	 * @param array $filter
	 * @return Select
	 */
	public function getSelect($filter){
	
		$select = new Select(['v' => 'content_videos']);		

		if(!empty($filter['query'])){
			$select->where->expression('LOWER(v.title'.$this->lang().') like ?', mb_strtolower($filter['query']."%"));
		}
			
		if(!empty($filter['album'])){
			$select->where->equalTo('videoalbum_id', $filter['album']);
		}
		
		return $select;
	}
	
	public function getTotals($filter){
		
		$select = $this->getSelect($filter);
		$select->reset(Select::COLUMNS)
			->columns(['count' => new Expression('count(v.id)')]);
		return $select->fetchRow();
		
	}
	
	public function getItems($filter, $p = 1, $ipp = 100){
		$select = $this->getSelect($filter);
		$select->limit($ipp)->offset(($p-1)*$ipp);
		
		$select->join(['u' => 'users_accounts'], 		'u.id = v.author', 			['author_displayname' => 'displayname'], 		Join::JOIN_LEFT);
		$select->join(['va' => 'content_videoalbums'], 	'va.id = v.videoalbum_id', 	['videoalbum_title' => 	'title_'.$this->lang()], Join::JOIN_LEFT);
		
		$select->order('v.priority desc');
		$select->order('v.id asc');
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

	
		
	// History Model implementation
	public function saveHistory(array $newValues = null, array $oldValues = null, $id = null) {	
		$historyWriter = $this->getHistoryWriter($newValues, $oldValues, $id);				
		$historyWriter->setSkipDataFor(['seo_keywords_ru', 'seo_keywords_en', 'seo_description_ru', 'seo_description_en', 'body_ru', 'body_en']);
		$historyWriter->writeAll();
	}
	
	public function readHistory($id) {
		$historyReader = $this->getHistoryReader($id);
		$dir = [0 => 'Выкл', '1' => 'Вкл'];
		$historyReader->addDictionary('top',$dir);
		return $historyReader->getRecordsByDate();
	}


	// Misc	
	public function getStat($id){
		$historyReader = $this->getHistoryReader($id);
		$stat = $historyReader->getStat();
		$item = $this->get($id);
		$stat['views'] = $item['views'];
		return $stat;
	}
		
	
	
	
	const SOURCE_YOUTUBE = 'youtube';
	const SOURCE_VIMEO = 'vimeo';
	const SOURCE_HTML = 'html';
	/**
	 * @param string $url
	 * @return Videos
	 */
	public function import($url){
		if(substr($url, 0, 4) != 'http'){
			$url = 'http://'.$url;
		}
		$urlParts = parse_url($url);
	
		$video = [];
	
		if(strpos($urlParts['host'], 'youtube') !== false){
			if(substr($urlParts['path'], 0, 7) == '/embed/'){
				$code = substr($urlParts['path'], 7);
			} else if (!empty($urlParts['query'])){
				$urlVars = [];
				parse_str($urlParts['query'], $urlVars);
				$code = $urlVars['v'];
			} else {
				throw new \Exception('Не корректный адрес youtube');
			}
			$video['code'] = $code;
			$video['source'] = self::SOURCE_YOUTUBE;
			$video['thumb'] = $this->createYoutubeThumb($code);
				
		} else if(strpos($urlParts['host'], 'youtu.be') !== false){				
			$code = trim($urlParts['path'], '/');
			$code = explode('/', $code);				
			$code = $code[0];			
			
			$video['source'] = self::SOURCE_YOUTUBE;
			$video['code'] = $code;
			$video['thumb'] = $this->createYoutubeThumb($code);
				
		} else if(strpos($urlParts['host'], 'vimeo')  !== false){
			$code = trim($urlParts['path'], '/');
			$code = explode('/', $code);
			$code = $code[0];			
			$video['source'] = self::SOURCE_VIMEO;
			$video['code'] = $code;
			$video['thumb'] = $this->createVimeoThumb($code);
				
		} else {
			throw new \Exception('Неверный формат ссылки');
		}
		
		return $video;
	}
	
	public function importRemoteThumb(&$item){
		if(empty($item['thumb']) || substr($item['thumb'], 0, 4) !== 'http'){
			return;
		}
		$ext = pathinfo($item['thumb'], PATHINFO_EXTENSION);
		if($ext != 'jpg' && $ext != 'jpeg' && $ext != 'png'){
			return;
		}
		$id = $item['id'] ? : $this->getNextId();
		$config = $this->serv('Config');
		
		$dir = $config['path']['images'].'videos'.DIRECTORY_SEPARATOR;
		$fileName = $dir.$id.'.'.$ext;
		if(!is_dir($dir)){
			mkdir($dir);
		}
		file_put_contents($fileName, fopen($item['thumb'], 'r'));
		$item['thumb'] = $config['imagesUrl'].'videos/'.$id.'.'.$ext;
	}
	
	public function importYoutubeRemote(&$item){		
		$config = $this->serv('Config');
		$apiKey = $config['google-api-key'];
		$url = 'https://www.googleapis.com/youtube/v3/videos?id='.$item['code'].'&part=contentDetails,snippet&key='.$apiKey;
		$responce = json_decode(file_get_contents($url));
		if(empty($responce->items)){
			throw new \Exception("Видео ".$item['code'].' не доступно на ютубе');
		}
		$duration = $responce->items[0]->contentDetails->duration;
		$di = new \DateInterval($duration);
		$start = new \DateTime('@0'); // Unix epoch
		$start->add($di);
		$duration = $start->getTimestamp();		
		$item['duration'] = $duration;
		if(empty($item['title'])){
			$item['title'] = $responce->items[0]->snippet->title;
			$item['seo_title'] = $responce->items[0]->snippet->title;
			$item['seo_description'] = implode(' ', $responce->items[0]->snippet->tags);
			$item['seo_keywords'] = implode(' ', $responce->items[0]->snippet->tags);
			$item['body'] = $responce->items[0]->snippet->description;
			$item['created'] = strtotime($responce->items[0]->snippet->publishedAt);
		}
		$item['duration'] = $duration;
	}
	
	
	public function createYoutubeThumb($code){
		// return "http://img.youtube.com/vi/".$code."/mqdefault.jpg";
		return "http://img.youtube.com/vi/".$code."/sddefault.jpg";
	
	}
	
	public function	createVimeoThumb($code){
	
		$data = simplexml_load_file("http://vimeo.com/api/v2/video/" . $code . ".xml");
		$thumbnail = $data->video->thumbnail_large;
		return ''.$thumbnail;
	
	}

	
	public function getAlbumVideos($albumId) {
		$select = new Select(['v' => 'content_videos']);
		$select->where->equalTo('videoalbum_id', $albumId);
		$select->order('priority desc');
		$select->order('id asc');
		return $this->fetchAll($select);
	}
	
	
	public function addVideosToAlbum($videoIds,$albumId){
		$update = ['videoalbum_id' => $albumId];
		$where = new Where();
		$where->in('id', $videoIds);
		return parent::update($update, $where);
	}
	
}
