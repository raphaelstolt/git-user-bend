<?php
declare(strict_types=1);

namespace Stolt\GitUserBend\Persona;

use Stolt\GitUserBend\Exceptions\NoDefinedPersonas;
use Stolt\GitUserBend\Exceptions\UnknownPersona;
use Stolt\GitUserBend\Persona;

class Storage
{
    const FILE_NAME = '.gub.personas';

    /**
     * @var string
     */
    private string $storageFile;

    /**
     * @param string $storageFile
     */
    public function __construct(string $storageFile)
    {
        $this->storageFile = $storageFile;
    }

    /**
     * @param  Persona $persona The persona to add.
     * @return boolean
     */
    public function add(Persona $persona): bool
    {
        if (!file_exists($this->storageFile)) {
            return file_put_contents($this->storageFile, json_encode([$persona])) > 0;
        }

        $personas = $this->all();
        $personas->add($persona);

        return file_put_contents($this->storageFile, json_encode($personas)) > 0;
    }

    /**
     * Removes a persona via its alias, when it's the last persona the
     * storage file is deleted.
     *
     * @param  string $alias The alias of the persona to remove.
     * @throws NoDefinedPersonas
     * @throws UnknownPersona
     * @return boolean
     */
    public function remove(string $alias): bool
    {
        $personas = $this->all();
        $personas->removeByAlias($alias);

        if ($personas->count() === 0) {
            return unlink($this->storageFile);
        }

        return file_put_contents($this->storageFile, json_encode($personas)) > 0;
    }

    /**
     * Returns all personas sorted by their usage frequency.
     *
     * @return Collection
     */
    public function all(): Collection
    {
        if (!file_exists($this->storageFile)) {
            return new Collection();
        }

        $personas = json_decode((string) file_get_contents($this->storageFile), true);

        $collection = new Collection();

        foreach ($personas as $personaEntry) {
            $collection->add(Persona::fromStorageEntry((array) $personaEntry));
        }

        return $collection->sorted();
    }

    /**
     * Increments the usage frequency of the aliased persona.
     *
     * @param  string $alias The alias to increment the usage frequency for.
     * @return boolean
     */
    public function incrementUsageFrequency(string $alias): bool
    {
        $personas = $this->all();
        $personaToModify = $personas->getByAlias($alias);

        $modifiedPersona = new Persona(
            $alias,
            $personaToModify->getName(),
            $personaToModify->getEmail(),
            $personaToModify->getUsageFrequency() + 1
        );

        $personas->removeByAlias($alias);
        $personas->add($modifiedPersona);

        return file_put_contents($this->storageFile, json_encode($personas)) > 0;
    }
}
