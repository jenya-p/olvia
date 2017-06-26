<?
namespace Admin\Controller\Orders;

use Admin\Forms\Orders\OrdersForm;
use Admin\Model\Orders\OrdersDb;
use Common\Annotations\Layout;
use Common\Annotations\Roles;
use Common\CRUDController;
use Common\CRUDEditModel;
use Common\ViewHelper\Flash;
use ZfAnnotation\Annotation\Controller;
use ZfAnnotation\Annotation\Route;
use Zend\View\Model\JsonModel;
use Admin\Model\Courses\TarifsDb;
use Common\Db\Select;
use Admin\Model\Courses\EventDb;
use Zend\Db\Sql\Expression;
use Common\Utils;
use Zend\Db\Sql\Join;

/**
 * @Controller
 * @Roles(value="admin")
 * @Layout(value="private")
 * @property OrdersDb $db 
 */
class OrdersController extends CRUDController implements CRUDEditModel{
	
	/** @var OrdersDb */
	var $db;
	
	/** @var EventDb */
	var $eventDb;
	
	/** @var TarifsDb */
	var $tarifsDb;
	
	public function init(){
		$this->db = $this->serv(OrdersDb::class);		 
		$this->crudInit('order');
		$this->eventDb = $this->serv(EventDb::class);
		$this->tarifsDb = $this->serv(TarifsDb::class);
	}
	
	/**
	 * @Route(name="order-index",route="/order-index[/f-:f][/p-:p]",extends="private",type="segment")
	 */
	public function ordersIndexAction(){		
		return $this->crudList($this->db);		
	}
	
	protected function index(){
				
	}
	
	/**
	 * @Route(name="order-edit", route="/order-edit/:id",extends="private",type="segment")
	 */
	public function ordersEditAction(){
		return parent::processEditForm(OrdersForm::class, $this);
	}
		

	/* CRUD Model *************************** */
	
	public function load($id) {
		$item = $this->db->get($id);
		
		$event = $this->eventDb->get($item['event_id']);
		
		if(!empty($event)){
			$item['course_id'] = $event['course_id'];
		}		
		
		$item['dates'] = $this->db->getOrderShedule($id);

		$item['use_discounts'] = !empty($item['discounts']);
		
		return $item;
	}

	public function create() {
		return [ 'status' => OrdersDb::STATUS_NEW ];
	}

	public function validate(array $data){
		
		$event = $this->eventDb->get($data['event_id']);
		
		if(empty($event)){
			$this->form('event_id')->error('Не найдено событие (id='.$data['event_id'].')');
			return;
		} else {
			if($event['course_id'] != $data['course_id']){
				$this->form('event_id')->error('Cобытие не соответствует курсу, попробуйте повторить сохранение');
			}
		}
		
		
	}
	
	public function save(array $data){
		
		unset ($data['course_id']);
		$dates = $data['dates'];
		unset($data['dates']);
		
		if(empty($data['use_discounts'])){
			$data['discounts'] = null;			
		}
		
		unset($data['use_discounts']);
		
		if($this->isNew){						
			$this->id = $this->db->insert($data);			
			if($this->id == null) throw new \Exception("Ошибка сохранения");
		} else {
			$this->db->updateOne($data, $this->id);			
		}		

		$this->db->saveOrderShedule($this->id, $dates);
		
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
			
		}
				
		$courseId = $this->form('course_id')->value();
		
		$eventId = $this->form('event_id')->value();
		
