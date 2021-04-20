<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;

use AlanVdb\Stream\Stream;
use AlanVdb\Stream\Exception\{ StreamArgumentException, StreamRuntimeException };

class StreamTest extends TestCase
{
    /**
     * @var string TEST_FILE Test file path
     */
    private const TEST_FILE = __DIR__ . DIRECTORY_SEPARATOR . 'test.txt';

    // -----------------------------------------------------------------------------------------------------------------
    //      TEST > __construct()
    // -----------------------------------------------------------------------------------------------------------------

    /**
     * Test Stream constructor providing an empty string.
     *
     * @return void
     */
    public function testConstructWithEmptyString() : void
    {
        $stream = new Stream('');
        $this->assertInstanceOf(Stream::class, $stream);
        $stream->close();
    }

    /**
     * Test Stream constructor providing a non empty string.
     *
     * @return void
     */
    public function testConstructWithNonEmptyString() : void
    {
        $stream = new Stream('hello');
        $this->assertInstanceOf(Stream::class, $stream);
        $stream->close();
    }

    /**
     * Test Stream constructor providing a resource.
     *
     * @return void
     */
    public function testConstructWithResource() : void
    {
        $stream = new Stream(fopen(self::TEST_FILE, 'w'));
        $this->assertInstanceOf(Stream::class, $stream);
        $stream->close();
        unlink(self::TEST_FILE);
    }

    /**
     * Test Stream constructor providing invalid argument type
     * 
     * @return void
     */
    public function testConstructWithInvalidType() : void
    {
        $this->expectException(StreamArgumentException::class);
        $this->expectExceptionCode(Stream::INVALID_TYPE);
        $stream = new Stream(null);
    }

    // -----------------------------------------------------------------------------------------------------------------
    //      TEST > getSize()
    // -----------------------------------------------------------------------------------------------------------------

    /**
     * Test getSize() with several Streams.
     * 
     * @depends testConstructWithEmptyString
     * @depends testConstructWithNonEmptyString
     * 
     * @dataProvider getSizeTestProvider
     * 
     * @return void
     */
    public function testGetSize(Stream $stream, int $expected) : void
    {
        $this->assertSame($stream->getSize(), $expected);
    }

    /**
     * testGetSize() arguments provider
     * 
     * @return array[]
     */
    public function getSizeTestProvider() : array
    {
        return [
            [new Stream(''), 0],
            [new Stream('hello'), 5]
        ];
    }

    // -----------------------------------------------------------------------------------------------------------------
    //      TEST > __toString()
    // -----------------------------------------------------------------------------------------------------------------

    /**
     * Test __toString() method
     * 
     * @depends testConstructWithEmptyString
     * @depends testConstructWithNonEmptyString
     * 
     * @dataProvider toStringTestProvider
     */
    public function testToString($stream, $expected) : void
    {
        $this->assertSame((string) $stream, $expected);
    }

    /**
     * testToString() arguments provider
     * 
     * @return array[]
     */
    public function toStringTestProvider() : array
    {
        return [
            [new Stream(''), ''],
            [new Stream('hello'), 'hello']
        ];
    }

    // -----------------------------------------------------------------------------------------------------------------
    //      TEST > detach()
    // -----------------------------------------------------------------------------------------------------------------

    /**
     * Test detach() to know if it returns resource
     * 
     * @return void
     */
    public function testDetachReturnsResource() : void
    {
        $stream = new Stream('');
        $this->assertIsResource($stream->detach());
    }
}
