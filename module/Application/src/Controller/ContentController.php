<?php

namespace Application\Controller;

use Application\Model\Content\ContentDb;
use Common\SiteController;
use Common\Traits\LoggerAware;
use Common\Traits\LoggerTrait;
use Zend\View\Model\ViewModel;
use ZfAnnotation\Annotation\Controller;
use ZfAnnotation\Annotation\Route;
use Application\Model\Content\DivisionDb;

/**
 * @Controller
 */
class ContentController extends SiteController implements LoggerAware{

	use LoggerTrait;
	
	const ARTICLE_COUNT_IN_DIVISION = 6;
	
	/** @var DivisionDb */
	var $divisionDb;
	
	/** @var ContentDb */
	var $contentDb;
	
	public function init(){
		$this->contentDb = $this->serv(ContentDb::class);
		$this->divisionDb = $this->serv(DivisionDb::class);		
	}
	
	
	/**
	 * 	@Route(name="content-index",route="/articles")
	 */
	public function indexAction() {
	
		$divisions = $this->divisionDb->getDivisions();
		
		foreach ($divisions as $key => &$division){
			$items = $this->contentDb->getArticles($division['id'], self::ARTICLE_COUNT_IN_DIVISION);
			if(count($items) > 0) {
				$division['items'] = $items;
			} else {
				unset($divisions[$key]);
			}
		}
		
		return new ViewModel([
			'divisions' => $divisions,
		]);
	}
	
	
	/**
	 * 	@Route(name="content-division",route="/articles/:alias")
	 */
	public function divisionAction() {
		$alias = $this->params('alias', null);
				
		$division = $this->divisionDb->getByAlias($alias);
		if($division == false){
			return $this->notFoundAction();
		}
				
		$articles = $this->contentDb->getArticles($division['id']);
		
		return new ViewModel([
			'division' => $division,
			'articles' => $articles,
		]);
	}
	
	/**
	 * 	@Route(name="content-article",route="/article/:alias")
	 */
	public function articleAction() {
		$alias = $this->params('alias', null);
	
		$article = $this->contentDb->getArticleByAlias($alias);
		if($article == false){
			return $this->notFoundAction();
		}

		$division = $this->divisionDb->get($article['division_id']);

		$this->contentDb->incViews($article['id']);
	
		$this->layout()->admin_url = $this->url()->fromRoute('private/content-edit', ['id' => $article['id']]);
		
		return new ViewModel([
			'article' => $article,
			'division' => $division,			
		]);
	}
	
}
