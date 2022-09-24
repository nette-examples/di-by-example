# 14 · Writing a Compiler Extension

Adding your own section to the configuration, and doing work while the container is being built rather than while it runs.

**You will learn:**

- why a configuration file cannot do this on its own
- what a `CompilerExtension` is and which methods matter
- how to declare and validate your own configuration section
- how to wire services together at compile time
- the vocabulary: compiler, container builder, definition

## Run it

```shell
php 14-compiler-extension/example.php
```

## Configuration cannot decide anything

Chapter 12 wired the dispatcher in one line - `EventDispatcher(tagged(subscriber))` - and there is nothing wrong with that line. It stops being enough the moment the wiring is not yours to write.

Suppose this event system is a package: your own library, or a module shared by several projects at work. Every project that installs it has to copy the same block into its `services:` and keep it right forever. Nothing checks that they did, nothing tells them when the package changes its mind, and the block carries no information - it is the same three lines every time, retyped by hand.

And there are things a configuration file cannot express at all, however carefully you write it:

- **A decision.** "Register the dispatcher only if events are switched on." NEON has no `if`. A configuration file states facts; it does not branch.
- **A reaction to the environment.** "Add the Tracy panel only in debug mode." "Register the console command only if Symfony Console happens to be installed."
- **A section of its own, validated.** Where would a package even put its options? Everything under `services:` is a service, so a mistyped option there fails as a missing class rather than as a mistyped option.

All three want the same thing: code that runs while the container is being built. That is a compiler extension, and Nette itself is assembled from them - `application`, `http`, `database`, `latte` and the rest each contribute their section and their services exactly this way.

With the extension from this chapter installed, everything a project has to write is:

```neon
events:
	tag: subscriber
```

The wiring is no longer something anyone can forget.

## The extension

```php
class EventsConfig
{
	public string $tag = 'subscriber';
	public bool $enabled = true;
}

class EventsExtension extends CompilerExtension
{
	public function getConfigSchema(): Schema
	{
		return Expect::from(new EventsConfig);
	}

	public function loadConfiguration(): void
	{
		$this->getContainerBuilder()
			->addDefinition($this->prefix('dispatcher'))
			->setFactory(EventDispatcher::class);
	}

	public function beforeCompile(): void
	{
		$builder = $this->getContainerBuilder();
		$handlers = array_keys($builder->findByTag($this->getEventsConfig()->tag));
		$refs = array_map(fn(string $name) => '@' . $name, $handlers);

		$definition = $builder->getDefinition($this->prefix('dispatcher'));
		if ($definition instanceof ServiceDefinition) {
			$definition->setArgument('handlers', $refs);
		}
	}
}
```

Three methods, three distinct jobs.

**`getConfigSchema()`** describes the section your extension owns. Nette Schema validates it, so a user who writes `tags:` instead of `tag:` gets a clear error naming the offending key, and a user who writes nothing gets the defaults. Free input validation for a config file - worth it for the error messages alone.

Note how the schema is declared: `Expect::from(new EventsConfig)` derives it from a plain class with typed properties and default values. You could spell the rules out with `Expect::structure(['tag' => Expect::string('subscriber')])` instead, and for a one-off that is fine - but deriving it from a class means the configuration has a *type* everywhere it is used afterwards. Your IDE completes it, PHPStan checks it, and the same argument this course has been making about constructors applies to config: a shape a tool can see beats a shape only a human can.

**`loadConfiguration()`** runs early and adds definitions. `$this->prefix('dispatcher')` names the service `events.dispatcher`, keeping it out of everyone else's namespace.

**`beforeCompile()`** runs after *every* extension has loaded its configuration. That timing is the whole point: only now is the list of services complete, so only now can you ask "who is tagged as a subscriber?" and get the real answer. This is the standard place for collect-and-wire work.

Note also what we are manipulating. `setArgument('handlers', $refs)` does not create a dispatcher and hand it objects - it *edits the recipe* that will be compiled into the container class. Nothing exists yet. You are writing code that writes code.

## The vocabulary

The words that keep coming up, in one place:

- **Compiler** - the machinery that turns configuration and extensions into a container class.
- **ContainerBuilder** - the mutable model used during compilation. It holds definitions, not services.
- **Definition** - the recipe for one service: its type, how to create it, what to call afterwards.
- **Service** - the object that exists at runtime, once the container is built.
- **Tag** - a label on a definition, which is exactly how `findByTag()` above works.
- **Extension** - a class like this one, owning a config section and contributing definitions.

The distinction between *definition* and *service* is the one to hold on to. Extensions only ever touch definitions.

## The output tells you when it ran

Run the example after deleting `temp/` and the extension's own line appears **before** anything else - it happened during compilation. Run it again without deleting: the line is gone, because there was nothing to compile; the container was loaded from cache.

That is the deal with compiled containers, and it is why they are fast. All this reflection, tag scanning and argument wiring happens once, on the first run after a change, and never again. On a production request, the "dependency injection framework" is a `require` of a plain PHP file.

## Output

```
The container is built. Dispatching:
[log] 2 handlers for user.registered
[SendWelcomeEmail] handling user.registered
[UpdateStatistics] handling user.registered
```

## Try it yourself

1. Delete `temp/` and run twice, watching the `[EventsExtension]` line appear and then not.
2. Set `enabled: false` in the `events` section. The dispatcher is never registered: `Service of type EventDispatcher not found. Did you add it to configuration file?` That decision is the one a config file could not have made.
3. Write `tagz: subscriber` instead of `tag:` and read the error: `Unexpected item 'events › tagz', did you mean 'tag'?` That is what `Expect::from(new EventsConfig)` bought you.
4. Add a third handler with the tag and confirm the extension picks it up with no configuration change.

## Where to go next

You have now seen the whole arc: three rules, a container written by hand, the same container generated, and the machinery that generates it. What is left is mostly breadth - the [extensions documentation](https://doc.nette.org/dependency-injection/extensions) covers the remaining hooks, and [compilation internals](https://doc.nette.org/dependency-injection/compilation-internals) goes under the floorboards if you are curious.

But honestly, the important part was chapters 01 to 06. Everything since has been convenience; the rules are the thing that changed how I write code.

## Further reading

- [Creating Extensions](https://doc.nette.org/dependency-injection/extensions)
- [Compilation Internals](https://doc.nette.org/dependency-injection/compilation-internals)
- [Nette Schema](https://doc.nette.org/schema)
