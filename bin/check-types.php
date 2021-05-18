<?php declare(strict_types=1);

/**
 * Runs PHPStan over the chapters.
 *
 * Chapters deliberately reuse class names - `bad.php` and `good.php` define the
 * same class on purpose, because the diff between them is the lesson - so they
 * cannot be analysed in one pass. Each entry point is therefore analysed on its
 * own, together with the chapter's app/ directory.
 */

if (@!include __DIR__ . '/../vendor/autoload.php') {
	echo "Install dependencies using `composer install`\n";
	exit(1);
}

$root = dirname(__DIR__);
$phpstan = $root . '/vendor/bin/phpstan';
$failures = 0;

$targets = [[$root . '/bin', null]];
foreach (glob("$root/[0-9][0-9]-*", GLOB_ONLYDIR) ?: [] as $dir) {
	$app = is_dir("$dir/app") ? "$dir/app" : null;
	foreach (glob("$dir/*.php") ?: [] as $script) {
		$targets[] = [$script, $app];
	}
}

foreach ($targets as [$path, $app]) {
	$args = escapeshellarg($path) . ($app ? ' ' . escapeshellarg($app) : '');
	$cmd = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($phpstan)
		. " analyse $args --no-progress --error-format=raw";
	exec($cmd . ' 2>&1', $lines, $code);
	$label = substr((string) $path, strlen($root) + 1);

	if ($code === 0) {
		echo "$label: OK\n";
	} else {
		echo "$label:\n";
		foreach ($lines as $line) {
			if (str_contains($line, ':')) {
				echo "    $line\n";
			}
		}
		$failures++;
	}
	unset($lines);
}

exit($failures === 0 ? 0 : 1);
