<?php

namespace AlanVdb\Stream;

use AlanVdb\Stream\Exception\{ StreamArgumentException, StreamRuntimeException };

class Stream implements StreamInterface
{
    // =================================================================================================================
    //
    //      ATTRIBUTES
    //
    // =================================================================================================================

    /**
     * @var resource $handle Stream resource
     */
    private $handle;

    /**
     * @var int|null $size Stream size
     */
    private $size;

    /**
     * @var bool|null $isReadable Whether or not the stream is readable
     */
    private $isReadable;

    /**
     * @var bool|null $isWritable Whether or not the stream is writable
     */
    private $isWritable;

    /**
     * @var bool $isUsable Whether or not stream is usable
     */
    private $isUsable = true;

    // =================================================================================================================
    //
    //      ATTRIBUTES > THROWN ERROR CODES
    //
    // =================================================================================================================

    /**
     * @var int INVALID_TYPE Error code thrown on invalid argument type provided.
     */
    public const INVALID_TYPE = 1;

    /**
     * @var int OPEN_FAILED Error code thrown on stream opening failure
     */
    public const OPEN_FAILED = 2;

    /**
     * @var int SEEK_FAILED Error code thrown trying to seek an unseekable stream
     */
    public const SEEK_FAILED = 3;

    /**
     * @var int READ_FAILED Error code thrown trying to seek an unseekable stream
     */
    public const READ_FAILED = 4;

    /**
     * @var int WRITE_FAILED Error code thrown trying to seek an unseekable stream
     */
    public const WRITE_FAILED = 5;

    /**
     * @var int TELL_FAILED Error code thrown on tell() failure
     */
    public const TELL_FAILED = 6;

    /**
     * @var int UNUSABLE Error code thrown thrown trying to use detached stream
     */
    public const UNUSABLE = 7;

    // =================================================================================================================
    //
    //      METHODS
    //
    // =================================================================================================================

    /**
     * Constructor
     * 
     * @param resource|string $resource
     * A resource 
     * OR string to build temporary file stream
     * 
     * @throws StreamArgumentException On invalid argument type provided (code: Stream::OPEN_FAILED)
     * @throws StreamRuntimeException  On error opening stream (code: Stream::INVALID_TYPE)
     */
    public function __construct($resource = '')
    {
        if (is_string($resource)) {
            $this->mode = 'r+';
            $this->uri  = 'php://memory';
            if (($this->handle = fopen($this->uri, $this->mode)) === false) {
                throw new StreamRuntimeException('An error occured trying to open temporary stream.', self::OPEN_FAILED);
            }
            if ($resource !== '') {
                $this->write($resource);
                $this->size = strlen($resource);
            } else {
                $this->size = 0;
            }
        } elseif (is_resource($resource)) {
            $this->handle = $resource;
        } else {
            throw new StreamArgumentException(
                'Provided argument must be a resource or a string. ' . gettype($resource) . ' provided.',
                self::INVALID_TYPE
            );
        }
    }

    // -----------------------------------------------------------------------------------------------------------------
    //      METHODS > STREAM METADATA
    // -----------------------------------------------------------------------------------------------------------------

    /**
     * @return int|null Stream size or null
     * 
     * @throws StreamRuntimeException On unusable state (code: Stream::UNUSABLE)
     */
    public function getSize() : ?int
    {
        $this->assertUsable();
        if ($this->size === null && is_resource($this->handle)) {
            $this->size = fstat($this->handle)['size'] ?? null;
        }
        return $this->size;
    }

    /**
     * @see https://www.php.net/manual/en/function.stream-get-meta-data.php
     * 
     * @return mixed Stream metadata
     * 
     * @throws StreamRuntimeException On unusable state (code: Stream::UNUSABLE)
     */
    public function getMetadata() : array
    {
        $this->assertUsable();
        if ($this->metadata === null) {
            $this->metadata = stream_get_meta_data($this->handle);
        }
        return $this->metadata;
    }

    // -----------------------------------------------------------------------------------------------------------------
    //      METHODS > SEEK
    // -----------------------------------------------------------------------------------------------------------------

