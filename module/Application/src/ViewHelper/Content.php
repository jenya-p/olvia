<?php
namespace Application\ViewHelper;

use ZfAnnotation\Annotation\ViewHelper;
use Zend\Form\View\Helper\AbstractHelper;
use Application\Model\Content\ContentDb;


class Content extends AbstractHelper{
	
	/* @var ContentDb */
	var $contentDb = null;
	
	public function __construct(ContentDb $contentDb){
		$this->contentDb = $contentDb;
	}
	
	public function __invoke($alias, $seo = true){
		
		$article = $this->contentDb->getArticleByAlias($alias);
		
		if(empty($article)){
			return '';
		}
		
		$this->contentDb->incViews($article['id']);
		
		if($seo){
			$view = $this->getView();

			$view->headTitle($article['seo_title']);
			if(!empty($article['seo_keywords'])){
				$view->headMeta($article['seo_keywords'], "keywords");
			}
			if(!empty($article['seo_description'])){
				$view->headMeta($article['seo_description'], "description");
			}			
		}
		
		return '<div class="content" data-editable="content-'.$article['id'].'-body" >'
					.$article['body'].'</div>';
		
	}	
	
	

}