<?php
namespace Stolt\GitUserBend\Commands;

use Stolt\GitUserBend\Exceptions\CommandFailed;
use Stolt\GitUserBend\Exceptions\Exception;
use Stolt\GitUserBend\Exceptions\UnresolvablePair;
use Stolt\GitUserBend\Exceptions\UnresolvablePersona;
use Stolt\GitUserBend\Git\Repository;
use Stolt\GitUserBend\Persona\Pair;
use Stolt\GitUserBend\Persona\Storage;
use Stolt\GitUserBend\Traits\Guards;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class UseCommand extends Command
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
        $this->setName('use');
        $this->setDescription('Uses a persona for a Git repository');

        $personaArgumentDescription = 'The persona alias for the Git repository';
        $this->addArgument(
            'alias',
            InputArgument::OPTIONAL,
            $personaArgumentDescription
        );

        $aliasesArgumentDescription = 'The comma-separated persona aliases';
        $this->addArgument(
            'aliases',
            InputArgument::OPTIONAL,
            $aliasesArgumentDescription
        );

        $directoryArgumentDescription = 'The directory of the Git repository';
        $this->addArgument(
            'directory',
            InputArgument::OPTIONAL,
            $directoryArgumentDescription,
            WORKING_DIRECTORY
        );

        $fromDotfileOptionDescription = 'Use .gub dotfile of the Git repository';
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
        $isFromDotfileUse = $input->getOption('from-dotfile');
        $alias = $input->getArgument('alias');
        $aliases = $input->getArgument('aliases');

        try {
            $this->guardDualAliasArguments($input, $output);
            $this->repository->setDirectory($directory);

            if ($isFromDotfileUse) {
                return $this->useFromGubDotfile($input, $output);
            }
            if ($aliases) {
                $pairPersonas = $this->guardAliases($aliases);
                $pair = new Pair();
                foreach ($pairPersonas as $persona) {
                    $pair->add($persona);
                }
                $user = $pair->factorUser();
            } else {
                $alias = $this->guardRequiredAlias($input->getArgument('alias'));
                $alias = $this->guardAlias($alias);
                $persona = $this->storage->all()->getByAlias($alias);
                $user = $persona->factorUser();
            }

            $persona = $user->factorPersona();

            try {
                if ($aliases) {
                    $pairFromConfiguration = $this->repository->getPairUserFromConfiguration();
                    if ($persona->equals($pairFromConfiguration->factorPersona())) {
                        throw new Exception("Pair {$persona} already in use.");
                    }
                } else {
                    $personaFromGitConfiguration = $this->repository->getPersonaFromConfiguration();
                    if ($persona->equals($personaFromGitConfiguration)) {
                        throw new Exception("Persona {$persona} already in use.");
                    }
                }
            } catch (UnresolvablePersona $e) {
                // ignore because we are using user from persona storage
            } catch (UnresolvablePair $e) {
                // ignore because we are using users from persona storage
            }

            if ($this->repository->setUser($user)) {
                if ($aliases) {
                    foreach ($pair as $pairPersona) {
                        $this->storage->incrementUsageFrequency($pairPersona->getAlias());
                    }
                    $outputContent = "<info>Set pair <comment>"
                        . "{$persona}</comment>.</info>";
                } else {
                    $this->storage->incrementUsageFrequency($persona->getAlias());
                    $outputContent = "<info>Set persona <comment>"
                        . "{$persona}</comment>.</info>";
                }

                $output->writeln($outputContent);

                return 0;
            }

            throw new CommandFailed("Failed to set persona '{$persona}'.");
        } catch (Exception $e) {
            $error = "<error>Error:</error> " . $e->getInforizedMessage();
            $output->writeln($error);

            return 1;
        }
    }

    /**
     * @param  \Symfony\Component\Console\Input\InputInterface   $input
     * @param  \Symfony\Component\Console\Output\OutputInterface $output
     * @throws \Stolt\GitUserBend\Exceptions\Exception
     */
    private function guardDualAliasArguments(
        InputInterface $input,
        OutputInterface $output
    ) {
        $alias = $input->getArgument('alias');
        $aliases = explode(',', $input->getArgument('aliases'));

        if ($alias && count($aliases) > 1) {
            $exceptionMessage = "The 'alias' and 'aliases' arguments can't be used together.";
            throw new Exception($exceptionMessage);
        }
    }

    /**
     * @param  \Symfony\Component\Console\Input\InputInterface   $input
     * @param  \Symfony\Component\Console\Output\OutputInterface $output
     * @return integer
     */
    private function useFromGubDotfile(
        InputInterface $input,
        OutputInterface $output
    ) {
        $directory = $input->getArgument('directory');

        try {
            $personaFromGubDotfile = $this->repository->getPersonaFromGubDotfile();
            try {
                $personaFromLocalGitConfiguration = $this->repository->getPersonaFromConfiguration();
                if ($personaFromGubDotfile->equals($personaFromLocalGitConfiguration)) {
                    throw new Exception("Persona {$personaFromGubDotfile} already in use.");
                }
            } catch (UnresolvablePersona $e) {
                // ignore because we are using user from persona storage
            }

            $gubDotfile = $this->repository->getGubDotfilePath();
            if ($this->repository->setUser($personaFromGubDotfile->factorUser())) {
                if ($this->storage->all()->count() > 0) {
                    $this->storage->incrementUsageFrequency($personaFromGubDotfile->getAlias());
                }
                $outputContent = "<info>Set <comment>{$personaFromGubDotfile}</comment>"
                    . " from <comment>{$gubDotfile}</comment>.</info>";
                $output->writeln($outputContent);
                return 0;
            }

            $exceptionMessage = "Failed to set persona '{$personaFromGubDotfile}' "
                . "from '{$gubDotfile}'.";
            throw new CommandFailed($exceptionMessage);
        } catch (Exception $e) {
            $error = "<error>Error:</error> " . $e->getInforizedMessage();
            $output->writeln($error);

            return 1;
        }
    }
}
