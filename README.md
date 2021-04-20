# AlanVdb\Stream

Stream class and interface with custom exceptions.

## Basic Usage

See [StreamInterface](https://github.com/Alanvdb/Stream/blob/main/src/StreamInterface.php) For more details !

### Instanciate class

You can instanciate Stream class providing a resource:

```PHP
use AlanVdb\Stream\Stream;

$resource = fopen('myFile.txt', 'r');
$stream   = new Stream($resource);
```

OR a string to build a temporary file stream:

```PHP
use AlanVdb\Stream\Stream;

$stream = new Stream('hello world !');
```

### Read in stream

You can read in stream providing a maximum amount of bytes (default to 8192):

```PHP
//...
if ($stream->isReadable()) {
    while (!$stream->eof()) {
        $contents = $stream->read(4096);
        // Do something here ...
    }
    $stream->close();
}
```

You can also read line by line. If no maximum size is provided, the entire line is returned.

```PHP
// ...
while (!$stream->eof()) {
    $line = $stream->readLine();
    // Do something here ...
}
$stream->close();
```

### Write in stream

In order to write in stream:

```PHP
// ...
if ($stream->isWritable()) {
    $stream->write('Hello world !');
}
$stream->close();
```

### Seek in stream

In order to seek in stream:

```PHP
// ...
if ($stream->isSeekable()) {
    $stream->seek(10);
    $position = $stream->tell(); // 10
}
```
