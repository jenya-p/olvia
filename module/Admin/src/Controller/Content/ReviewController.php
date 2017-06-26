<?
namespace Admin\Controller\Content;

use Admin\Forms\Content\ReviewForm;
use Admin\Model\Content\ReviewDb;
use Common\Annotations\Layout;
use Common\Annotations\Roles;
use Common\CRUDController;
use Common\CRUDEditModel;
use Common\ViewHelper\Flash;
use ZfAnnotation\Annotation\Controller;
use ZfAnnotation\Annotation\Route;
use Common\ImageService;
use Zend\View\Model\JsonModel;
use Admin\Model\Content\ReviewRefsDb;
use Admin\Model\Users\MasterDb;
use Admin\Model\Courses\CourseDb;

/**
 * @Controller
 * @Roles(value="admin")
 * @Layout(value="private")
 * @property ReviewDb $db 
 */
class ReviewController extends CRUDController implements CRUDEditModel{
	
	/** @var ReviewDb */
	var $db;
	
	/** @var ReviewRefsDb */
	var $reviewRefsDb;
	
	public function init(){
		$this->db = $this->serv(ReviewDb::class);
		$this->reviewRefsDb = $this->serv(ReviewRefsDb::class);
		$this->crudInit();
	}
	
	/**
	 * @Route(name="review-index",route="/review-index[/f-:f][/p-:p]",extends="private",type="segment")
	 */
	public function reviewIndexAction(){		
		return $this->crudList($this->db);		
	}
	
	protected function index(){
			
	}
	
	/**
	 * @Route(name="review-edit", route="/review-edit/:id",extends="private",type="segment")
	 */
	public function reviewEditAction(){
		return parent::processEditForm(ReviewForm::class, $this);
	}
	
	
	/**
	 * @Route(name="review-image-upload", route="/review-upload/[:id]",extends="private",type="segment")
	 */
	public function uploadAction(){
		/* @var $imageService ImageService */
		$imageService = $this->serv(ImageService::class);
	
		$id = $this->params('id', 'new');
	
		if($id == 'new'){
			$id = $this->db->getNextId();
		}
	
		$image = $imageService->import($this->params()->fromFiles('userpic'), 'reviews/'.$id);
	
		return new JsonModel([
				'result' => 'ok',
				'original' => $image,
				'preview' => $imageService->resize($image,  ImageService::SIZE_REVIEW_USERPICK)
		]);
	
	}
	

	/* CRUD Model *************************** */
	
	public function load($id) {
		$item = $this->db->get($id);		
		return $item;
	}

	public function create() {
		return ['date' => time()];
	}

	public function validate(array $data){
		
	}
	
	public function save(array $data){
		
		if($this->isNew){						
			$this->id = $this->db->insert($data);			
			if($this->id == null) throw new \Exception("Ошибка сохранения");
		} else {
			$this->db->updateOne($data, $this->id);			
		}		
		return $this->id;
	}
	
	public function afterSave(){		
		$this->sendFlashMessage("Отзыв сохранен", Flash::SUCCESS);
		
		return $this->afterSaveRedirect();
	}
	
	public function edit(){
		/* @var $imageService ImageService */
		$imageService = $this->serv(ImageService::class);
		
		$uploadUrl = $this->url()->fromRoute('private/review-image-upload', ['id' => $this->id]);
		
		$this->form->field('userpic')
			->url($uploadUrl)
			->preview($imageService->resize($this->item['userpic'], ImageService::SIZE_REVIEW_USERPICK))
			->full($imageService->resize($this->item['userpic']));
		
		if(!$this->isNew){
			// $this->layout()->site_url = $this->url()->fromRoute('review', ['id' => $this->id]);

			
		}
		return [
			'stat' => $this->db->getStat($this->item['id']),
			'refs' => $this->reviewRefsDb->getRefs($this->item['id'])
		];
	}
	
	
	/**
     * @Route(name="review-delete", route="/review-delete/:id",extends="private",type="segment")
     */
    public function deleteAction(){
    	$id = $this->params('id', 'new');
    	$this->db->deleteOne($id);
    	return new JsonModel(['result' => 'ok']);
    }
    