    /**
     * @return bool Whether or not the stream is seekable
     */
    public function isSeekable() : bool
    {
        return $this->getMetadata()['seekable'];
    }

    /**
     * Places stream pointer at the specified position
     * 
     * @see http://www.php.net/manual/en/function.fseek.php
     * 
     * @param int $offset Position to place stream pointer
     * @param int $whence 
     * SEEK_SET - Set position equal to offset bytes.
     * SEEK_CUR - Set position from current location.
     * SEEK_END - Set position from the end of file.
     * 
     * @throws StreamRuntimeException 
     * On failure (code: Stream::SEEK_FAILED)
     * On unusable state (code: Stream::UNUSABLE)
     * 
     * @return void
     */
    public function seek(int $offset, int $whence = SEEK_SET) : void
    {
        $this->assertUsable();
        if (fseek($this->handle, $offset, $whence) === -1) {
            throw new StreamRuntimeException('Cannot seek the specified position.', self::SEEK_FAILED);
        }
    }

    /**
     * Places stream pointer at the begining
     * 
     * @throws StreamRuntimeException 
     * On failure (code: Stream::SEEK_FAILED)
     * On unusable state (code: Stream::UNUSABLE)
     * 
     * @return void
     */
    public function rewind() : void
    {
        $this->seek(0);
    }

    /**
     * @return int Current position of the pointer
     * 
     * @throws StreamRuntimeException
     * On failure (code: Stream::TELL_FAILED)
     * On unusable state (code: Stream::UNUSABLE)
     */
    public function tell() : int
    {
        $this->assertUsable();
        if (($position = ftell($this->handle)) === false) {
            throw new StreamRuntimeException(
                'An error occured trying to find current stream position',
                self::TELL_FAILED
            );
        }
        return $position;
    }

    /**
     * @return bool Whether the stream is at the end.
     * 
     * @throws StreamRuntimeException On unusable state (code: Stream::UNUSABLE)
     */
    public function eof() : bool
    {
        $this->assertUsable();
        return feof($this->handle);
    }

    // -----------------------------------------------------------------------------------------------------------------
    //      METHODS > READ
    // -----------------------------------------------------------------------------------------------------------------

    /**
     * @return bool Whether or not the stream is readable
     */
    public function isReadable() : bool
    {
        if ($this->isReadable === null) {
            $this->isReadable = in_array(
                $this->getMetadata()['mode'],
                [
                    'r', 'w+', 'r+', 'x+', 'c+',
                    'rb', 'w+b', 'r+b', 'x+b', 'c+b',
                    'rt', 'w+t', 'r+t', 'x+t', 'c+t', 'a+'
                ]
            );
        }
        return $this->isReadable;
    }

    /**
     * Reads the stream
     * 
     * @param int $maxBytes Maximum bytes to read
     * If provided argument is lower than 1, an empty string is returned.
     * 
     * @throws StreamRuntimeException 
     * On error trying to read (code: Stream::READ_FAILED)
     * On unusable state (code: Stream::UNUSABLE)
     * 
     * @return string
     */
    public function read(int $maxBytes = 8192) : string
    {
        $this->assertUsable();
        if ($maxBytes < 1) {
            return '';
        }
        if (($data = fread($this->handle, $maxBytes)) === false) {
            throw new StreamRuntimeException('An error occured trying to read stream.', self::READ_FAILED);
        }
        return $data;
    }

    /**
     * Reads line
     * 
     * @param int|null $maxBytes Maximum bytes to read
     * If provided argument is lower than 1, an empty string is returned.
     * 
     * @throws StreamRuntimeException 
     * On error trying to read (code: Stream::READ_FAILED)
     * On unusable state (code: Stream::UNUSABLE)
     * 
     * @return string
     */
    public function readLine(?int $maxBytes = null) : string
    {
        $this->assertUsable();
        if ($maxBytes < 1) {
            return '';
        }
        $data = $maxBytes === null ? fgets($this->handle) : fgets($this->handle, $maxBytes);
        if ($data === false) {
            throw new StreamRuntimeException('An error occured trying to read.', self::READ_FAILED);
        }
        return $data;
    }

