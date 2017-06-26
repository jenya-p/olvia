<?
namespace Admin\Forms;

use Common\Form\Element;
use Admin\Model\Users\UserDb;
use Common\Traits\ServiceManagerAware;
use Zend\View\HelperPluginManager;
use Zend\View\Helper\Url;

trait CourseElements {
	
	protected function htmlCourse(Element $element){
	
		$element->addClass('course-select');
		$extras = $element->extraString();
		
		$value = $element->value();
		if(!empty($value)){
			$option = $element->option($element->value());
			if(empty($option)){
				$label = '';
			} else {
				$label = $option->label();
				$alias = $option->extra()['alias'];
			}
		} else {
			$label = '';
		}
		
		$ret = '<input type="hidden" name="'.$element->name().'" value="'.$value.'" id="'.$element->id().'_hidden"/>'."\n";

		/* @var $vhm HelperPluginManager */
		$vhm = $this->serv('ViewHelperManager');
		$helper = $vhm->get('url');
		
		
		if($element->disabled() && !empty($value)){
			
			$ret .= '<a href="'.$helper('private/course-edit', ['id' => $value]).'" title="Редактировать" class="course-link"><i class="fa fa-book"></i>&nbsp;&nbsp;'.$label.'</a>';
					
		} else {
			$ret .= '<input type="text" value="'.$label.'" '.$extras.'/>';
			
			if(!empty($value)){
				
				$ret .= '<span class="buttons"><a href="'.$helper('private/course-edit', ['id' => $value]).'" title="Редактировать"><i class="fa fa-book"></i></a>';
				$ret .= '</span>';
			}
			
			
		}
		
		
			   		
		return $ret;
	
	}
	
	protected function parseCourse(Element $element, $data){
		$val = $data[$element->name()];
		if(empty($val)){
			$val = null;
		}	
		$element->value($val);
	}
	
	
	
	protected function htmlCourses(Element $element){
		
		$element->addClass('courses-add');
		$extras = $element->extraString();
		
		$ret = '';
		$ids = [];

		foreach ($element->value() as $courseId){
			$course = $element->option($courseId);
			
			$courseData = $course->label();
			
			$ret .= '<tr data-id="'.$course->value().'">
						<td>'.$course->label().'</td>	
						<td class="options">
							<a href="javascript:;" class="fa fa-remove course-remove"></a>
						</td>
					</tr>';
			$ids[] = $course->value();
		}
		
		$ret = '<table class="item-list">
					<tr>
						<th>Курс</th>
						<th></th>
					</tr>
					'.$ret.'</table>';
		
		$ret .= '<input type="hidden" name="'.$element->name().'" value="'.implode(',', $ids).'" id="'.$element->id().'_hidden" class="courses-hidden"/>'."\n".
				'<input type="text" value="" '.$extras.' placeholder="Добавить курс"/>';
		
		return '<div class="courses-field-wrapper">'.$ret.'</div>';
	}
	
	protected function parseCourses(Element $element, $data){
		if(!isset($data[$element->name()]) || empty($data[$element->name()])){
			$element->value([]);
			return ;
		}
		$val = explode(',', $data[$element->name()]);
		$element->value($val);
	}
	
	
}