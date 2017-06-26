<?
namespace Admin\Forms\Orders;

use Common\Form\Form;
use Common\Traits\Initializable;
use ZfAnnotation\Annotation\Service;
use Admin\Model\Orders\ConsultationDb;
use Admin\Forms\UsersElements;
use Common\Traits\ServiceManagerTrait;
use Admin\Model\Users\UserDb;
use Common\Traits\ServiceManagerAware;

/**
 * @Service(name="Admin\Forms\Orders\ConsultationForm")
 */
class ConsultationForm extends Form implements Initializable, ServiceManagerAware{

	use UsersElements, ServiceManagerTrait;
	
	var $defaultElementDecorator = 'default';
		
	public function init(){
		
		/* @var $consultationDb ConsultationDb */
		$consultationDb = $this->serv(ConsultationDb::class);
		
		/* @var $userDb UserDb */
		$userDb = $this->serv(UserDb::class);
		
		$this->field('name',  'text', 	'Имя в заявке');
 		$this->field('phone', 'phone', 	'Телефон для связи');
 		$this->field('skype', 'skype', 	'Скайп');
 		
 		$this->field('user_id', 'user', 'Клиент')
	 		->options($userDb)
	 		->addExtra('attributes', 'data-type = "c"');
 			
 		$this->field('status', 'select', 'Статус')
 			->options([$consultationDb, 'statusOptions']);
 		
 		$this->field('message', 'textarea', 'Сообщение клиента');
 		 		
 		$this->field('master_id', 'user', 'Специалист')
	 		->options($userDb)
	 		->addExtra('attributes', 'data-type = "m"');
 		
 		$this->field('tarif_id',  'number', 'Тариф');
 		
 		$this->field('meet_date', 'date', 'Дата и время мероприятия')->format('d.m.Y H:i');
 		 		 		
 		$this->field('submit', 'submit-group', null, ['label-save' => 'Сохранить', 'label-apply' => 'Применить', 'decorator' => 'simple']);
 		
 		$this->addClass('g-form');
 		  		
	}
		
} 
