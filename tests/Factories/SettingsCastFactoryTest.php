<?php

namespace Spatie\LaravelSettings\Tests\Factories;

use DateTime;
use ReflectionProperty;
use Spatie\LaravelSettings\Factories\SettingsCastFactory;
use Spatie\LaravelSettings\SettingsCasts\ArraySettingsCast;
use Spatie\LaravelSettings\SettingsCasts\DateTimeInterfaceCast;
use Spatie\LaravelSettings\SettingsCasts\DtoCast;
use Spatie\LaravelSettings\Tests\TestCase;
use Spatie\LaravelSettings\Tests\TestClasses\DummyDto;

class SettingsCastFactoryTest extends TestCase
{
    /** @test */
    public function it_will_not_resolve_a_cast_for_built_in_types()
    {
        $fake = new class {
            /** @var int */
            public $integer;
        };

        $reflectionProperty = new ReflectionProperty($fake, 'integer');

        $cast = SettingsCastFactory::resolve($reflectionProperty, []);

        $this->assertNull($cast);
    }

    /** @test */
    public function it_can_resolve_a_global_cast()
    {
        $fake = new class {
            /** @var DateTime */
            public $datetime;
        };

        $reflectionProperty = new ReflectionProperty($fake, 'datetime');

        $cast = SettingsCastFactory::resolve($reflectionProperty, []);

        $this->assertEquals(new DateTimeInterfaceCast(DateTime::class), $cast);
    }

    /** @test */
    public function it_can_resolve_a_global_cast_as_docblock()
    {
        $fake = new class {
            /** @var DateTime */
            public $datetime;
        };

        $reflectionProperty = new ReflectionProperty($fake, 'datetime');

        $cast = SettingsCastFactory::resolve($reflectionProperty, []);

        $this->assertEquals(new DateTimeInterfaceCast(DateTime::class), $cast);
    }

    /** @test */
    public function it_can_have_no_type_and_no_cast()
    {
        $fake = new class {
            public $noType;
        };

        $reflectionProperty = new ReflectionProperty($fake, 'noType');

        $cast = SettingsCastFactory::resolve($reflectionProperty, []);

        $this->assertNull($cast);
    }

    /** @test */
    public function it_can_have_a_global_cast_with_an_array()
    {
        $fake = new class {
            /** @var \Spatie\LaravelSettings\Tests\TestClasses\DummyDto[] */
            public $dto_array;
        };

        $reflectionProperty = new ReflectionProperty($fake, 'dto_array');

        $cast = SettingsCastFactory::resolve($reflectionProperty, []);

        $this->assertEquals(new ArraySettingsCast(new DtoCast(DummyDto::class)), $cast);
    }

    /** @test */
    public function it_can_have_a_global_cast_with_an_array_without_array_type()
    {
        $fake = new class {
            /** @var \Spatie\LaravelSettings\Tests\TestClasses\DummyDto[] */
            public $dto_array;
        };

        $reflectionProperty = new ReflectionProperty($fake, 'dto_array');

        $cast = SettingsCastFactory::resolve($reflectionProperty, []);

        $this->assertEquals(new ArraySettingsCast(new DtoCast(DummyDto::class)), $cast);
    }

    /** @test */
    public function it_can_have_a_plain_array_without_cast()
    {
        $fake = new class {
            /** @var array */
            public $array;
        };

        $reflectionProperty = new ReflectionProperty($fake, 'array');

        $cast = SettingsCastFactory::resolve($reflectionProperty, []);

        $this->assertNull($cast);
    }

    /** @test */
    public function it_can_have_a_nullable_cast()
    {
        $fake = new class {
            /** @var DateTime|null */
            public $array;
        };

        $reflectionProperty = new ReflectionProperty($fake, 'array');

        $cast = SettingsCastFactory::resolve($reflectionProperty, []);

        $this->assertEquals(new DateTimeInterfaceCast(DateTime::class), $cast);
    }

    /** @test */
    public function it_can_have_a_nullable_docblock_cast()
    {
        $fake = new class {
            /** @var \DateTime|null */
            public $array;
        };

        $reflectionProperty = new ReflectionProperty($fake, 'array');

        $cast = SettingsCastFactory::resolve($reflectionProperty, []);

        $this->assertEquals(new DateTimeInterfaceCast(DateTime::class), $cast);
    }

    /** @test */
    public function it_can_create_a_local_cast_without_arguments()
    {
        $this->withoutGlobalCasts();

        $fake = new class {
            /** @var DateTime */
            public $datetime;
        };

        $reflectionProperty = new ReflectionProperty($fake, 'datetime');

        $cast = SettingsCastFactory::resolve($reflectionProperty, [
            'datetime' => DateTimeInterfaceCast::class,
        ]);

        $this->assertEquals(new DateTimeInterfaceCast(DateTime::class), $cast);
    }

    /** @test */
    public function it_can_create_a_local_cast_with_class_identifier_and_arguments()
    {
        $fake = new class {
            public $dto;
        };

        $reflectionProperty = new ReflectionProperty($fake, 'dto');

        $cast = SettingsCastFactory::resolve($reflectionProperty, [
            'dto' => DtoCast::class . ':' . DummyDto::class,
        ]);

        $this->assertEquals(new DtoCast(DummyDto::class), $cast);
    }

    /** @test */
    public function it_can_create_a_local_cast_with_an_already_constructed_cast()
    {
        $fake = new class {
            /** @var DummyDto */
            public $dto;
        };

        $reflectionProperty = new ReflectionProperty($fake, 'dto');

        $cast = SettingsCastFactory::resolve($reflectionProperty, [
            'dto' => new DtoCast(DummyDto::class),
        ]);

        $this->assertEquals(new DtoCast(DummyDto::class), $cast);
    }

    private function withoutGlobalCasts()
    {
        config()->set('settings.global_casts', []);
    }
}
