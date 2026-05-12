<?php

declare(strict_types=1);

namespace AntiPatterns\StructureAndArchitecture\solution;

/**
 * Result - Consistent return type for all service operations.
 *
 * Replaces the inconsistent mix of arrays, stdClass, and HTML strings
 * that the God Class returned. Every operation returns a Result.
 */
final readonly class Result
{
    public bool $success;
    public mixed $data;
    public ?string $error;

    private function __construct(bool $success, mixed $data = null, ?string $error = null)
    {
        $this->success = $success;
        $this->data = $data;
        $this->error = $error;
    }

    public static function success(mixed $data = null): self
    {
        return new self(true, $data);
    }

    public static function failure(string $error): self
    {
        return new self(false, null, $error);
    }

    public function isOk(): bool
    {
        return $this->success;
    }

    public function isFail(): bool
    {
        return !$this->success;
    }
}