    /**
     * @Route(name="review-status", route="/review-status/:id[/:field]",extends="private",type="segment")
     */
    public function statusAction(){
    	$id = $this->params('id', 'new');
    	$field = $this->params('field', 'status');
    	 
    	$item = $this->db->get($id);
    	if(empty($item)){
    		return new JsonModel(['result' => 'error', 'message' => 'Объект не найден']);
    	}
    	$update = [$field => 0];
    	if($item[$field] == 0){
    		$update[$field] = 1;
    	}
    	 
    	$this->db->updateOne($update, $id);
    	return new JsonModel(['result' => 'ok', $field => $update[$field]]);
    
    }
    
    
    /**
     * @Route(name="review-ref-delete", route="/review-ref-delete/:review_id/:entity/:item_id",extends="private",type="segment")
     */
    public function refDeleteAction(){
    	$reviewId = $this->params('review_id', null);
    	$entity = $this->params('entity', null);
    	$itemId = $this->params('item_id', null);
    
    	$this->reviewRefsDb->removeRef($reviewId, $entity, $itemId);    	
    	
    	return new JsonModel(['result' => 'ok']);
    }
    
    /**
     * @Route(name="review-ref-add", route="/review-ref-add/:review_id/:entity/:item_id",extends="private",type="segment")
     */
    public function refAddAction(){
    	$reviewId = $this->params('review_id', null);
    	$entity = $this->params('entity', null);
    	$itemId = $this->params('item_id', null);
    
    	$this->reviewRefsDb->addRef($reviewId, $entity, $itemId);
    	$ref = [
	    		'review_id' => $reviewId,
	    		'entity' => $entity, 
	    		'item_id' => $itemId    			
	    	];
    	$this->reviewRefsDb->buildItem($ref);
    	
    	/* @var RendererInterface $renderer */
    	$renderer = $this->serv('ViewRenderer');
    	 
    	return new JsonModel(['result' => 'ok', 
    		'html' => $renderer->render('admin/content/review/review-edit.ref.phtml',['ref' => $ref])
    	]);
    }
    	
    
    /**
     * @Route(name="review-ref-suggestion",route="/review-ref-suggestion",extends="private",type="segment")
     */
    public function refSuggestionAction(){
    	$query = $this->params()->fromQuery('q');
    	if(empty($query)){
    		return new JsonModel([]);
    	}
    	
    	/* @var $masterDb MasterDb */
    	$masterDb = $this->serv(MasterDb::class);    	
    	$select = $masterDb->getSelect(['query' => $query]);
    	$select->order('id');
    	    		
    	// TODO Union query with courses. See, how to do this: http://stackoverflow.com/questions/13649648/select-union-in-zf2-query 
    	
    	$suggestions = [];
    	$items = $select->fetchAll();    	
    	foreach ($items as $item) {
    		$suggestions[] = [
    			'value' => "Спец. ".$item['displayname'],
    			'entity' => 'master',
    			'item_id' => $item['id'],
    			'id' => $item['id']
    		];    	
    	}
    	
    	/* @var $courseDb CourseDb */
    	$courseDb = $this->serv(CourseDb::class);
    	$select = $courseDb->getSelect(['query' => $query]);
    	$select->order('id');    	    	
    	$items = $select->fetchAll();
    	foreach ($items as $item) {
    		$suggestions[] = [
    				'value' => "Курс. ".$item['title_ru'],
    				'entity' => 'course',
    				'item_id' => $item['id'],
    				'id' => $item['id']
    		];
    	}
    	
    	
    	return new JsonModel([
    			"query" => $query,
    			"suggestions" => $suggestions]);
    		
    
    }
    
}

