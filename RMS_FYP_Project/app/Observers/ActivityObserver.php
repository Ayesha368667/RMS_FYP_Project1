<?php

namespace App\Observers;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;

class ActivityObserver
{
    // Ye fields log mein kabhi nahi jayenge (passwords safe rahein)
    private $hidden = ['password', 'previous_password', 'remember_token', 'updated_at'];

    public function created(Model $model)
    {
        $this->save('created', $model, null, $this->clean($model->getAttributes()));
    }

    public function updated(Model $model)
    {
        $new = $this->clean($model->getChanges());

        if (empty($new)) {
            return;
        }

        // Badalne se pehle ki values
        $old = [];
        foreach ($new as $field => $value) {
            $old[$field] = $model->getOriginal($field);
        }

        // Active/inactive badla to alag naam se likho
        $action = 'updated';
        if (array_key_exists('is_active', $new)) {
            $action = $new['is_active'] ? 'activated' : 'deactivated';
        }

        $this->save($action, $model, $old, $new);
    }

    // Passwords log mein nahi jayenge
    private function clean($values)
    {
        return array_diff_key($values, array_flip($this->hidden));
    }

    private function save($action, $model, $old, $new)
    {
        $name = class_basename($model);

        ActivityLog::create([
            'user_id'      => auth()->id(),
            'action'       => $action,
            'subject_type' => $name,
            'subject_id'   => $model->id,
            'description'  => $name . ' #' . $model->id . ' ' . $action,
            'old_values'   => $old,
            'new_values'   => $new,
            'ip_address'   => request()->ip(),
        ]);
    }
}