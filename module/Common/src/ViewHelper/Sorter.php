<? 

namespace Common\ViewHelper;
use Zend\View\Helper\AbstractHelper;
use ZfAnnotation\Annotation\ViewHelper;

/**
 * @ViewHelper(name="sorter")
 */
class Sorter extends AbstractHelper{
	
	
	public function __invoke($title, $name, $value){
		$class = 'sorter';
		if(is_array($value) && count($value) == 2 && $value[0] == $name){
			$class .= ' '.$value[1];
		}
		
		return '<a href="javascript:;" class="'.$class.'" data-name="'.$name.'">'.$title.'</a>';
	}
	
}
