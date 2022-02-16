# 09 · Configuration

Parameters, arguments, setup calls, one file overriding another - and an honest answer to "why NEON and not YAML?".

**You will learn:**

- how to pull repeated values out into `parameters` and use them as `%name%`
- why a parameter is frozen when the container compiles, and what to do about it
- how to write constructor arguments and `setup` calls
- how one config file can include another and override a service in it
- why the configuration format is a NEON file and what that buys you

## Run it

```shell
php 09-configuration/example.php
php 09-configuration/test.php
```

## Parameters

Values you would otherwise repeat, or want to be able to find in one place, go up top:

```neon
parameters:
	logFile: %tempDir%/app.log
	smtp:
		host: smtp.example.com
		port: 465
```

and comes back as `%logFile%` or `%smtp.host%`. Nested keys use a dot. Parameters can also be injected from PHP, which is how `%tempDir%` gets its value in `example.php`:

```php
$compiler->addConfig(['parameters' => ['tempDir' => __DIR__ . '/temp']]);
```

One thing to be clear about: parameters are for *the configuration*, not for your classes. There is no global settings object your class can query - if a class needs a value, it gets it through its constructor. That is Rule #1, and the container is not an exception to it.

## Parameters are frozen when the container compiles

This one follows from the previous chapter and still catches people, me included. Open the generated container from chapter 07 and look at what became of the `%dsn%` parameter:

```php
return new Database('mysql:host=127.0.0.1;dbname=blog', 'admin');
```

A string literal. The parameter was evaluated once, at compile time, and *baked into the code*. Since the container is then cached and reused, that value is what every subsequent request sees.

Which means a parameter is the wrong place for anything that differs between machines. Write `dbPassword: ::getenv('DB_PASSWORD')` and you have not written "read the environment variable" - you have written "read it once, on whichever machine compiled the container, and hard-code the answer". The mystery that follows ("why does production use the staging password?") is unpleasant to debug because the configuration file looks entirely correct.

Two honest ways to handle a value that genuinely varies:

- **Hand it in when you build the container.** That is `addConfig()` above, and the container constructor takes an array too: `new $class(['dbPassword' => getenv('DB_PASSWORD')])`. The value is read on every request, by code you can see.
- **Declare it dynamic** with `$compiler->setDynamicParameterNames(['dbPassword'])`, which compiles the parameter into a runtime lookup instead of a constant.

In a full Nette application `Bootstrap` handles the first for you. Standalone, it is one more line, and it is worth knowing which one you are getting.

## Arguments and setup

```neon
services:
	mailer: SmtpMailer(%smtp.host%, %smtp.port%, secure: true)

	logger:
		create: FileLogger(%logFile%)
		setup:
			- setPrefix('[newsletter]')

	distributor: NewsletterDistributor(@mailer, @logger)
```

Three things happening here:

- **Arguments** are written as a call, positionally or by name (`secure: true`). Named arguments in configuration, same as in PHP - so you can skip the ones you do not care about.
- **`setup`** lists what happens right after construction: method calls, or property assignments like `- $timeout = 30`. This is where setter injection from chapter 04 lives.
- **`@mailer`** references a service by name; `@Mailer` would reference one by type. Here the names are explicit because we wanted to be, not because we had to - autowiring would have found both.

Run it and the output narrates the whole lifecycle: mailer built, logger built, prefix set, then the actual work.

## One config including another

A configuration file can pull in another one and then change its mind about something. That is all of `test.neon`:

```neon
includes:
	- config.neon

services:
	mailer: FakeMailer
```

Run `test.php` and compare it with `example.php`. The application is wired exactly as usual - same logger, same setup call, same distributor, same parameters - except the mailer is a double that records recipients instead of sending to them. Redefining a name replaces the whole definition it found, arguments included, and the file doing the including has the last word over the file it includes.

The assertion at the end of `test.php` then reads the double, and notice there is no `[SmtpMailer]` line in the output at all. `NewsletterDistributor` was neither modified nor consulted. This is the container-level version of the fake logger you passed by hand in chapter 02, and it is how one application runs in several shapes: the real configuration, plus a small override for tests, for a developer machine, for production.

One practical note, since the chapter ships two configurations: both compile into the same `temp/`, so `test.php` passes a key as the second argument to `load()` to keep the two cached containers apart.

## Why NEON and not YAML

