<?php

namespace App\Services;

use App\Models\Customer;

class PidGenerator
{
    /**
     * Call inside a DB transaction. First PID is 5000, then 5001, 5002, ...
     */
    public static function next(): string
    {
        $start = (int) config('crm.pid_start', 5000);
        $max = 0;

        foreach (Customer::query()->lockForUpdate()->pluck('pid') as $pid) {
            if (ctype_digit((string) $pid)) {
                $max = max($max, (int) $pid);
            }
        }

        $next = $max >= $start ? $max + 1 : $start;

        return (string) $next;
    }
}
