<?php

namespace Modules\LMS\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\Academic\Events\AcademicPeriodClosed;
use Modules\LMS\Listeners\SendPeriodClosedNotifications;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        AcademicPeriodClosed::class => [
            SendPeriodClosedNotifications::class,
        ],
    ];

    protected $subscribe = [];

    public function shouldDiscoverEvents(): bool
    {
        return true;
    }
}