		return [
			'stat' => 	$this->db->getStat($this->item['id']),
			'events' => $this->eventDb->getCourseEvents($courseId),
			'shedule' => $this->getEventShedule($eventId, $this->id),
			'tarifs' => $this->tarifsDb->getEventTarifs($eventId),
			'eventShedule' => $this->eventDb->getShedule($eventId)
		];
	}
	
	
	public function getEventShedule($eventId, $orderId){
		$select = new Select(['esh' => 'course_event_shedule']);
		$select->join(['o2s' => 'order_order2shedule'], 'o2s.shedule_id = esh.id',
				['order_count' => new Expression('count(o2s.order_id)')], Join::JOIN_LEFT);
		$nest = $select->where->nest;
		$nest->between('esh.date', time() - 7*24*60*60, time() + 6*7*24*60*60); // планирование на одну неделю назад и 6 вперед.
		if(!empty($orderId)){
			// Кроме дат в диапазоне планирования, нужны так же уже установленные даты
			$dateIdsSelect = new Select(['o2s' 	=> 'order_order2shedule']);
			$dateIdsSelect->reset(Select::COLUMNS)->columns(['shedule_id']);
			$dateIdsSelect->where->equalTo('o2s.order_id', $orderId);
			
			$nest->or->in('o2s.order_id', $dateIdsSelect);
		}
			
		$select->order('esh.date asc');
		$select->group('esh.id');
		$select->where->equalTo('esh.event_id', $eventId);		
		return $select->fetchAll();
	}
	
	/**
     * @Route(name="order-delete", route="/order-delete/:id",extends="private",type="segment")
     */
    public function deleteAction(){
    	$id = $this->params('id', 'new');

    	$this->db->deleteOne($id);
    	return new JsonModel(['result' => 'ok']);
//     	return new JsonModel(['result' => 'error', 'message' => 'Удаление невозможно.']);
    }
    
    
        	
    /**
     * @Route(name="order-status", route="/order-status/:id",extends="private",type="segment")
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
     * @Route(name="order-edit-events", route="/order-edit/:id/events",extends="private",type="segment")
     */
    public function eventsAction(){
    	/* @var RendererInterface $renderer */
    	$renderer = $this->serv('ViewRenderer');
    	
    	$id = $this->params('id', null);
    	$item = $this->db->get($id);
    	$vars = [];
    	
    	if(!empty($item)){
    		$vars['item'] = $item;
    		$vars['dates'] = $this->db->getOrderShedule($item['id']);
    		$vars['tarif_id'] = $item['tarif_id'];
    	}
    	
    	$courseId = $this->params()->fromQuery('course_id', null);
    	if(empty($courseId)){
    		if(empty($item)){
    			throw new \Exception('Не указан course_id');
    		}
    		$event = $this->eventDb->get($item['event_id']);
    		$courseId = $event['course_id'];
    		if(empty($courseId)){
    			throw new \Exception('Не указан course_id');
    		}
    	}
    	
    	if(!empty($courseId)){
    		$events = $this->eventDb->getCourseEvents($courseId);
    		$vars['events'] = $events;
    		
    		if(!empty($events)){

    			$vars['event_id'] = $eventId = $events[0]['id'];    			
    			$vars['tarifs'] = $this->tarifsDb->getEventTarifs($eventId);
    			$vars['eventShedule'] = $this->getOrderShedule($eventId, $id);
    			
    		}
    	}
    	
    	$htmlEvents = $renderer->render('admin/orders/orders/orders-edit.events.phtml',$vars);
    	$htmlTarifs =  $renderer->render('admin/orders/orders/orders-edit.tarifs.phtml'	,$vars);
    	$htmlShedule = $renderer->render('admin/orders/orders/orders-edit.shedule.phtml',$vars);
    	    
    	return new JsonModel([
    			'result' => 'ok',
    			'html_events' => $htmlEvents,
    			'html_tarifs' => $htmlTarifs,
    			'html_shedule' => $htmlShedule
    	]);
    }
    
    /**
     * @Route(name="order-edit-tarifs", route="/order-edit/:id/tarifs",extends="private",type="segment")
     */
    public function tarifsAction(){
    	$id = $this->params('id', null);
    	$item = $this->db->get($id);
    	
    	$vars = [];
    	
    	$eventId = $this->params()->fromQuery('event_id', null);
    	
    	if(empty($eventId)){
    		if(empty($item)){
    			throw new \Exception('Не указан event_id');
    		}
    		$eventId = $item['event_id'];
    	}
    	
    	if(!empty($eventId)){    		
    		$vars['tarifs'] = $this->tarifsDb->getEventTarifs($eventId);
    		$vars['eventShedule'] = $this->getEventShedule($eventId, $id);
    	}

    	$tarifListIds = array_column($vars['tarifs'], 'id');
    	
    	if(!empty($item)){
    		$vars['item'] = $item;    		
    		$tarifId = $item['tarif_id'];    		     		
    	} 
    	
    	if(!empty($item) && in_array($tarifId, $tarifListIds) ) {
    		$vars['tarif_id'] = $tarifId;
    		$vars['dates'] = $this->db->getOrderShedule($item['id']);
    	} else {
    		$vars['tarif_id'] = $tarifListIds[0];
    		$vars['dates'] = $this->eventDb->getDefaultOrderDates($vars['tarif_id']);
    	}
    	
    	/* @var RendererInterface $renderer */
    	$renderer = $this->serv('ViewRenderer');
    	$htmlTarifs =  $renderer->render('admin/orders/orders/orders-edit.tarifs.phtml'	,$vars);
    	$htmlShedule = $renderer->render('admin/orders/orders/orders-edit.shedule.phtml',$vars);
    	 
    	return new JsonModel([
    			'result' => 'ok',
    			'html_tarifs' => $htmlTarifs,
    			'html_shedule' => $htmlShedule
    	]);
    }
    
    
    /**
     * 
     * Подсказка для выбора курсов, разниуа с основным в том, что выводятся только курсы с мероприятиями
     * @Route(name="order-edit-course-suggestion'",route="/order-edit-course-suggestion",extends="private",type="segment")
     */
    public function courseSuggestionAction(){
    	$query = $this->params()->fromQuery('q');
    	$query = mb_strtolower($query);
    	if(empty($query)){
    		return new JsonModel([]);
    	}
    
    	$lang = $this->eventDb->lang();
    	
    	$select = new Select(['c' => 'courses']);
    	$select->columns(['id', 'value' => 'title_'.$lang]);
    	$select->join(['e' => 'course_events'], 'e.course_id = c.id', [], Join::JOIN_INNER);
    	
    	$nest = $select->where->nest;
    	$nest -> expression('concat(" ", LOWER(c.title_'.$lang.')) like ?', "% ".$query."%")
    		->or->expression('c.alias like ?', $query."%");
    
    	$select->limit(20);
    	 
    	$suggestions = $select->fetchAll();
    
    	return new JsonModel([
    			"query" => $query,
    			"suggestions" => $suggestions]);
    
    }
    
    
}

