<?
namespace Common; 


use Admin\ViewHelper\Comments;
use Application\ViewHelper\Popup;
use Application\ViewHelper\UserFlowFlash;
use Common\ViewHelper\Flash;
use Common\ViewHelper\RouteName;
use Main\View\Helper\Html;
use Zend\View\Renderer\PhpRenderer;
	
/**
 * @method RouteName routeName()
 * @method Flash flash() 
 * @method Sidebar sidebar()
 * @method Assets assets()
 * @method Html html()
 * @method Comments adminComments()
 * @method Popup popup($name = null, $vars = null)  
 * @method UserFlowFlash userFlowFlash()  
 */
class View extends PhpRenderer{
	
}