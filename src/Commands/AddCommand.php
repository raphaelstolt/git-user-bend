<?php
namespace Stolt\GitUserBend\Commands;

use Stolt\GitUserBend\Exceptions\CommandFailed;
use Stolt\GitUserBend\Exceptions\Exception;
use Stolt\GitUserBend\Exceptions\InvalidAlias;
use Stolt\GitUserBend\Exceptions\InvalidEmail;
use Stolt\GitUserBend\Persona;
use Stolt\GitUserBend\Persona\Storage;
use Stolt\GitUserBend\Traits\Guards;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class AddCommand extends Command
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
     * @param InputInterface   $input
     * @param OutputInterface $output
     *
     * @return integer
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $alias = (string) $input->getArgument('alias');
        $name = (string) $input->getArgument('name');
        $email = (string) $input->getArgument('email');

        try {
            $alias = $this->guardAlias($alias);
            $this->guardEmail($email);

            $persona = new Persona($alias, $name, $email);

            if ($this->storage->add($persona)) {
                $outputContent = "<info>Added persona <comment>{$persona}</comment>.</info>";
                $output->writeln($outputContent);

                return self::SUCCESS;
            }

            throw new CommandFailed("Failed to add persona {$persona}.");
        } catch (Exception $e) {
            $error = "<error>Error:</error> " . $e->getInforizedMessage();
            $output->writeln($error);

            return self::FAILURE;
        }
    }
}
