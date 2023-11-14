<?php
namespace Stolt\GitUserBend\Commands;

use Stolt\GitUserBend\Exceptions\Exception;
use Stolt\GitUserBend\Git\Repository;
use Stolt\GitUserBend\Persona;
use Stolt\GitUserBend\Persona\Storage;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class WhoamiCommand extends Command
{
    /**
     * @var Stolt\GitUserBend\Git\Repository
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
     * @param Stolt\GitUserBend\Git\Repository $repository
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
        $this->setName('whoami');
        $this->setDescription('Shows the current persona of a Git repository');

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
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $directory = $input->getArgument('directory');

        try {
            $this->repository->setDirectory($directory);

            if ($this->repository->hasPair()) {
                $pairUser = $this->repository->getPairUserFromConfiguration();
                $pairPersona = $pairUser->factorPersona();
                $outputContent = "<info>The current pair is <comment>{$pairPersona}"
                    . "</comment>.</info>";
                $output->writeln($outputContent);

                return self::SUCCESS;
            }

            $persona = Persona::fromRepository($this->repository);
            $personas = $this->storage->all();

            if ($personas->count() === 0 || $personas->hasAliasedPersona($persona) === false) {
                $outputContent = "<info>The current unaliased persona is "
                    . "<comment>{$persona}</comment>.</info>";
            } else {
                $persona = $personas->getByNameAndEmail(
                    $persona->getName(),
                    $persona->getEmail()
                );
                $outputContent = "<info>The current persona is <comment>{$persona}"
                    . "</comment>.</info>";
            }

            $output->writeln($outputContent);

            return self::SUCCESS;
        } catch (Exception $e) {
            $error = "<error>Error:</error> " . $e->getInforizedMessage();
            $output->writeln($error);

            return 1;
        }
    }
}
