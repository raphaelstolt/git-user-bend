<?php
namespace Stolt\GitUserBend\Commands;

use Stolt\GitUserBend\Exceptions\CommandFailed;
use Stolt\GitUserBend\Exceptions\Exception;
use Stolt\GitUserBend\Git\Repository;
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
     * @var Stolt\GitUserBend\Persona\Repository
     */
    private $repository;

    /**
     * @var Stolt\GitUserBend\Persona\Storage
     */
    private $storage;

    /**
     * Initialize.
     *
     * @param Stolt\GitUserBend\Persona\Storage $storage
     * @param Stolt\GitUserBend\Persona\Git\Repository $repository
     * @return void
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
    protected function configure()
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
    }

    /**
     * Execute command.
     *
     * @param \Symfony\Component\Console\Input\InputInterface   $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     *
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $aliases = $input->getArgument('aliases');
        $directory = $input->getArgument('directory');

        try {
            $this->repository->setDirectory($directory);
            $pairPersonas = $this->guardAliases($aliases);

            $pair = new Pair();
            foreach ($pairPersonas as $persona) {
                $pair->add($persona);
            }

            if ($this->repository->setUser($pair->factorUser())) {
                foreach ($pair as $persona) {
                    $this->storage->incrementUsageFrequency($persona->getAlias());
                }

                $outputContent = "<info>Set pair <comment>"
                    . "'{$pair}'</comment>.</info>";
                $output->writeln($outputContent);

                return 0;
            }

            throw new CommandFailed("Failed to set pair '{$pair}'.");
        } catch (Exception $e) {
            $error = "<error>Error:</error> " . $e->getInforizedMessage();
            $output->writeln($error);

            return 1;
        }
    }
}
