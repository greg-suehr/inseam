<?php

namespace App\Import\Command;

use App\Import\Discovery\DiscoveryEngine;
use App\Import\DTO\ImportPolicy;
use App\Import\DTO\ImportSource;
use App\Import\Entity\ImportSession;
use App\Import\Planning\PlanRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
  name: 'app:import:site',
  description: 'Import a website into the CMS'
)]
class ImportSiteCommand extends Command
{
  public function __construct(
    private DiscoveryEngine $discoveryEngine,
    private PlanRepository $planRepository,
    private EntityManagerInterface $em,
    private LoggerInterface $logger
  ) {
      parent::__construct();
    }
  
  protected function configure(): void
  {
      $this
        ->addArgument('url', InputArgument::REQUIRED, 'Website URL to import')
        ->addOption('max-pages', 'm', InputOption::VALUE_OPTIONAL, 'Maximum pages to crawl', 50)
        ->addOption('platform', 'p', InputOption::VALUE_OPTIONAL, 'Source platform', 'generic')
        ->addOption('site-id', 's', InputOption::VALUE_OPTIONAL, 'Target site ID', 'default')
        ->addOption('follow-subdomains', null, InputOption::VALUE_NONE, 'Follow subdomains')
        ->addOption('ignore-robots', null, InputOption::VALUE_NONE, 'Ignore robots.txt');
    }
  
  protected function execute(InputInterface $input, OutputInterface $output): int
  {
      $io = new SymfonyStyle($input, $output);
      $url = $input->getArgument('url');
      
      try {
        // Step 1: Create import session
        $io->section('Creating Import Session');
        $session = $this->createSession($input);
        $io->success("Session created: {$session->getId()}");
        
        // Step 2: Discovery phase
        $io->section('Discovery Phase - Crawling Website');
        $source = new ImportSource(
          entryUrlOrFile: $url,
          platform: $input->getOption('platform'),
          siteId: $input->getOption('site-id')
            );
        
        $policy = new ImportPolicy(
          maxPages: (int) $input->getOption('max-pages'),
          respectRobotsTxt: !$input->getOption('ignore-robots'),
          followSubdomains: $input->getOption('follow-subdomains')
            );
        
        $discoveryResult = $this->discoveryEngine->discover($source, $policy);
        $io->success(sprintf(
          'Discovered %d nodes %d edges',
          count($discoveryResult->nodes),
          count($discoveryResult->edges)
        ));

        // Step 3: Planning phase (TODO)
        $io->section('Planning Phase - Creating Import Plan');
        $plan = $this->planRepository->createPlanFromDiscovery($discoveryResult);
        $io->success("Plan created: {$plan->planId}");
        
        // Step 4: Show summary
        $this->showSummary($io, $discoveryResult, $plan);
        
        return Command::SUCCESS;
        
      } catch (\Exception $e) {
        $io->error("Import failed: {$e->getMessage()}");
        $this->logger->error('Import command failed', [
          'url' => $url,
          'error' => $e->getMessage(),
          'trace' => $e->getTraceAsString()
            ]);
        return Command::FAILURE;
      }
    }
  
  private function createSession(InputInterface $input): ImportSession
  {
      $session = new ImportSession(
        siteId: $input->getOption('site-id'),
        platform: $input->getOption('platform'),
        source: $input->getArgument('url'),
      );
      
      $this->em->persist($session);
      $this->em->flush();
      
      return $session;
    }
  
  private function showSummary(SymfonyStyle $io, $discoveryResult, $plan): void
  {
      $io->section('Import Summary');
        
      $io->definitionList(
        ['Graph ID' => $discoveryResult->graphId],
        ['Total Nodes' => count($discoveryResult->nodes)],
        ['Total Edges' => count($discoveryResult->edges)],
        ['Plan ID' => $plan->planId],
        ['Pages to Import' => count($plan->pages)],
        ['Assets to Download' => count($plan->assets)]
      );
      
      $io->note('Discovery and planning complete. Use app:execute-import to run the actual import.');
    }
}
