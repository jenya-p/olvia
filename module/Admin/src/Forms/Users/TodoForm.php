<?
namespace Admin\Forms\Users;

use Common\Form\Form;
use Common\Traits\Initializable;
use ZfAnnotation\Annotation\Service;
use Admin\Forms\UsersElements;
use Admin\Model\Users\UserDb;
use Common\Traits\ServiceManagerAware;
use Common\Traits\ServiceManagerTrait;
use Admin\Model\Users\TodoDb;

/**
 * @Service(name="Admin\Forms\Users\TodoForm")
 */
class TodoForm extends Form implements Initializable, ServiceManagerAware{
		
	use UsersElements, ServiceManagerTrait;
	
	var $defaultElementDecorator = 'default';
		
	public function init(){
		$userDb = $this->serv(UserDb::class);
		$todoDb = $this->serv(TodoDb::class);
		
		
		$this->field('title', 'text', 'Заголовлк');
 		$this->field('body', 'textarea', 'Описание');
 		$this->field('status', 'select', 'Статус')
 			->options($todoDb->statusOptions());
 		
 		$this->field('priority', 'number', 'Приоритет');
 		
 		$this->field('user_id', 'user', 'Cотрудник')
 			->options($userDb)
 			->addExtra('attributes', 'data-type = "a"');
 		
 		$this->field('intensity', 'number', 'Трудоемкость');
 		$this->field('till_date', 'date', 'Дата')->format('d.m.Y H:i');
 		 		 		
 		$this->field('submit', 'submit-group', null, ['label-save' => 'Сохранить', 'label-apply' => 'Применить', 'decorator' => 'simple']);
 		
 		$this->addClass('g-form');
 		  		
	}
		
} 
