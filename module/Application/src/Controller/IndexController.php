<?php

namespace Application\Controller;

use Common\SiteController;
use Zend\View\Model\ViewModel;
use ZfAnnotation\Annotation\Controller;
use ZfAnnotation\Annotation\Route;
use Common\Traits\LoggerTrait;
use Common\Traits\LoggerAware;
use Application\Model\Content\BannerDb;
use Application\Model\Content\DiplomDb;
use Application\Model\Content\ReviewDb;
use Common\ViewHelper\Phone;
use Zend\View\Model\JsonModel;
use Application\ControllerPlugin\UserFlow;
use Zend\Session\Container;

/**
 * @Controller
 */
class IndexController extends SiteController implements LoggerAware{

	use LoggerTrait;
	
	/**
	 * @Route(name="home",route="/")
	 */
	public function homeAction() {
		/* @var $reviewDb ReviewDb */
		$reviewDb = $this->serv(ReviewDb::class);
	
		return new ViewModel([
				'banners' => $this->getBanners(),
				'diplomas' => $this->getDiplomas(),
				'review_totals' => $reviewDb->getTotals([]),
				'review_items' => $reviewDb->getItems(['home' => true], 1, 2),
			]);
	}
		
	private function getBanners(){
		/* @var $bannerDb BannerDb */
		$bannerDb = $this->serv(BannerDb::class);
		return $bannerDb->getBanners();
	}
	
	private function getDiplomas(){
		/* @var $diplomDb DiplomDb */
		$diplomDb = $this->serv(DiplomDb::class);
		return $diplomDb->getHomeDiplomas();
	}
	
	
	
	/**
	 * @Route(name="order-consultation",route="/order-consultation")
	 */
	public function signeAction(){
		if($this->getRequest()->isPost()){
			
			$data = $this->params()->fromPost();
			
			$insert = filter_var_array($data, [
					'type' => FILTER_SANITIZE_STRING,					
					'phone' => FILTER_SANITIZE_STRING,
					'name' => FILTER_SANITIZE_STRING,
					'skype' => FILTER_SANITIZE_STRING,
					'message' => FILTER_SANITIZE_STRING					
				]);
			
			$insert['phone'] = Phone::normalize($insert['phone']);
			$masterId = $this->params('master', 'any');
			if(is_numeric($masterId)){
				$insert['master_id'] = $masterId;
			} else {
				$insert['master_id'] = null;
			}
			
			$insert['date'] = time();
			$insert['user_id'] = $this->identity()->id;
						
			return new JsonModel(['result' => 'ok']);
		
		} else {
			die;
		}
	}
	
	/**
	 * @Route(name="session-view",route="/session-view")
	 */
	// FIXME remove it from prod
	public function sessionViewAction(){
		$this->session = new Container(UserFlow::class);
		foreach ($this->session['cart'] as $orderDefinition){
			
			print_r($orderDefinition);
			echo "<br /><br /><br />";
			
		}	
		die;
	}
	
	
	
	
}
