<?php

namespace App\Actions\Rooms;

use App\Models\AuditEvent;
use App\Models\Room;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class SaveRoom
{
    public function handle(
        User $actor,
        array $input,
        ?Room $room = null
    ): Room {
        return DB::transaction(function () use ($actor, $input, $room) {
            $actor = User::find($actor->getKey());

            abort_unless(
                $actor && ! $actor->must_change_password,
                403
            );

            $creating = $room === null;

            if ($creating) {
                Gate::forUser($actor)->authorize('create', Room::class);
                $current = new Room();
            } else {
                Gate::forUser($actor)->authorize('update', $room);
                $current = Room::findOrFail($room->getKey());
            }

            $data = [];

            foreach (['r_name', 'r_floor', 'r_type', 'r_rent'] as $field) {
                if (! array_key_exists($field, $input)) {
                    continue;
                }

                $value = $input[$field];

                if (is_string($value)) {
                    $value = trim($value);
                }

                $data[$field] = $value;
            }

            $uniqueName = Rule::unique('rooms', 'r_name');

            if (! $creating) {
                $uniqueName->ignore($current->getKey(), 'r_id');
            }

            $presence = $creating
                ? ['required']
                : ['sometimes', 'required'];

            $validated = Validator::make($data, [
                'r_name' => [
                    ...$presence,
                    'string',
                    'max:255',
                    $uniqueName,
                ],
                'r_floor' => [
                    ...$presence,
                    'integer',
                    'between:-2147483648,2147483647',
                ],
                'r_type' => [...$presence, 'string', 'max:255'],
                'r_rent' => [
                    ...$presence,
                    'numeric',
                    'regex:/\A[0-9]{1,8}(?:\.[0-9]{1,2})?\z/',
                ],
            ])->validate();

            $current->fill($validated);

            if ($creating) {
                $current->r_status = 'VACANT';
            }

            $changes = $current->getDirty();

            if (! $creating && $changes === []) {
                return $current;
            }

            $oldValues = [];

            if (! $creating) {
                foreach (array_keys($changes) as $field) {
                    $oldValues[$field] = $current->getOriginal($field);
                }
            }

            $current->save();

            AuditEvent::create([
                'actor_user_id' => $actor->getKey(),
                'entity_type' => 'rooms',
                'entity_id' => $current->getKey(),
                'action' => $creating ? 'room_created' : 'room_updated',
                'old_values' => $creating ? null : $oldValues,
                'new_values' => $current->only(array_keys($changes)),
            ]);

            return $current;
        }, 3);
    }
}