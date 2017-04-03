<?php
namespace Stolt\GitUserBend\Commands;

use Stolt\GitUserBend\Exceptions\CommandFailed;
use Stolt\GitUserBend\Exceptions\Exception;
use Stolt\GitUserBend\Persona;
use Stolt\GitUserBend\Traits\Guards;
use Stolt\GitUserBend\Exceptions\InvalidAlias;
use Stolt\GitUserBend\Exceptions\InvalidEmail;
use Stolt\GitUserBend\Persona\Storage;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class AddCommand extends Command
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
        $this->setName('add');
        $this->setDescription('Adds a new persona');

        $aliasArgumentDescription = 'The alias of the persona';
        $this->addArgument(
            'alias',
            InputArgument::REQUIRED,
            $aliasArgumentDescription
        );

        $nameArgumentDescription = 'The name of the persona';
        $this->addArgument(
            'name',
            InputArgument::REQUIRED,
            $nameArgumentDescription
        );

        $emailArgumentDescription = 'The email of the persona';
        $this->addArgument(
            'email',
            InputArgument::REQUIRED,
            $emailArgumentDescription
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
        $alias = $input->getArgument('alias');
        $name = $input->getArgument('name');
        $email = $input->getArgument('email');

        try {
            $alias = $this->guardAlias($alias);
            $this->guardEmail($email);

            $persona = new Persona($alias, $name, $email);

            if ($this->storage->add($persona)) {
                $outputContent = "<info>Added persona <comment>{$persona}</comment>.</info>";
                $output->writeln($outputContent);

                return 0;
            }

            throw new CommandFailed("Failed to add persona {$persona}.");
        } catch (Exception $e) {
            $error = "<error>Error:</error> " . $e->getInforizedMessage();
            $output->writeln($error);

            return 1;
        }
    }
}
