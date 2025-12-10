<?php

namespace Ajooda\AiMetering\Console\Concerns;

trait ResolvesBillable
{
    /**
     * Validate and resolve a billable entity.
     *
     * @param  mixed  $billableId
     * @return array{0: bool, 1: mixed|null, 2: string|null}
     */
    protected function resolveBillable(string $billableType, $billableId): array
    {
        if (! class_exists($billableType)) {
            $this->error("Invalid billable type: {$billableType}");

            return [false, null, "Invalid billable type: {$billableType}"];
        }

        $billable = $billableType::find($billableId);

        if (! $billable) {
            $this->error("Billable entity not found: {$billableType} #{$billableId}");

            return [false, null, "Billable entity not found: {$billableType} #{$billableId}"];
        }

        return [true, $billable, null];
    }

    /**
     * Get a human-readable label for the billable entity.
     *
     * @param  mixed  $billable
     */
    protected function getBillableLabel($billable): string
    {
        $attributes = ['name', 'email', 'title', 'slug', 'id'];

        foreach ($attributes as $attribute) {
            if (isset($billable->{$attribute})) {
                $value = $billable->{$attribute};

                return "{$value} (#{$billable->id})";
            }
        }

        return get_class($billable)." #{$billable->id}";
    }
}
