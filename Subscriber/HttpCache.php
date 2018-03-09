<?php

namespace NetiStockChangeCacheCondition\Subscriber;

use Enlight\Event\SubscriberInterface;
use Enlight_Event_EventArgs;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Article\Detail;

class HttpCache implements SubscriberInterface
{
    /**
     * @var ModelManager
     */
    private $em;

    /**
     * HttpCache constructor.
     *
     * @param ModelManager $em
     */
    public function __construct(ModelManager $em)
    {
        $this->em = $em;
    }

    /**
     * Returns an array of event names this subscriber wants to listen to.
     *
     * The array keys are event names and the value can be:
     *
     *  * The method name to call (position defaults to 0)
     *  * An array composed of the method name to call and the priority
     *  * An array of arrays composed of the method names to call and respective
     *    priorities, or 0 if unset
     *
     * For instance:
     *
     * <code>
     * return array(
     *     'eventName0' => 'callback0',
     *     'eventName1' => array('callback1'),
     *     'eventName2' => array('callback2', 10),
     *     'eventName3' => array(
     *         array('callback3_0', 5),
     *         array('callback3_1'),
     *         array('callback3_2')
     *     )
     * );
     *
     * </code>
     *
     * @return array The event names to listen to
     */
    public static function getSubscribedEvents()
    {
        return [
            'Shopware_Plugins_HttpCache_ShouldNotInvalidateCache' => 'shouldNotInvalidateCache',
        ];
    }

    public function shouldNotInvalidateCache(Enlight_Event_EventArgs $args)
    {
        $entity = $args->get('entity');

        if (! ($entity instanceof Detail)) {
            return null;
        }

        /** @var \Doctrine\ORM\UnitOfWork $unitOfWork */
        $unitOfWork = $this->em->getUnitOfWork();
        $changeSet  = $unitOfWork->getEntityChangeSet($entity);

        if (1 < count($changeSet) || ! isset($changeSet['inStock'])) {
            return null;
        }

        $modeBefore = 0 < $changeSet['inStock'][0];
        $modeAfter  = 0 < $changeSet['inStock'][1];

        if ($modeAfter === $modeBefore) {
            return true;
        }

        return null;
    }
}
