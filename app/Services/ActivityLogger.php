<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class ActivityLogger
{
    /**
     * Log an admin action.
     *
     * @param  string       $action       e.g. 'created', 'updated', 'deleted', 'approved'
     * @param  string       $description  Human-readable sentence
     * @param  Model|null   $model        The Eloquent model that was affected
     * @param  array        $properties   Extra data (optional)
     */
    public static function log(
        string $action,
        string $description,
        ?Model $model = null,
        array $properties = []
    ): void {
        try {
            ActivityLog::create([
                'user_id'    => Auth::id(),
                'action'     => $action,
                'model_type' => $model ? class_basename($model) : null,
                'model_id'   => $model?->getKey(),
                'description'=> $description,
                'properties' => $properties ?: null,
                'ip_address' => Request::ip(),
            ]);
        } catch (\Throwable $e) {
            // never break the app because of logging
        }
    }
}
