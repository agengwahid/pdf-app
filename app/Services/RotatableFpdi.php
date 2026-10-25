<?php

namespace App\Services;

use setasign\Fpdi\Fpdi;

class RotatableFpdi extends Fpdi
{
    protected float $angle = 0.0;

    /**
     * Apply 2D rotation matrix to the PDF graphics state.
     *
     * @param float $angle in degrees (clockwise)
     * @param float $x center of rotation x (defaults to current x)
     * @param float $y center of rotation y (defaults to current y)
     */
    public function Rotate(float $angle, float $x = -1, float $y = -1): void
    {
        if ($x == -1) {
            $x = $this->x;
        }
        if ($y == -1) {
            $y = $this->y;
        }

        if ($this->angle != 0) {
            $this->_out('Q');
        }

        $this->angle = $angle;

        if ($angle != 0) {
            $angleRad = $angle * M_PI / 180;
            $c = cos($angleRad);
            $s = sin($angleRad);
            $cx = $x * $this->k;
            $cy = ($this->h - $y) * $this->k;
            $this->_out(sprintf('q %.5F %.5F %.5F %.5F %.2F %.2F cm 1 0 0 1 %.2F %.2F cm', $c, $s, -$s, $c, $cx, $cy, -$cx, -$cy));
        }
    }

    public function _endpage(): void
    {
        if ($this->angle != 0) {
            $this->angle = 0;
            $this->_out('Q');
        }
        parent::_endpage();
    }
}
