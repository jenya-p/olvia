<?php

namespace Application\Controller;

use Common\SiteController;
use Common\Traits\LoggerAware;
use Common\Traits\LoggerTrait;
use Zend\View\Model\ViewModel;
use ZfAnnotation\Annotation\Controller;
use ZfAnnotation\Annotation\Route;
use Application\Model\Content\VideoDb;
use Application\Model\Content\VideoalbumDb;


/**
 * @Controller
 */
class VideoController extends SiteController implements LoggerAware{

	use LoggerTrait;
	
	/** @var VideoDb */
	var $videoDb;
	
	/** @var VideoalbumDb */
	var $videoalbumDb;
	
	const ITEM_INROW_COUNT = 5;
	
	public function init(){
		$this->videoDb = $this->serv(VideoDb::class);
		$this->videoalbumDb = $this->serv(VideoalbumDb::class);
	}
	
	/**
	 * 	@Route(name="video-index",route="/video", type="Literal")
	 */
	public function indexAction(){
		$albums = $this->videoalbumDb->getAlbums();
		foreach ($albums as $key => &$album){
			$items = $this->videoDb->getVideos($album['id'], self::ITEM_INROW_COUNT);
			if(count($items) == 0){
				unset($albums[$key]);
			} else {
				$album['items'] = $items;
			}
		}
			
		return [
			'latest' => $this->videoDb->getLatest(self::ITEM_INROW_COUNT),
			'top' =>  $this->videoDb->getTop(self::ITEM_INROW_COUNT),
			'albums' => $albums
		];
	}
	
	/**
	 * 	@Route(name="video-album",route="/video/album-:alias", type="Segment")
	 */
	public function albumAction() {
		$alias = $this->params('alias', null);
		$album = $this->videoalbumDb->getByAlias($alias);
	
		if(empty($album)){
			return $this->notFoundAction();
		}
		
		$this->videoalbumDb->incViews($album['id']);
		$videos = $this->videoDb->getVideos($album['id']);
	
		return [
			'videos' => $videos,
			'album' => $album
		];
	}
	
	
	/**
	 * 	@Route(name="video-view",route="/video/video-:alias", type="Segment")
	 */
	public function videoAction() {
		/* @var $videoDb VideoDb */
		$videoDb = $this->serv(VideoDb::class);

		/* @var $videoalbumDb VideoalbumDb */
		$videoalbumDb = $this->serv(VideoalbumDb::class);
		
		$videoAlias = $this->params('alias', null);
		
		$video = $videoDb->getByAlias($videoAlias);
		
		if(empty($video)){
			return $this->notFoundAction();
		}
		
		$album = $videoalbumDb->get($video['videoalbum_id']);
		
		$this->videoDb->incViews($video['id']);

		$this->videoDb->buildHtml($video);
						
		return new ViewModel([
				'video' => $video,
				'album' => $album,
		]);
	}
	
	

	

	
}
