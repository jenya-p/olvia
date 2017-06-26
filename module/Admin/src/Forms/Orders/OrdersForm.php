<?
namespace Admin\Forms\Orders;

use Common\Form\Form;
use Common\Traits\Initializable;
use ZfAnnotation\Annotation\Service;
use Admin\Model\Orders\OrdersDb;
use Admin\Model\Users\UserDb;
use Common\Traits\ServiceManagerAware;
use Common\Traits\ServiceManagerTrait;
use Admin\Forms\UsersElements;
use Admin\Forms\CourseElements;
use Admin\Model\Courses\CourseDb;
use Admin\Forms\DiscountsElements;

/**
 * @Service(name="Admin\Forms\Orders\OrdersForm")
 */
class OrdersForm extends Form implements Initializable, ServiceManagerAware{

	use UsersElements, ServiceManagerTrait, CourseElements, DiscountsElements;
	
	var $defaultElementDecorator = 'default';
		
	public function init(){
		/* @var $courseDb CourseDb */
		$courseDb = $this->serv(CourseDb::class);
		
		/* @var $ordersDb OrdersDb */
		$ordersDb = $this->serv(OrdersDb::class);
		
		/* @var $userDb UserDb */
		$userDb = $this->serv(UserDb::class);
		
		$this->field('name',  'text', 	'Имя в заявке');
		$this->field('phone', 'phone', 	'Телефон для связи');
		$this->field('skype', 'skype', 	'Скайп');
			
		$this->field('user_id', 'user', 'Клиент')
		->options($userDb)
		->addExtra('attributes', 'data-type = "c"');
		
		$this->field('status', 'select', 'Статус')
		->options([$ordersDb, 'statusOptions']);
			
		$this->field('message', 'textarea', 'Сообщение клиента');
 		
		
		$this->field('course_id', 'course', 'Курс')
			->validator('required', 'Укажите курс')
			->options($courseDb);
			
		$this->field('event_id', 'hidden', ['text' => null])
			->validator('required', 'Укажите мероприятие')
			->decorator('only-errors');
				
 		$this->field('tarif_id', 'hidden', ['text' => null])
 			->decorator('only-errors');
 		
 		$this->field('dates', 'hidden')
 			->decorator('only-errors');
 		
 		$this->field('price', 		'number', 	'Цена'); 		
 		 		
 		$this->field('use_discounts', 'checkbox',  'Применять скидки тарифа');
 		
 		$this->field('payed', 		'number',  'Оплачно');
 		
 		$this->field('submit', 'submit-group', null, ['label-save' => 'Сохранить', 'label-apply' => 'Применить', 'decorator' => 'simple']);
 		
 		$this->addClass('g-form');
 		  		
	}
		
} 
