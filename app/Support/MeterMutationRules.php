<?php

namespace App\Support;

use App\Models\AuditEvent;
use App\Models\Invoice;
use App\Models\Meter;
use App\Models\Rental;

class MeterMutationRules
{
    public static function activeOpening(Meter $meter): bool
    {
        return Rental::withTrashed()->where('rooms_r_id', $meter->rooms_r_id)
            ->where('rt_status', 'ACTIVE')->whereNull('rt_moveout')
            ->whereDate('rt_movein', $meter->m_date->toDateString())->exists();
    }

    public static function usedByInvoice(Meter $meter): bool
    {
        return Invoice::withTrashed()->where(function ($query) use ($meter) {
            $query->where('start_meter_id', $meter->getKey())->orWhere('end_meter_id', $meter->getKey());
        })->exists();
    }

    public static function rentalBoundary(Meter $meter): bool
    {
        return Rental::withTrashed()->where('rooms_r_id', $meter->rooms_r_id)
            ->where(function ($query) use ($meter) {
                $query->whereDate('rt_movein', $meter->m_date->toDateString())
                    ->orWhereDate('rt_moveout', $meter->m_date->toDateString());
            })->exists();
    }

    public static function latestEventId(Meter $meter): ?int
    {
        $id = AuditEvent::where('entity_type', 'meters')->where('entity_id', $meter->getKey())
            ->orderByDesc('ae_id')->value('ae_id');

        return $id === null ? null : (int) $id;
    }
}
