##php##
/*
 * This file is autogenerate and part of the Austral Admin Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ##NAMESPACE##;

use Austral\FormBundle\Mapper\Fieldset;
use Austral\FormBundle\Mapper\GroupFields;
use Austral\FormBundle\Field as Field;

use Austral\FilterBundle\Filter\Type as FilterType;


use Austral\ListBundle\DataHydrate\DataHydrateORM;
use Austral\ListBundle\Column as Column;

use Austral\AdminBundle\Admin\Admin;
use Austral\AdminBundle\Admin\AdminModuleInterface;

use Austral\AdminBundle\Admin\Event\FilterEventInterface;
use Austral\AdminBundle\Admin\Event\FormAdminEvent;
use Austral\AdminBundle\Admin\Event\ListAdminEvent;

use Symfony\Component\Validator\Constraints as Constraints;

use Doctrine\ORM\QueryBuilder;

use Exception;

/**
* ##NAME## Admin .
* @author Matthieu Beurel <matthieu@austral.dev>
* @final
*/
class ##CLASSNAME## extends Admin implements AdminModuleInterface
{

  /**
   * @return array
   */
  public function getEvents() : array
  {
    return array(
      FormAdminEvent::EVENT_UPDATE_BEFORE =>  "formUpdateBefore"
    );
  }

  /**
   * @param FilterEventInterface $listAdminEvent
   *
   * @throws Exception
   */
  public function configureFilterMapper(FilterEventInterface $listAdminEvent)
  {
    $listAdminEvent->getFilterMapper()->filter("default")
      ##FILTER_MAPPER##;
  }

  /**
   * @param ListAdminEvent $listAdminEvent
   */
  public function configureListMapper(ListAdminEvent $listAdminEvent)
  {
    $listAdminEvent->getListMapper()
      ##LIST_MAPPER##;
  }

  /**
   * @param FormAdminEvent $formAdminEvent
   *
   * @throws Exception
   */
  public function configureFormMapper(FormAdminEvent $formAdminEvent)
  {
    $formAdminEvent->getFormMapper()
      ##FORM_MAPPER##;
  }

  /**
   * @param FormAdminEvent $formAdminEvent
   *
   * @throws Exception
   */
  protected function formUpdateBefore(FormAdminEvent $formAdminEvent)
  {

  }

}
