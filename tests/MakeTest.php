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
}
