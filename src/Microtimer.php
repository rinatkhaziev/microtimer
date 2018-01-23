<?php
/**
 * Dead-simple timing/profiling tool
 *
 * @version 1.0
 */
declare(strict_types=1);

namespace Microtimer;

class Microtimer {

  // Timestamp
  private $start;
  // Hold all the marks
  private $marks = [];
  // A nice name
  private $name;
  // Whether the timer had been stopped
  private $frozen = false;
  // Precision to round down to
  private $precision = 4;
  // To minimize effect on performance we store the last key
  // Looking up by key is cheaper than trying to figure out the last element otherwise
  private $last_key = '';

  /**
   * Constructor
   *
   * @param string $name
   * @param integer $precision number of digits to round up to
   */
  private function __construct( string $name, int $precision ) {
    $this->start = microtime(true);
    $this->precision = $precision;
    $this->name = $name;
    $this->mark( 'start', [ 'message' => "Marker ${name} created" ] );
  }

  /**
   * Factory method
   *
   * @param string $name
   * @param integer $precision
   * @return self
   */
  public static function create( string $name = 'default', $precision = 4 ): self {
    return new self( $name, $precision );
  }

  /**
   * Mark current time
   *
   * @param string $key any valid key to your liking
   * @param array $data any additional data
   * @return array $mark
   */
  public function mark( string $key, array $data = [] ): array {
    if ( $this->frozen ) {
      throw new \Exception( 'Unable to mark. The timer has been stopped.' );
    }

    $ts = microtime(true);
    $uniqkey = !isset( $this->marks[ $key ] ) ? $key : "{$key}:{$ts}";
    $mark = [
      'timestamp' => $ts,
      'key' => $key,
      'data' => array_merge( [
        'since_start' => $this->calc_round( $ts, $this->start ),
        'since_last' => $this->since_last( $ts ),
      ], $data ),
    ];

    $this->last_key = $uniqkey;
    return $this->marks[ $uniqkey ] = $mark;
  }

  /**
   * Get last mark
   *
   * Returns either the mark or a dummy with current timestamp
   *
   * @return array
   */
  public function get_last_mark(): array {
    return $this->marks[ $this->last_key ] ?? [ 'timestamp' => microtime(true) ];
  }

  /**
   * Calculate the difference between two timestamps and round it down
   *
   * @param float $current microtime()
   * @param float $previous microtime()
   * @return void
   */
  public function calc_round( float $date1, float $date2 ): float {
    return abs( round( $date1 - $date2, $this->precision, PHP_ROUND_HALF_DOWN ) );
  }

  /**
   * Calculate the difference between two points in time
   *
   * @param mixed ...$marks
   * @return void
   */
  public function diff( ...$marks ) {
    if ( count( $marks ) < 2 )
      throw new \Exception( 'Too few arguments' );

    $marks = array_map( function( $mark ) {
      switch( gettype( $mark ) ) {
        case 'string':
        if ( isset( $this->{$mark}['timestamp'] ) )
          return $this->{$mark}['timestamp'];

          throw new \Exception( "Invalid key {$mark} passed" );
        break;
        case 'double':
        case 'integer':
          return $mark;
        break;
        case 'array':
          if ( isset( $mark['timestamp'] ) )
            return $mark['timestamp'];

          throw new \Exception( "Invalid array passed as a mark" );
        break;

        default:
          throw new \Exception( "Invalid argument passed" );
      }
    }, $marks );

    return $this->calc_round( ...$marks );
  }

  /**
   * Get time elapsed since the last mark
   *
   * @param float $ts optional microtime timestamp
   * @return float
   */
  public function since_last( float $ts = null ): float {
    return $this->calc_round( $ts ?: microtime(true), $this->get_last_mark()['timestamp'] );
  }

  /**
   * Add final mark and freeze the object.
   *
   * @return float time passed since creation of the timer
   */
  public function stop(): float {
    $this->mark( 'end', [
      'message' => "Timer {$this->name} stopped",
    ] );

    $this->freeze();

    return $this->calc_round( $this->get_last_mark()['timestamp'], $this->start );
  }

  /**
   * Mark the timer as frozen, making no further marks possible
   *
   * @return void
   */
  private function freeze() {
    $this->frozen = true;
  }

  /**
   * By default return all marks passed
   * Optionally pass a search key. e.g. $microtimer()->marks('Mark 1');
   *
   * @param string $search_substring
   * @return array array of all marks, or a filtered array with 'key' attribute matching $search_substring substring
   */
  public function marks( string $search_substring = '' ): array {
    return ! $search_substring ? $this->marks : array_filter( $this->marks, function( $m ) use ( $search_substring ) { return stristr( $m['key'], $search_substring ); } );
  }

  /**
   * Either get one of the properties or a mark based on key
   *
   * @param [type] $key
   * @return mixed
   */
  public function __get( $key ) {
    return $this->$key ?? ( $this->marks[ $key ] ?? null );
  }

  /**
   * Shorthand for mark() call
   *
   * @param [type] ...$args
   * @return void
   */
  public function __invoke( ...$args ) {
    return $this->mark( ...$args );
  }
}
