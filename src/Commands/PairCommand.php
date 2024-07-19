<?php
namespace Stolt\GitUserBend\Commands;

use Stolt\GitUserBend\Exceptions\CommandFailed;
use Stolt\GitUserBend\Exceptions\Exception;
use Stolt\GitUserBend\Git\Repository;
use Stolt\GitUserBend\Persona;
use Stolt\GitUserBend\Persona\Pair;
use Stolt\GitUserBend\Persona\Storage;
use Stolt\GitUserBend\Traits\Guards;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class PairCommand extends Command
{
    use Guards;

    /**
     * @var Repository
     */
    private Repository $repository;

    /**
     * @var Storage
     */
    private Storage $storage;

    /**
     * Initialize.
     *
     * @param Storage $storage
     * @param Repository $repository
     */
    public function __construct(Storage $storage, Repository $repository)
    {
        $this->storage = $storage;
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
        $this->setName('pair');
        $this->setDescription('Enables pair programming by using several defined personas');

        $aliasesArgumentDescription = 'The comma-separated persona aliases to use';
        $this->addArgument(
            'aliases',
            InputArgument::REQUIRED,
            $aliasesArgumentDescription
        );

        $directoryArgumentDescription = 'The directory of the Git repository';
        $this->addArgument(
            'directory',
            InputArgument::OPTIONAL,
            $directoryArgumentDescription,
            WORKING_DIRECTORY
        );

        $branchOptionDescription = 'The Git branch to create and pair in';
        $this->addOption('branch', 'b', InputOption::VALUE_NONE, $branchOptionDescription);
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
        $aliases = $input->getArgument('aliases');
        $directory = $input->getArgument('directory');

        $branch = $input->getOption('branch');

        try {
            $this->repository->setDirectory((string) $directory);
            $pairPersonas = $this->guardAliases((string) $aliases);

            $pair = new Pair();
            foreach ($pairPersonas as $persona) {
                $pair->add($persona);
            }

            $this->repository->storePreviousUser();

            if ($this->repository->setUser($pair->factorUser())) {
                foreach ($pair as $persona) {
                    /** @var Persona $persona */
                    $this->storage->incrementUsageFrequency($persona->getAlias());
                }

                $outputContent = "<info>Set pair <comment>"
                    . "'{$pair}'</comment>.</info>";
                $output->writeln($outputContent);

                if ($branch !== false) {
                    $this->repository->createBranch(trim($branch));
                    $outputContent = "Switched to a new branch <comment>{$branch}</comment>.";
                    $output->writeln($outputContent);
                }

                return self::SUCCESS;
            }

            throw new CommandFailed("Failed to set pair '{$pair}'.");
        } catch (Exception $e) {
            $error = "<error>Error:</error> " . $e->getInforizedMessage();
            $output->writeln($error);

            return self::FAILURE;
        }
    }
}
