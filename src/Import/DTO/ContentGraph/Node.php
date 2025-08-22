<?php
namespace App\Import\DTO\ContentGraph;

abstract readonly class Node
{
    public function __construct(
        public string $id,           // stable within graph
        public string $sourceUrl,
        public ?string $hash = null, // content fingerprint
    ) {}
}

final readonly class PageNode extends Node
{
    public function __construct(
        string $id,
        string $sourceUrl,
        public string $title,
        public string $rawHtml,
        ?string $hash = null,
        public array $meta = []
    ) { parent::__construct($id, $sourceUrl, $hash); }
}

enum AssetKind: string { case image='image'; case font='font'; case file='file'; case video='video'; }

final readonly class AssetNode extends Node
{
    public function __construct(
        string $id,
        string $sourceUrl,
        public AssetKind $kind,
        public ?string $contentType,
        public ?int $sizeBytes,
        ?string $hash = null
    ) { parent::__construct($id, $sourceUrl, $hash); }
}

final readonly class StylesheetNode extends Node
{
    public function __construct(
        string $id, string $sourceUrl, public string $cssText, ?string $hash=null
    ) { parent::__construct($id, $sourceUrl, $hash); }
}

final readonly class ScriptNode extends Node
{
    public function __construct(
        string $id, string $sourceUrl, public string $jsText, public bool $inline=false, ?string $hash=null
    ) { parent::__construct($id, $sourceUrl, $hash); }
}

final readonly class Edge
{
    public function __construct(
        public string $fromId,
        public string $toId,
        public string $type // LINKS_TO, EMBEDS_ASSET, USES_STYLE, USES_SCRIPT
    ) {}
}
