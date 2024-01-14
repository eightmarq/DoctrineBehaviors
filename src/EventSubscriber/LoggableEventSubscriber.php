<?php

declare(strict_types=1);

namespace Knp\DoctrineBehaviors\EventSubscriber;

use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Knp\DoctrineBehaviors\Contract\Entity\LoggableInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

#[AsDoctrineListener(event: Events::postPersist)]
#[AsDoctrineListener(event: Events::postUpdate)]
#[AsDoctrineListener(event: Events::preRemove)]
final class LoggableEventSubscriber
{
    public function __construct(
        private LoggerInterface $logger
    ) {
    }

    public function postPersist(LifecycleEventArgs $lifecycleEventArgs): void
    {
        $object = $lifecycleEventArgs->getObject();
        if (! $object instanceof LoggableInterface) {
            return;
        }

        $createLogMessage = $object->getCreateLogMessage();
        $this->logger->log(LogLevel::INFO, $createLogMessage);

        $this->logChangeSet($lifecycleEventArgs);
    }

    public function postUpdate(LifecycleEventArgs $lifecycleEventArgs): void
    {
        $object = $lifecycleEventArgs->getObject();
        if (! $object instanceof LoggableInterface) {
            return;
        }

        $this->logChangeSet($lifecycleEventArgs);
    }

    public function preRemove(LifecycleEventArgs $lifecycleEventArgs): void
    {
        $object = $lifecycleEventArgs->getObject();

        if ($object instanceof LoggableInterface) {
            $this->logger->log(LogLevel::INFO, $object->getRemoveLogMessage());
        }
    }

    /**
     * Logs entity changeset
     */
    private function logChangeSet(LifecycleEventArgs $lifecycleEventArgs): void
    {
        /** @var EntityManagerInterface $objectManager */
        $objectManager = $lifecycleEventArgs->getObjectManager();
        $unitOfWork = $objectManager->getUnitOfWork();
        $object = $lifecycleEventArgs->getObject();

        $objectClass = $object::class;
        $classMetadata = $objectManager->getClassMetadata($objectClass);

        /** @var LoggableInterface $object */
        $unitOfWork->computeChangeSet($classMetadata, $object);
        $changeSet = $unitOfWork->getEntityChangeSet($object);

        $message = $object->getUpdateLogMessage($changeSet);

        if ($message === '') {
            return;
        }

        $this->logger->log(LogLevel::INFO, $message);
    }
}
