<?php declare(strict_types=1);

/**
 * No implementation, and none needed: the container generates one.
 * The $authorId argument is passed straight to Article's constructor.
 */
interface ArticleFactory
{
	function create(int $authorId): Article;
}
