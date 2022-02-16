<?php declare(strict_types=1);

/**
 * An accessor: get() returns the shared service, but only when first called.
 * Useful when a dependency is expensive and rarely used.
 */
interface DatabaseAccessor
{
	function get(): Database;
}
