<?php

namespace App\Import\Command;

use App\Import\Planning\PlanRepository;
use App\Import\Entity\StoredImportPlan;
use App\Import\Util\PlanHydrator;
use App\Import\DTO\Planning\ImportPlan;
use App\Import\Message\ExecutePageMessage;
use App\Import\Message\ExecuteAssetMessage;
use App\Import\Message\FinalizeImportMessage;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsCommand(
    name: 'app:execute:import',
    description: 'Dispatch page + asset jobs for a stored import plan, then finalize.'
)]
final class ExecuteImportCommand extends Command
{
    public function __construct(
        private PlanRepository $plans,
        private MessageBusInterface $bus,
        private LoggerInterface $logger
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('plan-id', InputArgument::REQUIRED, 'The planId of the stored ImportPlan')
            ->addOption('site-id', 's', InputOption::VALUE_REQUIRED, 'Tenant/site scope', 'default')
            ->addOption('max-pages', null, InputOption::VALUE_OPTIONAL, 'Limit number of pages to dispatch', null)
            ->addOption('max-assets', null, InputOption::VALUE_OPTIONAL, 'Limit number of assets to dispatch', null)
            ->addOption('only-pages', null, InputOption::VALUE_NONE, 'Dispatch only pages')
            ->addOption('only-assets', null, InputOption::VALUE_NONE, 'Dispatch only assets')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Print what would be dispatched without enqueueing');
    }

    protected function execute(InputInterface $in, OutputInterface $out): int
    {
        $io = new SymfonyStyle($in, $out);
        $planId = (string) $in->getArgument('plan-id');
        $siteId = (string) $in->getOption('site-id');
        $maxPages = $this->toIntOrNull($in->getOption('max-pages'));
        $maxAssets = $this->toIntOrNull($in->getOption('max-assets'));
        $onlyPages = (bool) $in->getOption('only-pages');
        $onlyAssets = (bool) $in->getOption('only-assets');
        $dryRun = (bool) $in->getOption('dry-run');

        if ($onlyPages && $onlyAssets) {
            $io->error('Choose at most one of --only-pages or --only-assets.');
            return Command::INVALID;
        }

        // Load the plan (works whether PlanRepository exposes get()->ImportPlan or get()->StoredImportPlan)
        $plan = $this->loadImportPlanDTO($planId);

        $pageIds = array_keys($plan->pages);
        $assetIds = array_keys($plan->assets);

        if ($maxPages !== null)  { $pageIds  = array_slice($pageIds,  0, $maxPages); }
        if ($maxAssets !== null) { $assetIds = array_slice($assetIds, 0, $maxAssets); }

        $io->title('Execute Import');
        $io->text(sprintf('Plan: <info>%s</info>', $plan->planId));
        $io->text(sprintf('Site: <info>%s</info>', $siteId));
        $io->newLine();

        $io->section('Dispatch: Pages (first)');
        $io->text(sprintf('Count: %d', count($pageIds)));

        if (!$onlyAssets) {
            if ($dryRun) {
                foreach ($pageIds as $pid) {
                    $io->writeln(sprintf('- would enqueue ExecutePageMessage(pageId=%s)', $pid));
                }
            } else {
                $bar = $io->createProgressBar(count($pageIds));
                $bar->start();
                foreach ($pageIds as $pid) {
                    $this->bus->dispatch(new ExecutePageMessage($plan->planId, $pid, $siteId));
                    $bar->advance();
                }
                $bar->finish();
                $io->newLine(2);
            }
        }

        $io->section('Dispatch: Assets (second)');
        $io->text(sprintf('Count: %d', count($assetIds)));

        if (!$onlyPages) {
            if ($dryRun) {
                foreach ($assetIds as $aid) {
                    $io->writeln(sprintf('- would enqueue ExecuteAssetMessage(assetId=%s)', $aid));
                }
            } else {
                $bar = $io->createProgressBar(count($assetIds));
                $bar->start();
                foreach ($assetIds as $aid) {
                    $this->bus->dispatch(new ExecuteAssetMessage($plan->planId, $aid, $siteId));
                    $bar->advance();
                }
                $bar->finish();
                $io->newLine(2);
            }
        }

        // Always enqueue finalize unless dry-run
        if (!$dryRun) {
            $this->bus->dispatch(new FinalizeImportMessage($plan->planId, $siteId));
            $io->success('Queued finalize message.');
        } else {
            $io->writeln('- would enqueue FinalizeImportMessage()');
        }

        $io->success('Dispatch complete.');
        $this->logger->info('ExecuteImportCommand dispatched messages', [
            'planId' => $plan->planId,
            'siteId' => $siteId,
            'pages'  => count($pageIds),
            'assets' => count($assetIds),
            'dryRun' => $dryRun,
        ]);

        return Command::SUCCESS;
    }

    private function toIntOrNull(mixed $v): ?int
    {
        if ($v === null || $v === '' || $v === false) return null;
        $i = (int) $v;
        return $i > 0 ? $i : null;
    }

    /** Load an ImportPlan DTO regardless of PlanRepository::get() return type. */
    private function loadImportPlanDTO(string $planId): ImportPlan
    {
        if (method_exists($this->plans, 'load')) {
            /** @var ImportPlan $plan */
            $plan = $this->plans->load($planId);
            return $plan;
        }

        $maybe = $this->plans->get($planId);
        if ($maybe instanceof ImportPlan) {
            return $maybe;
        }
        if ($maybe instanceof StoredImportPlan) {
            return PlanHydrator::fromArray($maybe->getPlanJson());
        }

        /** @var ImportPlan $maybe */
        return $maybe;
    }
}
