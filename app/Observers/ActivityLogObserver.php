<?php
namespace App\Observers;

use Illuminate\Database\Eloquent\Model;
use App\Helpers\ActivityLogger;

class ActivityLogObserver
{
    public function created(Model $model)
    {
        ActivityLogger::log([
            'type' => 'created',
            'module' => class_basename($model),
            'action' => 'create',
            'details' => json_encode($model->getAttributes()),
            'subject_type' => get_class($model),
            'subject_id' => $model->getKey(),
        ]);
    }

    public function updated(Model $model)
    {
        ActivityLogger::log([
            'type' => 'updated',
            'module' => class_basename($model),
            'action' => 'update',
            'details' => json_encode($model->getChanges()),
            'subject_type' => get_class($model),
            'subject_id' => $model->getKey(),
        ]);
    }

    public function deleted(Model $model)
    {
        ActivityLogger::log([
            'type' => 'deleted',
            'module' => class_basename($model),
            'action' => 'delete',
            'details' => json_encode($model->getOriginal()),
            'subject_type' => get_class($model),
            'subject_id' => $model->getKey(),
        ]);
    }
}
