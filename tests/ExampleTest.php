<?php

declare(strict_types=1);

namespace Vendor\Package\Tests;

use Vendor\Package\PackageServiceProvider;

final class ExampleTest extends TestCase
{
    public function test_provider_class_exists(): void
    {
        $this->assertTrue(class_exists(PackageServiceProvider::class));
    }
}
