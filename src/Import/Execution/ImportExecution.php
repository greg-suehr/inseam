<?php
namespace App\Import\Execution;

final class ImportExecution
{
    public function __construct(public string $planId, public ?int $executionId = null) {}
}
