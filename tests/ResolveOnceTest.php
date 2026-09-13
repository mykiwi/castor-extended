<?php

namespace Mykiwi\CastorExtended\Tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function Mykiwi\CastorExtended\resolve_once;

/**
 * resolve_once() backs run_target()'s "a shared #[Requires] target only
 * runs once" guarantee. These tests race it with raw Fibers, the same
 * primitive Castor's own `parallel()` uses, without needing a booted
 * Castor container.
 */
class ResolveOnceTest extends TestCase
{
    #[Test]
    public function itRunsTheBuilderOnlyOnceWhenTwoFibersRaceForTheSameName(): void
    {
        $name = 'shared-' . uniqid();
        $runCount = 0;

        $build = static function () use (&$runCount): void {
            ++$runCount;
            // Simulates a recipe that suspends mid-build, e.g. run()
            // waiting on a subprocess.
            \Fiber::suspend();
        };

        $fiberA = new \Fiber(static fn () => resolve_once($name, $build));
        $fiberB = new \Fiber(static fn () => resolve_once($name, $build));

        $fiberA->start();
        self::assertFalse($fiberA->isTerminated());

        // Without the pending-registry guard, B would find the name not
        // yet resolved and run $build a second time here.
        $fiberB->start();
        self::assertFalse($fiberB->isTerminated());

        $fiberA->resume();
        self::assertTrue($fiberA->isTerminated());

        $fiberB->resume();
        self::assertTrue($fiberB->isTerminated());

        self::assertSame(1, $runCount);
    }

    #[Test]
    public function itReturnsImmediatelyWhenTheNameIsAlreadyResolved(): void
    {
        $name = 'already-resolved-' . uniqid();
        $runCount = 0;

        $build = static function () use (&$runCount): void {
            ++$runCount;
        };

        resolve_once($name, $build);
        resolve_once($name, $build);

        self::assertSame(1, $runCount);
    }

    #[Test]
    public function itDoesNotHangWaitersForeverWhenTheBuildFails(): void
    {
        $name = 'failing-' . uniqid();

        $fiberA = new \Fiber(static function () use ($name): void {
            resolve_once($name, static function (): void {
                \Fiber::suspend();

                throw new \RuntimeException('boom');
            });
        });
        $fiberB = new \Fiber(static function () use ($name): void {
            resolve_once($name, static function (): void {
                self::fail('Waiter must not re-run the build after a failed attempt.');
            });
        });

        $fiberA->start();
        $fiberB->start();

        try {
            $fiberA->resume();
            self::fail('Expected exception was not thrown.');
        } catch (\RuntimeException $e) {
            self::assertSame('boom', $e->getMessage());
        }

        $fiberB->resume();
        self::assertTrue($fiberB->isTerminated());
    }
}