Every time I show this to someone coming from Symfony, this is the question, and it deserves better than "because Nette".

The core of it is a single feature: NEON understands **entities** - a class name with arguments, written as one expression.

```neon
mailer: SmtpMailer(smtp.example.com, 465, secure: true)
```

YAML has no such concept. It knows scalars, sequences and mappings, so the same thing has to be described *about* rather than written:

```yaml
services:
    mailer:
        class: SmtpMailer
        arguments: ['smtp.example.com', 465, true]
```

One line against four, and that is YAML at its most compact - written out one argument per line, the way service definitions usually end up, it is six or seven. I have maintained a Symfony project where the definitions ran to several hundred lines and a real share of that was `class:` and `arguments:` keys: structure describing structure. It is not that YAML is bad. It was designed for data, and service wiring is not data, it is calls.

The gap widens as soon as you want anything expressive. Referring to a service by type (`@Nette\Database\Connection`), chaining (`@routerFactory::create()`), calling a function (`::getenv('DB_USER')`) - in NEON these are one-liners because the format was built for this job.

A few practical things too, which sound small until they bite you:

- **Tabs are allowed.** YAML forbids them, which is a special kind of cruel in a PHP project where everything else is tab-indented. Paste a snippet, get a parse error, spend four minutes finding an invisible character.
- **Quotes are almost always optional**, including inside entities.
- **NEON is a superset of JSON**, so valid JSON is valid NEON.
- **There is a linter.** `neon-lint` checks your files, which means your CI catches a broken config, and so does an agent working on your project - it does not have to run the app to find out it typed something wrong.
- **The wiring ends up inspectable.** The compiled container is plain PHP holding the whole service graph, so a tool - your IDE, a script, an MCP server pointed at the project - can answer "what services exist and what depends on what" by reading it, rather than by inferring it from configuration files.

And a related point that took me years to notice: because the wiring is expressed in *types*, renaming a class is a refactor your IDE can do. In string-keyed configuration, a rename is a search across files and a prayer. Same reason PHPStan can check your definitions and an AI agent can read your project's structure without guessing - there is a real type on both ends of every connection.

**And yet: the format is a choice, not a religion.** Configuration can be a PHP file that returns an array - the `includes` section loads those happily - and if you prefer something else, a custom loader is not a big piece of work. Nette DI cares that the container is described; it does not much care in what.

If you want to poke at NEON without a project around it, there is an [interactive playground](https://fiddle.nette.org/neon/). PhpStorm has several plugins for it, so you get highlighting and completion rather than a wall of grey text.

## There is more, and you can look it up later

The expression language goes further than this chapter needs: creating objects inline (`DateTime()`), class constants, first-class callables (`@user::logout(...)`), type casts (`int(...)`), collecting services (`typed(Bar)`, `tagged(logger)`). You do not need any of it today, and when you do, the [documentation](https://doc.nette.org/dependency-injection/services) has it in one page.

## Output

### example.php

```
[SmtpMailer] using smtps://smtp.example.com:465
[FileLogger] writing to app.log
[FileLogger] prefix set to [newsletter]
[SmtpMailer] to alice@example.com: Our monthly newsletter
[SmtpMailer] to bob@example.com: Our monthly newsletter
[FileLogger] [newsletter] sent 2 newsletters
```

### test.php

```
[FileLogger] writing to app.log
[FileLogger] prefix set to [newsletter]
[FileLogger] [newsletter] sent 2 newsletters
[PASS] the fake mailer received both recipients

No [SmtpMailer] line above: the class under test never knew.
```

## Try it yourself

1. Change `secure: true` to `secure: false`, then remove the `setup` block: the scheme in the first line becomes `smtp://` and log messages lose their `[newsletter]` tag.
2. Delete `(@mailer, @logger)` from the distributor definition entirely. It still works - autowiring matches by type, and there is exactly one candidate for each.
3. Add `logger: FakeLogger` to `test.neon` and write the class, so the test stops writing to `temp/app.log` as well.
4. Break a file on purpose (delete a closing bracket) and run `composer lint-config`. That is the check your CI should be running.

## Further reading

- [Service Definitions](https://doc.nette.org/dependency-injection/services)
- [Configuring the DI Container](https://doc.nette.org/dependency-injection/configuration)
- [NEON format](https://doc.nette.org/neon) · [try it online](https://fiddle.nette.org/neon/)
