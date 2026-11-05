<?php

namespace App\Observers;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;

class ActivityObserver
{
    //Observer kuch kam automatically krta he 
    //bgair hr controller me code likhy
    //example (RMS) Tumhare RMS mein UserObserver banaya gaya ho,
    //to ye automatically user ke create ya update hone par specific actions perform kar sakta hai.

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

        $old = [];
        foreach (array_keys($new) as $field) {
            $old[$field] = $model->getOriginal($field);
        }

        $action = 'updated';
        if (array_key_exists('is_active', $new)) {
            $action = $new['is_active'] ? 'activated' : 'deactivated';
        }

        $this->save($action, $model, $old, $new);
    }

    public function deleted(Model $model)
    {
        $this->save('deleted', $model, $this->clean($model->getAttributes()), null);
    }

    private function clean($values)
    {
        return array_diff_key($values, array_flip($this->hidden));
    }

    private function save($action, $model, $old, $new)
    {
        ActivityLog::create([
            'user_id'    => auth()->id(),
            'action'     => $action,
            'model_type' => class_basename($model),
            'model_id'   => $model->getKey(),
            'old_values' => $old,
            'new_values' => $new,
            'ip_address' => request()->ip(),
        ]);
    }
}
