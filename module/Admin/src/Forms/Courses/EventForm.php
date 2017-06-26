<?
namespace Admin\Forms\Courses;

use Common\Form\Form;
use Common\Traits\Initializable;
use ZfAnnotation\Annotation\Service;
use Common\Traits\ServiceManagerTrait;
use Common\Traits\ServiceManagerAware;
use Admin\Forms\CourseElements;
use Admin\Model\Courses\CourseDb;
use Admin\Model\Courses\EventDb;
use Admin\Model\Users\UserDb;
use Admin\Forms\UsersElements;

/**
 * @Service(name="Admin\Forms\Courses\EventForm")
 */
class EventForm extends Form implements Initializable, ServiceManagerAware{	
		
	use CourseElements, ServiceManagerTrait, UsersElements;
	
	var $defaultElementDecorator = 'default';
		
	public function init(){

		$courseDb = $this->serv(CourseDb::class);
		$eventDb = $this->serv(EventDb::class);
		$userDb = $this->serv(UserDb::class);
		
		$this->field('course_id', 'course', 'Курс')
			->validator('required', 'Заполните это поле')
			->options($courseDb);
			
		
		$this->field('title', 'text', 'Название')->validator('required');
			
		$this->field('status', 'checkbox', 'Опубликовано');
		
 		$this->field('type', 	'select', 	'Периодичность мероприятия')
 			->options([$eventDb, 'getTypeOptions']);
 		
 		$this->field('date_text', 	'text', 	'Дата мероприятия')->description('Тектовое представление');
 		
 		$this->field('time_text', 	'text', 	'Время мероприятия')->description('Тектовое представление');
 		
 		$this->field('expiration_date', 'date', 'Время окончания записи')->format('d.m.Y H:i');
 		
 		$this->field('place', 	'select', 	'Место')
 			->options([
 					null => 'Не указано',
 					'1' => 'м. Чеховская',
 					'2' => 'м. Пушкинская'
 			]) ;
 		
 		 		
 		$this->field('count', 	  'number', 'Количество мест');
 		
 		$this->field('add_dates', 'textarea', 'Добавить даты')
 			->description('Список дат и времени в формате '.date('Y-m-d H:i').'. Каждая новая дата в новой строке');
 			
 		$this->field('masters', 'multi-users', 'null', ['decorator' => 'simple'])
 			->options($userDb)
	 		->addExtra('attributes', 'data-type = "m"');
 		 		 		
 		$this->field('submit', 'submit-group', null, ['label-save' => 'Сохранить', 'label-apply' => 'Применить', 'decorator' => 'simple']);
 		
 		
 		
 		$this->addClass('g-form');
 		  		
	}
		
} 
