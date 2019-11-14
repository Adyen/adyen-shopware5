<?php

namespace MeteorAdyen\Commands;

use Doctrine\ORM\NoResultException;
use MeteorAdyen\Components\FifoNotificationLoader;
use MeteorAdyen\Components\NotificationManager;
use MeteorAdyen\Components\NotificationProcessor;
use MeteorAdyen\Subscriber\Cronjob\ProcessNotifications as ProcessNotificationsCronjob;
use Shopware\Commands\ShopwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ProcessNotifications
 * @package MeteorAdyen\Commands
 */
class ProcessNotifications extends ShopwareCommand
{
    /**
     * @var FifoNotificationLoader
     */
    private $loader;
    /**
     * @var NotificationProcessor
     */
    private $notificationProcessor;

    /**
     * ProcessNotifications constructor.
     * @param FifoNotificationLoader $loader
     * @param NotificationProcessor $notificationProcessor
     */
    public function __construct(
        FifoNotificationLoader $loader,
        NotificationProcessor $notificationProcessor
    ) {
        $this->loader = $loader;
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
                'Number of notifications to process. Defaults to ' .
                ProcessNotificationsCronjob::NUMBER_OF_NOTIFICATIONS_TO_HANDLE .  '.',
                ProcessNotificationsCronjob::NUMBER_OF_NOTIFICATIONS_TO_HANDLE
            )
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void|null
     * @throws \Doctrine\ORM\ORMException
     * @throws \Enlight_Event_Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $number = $input->getOption('number');

        $this->notificationProcessor->processMany(
            $this->loader->load($number)
        );

        $output->writeln('Done.');
    }
}
