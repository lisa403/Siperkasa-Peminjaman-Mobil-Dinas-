<?php

namespace App\Services;

use Illuminate\Support\Carbon;

/**
 * Date rule of the booking form.
 *
 * A vehicle can be requested for the day of use itself (hari H) — the earliest departure date is today.
 * Past dates are never allowed. When the departure is today, the departure *time* must also not be in the
 * past (checked in StoreBookingRequest, because it needs the time field).
 */
class BookingWindow
{
    /** First departure date that can be requested when filing at $now. */
    public static function earliestDeparture(?Carbon $now = null): Carbon
    {
        return ($now ?? now())->copy()->startOfDay();
    }
}
