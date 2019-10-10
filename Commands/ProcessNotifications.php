<?php

namespace MeteorAdyen\Commands;

use Doctrine\ORM\NoResultException;
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
     * @var NotificationManager
     */
    private $notificationManager;
    /**
     * @var NotificationProcessor
     */
    private $notificationProcessor;

    /**
     * ProcessNotifications constructor.
     * @param NotificationManager $notificationManager
     * @param NotificationProcessor $notificationProcessor
     */
    public function __construct(
        NotificationManager $notificationManager,
        NotificationProcessor $notificationProcessor
    ) {
        $this->notificationManager = $notificationManager;
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
     * {@inheritdoc}
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $number = $input->getOption('number');

        for ($i = 0; $i < $number; $i++) {
            if(!$this->processNotification($output)) {
                break;
            }
        }

        $output->writeln('Done.');
    }

    /**
     * @param OutputInterface $output
     * @return bool
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    private function processNotification(OutputInterface $output)
    {
        try {
            $notification = $this->notificationManager->getNextNotificationToHandle();
        } catch (NoResultException $exception) {
            $output->writeln('No notifications left to process. Exiting.');
            return false;
        }
        $this->notificationProcessor->process($notification);
        return true;
    }
}
