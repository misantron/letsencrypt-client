<?php

declare(strict_types=1);

namespace LetsEncrypt\Tests\Unit\Entity;

use LetsEncrypt\Entity\Entity;
use LetsEncrypt\Tests\TestCase;

class EntityTest extends TestCase
{
    public function testJsonSerialize(): void
    {
        $entity = new class () extends Entity {
            public $property1;
            protected $property2;
            private $property3;

            public function __construct(int $property3 = 123)
            {
                $data = [
                    'property1' => false,
                    'property2' => [],
                    'status' => 'pending',
                ];

                parent::__construct($data);

                $this->property3 = $property3;
            }
        };

        $expected = '{"property1":false,"property2":[],"property3":123,"status":"pending"}';

        $this->assertSame($expected, json_encode($entity));
    }
}
