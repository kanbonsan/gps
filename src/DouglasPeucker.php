<?php

class DP_Point {
  public $x;
  public $y;
  public $seq;

  public function __construct( $x, $y, $seq ){
    $this->x = $x;
    $this->y = $y;
    $this->seq = $seq;
}

class DP_Shape {
  protected $_points = array();
  protected $_needsSort = false;

  public function addPoint(DP_point $point){
    $this->_points[] = $point;
    $this->_needsSort = true;
    return $this;
  }

  public function points() {
    if($this->_needsSort){
      usort($this->_points, array(__CLASS__, 'sort'));
      $this->_needsSort = false;
    }
    return $this->_points;
  }

  public static function sort($a, $b){
    if($a->seq < $b->seq) { return -1; }
    if($a->seq > $b->seq) { return 1; }
    return 0;
  }
}

class DP_ShapeReducer {
  public function reduceWithTolerance( $shape, $tolerance ){
    // グラフが 2点以下の時は何もしない
    if( $tolerance <=0 || count($shape->points()) < 3 ){
      return $shape;
    }
    
    // グラフの全ポイント
    $points = $shape->points();
    // 間引き後のグラフを返すためのオブジェクト
    $newShape = new DP_Shape();
    $newShape->addPoint($points[0]);
    $newShape->addPoint($points[count($points) - 1]);
     
    $this->douglasPeuckerReduction( $shape,
				    $newShape,
				    $tolerance,
				    0,
				    count($points) - 1 );

    return $newShape;
  }

  public function douglasPeuckerReduction (DP_Shape $shape, DP_Shape $newShape, $tolerance, $firstIdx, $lastIdx){
    // 点が 1ないし2つの時は何もせず返す
    if($lastIdx <= $firstIdx + 1){
      return;
    }

    $points = $shape->points();
    $maxDistance = 0.0;
    $indexFarthest = 0;

    $firstPoint = $points[$firstIdx];
    $lastPoint = $points[$lastIdx];

    for($idx = $firstIdx + 1; $idx < $lastIdx; $idx++){
      $point = $points[$idx];
      $distance = $this->orthogonalDistance($point, $firstPoint, $lastPoint);
      if($distance > $maxDistance){
	$maxDistance = $distance;
	$indexFarthest = $idx;
      }
    }

    if($maxDistance > $tolerance){
      $newShape->addPoint($points[$indexFarthest]);
      $this->douglasPeuckerReduction($shape, $newShape, $tolerance, $firstIdx, $indexFarthest);
      $this->douglasPeuckerReduction($shape, $newShape, $tolerance, $indexFarthest, $lastIdx);
    }
  }

  public function orthogonalDistance($point, $lineStart, $lineEnd){
    $area = abs ( ( $lineStart->x * $lineEnd->y +
		    $lineEnd->x * $point->y +
		    $point->x * $lineStart->y -
		    $lineEnd->x * $lineStart->y -
		    $point->x * $lineEnd->y -
		    $lineStart->x * $point->y ) / 2 );
    $bottom = sqrt(pow($lineStart->x - $lineEnd->x, 2) + pow($lineStart->y - $lineEnd->y, 2));
    return $area / $bottom * 2.0;
  }
}
		    
      

class ShapePoint {
  public $lat;
  public $lng;
  public $alt;
  public $seq;
  public $datetime;

  public function __construct($lat, $lng, $alt, $datetime, $seq){
    $this->seq = $seq;
    $this->lat = $lat;
    $this->lng = $lng;
    $this->alt = $alt;
    $this->datetime = $datetime;
  }
}

class Shape {

  /**
   * @var ShapePoint[]    The list of points in the shape
   */

  protected $_points = array();

  /**
   * @var bool    Whether or not the list of points needs sorting
   */
   
  protected $_needsSort = false;

  /**
   * Add a point to the shape. Marks the list of points as out-of-order
   *
   * @param   ShapePoint  $point  The point to add to the shape
   */
   
  public function addPoint(ShapePoint $point) {
    $this->_points[] = $point;
    $this->_needsSort = true;
    return $this;
  }
 
  /**
   * Get the list of points. If the list is out of order
   * it is sorted by sequence value prior to returning
   *
   * @return  ShapePoint[]
   */

  public function points() {

    if ($this->_needsSort) {
      usort($this->_points, array(__CLASS__, 'sort'));
      $this->_needsSort = false;
    }
 
    return $this->_points;
  }
 
