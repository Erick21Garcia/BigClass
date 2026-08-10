<?php

namespace Modules\Academic\Events;

use Modules\Academic\Models\AcademicPeriod;

class AcademicPeriodClosed
{
    public function __construct(
        public readonly AcademicPeriod $period
    ) {}
}