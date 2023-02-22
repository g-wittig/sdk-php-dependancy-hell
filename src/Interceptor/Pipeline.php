<?php

declare(strict_types=1);

namespace Temporal\Interceptor;

use Closure;
use Temporal\Exception\InterceptorCallException;

/**
 * Pipeline is a processor for interceptors chain.
 *
 * @template TInterceptor of object
 * @template TReturn of mixed
 *
 * @psalm-type TLast = Closure(mixed ...): mixed
 * @psalm-type TCallable = callable(mixed ...): mixed
 *
 * @psalm-immutable
 * @internal
 */
final class Pipeline
{
    /** @var non-empty-string */
    private string $method;

    /** @var Closure */
    private Closure $last;

    /** @var list<TInterceptor> */
    private array $interceptors = [];
    /** @var int<0, max> Current interceptor key */
    private int $current = 0;

    /**
     * @param iterable<TInterceptor> $interceptors
     */
    private function __construct(
        iterable $interceptors,
    ) {
        // Reset keys
        foreach ($interceptors as $interceptor) {
            $this->interceptors[] = $interceptor;
        }
    }

    /**
     * Make sure that interceptors implement the same interface.
     */
    public static function prepare(iterable $interceptors)
    {
        return new self($interceptors);
    }

    /**
     * @param TLast $last
     * @param non-empty-string $method Method name of the all interceptors.
     *
     * @return TCallable
     */
    public function with(\Closure $last, string $method): callable
    {
        $new = clone $this;

        $new->last = $last;
        $new->method = $method;

        return $new;
    }

    /**
     * Must be used after {@see with()} method.
     *
     * @param mixed ...$arguments
     *
     * @return TReturn
     */
    public function __invoke(mixed ...$arguments): mixed
    {
        try {
            $interceptor = $this->interceptors[$this->current] ?? null;

            if ($interceptor === null) {
                return ($this->last)(...$arguments);
            }

            $next = $this->next();
            $arguments[] = $next;

            return $interceptor->{$this->method}(...$arguments);
        } catch (\Throwable $e) {
            throw new InterceptorCallException(previous: $e);
        }
    }

    private function next(): self
    {
        $new = clone $this;
        ++$new->current;

        return $new;
    }
}
