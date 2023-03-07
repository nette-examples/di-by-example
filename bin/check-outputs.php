<?php declare(strict_types=1);

/**
 * Verifies that the "## Output" section of every chapter readme still matches
 * what the chapter's scripts actually print. Run it before committing:
 * composer check
 *
 * A chapter with a single script has one fenced block under "## Output".
 * A chapter with several scripts labels them with "### bad.php" subheadings.
 */

if (@!include __DIR__ . '/../vendor/autoload.php') {
	echo "Install dependencies using `composer install`\n";
	exit(1);
}

$root = dirname(__DIR__);
$failures = 0;

foreach (glob("$root/[0-9][0-9]-*", GLOB_ONLYDIR) ?: [] as $dir) {
	$name = basename($dir);
	$readme = "$dir/readme.md";
	$scripts = array_map('basename', glob("$dir/*.php") ?: []);

	if (!$scripts || !is_file($readme)) {
		echo "$name: no scripts or no readme.md\n";
		$failures++;
		continue;
	}

	// normalize line endings: on Windows the working copy may hold CRLF
	$text = str_replace("\r\n", "\n", (string) file_get_contents($readme));

	if (!preg_match('~^## Output\s*$(.*?)(?=^## |\z)~ms', $text, $m)) {
		echo "$name: no '## Output' section\n";
		$failures++;
		continue;
	}

	// Pair every fenced block with the script it documents: either the one
	// named by the nearest "### script.php" heading, or the only script there is.
	$section = $m[1];
	preg_match_all('~(?:^###\s*(?<script>\S+?\.php)\s*$)?(?:(?!^###).)*?^```\w*\n(?<body>.*?)^```~ms', $section, $blocks, PREG_SET_ORDER);
	if (!$blocks) {
		echo "$name: '## Output' has no fenced block\n";
		$failures++;
		continue;
	}

	$expected = [];
	foreach ($blocks as $block) {
		$script = $block['script'] ?: (count($scripts) === 1 ? $scripts[0] : null);
		if ($script === null) {
			echo "$name: several scripts, so each output block needs a '### <script>.php' heading\n";
			$failures++;
			continue 2;
		}
		$expected[$script][] = $block['body'];
	}

	foreach ($expected as $script => $bodies) {
		if (!in_array($script, $scripts, true)) {
			echo "$name: readme documents $script, which does not exist\n";
			$failures++;
			continue;
		}

		exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg("$dir/$script") . ' 2>&1', $lines, $code);
		$actual = implode("\n", $lines);
		unset($lines);
		if ($code !== 0) {
			echo "$name/$script: exited with code $code\n";
			$failures++;
			continue;
		}

		// The readme may abridge the output: each chunk between `...` markers
		// must appear in the real output, in order.
		$offset = 0;
		$missing = null;
		foreach (preg_split('~^\s*\.\.\.\s*$~m', trim(implode("\n", $bodies))) ?: [] as $chunk) {
			$chunk = trim($chunk, "\n");
			if ($chunk === '') {
				continue;
			}
			$pos = strpos($actual, $chunk, $offset);
			if ($pos === false) {
				$missing = $chunk;
				break;
			}
			$offset = $pos + strlen($chunk);
		}

		if ($missing === null) {
			echo "$name/$script: OK\n";
		} else {
			echo "$name/$script: readme shows output the script does not produce:\n";
			echo preg_replace('~^~m', '    ', $missing) . "\n";
			$failures++;
		}
	}
}

exit($failures === 0 ? 0 : 1);
