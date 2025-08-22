<?php
namespace App\Import\Adapter;

use App\Import\Adapter\StaticHttpAdaptor;

#[AutoconfigureTag('app.importer')]
final class SquarespaceAdaptor extends StaticHttpAdaptor
{
    public function getKey(): string { return 'squarespace'; }
    // override discover/plan to parse export ZIP & map known widgets
}
