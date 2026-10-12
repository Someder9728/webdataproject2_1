<?php

namespace App\Support;

use App\Models\AuditEvent;
use App\Models\Invoice;
use App\Models\Meter;
use App\Models\Rental;

class MeterPresentation
{
    /** @param iterable<Meter> $meters
     * @return array<int, array<string, mixed>>
     */
    public function rows(iterable $meters): array
    {
        $meters = collect($meters);
        if ($meters->isEmpty()) {
            return [];
        }
        $ids = $meters->pluck('m_id');
        $dates = $meters->map(fn (Meter $meter) => $meter->m_date->toDateString());
        $used = Invoice::withTrashed()->where(fn ($q) => $q->whereIn('start_meter_id', $ids)->orWhereIn('end_meter_id', $ids))->get(['start_meter_id', 'end_meter_id']);
        $usedIds = $used->pluck('start_meter_id')->merge($used->pluck('end_meter_id'))->all();
        $rentals = Rental::withTrashed()->whereIn('rooms_r_id', $meters->pluck('rooms_r_id'))->where(function ($q) use ($dates) {
            foreach ($dates->unique() as $date) {
                $q->orWhereDate('rt_movein', $date)->orWhereDate('rt_moveout', $date);
            }
        })->get(['rooms_r_id', 'rt_movein', 'rt_moveout']);
        $events = AuditEvent::where('entity_type', 'meters')->whereIn('entity_id', $ids)->selectRaw('entity_id, max(ae_id) as latest_id')->groupBy('entity_id')->pluck('latest_id', 'entity_id');

        return $meters->mapWithKeys(function (Meter $meter) use ($usedIds, $rentals, $events) {
            $boundary = $rentals->contains(fn ($r) => (int) $r->rooms_r_id === (int) $meter->rooms_r_id && in_array($meter->m_date->toDateString(), [$r->rt_movein->toDateString(), $r->rt_moveout?->toDateString()], true));
            $used = in_array($meter->getKey(), $usedIds);

            return [$meter->getKey() => ['m_id' => $meter->getKey(), 'rooms_r_id' => $meter->rooms_r_id, 'm_date' => $meter->m_date->toDateString(), 'm_water' => $meter->m_water, 'm_elec' => $meter->m_elec,
                'status' => $meter->trashed() ? 'CANCELLED' : 'ACTIVE', 'latest_event_id' => isset($events[$meter->getKey()]) ? (int) $events[$meter->getKey()] : null,
                'date_locked' => $boundary, 'can_edit' => ! $meter->trashed() && ! $used, 'can_delete' => ! $meter->trashed() && ! $used && ! $boundary]];
        })->all();
    }
}
