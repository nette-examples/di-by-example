# To My Agents!

It is my fervent wish that this file guide every AI coding agent working with code in this repository.


## Project Overview

*Dependency Injection by Example* is a course. Each numbered directory teaches
one concept, in order, and every chapter runs on its own.

The course has two halves. Chapters in the **Foundations** part use plain PHP
and no library at all: they teach the design principles. Only afterwards does
[Nette DI](https://doc.nette.org/dependency-injection) appear, as a generator of
the container the reader has already written by hand.

- **PHP**: 8.4+, **nette/di**: 3.2.6+
- Code, comments and readmes are in **English**.


## Essential Commands

```shell
php 01-hidden-dependencies/bad.php    # the problem, running
php 01-hidden-dependencies/good.php   # the same program, fixed
php 07-nette-container/example.php    # container chapters have one entry point

composer lint-config   # neon-lint over the configuration files
composer phpstan       # static analysis
composer check-outputs # readme Output sections match reality
composer check         # all three
```

There is no test suite and no testing framework. A chapter is verified by running
its scripts and comparing the result with the *Output* section of its readme.
Where a chapter teaches testability, the test is part of the chapter's own
scripts - see *Testing* below.


## Anatomy of a Chapter

Foundations chapters ship a pair of scripts:

```
NN-slug/
├── readme.md        the text of the chapter
├── bad.php          the problem, as a running program
└── good.php         the same program, fixed
```

Container chapters ship a container instead:

```
NN-slug/
├── readme.md
├── example.php      the entry point
├── config.neon      the service definitions
├── <scenario>.php   optional, at most one: a variant that fails on purpose or
│                    swaps a service out, with its own <scenario>.neon
├── app/             one class per file, no namespace
└── temp/            generated container, created at runtime, gitignored
```

`app/` classes are loaded by explicit `require_once` in the entry point. There is
no autoloader for them on purpose: nothing in this course should look like magic,
and it keeps identically named classes in different chapters from colliding.


## Entry Points

`bad.php` and `good.php`: max ~60 lines of program. Small classes may live inline
in the file unless a later chapter needs them, in which case they go to `app/`.
Both files keep the same structure so the diff between them reads as the lesson.
`bad.php` ends by printing what is wrong with what just happened. A closing test
section, in the chapters that have one, does not count against the 60 lines and
stays under ~25 itself.

`example.php`: max ~40 lines.

```php
<?php declare(strict_types=1);

if (@!include __DIR__ . '/../vendor/autoload.php') {
	echo 'Install dependencies using `composer install`';
	exit(1);
}

require_once __DIR__ . '/app/Logger.php';

$loader = new Nette\DI\ContainerLoader(__DIR__ . '/temp', autoRebuild: true);
$class = $loader->load(function (Nette\DI\Compiler $compiler) {
	$compiler->loadConfig(__DIR__ . '/config.neon');
	return null;
});
$container = new $class;

$distributor = $container->getByType(NewsletterDistributor::class);
$distributor->distribute(['alice@example.com', 'bob@example.com']);
```

A variant script uses the same skeleton and wraps the failing part in
`try`/`catch` so the error is shown, not just thrown.


## Testing

There is no testing framework here and none is to be added. A chapter that
claims something about testability ends with a test written in plain PHP, using
the same helper copied into the file that needs it:

```php
function test(string $expectation, bool $passed): void
{
	echo ($passed ? '[PASS] ' : '[FAIL] ') . $expectation . "\n";
	if (!$passed) {
		exit(1);
	}
}
```

- **The tests always pass.** `check-outputs` runs every script and fails on a
  non-zero exit, and the lesson is a difference in *cost*, not in result: the
  test in `bad.php` goes green while doing something you would not want, the one
  in `good.php` goes green for nothing.
- **Foundations chapters keep the test inside `bad.php` and `good.php`.** The
  pair defines the same class names in two different shapes, so it cannot share
  an `app/`, and a separate script would have to duplicate every class.
- **Container chapters use `test.php` with its own `test.neon`**, which is the
  scenario-script slot. Give `load()` a key so the two compiled containers do not
  share a cache entry.
- Only chapters whose readme actually claims something about testing carry a
  test. Do not add one elsewhere for completeness.


## Other Files

- `config.neon`: max ~30 lines, with a `#` comment on each construct the chapter
  introduces.
- Classes in `app/`: max ~40 lines, one class per file named after it, no
  namespace. Infrastructure classes (`Database`, `FileLogger`, `ConsoleMailer`)
  echo a single line when they do something, so the reader can see in the output
  when and how often objects are created.


## Chapter `readme.md`

60-140 lines, and up to ~180 for the few that also carry a test or, like the
configuration chapter, one broad subject with several faces. Past that, split.
These sections in this order:

```markdown
# NN · Chapter Title

One-sentence summary.

**You will learn:** 3-5 bullets

## Run it            fenced shell block

## The story        ) Foundations chapters open with these three,
## The pain         ) in this order
## The fix          )

## <Walkthrough>     container chapters: H2 sections named after the concepts

## Output            real output; H3 subsections per script where there are several
## Try it yourself   2-4 one-sentence exercises, easiest first
## Further reading   links to doc.nette.org
```

A chapter may add one or two sections of its own between the walkthrough and
*Output* - the objection a reader is about to raise, the caveat, the wider
point - and a third when one of them is the chapter's test. Anything more and
the chapter is teaching two things.

Second person, concrete, no filler. Every claim must come from an actual run.


## Rules

- **One chapter, one concept.** If it does not fit on a screen, split it.
- **No forward references in code.** A chapter may only use what earlier
  chapters introduced. Referring to a later topic in prose is fine, but do it by
  name, not by number - numbers shift while the course is being written.
- **Comment only what the current chapter teaches.**
- **Chapters are self-contained.** Classes are copied into each chapter's
  `app/`; never require a file from another chapter.
- **Foundations chapters must run without any dependency installed.**
- **Verify, do not remember.** Output sections, generated code quoted in the
  text and every exercise hint must be checked by running them. Error messages
  quoted in prose are copied from a real failure, not paraphrased.
- **Show, do not assert.** A chapter that claims a design is easier to test has
  to contain a test that runs. The same goes for any claim about a tool: if it
  cannot be demonstrated or linked, it does not belong in the text.
- **Deterministic output.** No timestamps, no randomness. The one intentional
  exception is the chapter on global state, where the changing balance is the
  point; its readme uses `...` in place of the varying number.


## The Domain

Every chapter models the same small publishing shop. **Names and roles are
fixed**, so a reader who has met `NewsletterDistributor` once recognises it
later:

- `Logger` (interface, `log(string $message): void`) with `FileLogger` and `ConsoleLogger`
- `Mailer` (interface) with `SmtpMailer`
- `NewsletterDistributor` - sends the newsletter, logs the result
- `Database` - a simulation of a connection, not a real driver; echoes what it does
- `Article`, `ArticleFactory`, `ArticleRepository`, `EditController`, `ReportGenerator`
- `PaymentGateway` with `RealPaymentGateway` and `FakePaymentGateway`, plus `CreditCard`
- `Shipper` (interface) with `DhlShipper`, `PplShipper`, and `ShipManager`
- `EventHandler` (interface) with implementations, and `EventDispatcher`
- `Cache`, `SlugGenerator`, `PageRenderer`, `Translator` where a chapter needs a second dependency
- `ProductController` and `OrderController` where one controller is not enough to show the point
- test doubles are named `Fake*` (`FakePaymentGateway`, `FakeLogger`, `FakeMailer`); they implement the interface and record what they were asked to do instead of doing it

**Signatures deliberately differ between chapters.** `Database` takes a DSN
alone where nothing else is needed and a DSN plus user where the chapter shows
parameters; `Article` grows a dependency exactly when a chapter needs to
demonstrate what that costs. Keep the name and the role, and give the class
whatever shape teaches the point - a class that carries an argument it never
uses is noise, and this course is partly about not doing that.

Fixed values: recipients `alice@example.com` and `bob@example.com`; DSN
`mysql:host=127.0.0.1;dbname=blog`; user `admin`; log file `temp/app.log`;
account balance $500 and a $100 charge.


## Adding a Chapter

1. Pick the single concept; check it needs nothing from later chapters.
2. Foundations: write `bad.php` first and make sure the problem is visible in
   its output, then `good.php` with the same structure. Container chapters:
   `config.neon`, `example.php`, `app/`.
3. Write the readme in the fixed shape; paste *Output* from a real run.
4. Keep the limits: bad/good ≤ 60 lines plus an optional test section,
   example.php ≤ 40, config.neon ≤ 30, a class in app/ ≤ 40, readme 60-140.
5. Run `composer check`, and run the scripts twice - the output must be
   identical, and the second run must reuse the cached container.
6. Add a row to the chapter table in the root readme.