    /**
     * Reads remainder of the stream and returns its string value
     * 
     * @throws StreamRuntimeException
     * On error trying to read (code: Stream::READ_FAILED)
     * On unusable state (code: Stream::UNUSABLE)
     * 
     * @return string Remaining contents
     */
    public function getRemainingContents() : string
    {
        $this->assertUsable();
        if (($contents = stream_get_contents($this->handle)) === false) {
            throw new StreamRuntimeException('An error occured trying to read.', self::READ_FAILED);
        }
        return $contents;
    }

    /**
     * @return string Entire stream contents or an empty string on error.
     */
    public function __toString() : string
    {
        try {
            $this->seek(0);
            return $this->getRemainingContents();
        } catch(Exception $e) {
            return '';
        }
    }

    // -----------------------------------------------------------------------------------------------------------------
    //      METHODS > WRITE
    // -----------------------------------------------------------------------------------------------------------------

    /**
     * @return bool Whether or not the stream is writable
     */
    public function isWritable() : bool
    {
        if ($this->isWritable === null) {
            $this->isWritable = in_array(
                $this->getMetadata()['mode'],
                [
                    'w', 'w+', 'rw', 'r+', 'x+', 'c+',
                    'wb', 'w+b', 'r+b', 'x+b', 'c+b',
                    'w+t', 'r+t', 'x+t', 'c+t', 'a', 'a+'
                ]
            );
        }
        return $this->isWritable;
    }

    /**
     * Writes
     * 
     * @param string $data Data to write
     * 
     * @throws StreamRuntimeException
     * On unusable state (code: Stream::UNUSABLE)
     * On error writing (code: Stream::WRITE_FAILED)
     * 
     * @return int Number of bytes written
     */
    public function write(string $data) : int
    {
        $this->assertUsable();
        if (($bytes = fwrite($this->handle, $data)) === false) {
            throw new StreamRuntimeException('An error occured trying to write.', self::WRITE_FAILED);
        }
        $this->size = null;
        return $bytes;
    }

    // -----------------------------------------------------------------------------------------------------------------
    //      METHODS > CLOSE
    // -----------------------------------------------------------------------------------------------------------------

    /**
     * Closes stream
     * 
     * @return void
     */
    public function close() : void
    {
        fclose($this->handle);
        $this->setUsable(false);
    }

    /**
     * Separates any underlying resources from the stream.
     * After the stream has been detached, the stream is in an unusable state.
     *
     * @return resource|null Underlying PHP stream, if any
     * 
     * @throws StreamRuntimeException On unusable state (code: Stream::UNUSABLE)
     */
    public function detach()
    {
        $this->assertUsable();
        if (is_resource($this->handle)) {
            $returnedResource = $this->handle;
            $this->setUsable(false);
            return $returnedResource;
        }
        return null;
    }

    // -----------------------------------------------------------------------------------------------------------------
    //      METHODS > INTERNAL
    // -----------------------------------------------------------------------------------------------------------------

    /**
     * Sets stream usable or unusable
     * 
     * @param bool Whether or not stream is usable
     */
    protected function setUsable(bool $isUsable) : void
    {
        if ($isUsable) {
            $this->isUsable = true;
        } else {
            $this->handle = null;
            $this->isReadable = false;
            $this->isWritable = false;
            $this->metadata = ['seekable' => false];
            $this->size = null;
            $this->isUsable = false;
        }
    }

    /**
     * Asserts that current stream is usable.
     * 
     * @throws StreamRuntimeException On unusable state (code: Stream::UNUSABLE)
     */
    protected function assertUsable()
    {
        if (!$this->isUsable) {
            throw new StreamRuntimeException('Stream is in an unusable state.', self::UNUSABLE);
        }
    }

    /**
     * Destructor
     */
    public function __destruct()
    {
        if (is_resource($this->handle)) {
            fclose($this->handle);
        }
    }
}
