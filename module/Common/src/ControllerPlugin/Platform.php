<?
namespace Common\ControllerPlugin;
use Common\ViewHelper\Flash;
use Zend\Mvc\Controller\Plugin\AbstractPlugin;

/**
 * @ControllerPlugin(name = "platform")
 */
class SendFlashMessage extends AbstractPlugin implements {
	
	public function __construct(){
		
	}
	
	public function __invoke($msg = null, $class=Flash::INFO){
		return $this;	
    }
    
    public function isMobile(){
    	
    }
    
    public function isDesktop(){
    	
    }
}
    