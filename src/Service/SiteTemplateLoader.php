<?php

namespace App\Service;

use Symfony\Component\Filesystem\Filesystem;
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
}
