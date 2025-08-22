<?php
namespace App\Import\Adapter;

use App\Import\Adapter\StaticHttpAdaptor;

#[AutoconfigureTag('app.importer')]
final class WixAdaptor extends StaticHttpAdaptor
{
    public function getKey(): string { return 'wix'; }
    // TODO: need for headless snapshots to capture JS rendered pages
}
