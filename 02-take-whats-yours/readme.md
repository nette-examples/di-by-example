# 02 · Take What's Yours

A class that accepts somebody else's dependencies, and what happens the day that somebody changes.

**You will learn:**

- **Rule #2: take what's yours** - accept your dependencies, not your dependencies' dependencies
- how an interface lets you swap an implementation without touching the class that uses it
- why the interface is called `Logger` and not `ILogger` or `LoggerInterface`
- what a test costs in each of the two designs

## Run it

```shell
php 02-take-whats-yours/bad.php
php 02-take-whats-yours/good.php
```

## The story

Chapter 01 ended well: `Logger` now says it needs a file path. So when I wrote the class that sends our newsletter, I did the obvious thing. It needs to log, the logger needs a path, so the newsletter class takes a path:

```php
$distributor = new NewsletterDistributor(__DIR__ . '/temp/app.log', '[newsletter]');
```

I remember being pleased with this. Everything is passed in, no globals anywhere, Rule #1 satisfied. And it is still wrong.

## The pain

Run `bad.php` and look at what `NewsletterDistributor` is holding:

```php
public function __construct(
	private string $logFile,
	private string $logPrefix,
) {
}
```

Neither of those is its business. The distributor does not write to files. It is carrying a log path and a prefix across half the application like somebody else's luggage, purely so it can build a logger later:

```php
$logger = new FileLogger($this->logFile, $this->logPrefix);
```

Two consequences, both of which cost me a real afternoon:

**I could not see the log while developing.** I wanted output in the console instead of a file. There is no way to ask for that - the distributor constructs its own `FileLogger`, hard-coded, in the middle of a method. The only way in is to edit the class.

**Every change to the logger reached the distributor.** Add a third argument to `FileLogger` - a minimum level, a date format, anything - and you must also edit `NewsletterDistributor`, plus every place that creates one. A class that does not log has to be modified because logging changed. That is the tell.

## The fix

Ask for the thing you actually need:

```php
public function __construct(
	private Logger $logger,
) {
}
```

> **Rule #2: Take what's yours.** Accept your own dependencies, not the dependencies of your dependencies. You need to log - so ask for a logger, not for the path to a log file.

Now the file path is the logger's business, where it belongs. `FileLogger` can grow ten constructor arguments and the distributor will never notice.

And the thing I wanted on that afternoon takes one line, because `Logger` is an interface with two implementations:

```php
$distributor = new NewsletterDistributor(new FileLogger(__DIR__ . '/temp/app.log', '[newsletter]'));
$distributor->distribute($recipients);

$distributor = new NewsletterDistributor(new ConsoleLogger);
$distributor->distribute($recipients);
```

Same class, twice, unmodified. The output shows the second run printing the log line straight to the console. That is not a framework feature; that is just what happens when a class is honest about what it needs.

## About that interface name

It is called `Logger`. Not `ILogger`, not `LoggerInterface`.

This is not aesthetics, it is a practical decision, and here is the scenario that proves it. You start with a single class named `Logger` that writes to a file. Later you need a second one for the database. Now `Logger` is the wrong name for the file one, so you rename it to `FileLogger` and create an interface for both - and if the interface is allowed to keep the name `Logger`, **nothing else in the codebase changes**. Every constructor that said `Logger $logger` still says it and still means the right thing.

Had the convention been `ILogger`, you would be editing every one of those constructors for no reason at all. A prefix that exists to tell you what the type system already knows is a prefix that gets in your way exactly when you need to move.

## Now write a test

Both files end with the same test: *does `distribute()` log what it sent?* Both pass. What differs is the price.

`bad.php` has no seam, so the only observable effect is a file. The test has to know the log path, delete the file the application logs into, run the distributor, and go fishing in the text that lands there. Two such tests running at once would eat each other's evidence.

`good.php` hands in a fake and reads it back:

```php
class FakeLogger implements Logger
{
	public array $messages = [];

	public function log(string $message): void
	{
		$this->messages[] = $message;
	}
}

$fake = new FakeLogger;
new NewsletterDistributor($fake)->distribute(['carol@example.com']);
test('the newsletter was logged', $fake->messages === ['sent 1 newsletters']);
```

One property and one method. Nothing on disk, nothing to clean up, and it asserts the exact message instead of fishing for a substring. Note that `FakeLogger` needed permission from nobody: it is simply a third implementation of an interface the distributor already accepts.

`test()` is five lines at the top of the file - print `[PASS]` or `[FAIL]`, `exit(1)` on failure - and it is the entire testing framework this course uses. A real one buys you better failure messages, a runner and isolation between cases. It does not buy you testability. That came from the constructor, before anything was installed.

## What this buys you beyond taste

The `good.php` version is easier for people, but notice who else benefits:

- **PHPStan** knows `$this->logger` is a `Logger` and checks every call against the interface. In `bad.php` it sees two strings and can tell you nothing about logging at all.
- **AI agents** open the constructor and immediately know what the class talks to. I have watched an agent add a feature to a class like `good.php` correctly on the first attempt, and get lost in a class like `bad.php`, because in the second one the real dependency is buried in a method body and nothing in the signature hints at it.

This is why I get uneasy around Laravel facades. `DB::insert(...)` inside a method is comfortable to write and it looks clean, but the class is now lying: its constructor claims it needs nothing while its body reaches for a database. Everything above - the type checking, the test double, the agent reading the file, the colleague onboarding on Monday - loses the thread at exactly that line. The convenience is real, and it is borrowed against a debt somebody pays later.

## Output

### bad.php

```
[Mail] to alice@example.com
[Mail] to bob@example.com

It works. Now try two things I actually needed last week:
  1. See the log in the console while developing, not in a file.
  2. Add a third argument to FileLogger, say a minimum level.
...
Testing that distribute() logs:
[Mail] to carol@example.com
[PASS] the newsletter was logged

Green - but to observe one method call the test had to know a file path,
touch the disk, and delete the file the application logs into.
```

### good.php

```
[Mail] to alice@example.com
[Mail] to bob@example.com

--- same distributor class, different logger ---

[Mail] to alice@example.com
[Mail] to bob@example.com
[log] sent 2 newsletters

NewsletterDistributor was not touched between those two runs.
...
Testing that distribute() logs:
[Mail] to carol@example.com
[PASS] the newsletter was logged

No file, no path, no cleanup, nothing left behind. The seam that made
this possible is the constructor - the same one that fixed the design.
```

## Try it yourself

1. Add a third argument to `FileLogger` in both files - say `private string $dateFormat`. Count how many classes you had to edit in each version.
2. Break the assertion in `good.php` on purpose, say by expecting `'sent 2 newsletters'`. The script prints `[FAIL]` and exits non-zero, which is all a CI job needs.
3. Add a second assertion to `good.php` checking that nothing was logged when the recipient list is empty. In `bad.php`, write the same test and notice you are back to reading a file.
4. Rename the `Logger` interface to `ILogger` in `good.php` and follow the compiler errors. That is the cost you would pay on every rename, forever.

## Further reading

- [Passing Dependencies](https://doc.nette.org/dependency-injection/passing-dependencies)
- [What is Dependency Injection?](https://doc.nette.org/dependency-injection/introduction)
