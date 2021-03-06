<?php
namespace Stolt\GitUserBend\Commands;

use Stolt\GitUserBend\Exceptions\CommandFailed;
use Stolt\GitUserBend\Exceptions\Exception;
use Stolt\GitUserBend\Git\Repository;
use Stolt\GitUserBend\Persona\Storage;
use Stolt\GitUserBend\Traits\Guards;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ExportCommand extends Command
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
        $commandDescription = 'Exports a persona into a '
            . Repository::GUB_FILENAME . ' file';

        $this->setName('export');
        $this->setDescription($commandDescription);

        $personaArgumentDescription = 'The persona alias to export';
        $this->addArgument(
            'alias',
            InputArgument::REQUIRED,
            $personaArgumentDescription
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
        try {
            $alias = $this->guardAlias($input->getArgument('alias'));
            $directory = $input->getArgument('directory');

            $this->repository->setDirectory($directory);
            $persona = $this->storage->all()->getByAlias($alias);
            $gubDotfile = $directory
                . DIRECTORY_SEPARATOR
                . Repository::GUB_FILENAME;

            if ($this->repository->hasGubDotfile()) {
                $gubDotfilePersona = $this->repository->getPersonaFromGubDotfile();
                if ($persona->equals($gubDotfilePersona)) {
                    $exceptionMessage = "The persona '{$persona}' is already "
                        . "present in '{$gubDotfile}'.";
                    throw new CommandFailed($exceptionMessage);
                }
            }

            if ($this->repository->createGubDotfile($persona)) {
                $outputContent = "<info>Exported persona aliased by "
                    . "<comment>{$alias}</comment> into "
                    . "<comment>{$gubDotfile}</comment>.</info>";
                $output->writeln($outputContent);

                return 0;
            }

            throw new CommandFailed("Failed to export persona {$persona}.");
        } catch (Exception $e) {
            $error = "<error>Error:</error> " . $e->getInforizedMessage();
            $output->writeln($error);

            return 1;
        }
    }
}
