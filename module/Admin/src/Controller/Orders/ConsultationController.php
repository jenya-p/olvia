<?
namespace Admin\Controller\Orders;

use Admin\Forms\Orders\ConsultationForm;
use Admin\Model\Orders\ConsultationDb;
use Common\Annotations\Layout;
use Common\Annotations\Roles;
use Common\CRUDController;
use Common\CRUDEditModel;
use Common\ViewHelper\Flash;
use ZfAnnotation\Annotation\Controller;
use ZfAnnotation\Annotation\Route;
use Zend\View\Model\JsonModel;
use Admin\Model\Users\MasterPricesDb;

/**
 * @Controller
 * @Roles(value="admin")
 * @Layout(value="private")
 * @property ConsultationDb $db 
 */
class ConsultationController extends CRUDController implements CRUDEditModel{
	
	/** @var ConsultationDb */
	var $db;
	
	public function init(){
		$this->db = $this->serv(ConsultationDb::class);		 
		$this->crudInit('order-consult');
	}
	
	/**
	 * @Route(name="order-consult-index",route="/order-consult-index[/f-:f][/p-:p]",extends="private",type="segment")
	 */
	public function consultationIndexAction(){		
		return $this->crudList($this->db);		
	}
	
	protected function index(){
				
	}
	
	/**
	 * @Route(name="order-consult-edit", route="/order-consult-edit/:id",extends="private",type="segment")
	 */
	public function consultationEditAction(){
		return parent::processEditForm(ConsultationForm::class, $this);
	}
		

	/* CRUD Model *************************** */
	
	public function load($id) {
		$item = $this->db->get($id);
		return $item;
	}

	public function create() {
		// return [];
	}

	public function validate(array $data){
		
	}
	
	public function save(array $data){
		if(empty($data['tarif_id'])){
			$data['tarif_id'] = null;
		}
		if($this->isNew){						
			$this->id = $this->db->insert($data);			
			if($this->id == null) throw new \Exception("Ошибка сохранения");
		} else {
			$this->db->updateOne($data, $this->id);			
		}		
		return $this->id;
	}
	
	public function afterSave(){
		if($this->isNew){
			$this->sendFlashMessage("Заявка сохранена", Flash::SUCCESS);
		} else {
			$this->sendFlashMessage("Заявка сохранена", Flash::SUCCESS);
		}
		
		return $this->afterSaveRedirect();
	}
	
	public function edit(){
		
		if(!$this->isNew){
			// $this->layout()->site_url = $this->url()->fromRoute('order-consult', ['id' => $this->id]);
		}
		
		$masterId = $this->form->field('master_id')->value();
		if(!empty($masterId)){
			/* @var $masterPricesDb MasterPricesDb */
			$masterPricesDb = $this->serv(MasterPricesDb::class);
			$prices = $masterPricesDb->getMasterPrices($masterId);			
		}
		return [
			'stat' => $this->db->getStat($this->item['id']),
			'master_prices' => $prices				
		];
	}
	
	
	/**
     * @Route(name="order-consult-delete", route="/order-consult-delete/:id",extends="private",type="segment")
     */
    public function deleteAction(){
    	$id = $this->params('id', 'new');

    	$this->db->deleteOne($id);
    	return new JsonModel(['result' => 'ok']);
//     	return new JsonModel(['result' => 'error', 'message' => 'Удаление невозможно.']);
    }
            	
    /**
     * @Route(name="order-consult-status", route="/order-consult-status/:id",extends="private",type="segment")
     */
    public function statusAction(){
    	$id = $this->params('id', 'new');
    	$item = $this->db->get($id);
    	if(empty($item)){
    		return new JsonModel(['result' => 'error', 'message' => 'Объект не найден']);
    	}
    	$update = ['status' => 0];
    	if($item['status'] == 0){
    		$update['status'] = 1;
    	}
    	$this->db->updateOne($update, $id);
    	return new JsonModel(['result' => 'ok', 'status' => $update['status']]);    	 
    }
    
    /**
     * @Route(name="order-consult-edit-tarifs", route="/order-consult-edit/:id/tarifs",extends="private",type="segment")
     */
    public function tarifsAction(){
    	$id = $this->params('id', null);
    	$item = $this->db->get($id);
    	if(empty($item)){
    		return new JsonModel(['result' => 'error', 'message' => 'Объект (id='.$id.') не найден']);
    	}
    	$masterId = $id = $this->params()->fromQuery('master_id', null);
    	if(empty($masterId)){
    		$masterId = $item['master_id'];
    	}
    	if(!empty($masterId)){
    		/* @var $masterPricesDb MasterPricesDb */
    		$masterPricesDb = $this->serv(MasterPricesDb::class);
    		$prices = $masterPricesDb->getMasterPrices($masterId);
    	}
    	
    	/* @var RendererInterface $renderer */
    	$renderer = $this->serv('ViewRenderer');
    	$html = $renderer->render('admin/orders/consultation/consultation-edit.tarifs.phtml',[
    			'item' => $item,
    			'master_prices' => $prices,
    			'value' => $item['tarif_id']
    	]);
    	
    	return new JsonModel([
    			'result' => 'ok',
    			'html' => $html
    	]);
    }
    
    	
}

