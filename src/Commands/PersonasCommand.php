<?php
namespace Stolt\GitUserBend\Commands;

use Stolt\GitUserBend\Helpers\Str as OsHelper;
use Stolt\GitUserBend\Persona\Collection;
use Stolt\GitUserBend\Persona\Storage;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class PersonasCommand extends Command
{
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
        $this->setName('personas');
        $this->setDescription('Lists the defined personas');

        $editorOptionDescription = 'Open the ' . Storage::FILE_NAME .' file for editing';
        $this->addOption(
            'edit',
            'e',
            InputOption::VALUE_NONE,
            $editorOptionDescription
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
        if ($input->getOption('edit')) {
            $editor = escapeshellcmd(getenv('EDITOR'));

            if (!$editor) {
                if ((new OsHelper())->isWindows()) {
                    $editor = 'notepad';
                } else {
                    foreach (array('editor', 'vim', 'vi', 'nano', 'pico', 'ed') as $candidate) {
                        if (exec('which ' . $candidate)) {
                            $editor = $candidate;
                            break;
                        }
                    }
                }
            }

            if (file_exists(STORAGE_FILE)) {
                system($editor . ' ' . STORAGE_FILE . ((new OsHelper())->isWindows() ? '' : ' > `tty`'));

                return 0;
            }

            $error = '<error>Error:</error> No personas defined yet '
                . 'therefore nothing to edit. '
                . 'Use the <comment>add</comment> or <comment>import</comment> '
                . 'command to define some.';
            $output->writeln($error);

            return 1;
        }

        $personas = $this->storage->all();
        if ($personas->count() === 0) {
            $error = '<error>Error:</error> No personas defined yet. '
                . 'Use the <comment>add</comment> or <comment>import</comment> '
                . 'command to define some.';
            $output->writeln($error);

            return 1;
        }

        $this->renderTable($output, $personas);
    }

    /**
     * @param  \Symfony\Component\Console\Output\OutputInterface $output
     * @param  \Stolt\GitUserBend\Persona\Collection             $personas
     * @return void
     */
    private function renderTable(OutputInterface $output, Collection $personas)
    {
        $rows = [];
        foreach ($personas as $persona) {
            $rows[] = [
                $persona->getAlias(),
                $persona->getName(),
                $persona->getEmail(),
                $persona->getUsageFrequency(),
            ];
        }

        $table = new Table($output);
        $table->setHeaders(['Alias', 'Name', 'Email', 'Usage frequency'])
              ->setRows($rows);
        $table->render();
    }
}
