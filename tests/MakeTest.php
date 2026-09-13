<?php

namespace Mykiwi\CastorExtended\Tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

use function Mykiwi\CastorExtended\make;

class MakeTest extends TestCase
{
    private string $dir;
    private Filesystem $fs;

    protected function setUp(): void
    {
        $this->fs = new Filesystem();
        $this->dir = sys_get_temp_dir() . '/castor-extended-make-test-' . uniqid();
        $this->fs->mkdir($this->dir);
    }

    protected function tearDown(): void
    {
        $this->fs->remove($this->dir);
    }

    #[Test]
    public function itRunsWhenTargetIsMissing(): void
    {
        $prerequisite = $this->dir . '/input.txt';
        $this->fs->dumpFile($prerequisite, 'content');

        $ran = false;
        make($this->dir . '/output.txt', $prerequisite, static function () use (&$ran): void {
            $ran = true;
        });

        self::assertTrue($ran);
    }

    #[Test]
    public function itSkipsWhenTargetIsUpToDate(): void
    {
        $prerequisite = $this->dir . '/input.txt';
        $target = $this->dir . '/output.txt';

        $this->fs->dumpFile($prerequisite, 'content');
        sleep(1);
        $this->fs->dumpFile($target, 'content');

        $ran = false;
        make($target, $prerequisite, static function () use (&$ran): void {
            $ran = true;
        });

        self::assertFalse($ran);
    }

    #[Test]
    public function itRunsWhenPrerequisiteIsNewerThanTarget(): void
    {
        $prerequisite = $this->dir . '/input.txt';
        $target = $this->dir . '/output.txt';

        $this->fs->dumpFile($target, 'content');
        sleep(1);
        $this->fs->dumpFile($prerequisite, 'content');

        $ran = false;
        make($target, $prerequisite, static function () use (&$ran): void {
            $ran = true;
        });

        self::assertTrue($ran);
    }

    #[Test]
    public function itAcceptsAFinderAsPrerequisites(): void
    {
        $target = $this->dir . '/output.txt';
        $this->fs->dumpFile($this->dir . '/a.txt', 'a');
        $this->fs->dumpFile($this->dir . '/b.txt', 'b');
        sleep(1);
        $this->fs->dumpFile($target, 'content');

        $finder = new Finder()->in($this->dir)->name('*.txt');

        $ran = false;
        make($target, $finder, static function () use (&$ran): void {
            $ran = true;
        });

        self::assertFalse($ran);
    }

    #[Test]
    public function itFailsOnAMissingPrerequisiteEvenWhenTheTargetIsMissing(): void
    {
        $prerequisite = $this->dir . '/input.txt';
        $target = $this->dir . '/output.txt';

        $ran = false;

        try {
            make($target, $prerequisite, static function () use (&$ran): void {
                $ran = true;
            });
            self::fail('Expected InvalidArgumentException.');
        } catch (\InvalidArgumentException $e) {
            self::assertStringContainsString($prerequisite, $e->getMessage());
        }

        self::assertFalse($ran);
    }

    #[Test]
    public function itFailsWhenAPatternMatchesNothing(): void
    {
        $pattern = $this->dir . '/*.nope';

        $ran = false;

        try {
            make($this->dir . '/output.txt', $pattern, static function () use (&$ran): void {
                $ran = true;
            });
            self::fail('Expected InvalidArgumentException.');
        } catch (\InvalidArgumentException $e) {
            self::assertStringContainsString($pattern, $e->getMessage());
        }

        self::assertFalse($ran);
    }

    #[Test]
    public function itExpandsQuestionMarkAndBracePatterns(): void
    {
        $target = $this->dir . '/output.txt';
        $this->fs->dumpFile($this->dir . '/a.txt', 'a');
        $this->fs->dumpFile($this->dir . '/b.txt', 'b');
        sleep(1);
        $this->fs->dumpFile($target, 'content');

        $patterns = [$this->dir . '/?.txt'];

        if (\defined('GLOB_BRACE')) {
            $patterns[] = $this->dir . '/{a,b}.txt';
        }

        $ran = false;
        make($target, $patterns, static function () use (&$ran): void {
            $ran = true;
        });

        self::assertFalse($ran);

        sleep(1);
        $this->fs->dumpFile($this->dir . '/b.txt', 'newer');

        make($target, $patterns, static function () use (&$ran): void {
            $ran = true;
        });

        self::assertTrue($ran);
    }
}
