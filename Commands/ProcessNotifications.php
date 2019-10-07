<?php

namespace MeteorAdyen\Commands;

use MeteorAdyen\Components\NotificationProcessor;
use MeteorAdyen\Models\Notification;
use Shopware\Commands\ShopwareCommand;
use Shopware\Components\Model\ModelManager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ProcessNotifications
 * @package MeteorAdyen\Commands
 */
class ProcessNotifications extends ShopwareCommand
{
    /**
     * @var ModelManager
     */
    private $models;
    /**
     * @var NotificationProcessor
     */
    private $notificationProcessor;

    /**
     * ProcessNotifications constructor.
     * @param ModelManager $models
     * @param NotificationProcessor $notificationProcessor
     */
    public function __construct(
        ModelManager $models,
        NotificationProcessor $notificationProcessor
    ) {
        $this->models = $models;
        $this->notificationProcessor = $notificationProcessor;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('meteor:adyen:process:notifications')
            ->setDescription('Process notifications in queue')
            ->addOption(
                'number',
                'no',
                \Symfony\Component\Console\Input\InputOption::VALUE_OPTIONAL,
                'Number of notifications to process. Defaults to 1.',
                1
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $number = $input->getOption('number');

        $repository = $this->models->getRepository(Notification::class);
        $qb = $repository->createQueryBuilder('getNotification');
        $qb->select()
            ->orderBy('getNotification.id', 'ASC');
        /** @var Notification $notification */
        $notification = $qb->getQuery()->getResult();

        if (!$notification) {
            // not found
            return;
        }

        $this->notificationProcessor->process($notification[0]);
    }
}
