<?php

namespace Tests\Unit;

use App\Services\CategoryNavigationService;
use PHPUnit\Framework\TestCase;

/**
 * Pure unit tests for CategoryNavigationService helper methods.
 */
class CategoryNavigationServiceTest extends TestCase
{
    public function test_remove_diacritics(): void
    {
        $service = new CategoryNavigationService();
        $ref = new \ReflectionMethod($service, 'removeDiacritics');
        $ref->setAccessible(true);

        $this->assertEquals('asta e', $ref->invoke($service, 'astă e'));
        $this->assertEquals('stiinta', $ref->invoke($service, 'știință'));
        $this->assertEquals('inaltimea', $ref->invoke($service, 'înălțimea'));
    }

    public function test_service_can_be_instantiated(): void
    {
        $service = new CategoryNavigationService();
        $this->assertInstanceOf(CategoryNavigationService::class, $service);
    }
}
