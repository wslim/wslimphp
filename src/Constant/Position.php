<?php
namespace Wslim\Constant;

use Wslim\Util\DataHelper;

class Position
{
    const TOP = 'top';
    const CENTER = 'center';
    const BOTTOM = 'bottom';
    const LEFT   = 'left';
    const MIDDLE = 'middle';
    const RIGHT  = 'right';
    
    /**
     * parse position
     * @param  mixed $data
     * @param  mixed $yval
     * @return array [x, y]
     */
    static public function parsePosition($data, $yval=null)
    {
        $x = $y = null;
        if (is_numeric($data)) {
            $data = intval($data);
            if ($data > 0 && $data < 10) {
                if ($data == 1) {
                    $x = static::LEFT;
                    $y = static::TOP;
                } elseif ($data == 2) {
                    $x = static::CENTER;
                    $y = static::TOP;
                } elseif ($data == 3) {
                    $x = static::RIGHT;
                    $y = static::TOP;
                } elseif ($data == 4) {
                    $x = static::LEFT;
                    $y = static::CENTER;
                } elseif ($data == 5) {
                    $x = static::CENTER;
                    $y = static::CENTER;
                } elseif ($data == 6) {
                    $x = static::RIGHT;
                    $y = static::CENTER;
                } elseif ($data == 7) {
                    $x = static::LEFT;
                    $y = static::BOTTOM;
                } elseif ($data == 8) {
                    $x = static::CENTER;
                    $y = static::BOTTOM;
                } else { // 9
                    $x = static::RIGHT;
                    $y = static::BOTTOM;
                }
            } else {
                $x = $data;
            }
        } elseif (is_string($data)) {
            $data = DataHelper::explode('\,\|', $data);
            $x = $data[0];
            if (isset($data[1])) {
                $y = $data[1];
            }
        } elseif (is_array($data)) {
            if (isset($data[0])) {
                $x = $data[0];
            }
            if (isset($data['x'])) {
                $x = $data['x'];
            }
            if (isset($data[1])) {
                $y = $data[1];
            }
            if (isset($data['y'])) {
                $y = $data['y'];
            }
        }
        if (!is_null($yval) && !is_null($y)) {
            $y = $yval;
        }
        
        $x = str_replace(['px', '%'], '', $x);
        $y = str_replace(['px', '%'], '', $y);
        
        if ($x == 'middle') {
            $x = static::CENTER;
        }
        if ($y == 'middle') {
            $y = static::CENTER;
        }
        
        return [$x, $y];
    }
}