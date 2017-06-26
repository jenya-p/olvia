<? 
namespace Common\ViewHelper;
use ZfAnnotation\Annotation\ViewHelper;

class Config extends \Zend\View\Helper\AbstractHelper {
	
	var $config;
	
    public function __construct(&$config){
        $this->config = &$config;
    }

    public function __invoke($key = null){
    	if($key == null){
    		return $this->config;
    	} else {
    		return $this->config[$key];
    	}
    }
}