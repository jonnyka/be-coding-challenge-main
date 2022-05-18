<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\LockableTrait;
use App\Service\LogService;

#[AsCommand(
    name: 'app:parse-logs',
    description: 'Parses the aggregated log file.',
    hidden: false,
    aliases: ['app:parselogs', 'app:parse-log', 'app:parselog']
)]
class ParseLogsCommand extends Command
{
    use LockableTrait;
    private $logService;

    public function __construct(LogService $logService)
    {
        $this->logService = $logService;

        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$this->lock()) {
            $output->writeln('The command is already running in another process.');

            return Command::SUCCESS;
        }

        $this->logService->parse('logs.txt');
        $output->writeln('<info>done.</info>');

        $this->release();
        return Command::SUCCESS;
    }
}
