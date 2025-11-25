<?php

namespace Base33\FilamentSignal\Support;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use JsonSerializable;

class SignalPayloadFactory
{
    /**
     * @return array<string, mixed>
     */
    public function fromEvent(object $event): array
    {
        $publicProperties = get_object_vars($event);

        $payload = [];

        foreach ($publicProperties as $key => $value) {
            $payload[$key] = $this->normalizeValue($value);
        }

        return $payload;
    }

    protected function normalizeValue(mixed $value): mixed
    {
        if ($value instanceof Model) {
            return $value->toArray();
        }

        if ($value instanceof Arrayable) {
            return $value->toArray();
        }

        if ($value instanceof Collection) {
            return $value->map(fn ($item) => $this->normalizeValue($item))->all();
        }

        if ($value instanceof JsonSerializable) {
            return $value->jsonSerialize();
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format(\DateTimeInterface::ATOM);
        }

        if (is_object($value)) {
            return Arr::map(get_object_vars($value), fn ($item) => $this->normalizeValue($item));
        }

        if (is_array($value)) {
            return Arr::map($value, fn ($item) => $this->normalizeValue($item));
        }

        return $value;
    }
}
