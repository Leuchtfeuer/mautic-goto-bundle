<?php

declare(strict_types=1);

namespace MauticPlugin\LeuchtfeuerGoToBundle\Command;

use Mautic\CoreBundle\Command\ModeratedCommand;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\PathsHelper;

use const MauticPlugin\LeuchtfeuerGoToBundle\Entity\STATUS_HIDDEN;

use MauticPlugin\LeuchtfeuerGoToBundle\Helper\GoToHelper;
use MauticPlugin\LeuchtfeuerGoToBundle\Helper\GoToProductTypes;
use MauticPlugin\LeuchtfeuerGoToBundle\Model\GoToModel;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * CLI Command : Synchronizes registrant information from GoTo products.
 *
 * php app/console leuchtfeuer:goto:sync [--product=webinar|meeting|assist|training [--id=%productId%]]
 */
class SyncCommand extends ModeratedCommand
{
    public const COMMAND_NAME = 'leuchtfeuer:goto:sync';

    protected static $defaultName        = self::COMMAND_NAME;
    protected static $defaultDescription = 'Synchronizes registrant information from Citrix products';

    public function __construct(
        private GoToModel $goToModel,
        private GoToHelper $goToHelper,
        PathsHelper $pathsHelper,
        CoreParametersHelper $coreParametersHelper,
    ) {
        parent::__construct($pathsHelper, $coreParametersHelper);
    }

    /**
     * @throws InvalidArgumentException
     */
    protected function configure(): void
    {
        $this
            ->addOption(
                'product',
                'p',
                InputOption::VALUE_OPTIONAL,
                'Product to sync (webinar, meeting, training, assist)',
                null
            )
            ->addOption('id', 'i', InputOption::VALUE_OPTIONAL, 'The id of an individual registration to sync', null);

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $options = $input->getOptions();
        $product = $options['product'];

        if (!$this->checkRunStatus($input, $output, $options['product'].$options['id'])) {
            return 0;
        }

        // @todo update the static calls with proper service call.
        $activeProducts = [];
        if (null === $product) {
            // all products
            foreach (GoToProductTypes::toArray() as $p) {
                if ($this->goToHelper->isAuthorized('Goto'.$p)) {
                    $activeProducts[] = $p;
                }
            }

            if ([] === $activeProducts) {
                $this->completeRun();

                return 0;
            }
        } else {
            if (!GoToProductTypes::isValidValue($product)) {
                $output->writeln('<error>Invalid product: '.$product.'. Aborted</error>');
                $this->completeRun();

                return 0;
            }

            $activeProducts[] = $product;
        }

        $count = 0;
        foreach ($activeProducts as $product) {
            $output->writeln('<info>Synchronizing registrants for <comment>GoTo'.ucfirst($product).'</comment></info>');

            $citrixChoices = [];
            $productIds    = [];
            if (null === $options['id']) {
                // all products
                $citrixChoices = $this->goToHelper->getGoToChoices($product, true, true);
                $productIds    = array_keys($citrixChoices);
            } else {
                $productIds[]                  = $options['id'];
                $citrixChoices[$options['id']] = $options['id'];
            }

            $diff = array_diff_key($this->goToModel->getProducts($product), $citrixChoices);
            foreach (array_keys($diff) as $key) {
                $productEntity = $this->goToModel->getProductById($key);
                $productEntity->setStatus(STATUS_HIDDEN);
                $this->goToModel->saveEntity($productEntity);
            }

            foreach ($productIds as $productId) {
                $output->writeln('Persisting ['.$productId.'] to DB');
                $this->goToModel->syncProduct($product, $citrixChoices[$productId], $output);
            }

            foreach ($productIds as $productId) {
                try {
                    $eventDesc = $citrixChoices[$productId]['subject'];
                    $eventName = $this->goToHelper->getCleanString(
                        $eventDesc
                    ).'_#'.$productId;
                    $output->writeln('Synchronizing: ['.$productId.'] '.$eventName);
                    $this->goToModel->syncEvent($product, (string) $productId, $eventName, $eventDesc, $count, $output);
                } catch (\Exception $exception) {
                    $output->writeln('<error>Error syncing '.$product.': '.$productId.'.</error>');
                    $output->writeln('<error>'.$exception->getMessage().'</error>');
                    if ('dev' === MAUTIC_ENV) {
                        $output->writeln('<info>'.$exception.'</info>');
                    }
                }
            }
        }

        $output->writeln($count.' contacts synchronized.');
        $output->writeln('<info>Done.</info>');

        $this->completeRun();

        return 0;
    }
}
