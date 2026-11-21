<?php

namespace App\Services;

use App\Models\Alert;
use App\Models\AlertAction;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;

class AlertActionService
{
    public const OPEN         = 'open';
    public const UNDER_ACTION = 'under_action';
    public const RESOLVED     = 'resolved';

    /** open -> under_action -> resolved. Peeche ya skip nahi. */
    protected const FLOW = [
        self::OPEN         => [self::UNDER_ACTION],
        self::UNDER_ACTION => [self::RESOLVED],
        self::RESOLVED     => [],
    ];

    /** Alert par kaam shuru: status under_action, alert user ko assign. */
    public function startAction(Alert $alert, User $user, string $notes): Alert
    {
        return $this->transition($alert, $user, self::UNDER_ACTION, 'started', $notes, [
            'assigned_to' => $user->id,
        ]);
    }

    /** Alert resolve: resolved_at, resolved_by aur notes save. */
    public function resolve(Alert $alert, User $user, string $notes): Alert
    {
        return $this->transition($alert, $user, self::RESOLVED, 'resolved', $notes, [
            'resolved_at'      => now(),
            'resolved_by'      => $user->id,
            'resolution_notes' => $notes,
        ]);
    }

    /** Status badle baghair sirf note/action log karna. */
    public function addNote(Alert $alert, User $user, string $notes): AlertAction
    {
        if ($alert->status === self::RESOLVED) {
            throw new DomainException('Resolved alert par naya note nahi lag sakta.');
        }

        return $alert->actions()->create([
            'user_id'     => $user->id,
            'action_type' => 'note',
            'notes'       => $notes,
        ]);
    }

    protected function transition(Alert $alert, User $user, string $to, string $actionType, string $notes, array $extra): Alert
    {
        if (trim($notes) === '') {
            throw new DomainException('Notes lazmi hain.');
        }

        return DB::transaction(function () use ($alert, $user, $to, $actionType, $notes, $extra) {
            $alert = Alert::lockForUpdate()->findOrFail($alert->id);

            if (! in_array($to, self::FLOW[$alert->status] ?? [], true)) {
                throw new DomainException("Status '{$alert->status}' se '{$to}' mein jana allowed nahi.");
            }

            $alert->update(['status' => $to] + $extra);

            $alert->actions()->create([
                'user_id'     => $user->id,
                'action_type' => $actionType,
                'notes'       => $notes,
            ]);

            return $alert->fresh();
        });
    }
}