<?php

namespace Common\ViewHelper;

use Common\ImageService;
use Zend\View\Helper\AbstractHelper;
use ZfAnnotation\Annotation\ViewHelper;

class Image extends AbstractHelper{
    
	/* @var ImagesService */
	var $imageService;
	
    public function __construct(ImageService $imageService) {
    	$this->imageService = $imageService;
    }
    
    /**
     * @return Image
     */
    public function __invoke($url = false, $sizeX, $sizeY) {
    	
    	if($url === false) {
    		return $this->imageService;
    	}
    	
    	$oldOverwriteStatus = $this->imageService->getOverwrite();
    	$this->imageService->setOverwrite(false);
    	
    	$url = $this->imageService->resize($url, $sizeX, $sizeY);
    	
    	$this->imageService->setOverwrite($oldOverwriteStatus);

    	return $url;    	
    }
    
    
    
}
