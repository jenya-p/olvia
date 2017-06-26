<?php

namespace Application\Controller;

use Application\Model\Content\PhotoalbumDb;
use Application\Model\Content\PhotoDb;
use Common\SiteController;
use Common\Traits\LoggerAware;
use Common\Traits\LoggerTrait;
use ZfAnnotation\Annotation\Controller;
use ZfAnnotation\Annotation\Route;

/**
 * @Controller
 */
class PhotoController extends SiteController implements LoggerAware{

	use LoggerTrait;
	
	/** @var PhotoDb */
	var $photoDb;
	
	/** @var PhotoalbumDb */
	var $photoalbumDb;
	
	public function init(){
		$this->photoDb = $this->serv(PhotoDb::class);
		$this->photoalbumDb = $this->serv(PhotoalbumDb::class);
	}
	
	
	/**
	 * 	@Route(name="photo-index",route="/photo", type="Literal")
	 */
	public function indexAction(){
		
			$photoalbums = $this->photoalbumDb->getPhotoalbums();
			foreach ($photoalbums as $key => &$photoalbum){
				$photos = $this->photoDb->getPhotos($photoalbum['id'], 6);
				if(count($photos) == 0){
					unset($photoalbums[$key]);
				} else {
					$photoalbum['photos'] = $photos;
				}
			}
			
			$topPhotos = $this->photoDb->getPhotos(null, 6);
			
			return [
				'top_photos' => $topPhotos,
				'albums' => $photoalbums
			];
	}
		
	
	/**
	 * 	@Route(name="photo-latest",route="/photo/latest", type="Literal",priority=1)
	 */
	public function latestAction() {

		$photos = $this->photoDb->getPhotos(null, 200);
	
		return [
			'photos' => $photos
		];
	}
	
	/**
	 * 	@Route(name="photo-album",route="/photo/:alias", type="Segment")
	 */
	public function albumAction() {
		$alias = $this->params('alias', null);
		$photoalbum = $this->photoalbumDb->getByAlias($alias);
		
		if(empty($photoalbum)){
			return $this->notFoundAction();
		}
		$this->photoalbumDb->incViews($photoalbum['id']);
		$photos = $this->photoDb->getPhotos($photoalbum['id']);
		
		return [
				'photos' => $photos,
				'album' => $photoalbum
		];
	}
	

	
}
