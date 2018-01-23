# Microtimer

Dead simple PHP timing/profiling tool.

## Description

Sometimes we need to measure performance impact of the code we run. There's nothing wrong with timing via `time()` or `microtime()` in your code, but you might need a little more.
The primary purpose of Microtimer is measuring time elapsed between two points (marks). On top of that you can pass extra data when marking a point in time, get a mark by key, or get all marks, or get a subset of marks. You can also measure a time between any arbitary mark or a timestamp. You can run multiple timers on each request.

## Why do I need this?

There's [HRTime](http://php.net/manual/en/intro.hrtime.php), it provides high resolution (up to nanoseconds and ticks), it's more accurate because it uses low-level APIs. The problem is it's a PECL extension, and  most likely you don't have it installed. There's also high chance that you won't be able to install it (shared hosting/managed hosting/IT department/you name it).
With Microtimer you don't need to jump any hoops, just install via composer or copy the file in your project.

## Usage
You can see a complete example further down.

### Create a new timer
Create a new timer called 'Test Timer', set precision to 4. This will start a timer and add a first mark with key `start`.
```php
$timer = Microtimer\Microtimer::create( 'Test Timer', 4 );
```

### Mark
A mark is an associative array containing `timestamp`, `key`, and `data`.

`data` contains `since_start` and `since_last` (time elapsed since creation of the timer and time elapsed since the last mark), and any additional info you passed as the second argument.
```php
array(3) {
  ["timestamp"]=>
  float(1516588452.1361)
  ["key"]=>
  string(6) "Mark 1"
  ["data"]=>
  array(3) {
    ["since_start"]=>
    float(0.0242)
    ["since_last"]=>
    float(0.0114)
    ["extra_information"]=>
    string(24)=>"extra_information"
  }
}
```
To make a new mark you can use either mark() method or calling the timer object as a function.
The new mark is returned on success or an exception is thrown when timer has already been stopped.
```php
// Shorthand
$timer('Mark Key', [ 'extra_information' => 'Anything you want to add', 'backtrace' => debug_backtrace() ]);
// Or
$mark = $timer->mark( 'Mark Key', [ 'extra_information' => 'Anything you want to add' ] );
// $logger->log( $mark );
```

### Stop the timer
Stops the timer, adds a mark with key `end`. No marks can be added after the timer has been stopped.
```php
$timer->stop();
```

### Get a single mark
```php
var_dump( $timer->{'Mark 1'} )
```

### Get all or subset of marks based on their key
```php
// All marks
$timer->marks();

// Search for a substring
// will match 'Mark 1', 'Mark 10', 'a Mark 1', etc
$timer->marks('Mark 1');
```

### Get time elapsed between two marks(or timestamps)

Arguments can be either a key, a timestamp, or a mark, order doesn't matter - Microtimer only returns absolute values.
An exception will be thrown in the following cases:
* Not enough arguments
* A string is not valid/existing key
* Passed associative array doesn't have `timestamp` key
```php
$timer->diff( $timer->{'Mark 1'}, $timer->{'Mark 7'} )
```

### Get time elapsed since the last mark
Accepts optional `$timestamp` argument, otherwise returns the difference between now and the last marker.
```php
$timer->since_last();
```

### Complete example
```php
$timer = \Microtimer\Microtimer::create( 'Test Timer', 4 );

foreach( range( 0, 10 ) as $mark ) {
	usleep(15000);
	$timer( "Mark {$mark}" );
}

$since_last = $timer->since_last();

// Getting time elapsed between registered marks and/or arbitrary timestamps
var_dump( $timer->diff( $timer->{'Mark 1'}, $timer->{'Mark 7'} ) ); // -> float(0.1003)

var_dump( $timer->diff( microtime(true), $timer->{'Mark 7'} ) ); // -> float(0.0522)

var_dump( $timer->diff( 'Mark 1', $timer->{'Mark 10'} ) ); // -> float(0.152)
```

## Installation

Install via composer

`# composer require Microtimer\microtimer`

Alternatively, just copy `src/Microtimer.php` in your project and include it.

## Requirements

There's no other dependencies/requirements except PHP 7. If you still haven't upgraded, [you should definitely do so](http://php.net/supported-versions.php).

## Notes
Microtimer uses _[strict types](http://php.net/manual/en/functions.arguments.php#functions.arguments.type-declaration.strict)_ so stuff like `$timer->mark( 0, '5' );` will throw a TypeError. For the exact method signatures please see the code.

## Questions/comments/new features

Microtimer has absolute minimum of features, and it is intended to be kept that way.
If you think that's something is missing, please create an issue first, and only submit a pull request after discussion (or you can fork it and do whatever you like).

## Changelog

* 1.0 - Initial Release

## License
MIT