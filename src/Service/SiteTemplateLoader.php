<?php

namespace App\Service;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\SerializerInterface;

class SiteTemplateLoader
{
  public function __construct(
    private SerializerInterface $serializer,
    private string $templateDir = __DIR__ . '/../../public/templates'
  ) {}

  public function load(string $templateName): array
  {
    $filePath = "{$this->templateDir}/{$templateName}.json";
        
    if (!file_exists($filePath)) {
      throw new \RuntimeException("Template not found: $templateName");
    }
    
    $data = file_get_contents($filePath);
    
    return json_decode($data, true, 512, JSON_THROW_ON_ERROR);
  }

  public function find(string $id): ?array
  {
    foreach ($this->all() as $tpl) {
      if (($tpl['id'] ?? null) === $id) {
        return $tpl;
      }
    }
    return null;
  }

  public function all(): array
  {
    $out = [];
    foreach ((new Finder())->files()->in($this->templateDir)->name('*.json') as $file) {
      $data = json_decode($file->getContents(), true);
      $out[] = [
        'id' => pathinfo($file->getFilename(), PATHINFO_FILENAME),
        'name' => $data['name'] ?? '(Unnamed)',
        'description' => $data['description'] ?? '',
        'preview_image' => $data['preview_image'] ?? null,
        'pages' => $data['pages'] ?? [],
        'assets' => $data['assets'] ?? [],
        'styles' => $data['styles'] ?? [],        
      ];
        }
    return $out;
  }
}
