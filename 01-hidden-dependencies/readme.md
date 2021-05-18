# 01 · Hidden Dependencies

A class that pretends it needs nothing, and the two-line change that makes it honest.

**You will learn:**

- how to spot a hidden dependency
- why a constructor is a contract, not paperwork
- **Rule #1: let it be passed to you**
- why explicit dependencies make life easier for PHPStan, your IDE and AI agents

## Run it

```shell
php 01-hidden-dependencies/bad.php
php 01-hidden-dependencies/good.php
```

## The story

Do you remember your first program? Mine added two numbers:

```php
function addition(float $a, float $b): float
{
	return $a + $b;
}

echo addition(23, 1); // 24
```

Nothing to explain. You pass two numbers in, one comes out. Now imagine a colleague shows you this instead:

```php
function addition(): float
```

Addition with no numbers. What does it add? Where does it get them? You have to open the body to find out, and inside you discover `Input::get('a')`. The signature told you nothing true, and now you have a question you did not have before: what is `Input`, who fills it, and what happens if nobody does?

We would never write that function. And then we write classes like it all day.

## The pain

Run `bad.php`. It logs a message with a class that could not look more innocent:

```php
$logger = new Logger;
$logger->log('Newsletter sent to 1,200 subscribers');
```

`new Logger` - no arguments, nothing to configure, nothing to get wrong. Except the output asks you three questions, and you cannot answer any of them without opening the class body:

1. Which file did the message land in?
2. What does `Logger` need in order to work?
3. How do you make a second logger writing somewhere else?

Question three has no answer at all. The path lives in a constant that the class reaches for on its own:

```php
file_put_contents(LOG_DIR . '/app.log', $message . "\n", FILE_APPEND);
```

One log file, forever, for the whole application. Want audit records in a separate file? Rewrite the class. Want to log to a temp file in a test? Redefine a constant and hope nothing else uses it.

And notice what the class did to *you*: it made you read its implementation. That is the real cost. Multiply it by every class in a codebase you did not write, and you have a week of onboarding that should have been an afternoon.

## The fix

We do not need a framework for this. We need a constructor.

```php
class Logger
{
	public function __construct(
		private string $file,
	) {
	}

	public function log(string $message): void
	{
		file_put_contents($this->file, $message . "\n", FILE_APPEND);
	}
}
```

That is the whole change, and it is the most important rule of the entire course:

> **Rule #1: Let it be passed to you.** Everything a class needs must be handed to it. Do not go looking for it in a constant, a global, a static property or a singleton.

The professional name for this is *dependency injection*. It is parameter passing. That is genuinely all it is - and if that sounds anticlimactic, good: the value is not in the technique, it is in what stops happening once you follow it everywhere.

Look at `good.php`: the second logger, the one that was impossible before, is now one line.

```php
$audit = new Logger(__DIR__ . '/temp/audit.log');
```

## Who else reads your code

Explicit dependencies are usually sold as being good for humans, which they are. But you are not the only one reading:

- **PHPStan** sees a `string $file` and can check it. In the `bad.php` version there is nothing to analyse - the dependency lives in a function body, invisible to types.
- **Your IDE** can refactor what it can see. Rename a class that is passed as an argument and every usage follows; rename one that is fetched from a global and you are doing find-and-replace on strings.
- **AI agents** read your project exactly like a new colleague does, only faster and with less patience for archaeology. A constructor that lists what the class needs answers the question immediately. A class that goes hunting for a constant makes the agent guess - and a guess in a code change is a bug with good manners.

I did not appreciate this until I watched an agent fix a bug in an old project of mine in about a minute, because every class it opened told it the truth about itself. The same agent had spent twenty minutes lost in a codebase full of static calls.

## Output

### bad.php

```
Logged something, somewhere.

Now answer these three questions without scrolling up to the class body:
  1. Which file did that message land in?
  2. What does Logger need in order to work at all?
  3. How do you make a second logger that writes audit records elsewhere?

You cannot answer any of them, and question 3 has no answer at all.
```

### good.php

```
Logged to temp/app.log, audited to temp/audit.log.

Same three questions, answered by the signature alone:
  1. The file you passed in.
  2. A path to write to - it says so, right there in the constructor.
  3. `new Logger('...')` again, with a different path. Done, see above.
```

## Try it yourself

1. In `good.php`, create a third logger writing to `temp/debug.log`. Notice that you needed no permission from anybody.
2. Delete the `$file` argument from one `new Logger(...)` call. PHP stops you immediately - the contract is enforced, not documented.
3. In `bad.php`, try to make the audit logger write elsewhere *without touching the Logger class*. Give up when you are convinced.

## Further reading

- [What is Dependency Injection?](https://doc.nette.org/dependency-injection/introduction)
- [Passing Dependencies](https://doc.nette.org/dependency-injection/passing-dependencies)
