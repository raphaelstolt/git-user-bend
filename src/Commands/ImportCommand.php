<?php
namespace Stolt\GitUserBend\Commands;

use Stolt\GitUserBend\Exceptions\CommandFailed;
use Stolt\GitUserBend\Exceptions\Exception;
use Stolt\GitUserBend\Git\Repository;
use Stolt\GitUserBend\Persona;
use Stolt\GitUserBend\Persona\Storage;
use Stolt\GitUserBend\Traits\Guards;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ImportCommand extends Command
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
        $commandDescription = 'Imports a persona from a Git repository'
            . ' its user details or a local '
            . Repository::GUB_FILENAME . ' file';

        $this->setName('import');
        $this->setDescription($commandDescription);

        $directoryArgumentDescription = 'The directory of the Git repository';
        $this->addArgument(
            'directory',
            InputArgument::OPTIONAL,
            $directoryArgumentDescription,
            WORKING_DIRECTORY
        );

        $aliasArgumentDescription = 'The alias to use for an import from Git user details';
        $this->addArgument(
            'alias',
            InputArgument::OPTIONAL,
            $aliasArgumentDescription
        );

        $fromDotfileOptionDescription = 'Do an import from a local '
            . Repository::GUB_FILENAME . ' file';
        $this->addOption(
            'from-dotfile',
            null,
            InputOption::VALUE_NONE,
            $fromDotfileOptionDescription
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
        $directory = $input->getArgument('directory');
        $isFromDotfileImport = $input->getOption('from-dotfile');

        try {
            $this->repository->setDirectory($directory);

            if ($isFromDotfileImport) {
                return $this->importFromGubDotfile($input, $output);
            }

            return $this->importFromRepository($input, $output);
        } catch (Exception $e) {
            $error = "<error>Error:</error> " . $e->getInforizedMessage();
            $output->writeln($error);

            return 1;
        }
    }

    /**
     * @param  \Symfony\Component\Console\Input\InputInterface   $input
     * @param  \Symfony\Component\Console\Output\OutputInterface $output
     * @return integer
     */
    private function importFromGubDotfile(
        InputInterface $input,
        OutputInterface $output
    ) {
        $directory = $input->getArgument('directory');

        try {
            $persona = $this->repository->getPersonaFromGubDotfile();
            $gubDotfile = $this->repository->getGubDotfilePath();

            if ($this->storage->add($persona)) {
                $outputContent = "<info>Imported persona <comment>{$persona}</comment> "
                    . "from <comment>{$gubDotfile}</comment>.</info>";
                $output->writeln($outputContent);

                return 0;
            }

            $exceptionMessage = "Failed to import persona '{$persona}' "
                 . "from '{$gubDotfile}'.";
            throw new CommandFailed($exceptionMessage);
        } catch (Exception $e) {
            $error = "<error>Error:</error> " . $e->getInforizedMessage();
            $output->writeln($error);

            return 1;
        }
    }

    /**
     * @param  \Symfony\Component\Console\Input\InputInterface   $input
     * @param  \Symfony\Component\Console\Output\OutputInterface $output
     * @return integer
     */
    private function importFromRepository(InputInterface $input, OutputInterface $output)
    {
        $directory = $input->getArgument('directory');

        try {
            $alias = $this->guardAlias($this->guardRequiredAlias($input->getArgument('alias')));

            $persona = $this->repository->getPersonaFromConfiguration();
            $persona = new Persona($alias, $persona->getName(), $persona->getEmail());

            if ($this->storage->add($persona)) {
                $outputContent = "<info>Imported persona from "
                    . "<comment>{$directory}</comment>.</info>";
                $output->writeln($outputContent);

                return 0;
            }

            $exceptionMessage = "Failed to import persona '{$persona}' "
                 . "from '{$directory}'.";
            throw new CommandFailed($exceptionMessage);
        } catch (Exception $e) {
            $error = "<error>Error:</error> " . $e->getInforizedMessage();
            $output->writeln($error);

            return 1;
        }
    }
}
