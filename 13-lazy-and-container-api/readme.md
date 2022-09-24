# 13 · Lazy Services and the Container API

Services that are not created until somebody actually uses them, and the handful of container methods worth knowing.

**You will learn:**

- what `lazy: true` does and exactly when a lazy service wakes up
- what it costs you
- the container methods beyond `getByType()`

## Run it

```shell
php 13-lazy-and-container-api/example.php
```

## Lazy services

```neon
di:
	lazy: true
```

With that on, requesting a service gives you a **proxy**: an object of the right class, with the right type, that has not been constructed yet. The real constructor runs the first time somebody actually uses it.

Watch the output. `Database` reports its connection in the constructor, and that line does not appear when the container is built, nor when `ReportGenerator` is fetched. It appears in the middle of the work, exactly when the query needs it.

Why bother? Because a request typically uses a fraction of the services it could. Without lazy loading, a controller that receives five dependencies builds all five - opening a connection, reading a config file, spinning up a mailer - to then use two of them. Lazy loading turns that into "pay for what you touch", which on a big application is a measurable chunk of the request.

### When exactly does it wake up?

This surprised me, and the example demonstrates it deliberately. A lazy object initialises on the first **property access**, not on the first method call:

```php
$db->ping();    // reads no property -> proxy stays asleep, no connection
$db->query(…);  // reads $this->dsn -> constructor runs now
```

Both lines call a method on the same object, and only the second one causes construction. That is how PHP 8.4's lazy objects work under the hood, and it also explains a detail in the output: `isCreated()` says `yes` after `ping()`, because the *proxy* was created - the constructor still had not run.

So "is the service created" and "has the service been initialised" are two different questions once you turn this on.

### The price

- **Errors arrive later.** A wrong database password used to blow up on startup. Now it blows up on the first query, which may be a different code path and a less convenient moment.
- **PHP 8.4 or newer**, and only for services created by instantiating a class. A service produced by a factory method cannot be a lazy proxy, and neither can a class extending an internal PHP class. When it cannot apply, the flag is quietly ignored.
- **Circular dependencies stop erroring.** A needs B, B needs A: with proxies, each gets a stand-in and it works. Convenient, and also a bit of a shame - the error was telling you something true about your design. Fix the cycle rather than enjoying that it went away.

You can flip the flag per service with `lazy: false` in its definition, which is what you want for anything used on every request anyway.

## The container API

`getByType()` covers most days. The rest, in the order I actually use them.

First a small thing that matters: some of these take a service *name*, so this chapter's configuration gives the database one (`database: Database(...)`) instead of leaving it to the bullet list. An unnamed service still gets a name, but it is a generated ordinal like `01`, and it shifts the day you add a service above it. Name whatever you intend to ask about later.

- **`isCreated(string $name)`** - has this service been instantiated yet? Mostly a debugging and teaching tool, as above.
- **`hasService(string $name)`** - is it defined at all?
- **`getService(string $name)`** - the service under that name. It can only promise you an `object`, because a name carries no type, which is precisely why `getByType()` is the one to reach for.
- **`getParameters()`** and **`getParameter($key)`** - the values from the `parameters` section. Here it prints `[]` for the least mysterious of reasons: this chapter's `config.neon` has no `parameters` section. Add one and they show up.
- **`findByTag(string $tag)`** - names and values of everything carrying a tag, as in the previous chapter.
- **`createInstance(string $class)`** - build a class the container does not manage and fill its constructor from the container. The example does this with `CsvExporter`, which appears in no configuration file and still gets its database and logger. Handy at the edges of a system: a class discovered at runtime, a command named on the CLI.
- **`callMethod(callable $fn)`** - call any callable and autowire its arguments.

One warning that is worth repeating here: none of these belong inside your services. A class that receives the container and asks it for things is a service locator, and everything chapter 06 said about that still applies. These methods are for the bootstrap layer - the place that already knows the container exists.

## Output

```
Container built. Nothing has connected to anything yet.
Got a ReportGenerator. Does the database service exist yet? no - the generator is a sleeping proxy and has not asked for one

Calling ping(), which reads no property:
[Database] ping
isCreated() now says yes - the proxy exists, but its constructor has not run.

Now doing actual work:
[Database] connected to mysql:host=127.0.0.1;dbname=blog as admin
[Database] SELECT count(*) FROM articles on mysql:host=127.0.0.1;dbname=blog
[log] monthly report generated

And now? created

hasService('database'): true
getParameters(): []

createInstance() builds an unregistered class with its dependencies:
[Database] SELECT * FROM articles on mysql:host=127.0.0.1;dbname=blog
[log] exported to CSV
```

## Try it yourself

1. Set `lazy: false` in `config.neon` and run again. The connection line jumps to the top - everything is built up front.
2. Add a second service that nobody uses. With lazy on, it costs nothing; with it off, it is built anyway.
3. Add a `parameters` section to `config.neon` and print `getParameters()` again - the empty array fills up.

## Further reading

- [Lazy Services](https://doc.nette.org/dependency-injection/services)
- [Working with the Container](https://doc.nette.org/dependency-injection/nette-container)
