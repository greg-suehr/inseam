<?php

namespace App\Import\DTO\Planning;

final readonly class ImageBlock extends BlockNode
{
  public function __construct(
    public string $alt,
    public ?int $width,
    public ?int $height,
    public string $assetId
  ) { parent::__construct([]); }
}
