<?php

namespace Stolt\GitUserBend\Commands;

use Stolt\GitUserBend\Exceptions\CommandFailed;
use Stolt\GitUserBend\Exceptions\Exception;
use Stolt\GitUserBend\Git\Repository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UnpairCommand extends Command
{
    /**
     * @var Repository
     */
    private Repository $repository;

    /**
     * Initialize.
     *
     * @param Repository $repository
     */
    public function __construct(Repository $repository)
    {
        $this->repository = $repository;

        parent::__construct();
    }

    /**
     * Command configuration.
     *
     * @return void
     */
    protected function configure(): void
    {
        $this->setName('unpair');
        $this->setDescription('Finishes current pair programming session');

        $directoryArgumentDescription = 'The directory of the Git repository';
        $this->addArgument(
            'directory',
            InputArgument::OPTIONAL,
            $directoryArgumentDescription,
            WORKING_DIRECTORY
        );
    }

    /**
     * Execute command.
     *
     * @param InputInterface   $input
     * @param OutputInterface $output
     *
     * @return integer
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $directory = $input->getArgument('directory');

        try {
            $this->repository->setDirectory((string) $directory);

            $formerPersona = $this->repository->getFormerPersonaFromConfiguration();

            $outputContent = "<info>Reset user config to <comment>"
                . "'{$formerPersona->getName()} <{$formerPersona->getEmail()}>'</comment>.</info>";
            $output->writeln($outputContent);

            return self::SUCCESS;

        } catch (Exception $e) {
            $error = "<error>Error:</error> " . $e->getInforizedMessage();
            $output->writeln($error);

            return self::FAILURE;
        }
    }
}
