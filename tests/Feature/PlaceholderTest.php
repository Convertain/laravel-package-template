<?php

declare(strict_types=1);

use Convertain\PackageTemplate\PackageTemplateServiceProvider;

it('service provider is registered', function () {
    $provider = $this->app->getProvider(PackageTemplateServiceProvider::class);
    
    expect($provider)
        ->not->toBeNull()
        ->toBeInstanceOf(PackageTemplateServiceProvider::class);
});
