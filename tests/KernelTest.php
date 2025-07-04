<?php

namespace App\Tests;

use App\Kernel;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;

class KernelTest extends TestCase
{
    public function testKernelBoots(): void
    {
        $kernel = new Kernel('test', true);
        $kernel->boot();
        $this->assertTrue($kernel->isBooted());
        $kernel->shutdown();
    }

    public function testGetBundles(): void
    {
        $kernel = new Kernel('test', true);
        $bundles = $kernel->registerBundles();

        $this->assertIsArray($bundles);
        $this->assertNotEmpty($bundles);
        $this->assertContainsOnlyInstancesOf(BundleInterface::class, $bundles);
    }
}