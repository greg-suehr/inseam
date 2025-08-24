<?php

namespace App\Import\DTO\ContentGraph;

enum AssetKind: string
{
  case image = 'image';
  case video = 'video';
  case audio = 'audio';
  case font = 'font';
  case document = 'document';
}
