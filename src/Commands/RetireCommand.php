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
     * @var Stolt\GitUserBend\Persona\Storage
     */
    private $storage;

    /**
     * Initialize.
     *
     * @param Stolt\GitUserBend\Persona\Storage $storage
     * @return void
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
    protected function configure()
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
     * @param \Symfony\Component\Console\Input\InputInterface   $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     *
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $alias = $this->guardAlias($input->getArgument('alias'));
            $persona = $this->storage->all()->getByAlias($alias);

            if ($this->storage->remove($alias)) {
                $outputContent = "<info>Retired persona <comment>{$persona}</comment>.</info>";
                $output->writeln($outputContent);

                return 0;
            }

            throw new CommandFailed("Failed to retire persona {$persona}.");
        } catch (Exception $e) {
            $error = "<error>Error:</error> " . $e->getInforizedMessage();
            $output->writeln($error);

            return 1;
        }
    }
}
