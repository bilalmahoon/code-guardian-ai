<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Analysis\Models\Analysis;
use App\Domain\Organization\Models\Organization;
use App\Policies\AnalysisPolicy;
use App\Policies\OrganizationPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Organization::class => OrganizationPolicy::class,
        Analysis::class     => AnalysisPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}
