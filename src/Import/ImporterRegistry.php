<?php
namespace App\Import;

final class ImporterRegistry
{

    /** @var array<string, ImporterInterface> */
    private array $map = [];
  
    /** @param ImporterInterface[] $importers */
    public function __construct(
      #[TaggedIterator('app.importer', indexBy: 'getKey')] iterable $importers
    )
    {
        $this->map = is_array($importers) ? $importers : iterator_to_array($importers);
    }

    public function get(string $key): ImporterInterface
    {
        return $this->map[$key]
            ?? throw new \InvalidArgumentException("Unknown importer: $key");
    }
}
