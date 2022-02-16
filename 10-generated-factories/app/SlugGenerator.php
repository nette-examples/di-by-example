<?php declare(strict_types=1);

class SlugGenerator
{
	public function generate(string $title): string
	{
		return strtolower(str_replace(' ', '-', $title));
	}
}
