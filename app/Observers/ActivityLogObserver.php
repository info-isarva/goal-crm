<?php

namespace App\Observers;

use App\Helpers\ActivityLogger;
use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;

class ActivityLogObserver
{
    public function created(Model $model): void
    {
        if ($this->shouldSkip($model)) {
            return;
        }

        ActivityLogger::log([
            'type' => 'created',
            'module' => class_basename($model),
            'action' => 'create',
            'details' => ActivityLogger::redact($model->getAttributes()),
            'subject_type' => get_class($model),
            'subject_id' => $model->getKey(),
        ]);
    }

    public function updated(Model $model): void
    {
        if ($this->shouldSkip($model)) {
            return;
        }

        $changes = $model->getChanges();
        unset($changes['updated_at'], $changes['created_at']);
        if ($changes === []) {
            return;
        }

        $before = [];
        foreach (array_keys($changes) as $key) {
            $before[$key] = $model->getOriginal($key);
        }

        ActivityLogger::log([
            'type' => 'updated',
            'module' => class_basename($model),
            'action' => 'update',
            'details' => ActivityLogger::redact([
                'before' => $before,
                'after' => $changes,
            ]),
            'subject_type' => get_class($model),
            'subject_id' => $model->getKey(),
        ]);
    }

    public function deleted(Model $model): void
    {
        if ($this->shouldSkip($model)) {
            return;
        }

        ActivityLogger::log([
            'type' => 'deleted',
            'module' => class_basename($model),
            'action' => method_exists($model, 'isForceDeleting') && $model->isForceDeleting()
                ? 'force_delete'
                : 'delete',
            'details' => ActivityLogger::redact($model->getOriginal()),
            'subject_type' => get_class($model),
            'subject_id' => $model->getKey(),
        ]);
    }

    public function restored(Model $model): void
    {
        if ($this->shouldSkip($model)) {
            return;
        }

        ActivityLogger::log([
            'type' => 'restored',
            'module' => class_basename($model),
            'action' => 'restore',
            'details' => ActivityLogger::redact($model->getAttributes()),
            'subject_type' => get_class($model),
            'subject_id' => $model->getKey(),
        ]);
    }

    protected function shouldSkip(Model $model): bool
    {
        return $model instanceof ActivityLog;
    }
}
