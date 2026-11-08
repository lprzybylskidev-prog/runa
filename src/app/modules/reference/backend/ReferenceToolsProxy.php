<?php

namespace App\Modules\Reference;

use App\Modules\Reference\Contracts\ReferenceTools;
use Runa\Support\Modules\Api\GuardedApi;

final class ReferenceToolsProxy extends GuardedApi implements ReferenceTools
{
    public function samples(): array
    {
        $result = $this->invoke(__FUNCTION__, []);

        if (! is_array($result)) {
            throw new \LogicException('Invalid reference response.');
        }
        /** @var array{items:list<array{public_id:string,revision:string,code?:string}>} $result */

        return $result;
    }

    public function editSample(string $publicId, string $revision, string $code): array
    {
        $result = $this->invoke(__FUNCTION__, [$publicId, $revision, $code]);

        if (! is_array($result)) {
            throw new \LogicException('Invalid reference response.');
        }
        /** @var array<string,mixed> $result */

        return $result;
    }

    public function settings(): array
    {
        $result = $this->invoke(__FUNCTION__, []);

        if (! is_array($result)) {
            throw new \LogicException('Invalid reference response.');
        }
        /** @var array{revision:int,review_id:string,settings:array<string,array{names:array<string,string>,descriptions:array<string,string>,value?:mixed,set?:bool}>} $result */

        return $result;
    }

    public function reviewSettings(#[\SensitiveParameter] array $patch, int $revision, string $reviewId): array
    {
        $result = $this->invoke(__FUNCTION__, [$patch, $revision, $reviewId]);

        if (! is_array($result)) {
            throw new \LogicException('Invalid reference response.');
        }
        /** @var array<string,mixed> $result */

        return $result;
    }

    public function publishSettings(#[\SensitiveParameter] array $patch, int $revision, string $reviewId, string $review): array
    {
        $result = $this->invoke(__FUNCTION__, [$patch, $revision, $reviewId, $review]);

        if (! is_array($result)) {
            throw new \LogicException('Invalid reference response.');
        }
        /** @var array<string,mixed> $result */

        return $result;
    }

    public function snapshots(?string $publicId = null): array
    {
        $result = $this->invoke(__FUNCTION__, [$publicId]);

        if (! is_array($result)) {
            throw new \LogicException('Invalid reference response.');
        }
        /** @var array<string,mixed> $result */

        return $result;
    }

    public function captureSnapshot(string $name, int $revision, string $reviewId): array
    {
        $result = $this->invoke(__FUNCTION__, [$name, $revision, $reviewId]);

        if (! is_array($result)) {
            throw new \LogicException('Invalid reference response.');
        }
        /** @var array<string,mixed> $result */

        return $result;
    }

    public function deleteSnapshot(string $publicId, int $revision, string $reviewId): array
    {
        $result = $this->invoke(__FUNCTION__, [$publicId, $revision, $reviewId]);

        if (! is_array($result)) {
            throw new \LogicException('Invalid reference response.');
        }
        /** @var array<string,mixed> $result */

        return $result;
    }

    public function history(?string $sample = null, ?string $before = null): array
    {
        $result = $this->invoke(__FUNCTION__, [$sample, $before]);

        if (! is_array($result)) {
            throw new \LogicException('Invalid reference response.');
        }
        /** @var array{items:list<array{public_id:string,occurred_at:string,action:string,outcome:string}>,next:?string} $result */

        return $result;
    }

    public function dispatch(): array
    {
        $result = $this->invoke(__FUNCTION__, []);

        if (! is_array($result)) {
            throw new \LogicException('Invalid reference response.');
        }
        /** @var array<string,mixed> $result */

        return $result;
    }

    public function work(): array
    {
        $result = $this->invoke(__FUNCTION__, []);

        if (! is_array($result)) {
            throw new \LogicException('Invalid reference response.');
        }
        /** @var array{items:list<array{work_id:string,operation:string,status:string,progress:string,attempts:int,cancellation_requested:bool} >} $result */

        return $result;
    }
}
