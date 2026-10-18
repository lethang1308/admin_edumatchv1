<?php

namespace App\Database\Relations;

use App\Models\Report;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use InvalidArgumentException;

/** Resolve DBML aliases locally, without changing Sanctum/Spatie's morph types. */
class ReportTarget extends MorphTo
{
    public function createModelByType($type)
    {
        $class = Report::getActualClassNameForMorph($type);
        $instance = new $class;
        $instance->setConnection($this->getConnection()->getName());

        return $instance;
    }

    public function associate($model)
    {
        if (! $model instanceof Model) {
            throw new InvalidArgumentException('Associate a report target with a model instance.');
        }

        $type = $this->parent->getAttribute($this->morphType);
        if ((Report::TARGET_MODELS[$type] ?? null) !== $model::class) {
            $type = array_search($model::class, Report::TARGET_MODELS, true);
        }
        if ($type === false) {
            throw new InvalidArgumentException('Unsupported report target model.');
        }

        parent::associate($model);
        $this->parent->setAttribute($this->morphType, $type);

        return $this->parent;
    }
}
