<?php

namespace Runa\Modules\Audit\Contracts;

use Illuminate\Database\Eloquent\SoftDeletes;
use Runa\Modules\Audit\Support\AutomaticCapture;

/** Opt-in single-model capture, including quiet writes; bulk SQL needs manual events. */
trait Auditable
{
    abstract public function auditPolicy(): AuditModelPolicy;

    public function save(array $options = [])
    {
        return app(AutomaticCapture::class)->mutate($this, $this->auditPolicy(), 'save', fn () => parent::save($options));
    }

    public function delete()
    {
        try {
            return app(AutomaticCapture::class)->mutate($this, $this->auditPolicy(), 'delete', fn () => parent::delete());
        } finally {
            // SoftDeletes::forceDelete itself does not unwind this flag on exceptions.
            if (in_array(SoftDeletes::class, class_uses_recursive($this), true)) {
                $this->forceDeleting = false;
            }
        }
    }

    /** @param array<string, mixed> $options
     * @param  list<string>|string|null  $uniqueBy
     */
    public function saveOrIgnore(array $options = [], array|string|null $uniqueBy = null)
    {
        throw new \LogicException('Auditable insert-or-ignore requires an explicit manual event.');
    }

    /** @param array<string, mixed> $extra */
    protected function incrementOrDecrement($column, $amount, $extra, $method)
    {
        throw new \LogicException('Auditable atomic counters require an explicit manual event.');
    }

    protected function incrementOrDecrementEach(array $columns, array $extra, string $method)
    {
        throw new \LogicException('Auditable atomic counters require an explicit manual event.');
    }
}
