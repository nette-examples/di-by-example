# 12 · Tags and Decorators

Two ways to talk about a *group* of services instead of one at a time.

**You will learn:**

- how to tag services and collect them with `tagged()`
- how to read tags at runtime with `findByTag()`
- how `decorator` applies setup to every service of a type

## Run it

```shell
php 12-tags-and-decorator/example.php
```

## The plugin problem

Sooner or later an application grows a plugin point. Something happens - a user registers - and several unrelated things should react: send a welcome email, update statistics, notify Slack, whatever next quarter brings.

The naive version is a dispatcher with a hard-coded list, which means every new handler edits a class that has nothing to do with it. Chapter 08 showed the first improvement: ask for `EventHandler[]` and autowiring passes every service of that type.

That works, and it has one limitation - *every* service of the type, no exceptions. Sometimes you want a subset: these handlers for this dispatcher, those for the admin one. That is what tags are for.

## Tags

```neon
services:
	welcome:
		create: SendWelcomeEmail
		tags: [subscriber]

	statistics:
		create: UpdateStatistics
		tags: [subscriber: 10]     # a tag may carry a value

	- EventDispatcher(tagged(subscriber))
```

`tagged(subscriber)` collects every service carrying the tag and passes them as an array. Adding a handler is now: write the class, register it with a tag. The dispatcher is untouched, as it should be - it dispatches, it does not curate.

Tags can carry values, which is how you attach metadata that belongs to the *relationship* rather than the service - a priority, a channel name, an event name. And the container can be asked at runtime:

```php
$container->findByTag('subscriber');   // ['statistics' => 10, 'welcome' => true]
```

## Decorators

A different question: how do you call a method on *every* service of a type, without listing them?

```neon
decorator:
	EventHandler:
		setup:
			- setVerbose(true)
```

Every service that is an `EventHandler` - now and in the future - gets `setVerbose(true)` after being created. The output shows both handlers switching to verbose without either definition mentioning it.

This is genuinely useful for cross-cutting setup: injecting a logger into everything that implements a marker interface, turning on a flag across a family of services, tagging a whole type at once. It is also, like all action at a distance, something to use sparingly - a `setup` call that happens to a service from a file the service does not mention is exactly the kind of thing that surprises the next person. My rule: decorators for infrastructure concerns, never for business behaviour.

## Which one to reach for

- **Autowired array** (`EventHandler[]`) - you want all services of a type. Simplest, most typed, no configuration.
- **Tags** (`tagged(...)`) - you want a named subset, possibly with metadata, possibly spanning several types.
- **Decorator** - you want to *configure* a group rather than collect it.

They compose: `search` can tag everything it registers, and a decorator can add tags to a whole type. That is how a Nette application manages to register dozens of event subscribers with no per-class configuration at all.

## Output

```
[log] 2 handlers for user.registered
[SendWelcomeEmail] handling user.registered verbosely
[UpdateStatistics] handling user.registered verbosely

Services tagged 'subscriber': {"statistics":10,"welcome":true}
```

## Try it yourself

1. Write a third handler, register it with `tags: [subscriber]`, and run again. The dispatcher counts three - and you never opened `EventDispatcher.php`.
2. Remove the tag from `statistics`. It stays in the container, but stops being dispatched.
3. Change the decorator to `setVerbose(false)` and watch both handlers go quiet at once.
4. Sort the handlers by their tag value before dispatching. (Hint: `findByTag()` gives you names and values; the next chapter shows how to fetch a service by name.)

## Further reading

- [Tags](https://doc.nette.org/dependency-injection/services)
- [Decorator](https://doc.nette.org/dependency-injection/configuration)
