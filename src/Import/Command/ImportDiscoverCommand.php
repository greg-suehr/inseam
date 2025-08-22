<?php // src/Import/Command/ImportDiscoverCommand.php
namespace App\Import\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use App\Import\ImporterRegistry;
use App\Import\DTO\{ImportSource, ImportPolicy};

#[AsCommand(name: 'app:import:discover')]
final class ImportDiscoverCommand extends Command
{
    public function __construct(private ImporterRegistry $registry) { parent::__construct(); }

    protected function configure(): void
    {
        $this->addArgument('platform', InputArgument::REQUIRED)
             ->addArgument('source', InputArgument::REQUIRED)
             ->addArgument('siteId', InputArgument::REQUIRED);
    }

    protected function execute(InputInterface $in, OutputInterface $out): int
    {
        $imp = $this->registry->get($in->getArgument('platform'));
        $res = $imp->discover(new ImportSource($in->getArgument('source'), platform: $in->getArgument('platform'), siteId: $in->getArgument('siteId')));
        $out->writeln('Graph: '.$res->graphId);
        return Command::SUCCESS;
    }
}
