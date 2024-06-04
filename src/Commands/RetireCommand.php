<?php
namespace Stolt\GitUserBend\Commands;

use Stolt\GitUserBend\Exceptions\CommandFailed;
use Stolt\GitUserBend\Exceptions\Exception;
use Stolt\GitUserBend\Persona;
use Stolt\GitUserBend\Persona\Storage;
use Stolt\GitUserBend\Traits\Guards;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class RetireCommand extends Command
{
    use Guards;

    /**
     * @var Storage
     */
    private Storage $storage;

    /**
     * Initialize.
     *
     * @param Storage $storage
     */
    public function __construct(Storage $storage)
    {
        $this->storage = $storage;

        parent::__construct();
    }

    /**
     * Command configuration.
     *
     * @return void
     */
    protected function configure(): void
    {
        $this->setName('retire');
        $this->setDescription('Retires a defined persona');

        $aliasArgumentDescription = 'The alias of the persona to retire';
        $this->addArgument(
            'alias',
            InputArgument::REQUIRED,
            $aliasArgumentDescription
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
        try {
            $alias = $this->guardAlias((string) $input->getArgument('alias'));
            $persona = $this->storage->all()->getByAlias($alias);

            if ($this->storage->remove($alias)) {
                $outputContent = "<info>Retired persona <comment>{$persona}</comment>.</info>";
                $output->writeln($outputContent);

                return self::SUCCESS;
            }

            throw new CommandFailed("Failed to retire persona {$persona}.");
        } catch (Exception $e) {
            $error = "<error>Error:</error> " . $e->getInforizedMessage();
            $output->writeln($error);

            return self::FAILURE;
        }
    }
}