  /**
   * Sort callback to sort ShapePoint by sequence
   *
   * @param   ShapePoint  $a
   * @param   ShapePoint  $b
   * @return  int         -1, 0, or 1
   */

  public static function sort($a, $b) {
    if ($a->seq < $b->seq) { return -1; }
    if ($a->seq > $b->seq) { return 1; }
    return 0;
  }
}


class ShapeReducer {

  /**
   * Reduce the number of points in a shape using the Douglas-Peucker algorithm
   *
   * @param   Shape   $shape      The shape to reduce
   * @param   float   $tolerance  The tolerance to decide whether or not
   *                              to keep a point, in geographic
   *                              coordinate system degrees
   * @return  Shape   The reduced shape
   */

  public function reduceWithTolerance($shape, $tolerance) {

    // if a shape has 2 or less points it cannot be reduced
    if ($tolerance <= 0 || count($shape->points()) < 3) {
      return $shape;
    }

    $points = $shape->points();
    $newShape = new Shape(); // the new shape to return
    
    // automatically add the first and last point to the returned shape
    $newShape->addPoint($points[0]);
    $newShape->addPoint($points[count($points) - 1]);

    // the first and last points in the original shape are
    // used as the entry point to the algorithm.
    $this->douglasPeuckerReduction(
				   $shape,             // original shape
				   $newShape,          // reduced shape
				   $tolerance,         // tolerance
				   0,                  // index of first point
				   count($points) - 1  // index of last point
				   );

    // all done, return the reduced shape
    return $newShape;
  }

  /**
   * Reduce the points in $shape between the specified first and last
   * index. Add the shapes to keep to $newShape
   *
   * @param   Shape   $shape      The original shape
   * @param   Shape   $newShape   The reduced (output) shape
   * @param   float   $tolerance  The tolerance to determine if a point is kept
   * @param   int     $firstIdx   The index in original shape's point of
   *                              the starting point for this line segment
   * @param   int     $lastIdx    The index in original shape's point of
   *                              the ending point for this line segment
   */

  public function douglasPeuckerReduction(Shape $shape, Shape $newShape, $tolerance, $firstIdx, $lastIdx) {
    if ($lastIdx <= $firstIdx + 1) {
      // overlapping indexes, just return
      return;
    }

    // retrieve all points for subsequent processing
    $points = $shape->points();

    // loop over the points between the first and last points
    // and find the point that is the furthest away

    $maxDistance = 0.0;
    $indexFarthest = 0;

    $firstPoint = $points[$firstIdx];
    $lastPoint = $points[$lastIdx];

    for ($idx = $firstIdx + 1; $idx < $lastIdx; $idx++) {
      $point = $points[$idx];
      $distance = $this->orthogonalDistance($point, $firstPoint, $lastPoint);

      // only keep the point with the greatest distance
      if ($distance > $maxDistance) {
	$maxDistance = $distance;
	$indexFarthest = $idx;
      }
    }
 
    // if the point that is furthest away is within the tolerance,
    // it is simply discarded. Otherwise, it's added to the reduced
    // shape and the algorithm continues
    if ($maxDistance > $tolerance) {
      $newShape->addPoint($points[$indexFarthest]);

      // reduce the shape between the starting point to newly found point
      $this->douglasPeuckerReduction($shape, $newShape, $tolerance, $firstIdx, $indexFarthest);

      // reduce the shape between the newly found point and the finishing point
      $this->douglasPeuckerReduction($shape, $newShape, $tolerance, $indexFarthest, $lastIdx);
    }
  }

  /**
   * Calculate the orthogonal distance from the line joining the
   * $lineStart and $lineEnd points from $point
   *
   * @param   ShapePoint  $point      The point the distance is being calculated for
   * @param   ShapePoint  $lineStart  The point that starts the line
   * @param   ShapePoint  $lineEnd    The point that ends the line
   * @return  float   The distance in geographic coordinate system degrees
   */

  public function orthogonalDistance($point, $lineStart, $lineEnd) {

    $area = abs(
                (
		 $lineStart->lat * $lineEnd->lng
		 + $lineEnd->lat * $point->lng
		 + $point->lat * $lineStart->lng
		 - $lineEnd->lat * $lineStart->lng
		 - $point->lat * $lineEnd->lng
		 - $lineStart->lat * $point->lng
		 ) / 2
		);
 
    $bottom = sqrt(pow($lineStart->lat - $lineEnd->lat, 2) + pow($lineStart->lng - $lineEnd->lng, 2));

    return $area / $bottom * 2.0;
  }
}

?>

