<?php

namespace App\Services;

use Illuminate\Support\Collection;

class PricingImportManager
{
    protected PricingImportService $importService;

    public function __construct(PricingImportService $importService)
    {
        $this->importService = $importService;
    }

    public function importFromSources(array $sources): void
    {
        foreach ($sources as $source) {

            $data = PricingSourceResolver::fetch($source);

            if (empty($data)) {
                continue;
            }

            $this->importService->import(collect($data));
        }
    }
}
