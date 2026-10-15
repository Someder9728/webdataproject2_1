<?php

namespace App\Support;

use App\Models\AuditEvent;
use App\Models\Contract;
use App\Models\Rental;
use Illuminate\Support\Collection;

class RentalTimeline
{
    private const CONTRACT_STATUS = ['ACTIVE' => 'มีผล', 'EXPIRED' => 'หมดอายุ', 'ENDED' => 'สิ้นสุด'];

    /**
     * @param  Collection<int, AuditEvent>  $events  ประวัติแก้ไขสัญญา เรียงใหม่ → เก่า
     * @return list<array<string, mixed>>
     */
    public static function build(Rental $rental, ?Contract $contract, Collection $events): array
    {
        $room = $rental->room->r_name;
        $items = [
            ['date' => ($rental->rt_movein ?? $rental->scheduled_movein_at)?->format('Y-m-d') ?? '', 'order' => 0, 'kind' => '', 'title' => $rental->rt_movein === null ? 'กำหนดเข้าพัก' : 'ย้ายเข้า', 'desc' => "ห้อง {$room}"],
        ];

        if ($contract) {
            $oldest = $events->last();
            $oldestOld = $oldest === null ? [] : ($oldest->old_values ?? []);
            $firstEnd = array_key_exists('c_end', $oldestOld) ? $oldestOld['c_end'] : $contract->c_end?->format('Y-m-d');
            $items[] = [
                'date' => $contract->c_start->format('Y-m-d'), 'order' => 1, 'kind' => '', 'title' => 'สร้างสัญญาเช่า',
                'desc' => $contract->c_number.' · '.($firstEnd ? "สิ้นสุด {$firstEnd}" : 'ไม่กำหนดวันสิ้นสุด'),
            ];
        }

        if ($rental->rt_moveout) {
            $items[] = ['date' => $rental->rt_moveout->format('Y-m-d'), 'order' => 3, 'kind' => 'end', 'title' => 'ย้ายออก', 'desc' => "ห้อง {$room}"];
        }

        $endDate = $rental->rt_moveout ?? $contract?->c_end;
        if ($contract?->c_status === 'ENDED' && $endDate) {
            $items[] = ['date' => $endDate->format('Y-m-d'), 'order' => 4, 'kind' => 'end', 'title' => 'สัญญาสิ้นสุด', 'desc' => $contract->c_number];
        }

        foreach ($events as $event) {
            $items[] = self::editItem($event);
        }

        usort($items, fn ($a, $b) => [$a['date'], $a['order'], $a['time'] ?? '', $a['seq'] ?? 0]
            <=> [$b['date'], $b['order'], $b['time'] ?? '', $b['seq'] ?? 0]);

        return $items;
    }

    /** @return array<string, mixed> */
    private static function editItem(AuditEvent $event): array
    {
        $old = $event->old_values ?? [];
        $new = $event->new_values ?? [];
        $hasOld = array_key_exists('c_end', $old);
        $hasNew = array_key_exists('c_end', $new);
        $oldEnd = $old['c_end'] ?? null;
        $newEnd = $new['c_end'] ?? null;

        $title = match (true) {
            $event->action === 'CONTRACT_EXTENDED' => 'ต่อสัญญา',
            ! $hasOld || ! $hasNew => 'แก้ไขสัญญา',
            $oldEnd && $newEnd && $newEnd > $oldEnd => 'ต่อสัญญา',
            $oldEnd && $newEnd && $newEnd < $oldEnd => 'แก้วันสิ้นสุดสัญญา',
            $oldEnd && ! $newEnd => 'เปลี่ยนเป็นไม่กำหนดวันสิ้นสุด',
            ! $oldEnd && $newEnd => 'กำหนดวันสิ้นสุดสัญญา',
            default => 'แก้ไขสัญญา',
        };

        $parts = [];
        if ($hasOld || $hasNew) {
            $parts[] = 'วันสิ้นสุด '.($hasOld ? ($oldEnd ?: 'ไม่กำหนด') : '?').' → '.($hasNew ? ($newEnd ?: 'ไม่กำหนด') : '?');
        }
        if (! empty($old['c_status']) && ! empty($new['c_status']) && $old['c_status'] !== $new['c_status']) {
            $parts[] = 'สถานะ '.(self::CONTRACT_STATUS[$old['c_status']] ?? $old['c_status'])
                .' → '.(self::CONTRACT_STATUS[$new['c_status']] ?? $new['c_status']);
        }

        $at = $event->created_at->copy()->timezone('Asia/Bangkok');

        return [
            'date' => $at->format('Y-m-d'),
            'time' => $at->format('H:i'),
            'order' => 2,
            'seq' => $event->getKey(),
            'kind' => 'edit',
            'title' => $title,
            'desc' => implode(' · ', $parts),
            'by' => $event->actor?->u_username ? "โดย {$event->actor->u_username}" : 'ไม่พบข้อมูลผู้ดำเนินการ',
            'reason' => $event->reason ?? 'ไม่ได้ระบุ',
        ];
    }
}
