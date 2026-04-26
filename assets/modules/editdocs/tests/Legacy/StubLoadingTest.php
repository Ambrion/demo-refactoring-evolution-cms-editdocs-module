<?php

declare(strict_types=1);

namespace EditDocs\Tests\Legacy;

use PHPUnit\Framework\TestCase;

class StubLoadingTest extends TestCase
{
    public function testDocumentParserStubExists(): void
    {
        $this->assertTrue(
            class_exists('DocumentParser', false),
            'DocumentParser stub should be loaded'
        );
    }

    public function testCanCreateDocumentParserMock(): void
    {
        $mock = $this->getMockBuilder(\DocumentParser::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['logEvent'])
            ->getMock();

        $this->assertInstanceOf(\DocumentParser::class, $mock);
    }
}
