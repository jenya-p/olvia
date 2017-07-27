<?

namespace Application\Controller;

use Common\SiteController;
use ZfAnnotation\Annotation\Controller;
use ZfAnnotation\Annotation\Route;
use Common\Traits\LoggerTrait;
use Common\Traits\LoggerAware;
use Application\Model\Content\ReviewDb;
use Admin\Model\Content\ReviewRefsDb;
use Common\Db\Select;
use Application\Model\MasterDb;
use Zend\View\Model\ViewModel;
use Zend\View\Model\JsonModel;
use Common\ViewHelper\Phone;
use Api\InitialImport\InitialImportController;
use Common\ImageService;
use Common\Db\Adapter;
use Application\Model\Courses\CourseDb;

/**
 * @Controller
 */
class ReviewsController extends SiteController implements LoggerAware{

	use LoggerTrait;
	
	const IPP = 25; // items per page
	
	/** @var ReviewDb */
	var $reviewDb;
	
	public function init(){
		$this->reviewDb = $this->serv(ReviewDb::class);
	}
	
	/**
	 * @Route(name="review-index",route="/reviews[/][:subject]",type="Segment")
	 */
	public function indexAction() {
		if($this->params()->fromQuery('p', null) === '1'){
			return $this->redirect()->toRoute('review-index');
		}
				
		$page = intval($this->params()->fromQuery('p', 1));		
		
		$subjectAlias = filter_var($this->params('subject', null), FILTER_SANITIZE_STRING);
		
		$ret = new ViewModel();
		$filter = [];
		
		$ret->setVariable('title', 'Все отзывы');
		if($subjectAlias == 'home'){			
			$filter['home'] = true;			
		} else if(!empty($subjectAlias)){
			$subjectRef = $this->getSubjectRefByAlias($subjectAlias);
			if(empty($subjectRef)){
				
				return $this->notFoundAction(); 
			}	
			$filter['subject'] = [
				'entity' => $subjectRef['subject_entity'],
				'item_id' => $subjectRef['subject']['id']					
			];
						if($subjectRef['subject_entity'] == ReviewRefsDb::ENTITY_MASTER){
				$ret->setVariable('title', 'Отзывы о специалисте '.$subjectRef['subject']['name']);
			} else if($subjectRef['subject_entity'] == ReviewRefsDb::ENTITY_COURSE){
				$ret->setVariable('title', 'Отзывы о курсе'.$subjectRef['subject']['name']);
			} else {
				$ret->setVariable('title', 'Отзывы'.$subjectRef['subject']['name']);
			}
			
		}
		
		$totals = $this->reviewDb->getTotals($filter);
		$items = $this->reviewDb->getItems($filter, $page, self::IPP);
		
		$ret->setVariables([
				'subject' => $subjectAlias,
				'totals' => $totals,
				'items' => $items,
				'page' => $page,
				'pageCount' => ceil($totals['count'] / self::IPP),
		]);
		
		if($this->getRequest()->isXmlHttpRequest()){			
			$ret->setTerminal(true);
			$ret->setTemplate('/application/reviews/index.items.phtml');
		}		
		
		return $ret;
		
	}
	
	
	/**
	 * @Route(name="reviews-post",route="/reviews-post",type="Segment")
	 */
	public function postReviewAction(){
		if($this->getRequest()->isPost()){
		
			$insert = [];
			
			$insert['name'] = 	$this->params()->fromPost('name', null);
			$insert['name'] = filter_var($insert['name'], FILTER_SANITIZE_STRING);
			
			$insert['phone'] = 	$this->params()->fromPost('phone', null);			
			$insert['phone'] = Phone::normalize($insert['phone']);
			
			$insert['social'] = 	$this->params()->fromPost('social', null);
			$insert['social'] = filter_var($insert['social'], FILTER_SANITIZE_URL);
			if(!empty($insert['social'])){
				$insert['userpic'] = $this->getImageFromSocial($insert['social'], $this->reviewDb->getNextId());
			}			
			
			$insert['body'] = 	$this->params()->fromPost('message', null);
			$insert['body'] = filter_var($insert['body'], FILTER_SANITIZE_STRING);
			
			$insert['date'] = time();
			$insert['status'] = 0;
			
			$id = $this->reviewDb->insert($insert);
			
			$subjectAlias = filter_var($this->params()->fromPost('subject', null), FILTER_SANITIZE_STRING);
			$subject = $this->getSubjectRefByAlias($subjectAlias);
			
			if(!empty($subject) && !empty($id)){
				/* @var $reviewRefDb ReviewRefsDb */
				$reviewRefDb = $this->serv(ReviewRefsDb::class);
				$reviewRefDb->addRef($id, $subject['subject_entity'], $subject['subject']['id']);
				
			}

			return new JsonModel(['result' => 'ok']);
				
		} else {
			die;
		}
		
	}
		
	
	
	private function getSubjectRefByAlias($subjectAlias){
			
		$delimiterPos = strpos($subjectAlias, '-');
		if($delimiterPos == false) return null;
		
		$entity = substr($subjectAlias, 0, $delimiterPos);
		$subjectAlias = substr($subjectAlias, $delimiterPos+1);
		$ret = [];
		$ret['subject_entity'] = $entity;

		if($entity == ReviewRefsDb::ENTITY_MASTER){
			/* @var $masterDb MasterDb */
			$masterDb = $this->serv(MasterDb::class);
			$subject = $masterDb->getByAlias($subjectAlias);			
			$ret['subject'] = $subject;
			
		} else if ($entity == ReviewRefsDb::ENTITY_COURSE){			
			/* @var $courseDb CourseDb */
			$courseDb = $this->serv(CourseDb::class);
			$subject = $courseDb->getByAlias($subjectAlias);
			$ret['subject'] = $subject;
			
		} else {
			return false;
		}
		if(empty($subject)){
			return false;
		}
	
		return $ret;
	}
	
	
	public function getImageFromSocial($url, $nextId){		
		$urlParts = parse_url($url);
		$imageUrl = null;
		if(strpos($urlParts['host'], 'vk.com') !== false){
		
			$vkId = substr($urlParts['path'], 1);			

			try{
				$request = 'https://api.vk.com/method/users.get?uids='.$vkId.'&fields=photo_100,status';
				$response = @file_get_contents($request);				
				$info = array_shift(json_decode($response)->response);				
				$imageUrl = $info->photo_100;
			} catch (\Exception $e){}
						
		} else if (strpos($urlParts['host'], 'facebook.com') !== false){
			
		}
		
		if (!empty($imageUrl)){
			/* @var $imageService ImageService */
			$imageService = $this->serv(ImageService::class);
			try {
				return $imageService->importUrl($imageUrl, 'reviews/'.$nextId);
			} catch (\Exception $e){}
		}
		
		return null;
	}
	
	
}
