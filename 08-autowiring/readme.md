# 08 · Autowiring

Why the configuration says `- ArticleRepository` and nothing else, and what happens when the container genuinely cannot decide.

**You will learn:**

- how autowiring matches constructor parameters to services by type
- what happens with two services of the same type, and the two ways out
- how to receive *all* services of a type as an array
- how optional dependencies and scalar arguments behave

## Run it

```shell
php 08-autowiring/example.php
php 08-autowiring/conflict.php
```

## Types, not names

`ArticleRepository` needs a database and a logger. The configuration says:

```neon
- ArticleRepository
```

That is the whole definition. Nette reads the constructor, sees `Database $db` and `Logger $logger`, finds the services matching those types and passes them in. It never looks at service names - only at the PHP type system, which also means it knows that `ConsoleLogger` satisfies a parameter typed `Logger`.

The consequence worth internalising: **a service name is just a label.** Rename `mainDb` to `primaryDb` and nothing breaks, because nothing was matching on that string. The connection between "who needs it" and "who provides it" is a type, and types are things tools understand.

It is worth knowing that this was not always the ordinary way to do it. Back when I was writing Symfony YAML, service ids were hand-picked strings sprinkled across the configuration and wiring meant naming them: rename a class and the config went on pointing at a name your IDE had no way to follow. Nette was matching by type already, which is why this course can show you a definition with no arguments in it at all. Symfony has since moved the same way - class names as ids, autowiring on by default - so this is not a scoreboard. I mention it because knowing *why* type-based wiring won makes it much harder to accidentally throw the benefit away later.

That is the same reason PHPStan can check your services and an AI agent can navigate your project: the wiring is expressed in a language that is checkable, not in strings that happen to match.

## When it cannot decide

Autowiring needs exactly one candidate per type. Run `conflict.php`, which registers two `Database` services and asks for one:

```
Service of type ArticleRepository: Multiple services of type Database found: mainDb, tempDb
(required by $db in ArticleRepository::__construct())
```

Read that message again, because two things about it are lovely. It names the two candidates *and* the exact parameter that wanted one. And it happened **at compile time** - while building the container, before any service existed, on any run of the application. This is not the class of bug that waits for the one code path nobody tested.

You have two ways out, and they are in `config.neon`:

```neon
mainDb: Database(%dsn%, %user%)

tempDb:
	create: Database('sqlite::memory:', test)
	autowired: false        # exists, but is never handed out automatically
```

`autowired: false` takes a service out of the running. It still exists and you can still fetch it by name - the example does exactly that at the end - it just stops being a candidate.

The alternative, when both services are legitimately used automatically, is to mark the preferred one with `autowired: Database`, or to point at one explicitly: `ArticleRepository(@mainDb)`.

## An array of everything

`ShipManager` wants every shipper you have:

```php
/** @param  Shipper[]  $shippers */
public function __construct(private array $shippers)
```

PHP has no array-of-type hint, so the phpDoc carries the information - and Nette reads it. Register `DhlShipper` and `PplShipper`, and `ShipManager` receives both. Add a third shipper class tomorrow, register it, and `ShipManager` starts using it without being edited.

This is the standard shape for "collect all the plugins" in a Nette application, and we build on it in the chapter on tags.

## Optional dependencies and scalars

Two rules that save confusion:

- **A parameter with a default value is optional.** `ArticleRepository` accepts `?Cache $cache = null`, and there is no `Cache` service registered - so the default stays and nothing complains. Remove the default and the same missing service becomes a compile-time error.
- **Scalars are never autowired.** Autowiring works on objects (and arrays of them). A `string $dsn` cannot be guessed, which is why `Database` gets its arguments spelled out in the configuration. When a class needs several settings, the tidy move is to wrap them in a small settings object and let *that* be autowired.

## Output

### example.php

```
[Database] connected to mysql:host=127.0.0.1;dbname=blog as admin
[Database] INSERT INTO articles (title) VALUES ('Ten things about dependency injection')
[log] article saved
[DHL] shipping order #42
[PPL] shipping order #42

tempDb is excluded from autowiring, but not from existence:
[Database] connected to sqlite::memory: as test
[Database] SELECT 1
```

Note the first line: `mainDb` was created, `tempDb` was not - services are built when someone asks for them, and until the last line nobody had.

### conflict.php

```
Compilation failed, and here is what it said:

Service of type ArticleRepository: Multiple services of type Database found: mainDb, tempDb (required by $db in ArticleRepository::__construct())

Note when this happened: at compile time, before a single service existed.
```

## Try it yourself

1. Remove `autowired: false` from `tempDb` in `config.neon`. You now get the conflict in the main example too - and the error tells you exactly where.
2. Fix that conflict a different way: give `mainDb` the line `autowired: Database` to make it the preferred one.
3. Register a `Cache` service and run again. `ArticleRepository` picks it up, and the log line changes to say so - the optional dependency filled itself in.
4. Write a third shipper, add one line to the configuration, and watch `ShipManager` ship three times without being touched.

## Further reading

- [Autowiring](https://doc.nette.org/dependency-injection/autowiring)
- [Service Definitions](https://doc.nette.org/dependency-injection/services)
