<?
namespace Common;

use Zend\Navigation\Service\AbstractNavigationFactory;
class NavigationFactory extends AbstractNavigationFactory
{
    /**
     * Name of navigation container
     * @var string
     */
    private $name;
    
    /**
     * Constructor
     * 
     * @param  string $name
     * @return void
     */
    public function __construct($name)
    {
        $this->name = $name;
    }
    
    /**
     * @return string
     */
    protected function getName()
    {
        return $this->name;
    }
}