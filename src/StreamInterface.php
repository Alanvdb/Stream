<?php

namespace AlanVdb\Stream;

use AlanVdb\Stream\Exception\{ StreamArgumentException, StreamRuntimeException };

interface StreamInterface
{
    /**
     * Constructor
     * 
     * @param resource|string $resource
     * A resource 
     * OR string to get temporary file stream
     * 
     * @throws StreamArgumentException On invalid argument type provided (code: Stream::OPEN_FAILED)
     * @throws StreamRuntimeException  On error opening stream (code: Stream::INVALID_TYPE)
     */
    public function __construct($resource = null);

    // -----------------------------------------------------------------------------------------------------------------
    //      METHODS > STREAM METADATA
    // -----------------------------------------------------------------------------------------------------------------

    /**
     * @return int|null Stream size or null
     * 
     * @throws StreamRuntimeException On unusable state (code: Stream::UNUSABLE)
     */
    public function getSize() : ?int;

    /**
     * @see https://www.php.net/manual/en/function.stream-get-meta-data.php
     * 
     * @return mixed Stream metadata
     * 
     * @throws StreamRuntimeException On unusable state (code: Stream::UNUSABLE)
     */
    public function getMetadata() : array;

    // -----------------------------------------------------------------------------------------------------------------
    //      METHODS > SEEK
    // -----------------------------------------------------------------------------------------------------------------

    /**
     * @return bool Whether or not the stream is seekable
     */
    public function isSeekable() : bool;

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
    public function seek(int $offset, int $whence = SEEK_SET) : void;

    /**
     * Places stream pointer at the begining
     * 
     * @throws StreamRuntimeException 
     * On failure (code: Stream::SEEK_FAILED)
     * On unusable state (code: Stream::UNUSABLE)
     * 
     * @return void
     */
    public function rewind() : void;

    /**
     * @return int Current position of the pointer
     * 
     * @throws StreamRuntimeException
     * On failure (code: Stream::TELL_FAILED)
     * On unusable state (code: Stream::UNUSABLE)
     */
    public function tell() : int;

    /**
     * @return bool Whether the stream is at the end.
     * 
     * @throws StreamRuntimeException On unusable state (code: Stream::UNUSABLE)
     */
    public function eof() : bool;

    // -----------------------------------------------------------------------------------------------------------------
    //      METHODS > READ
    // -----------------------------------------------------------------------------------------------------------------

    /**
     * @return bool Whether or not the stream is readable
     */
    public function isReadable() : bool;

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
    public function read(int $maxBytes) : string;

    /**
     * Reads remainder of the stream and returns its string value
     * 
     * @throws StreamRuntimeException
     * On error trying to read (code: Stream::READ_FAILED)
     * On unusable state (code: Stream::UNUSABLE)
     * 
     * @return string Remaining contents
     */
    public function getRemainingContents() : string;

    /**
     * @return string Stream contents
     * If stream is not seekable, you can use getContents()
     * to retrieve remaining contents.
     * 
     * @throws StreamRuntimeException
     * On unsusable state (code: Stream::UNUSABLE)
     * On error trying to rewind (code: Stream::SEEK_FAILED)
     * On error trying to read (code: Stream::READ_FAILED)
     */
    public function __toString() : string;

    // -----------------------------------------------------------------------------------------------------------------
    //      METHODS > WRITE
    // -----------------------------------------------------------------------------------------------------------------

    /**
     * @return bool Whether or not the stream is writable
     */
    public function isWritable() : bool;

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
    public function write(string $data) : int;

    // -----------------------------------------------------------------------------------------------------------------
    //      METHODS > CLOSE
    // -----------------------------------------------------------------------------------------------------------------

    /**
     * Closes stream
     * 
     * @return void
     */
    public function close() : void;

    /**
    * Separates any underlying resources from the stream.
    * After the stream has been detached, the stream is in an unusable state.
    *
    * @return resource|null Underlying PHP stream, if any
    * 
    * @throws StreamRuntimeException On unusable state (code: Stream::UNUSABLE)
    */
    public function detach();
}
