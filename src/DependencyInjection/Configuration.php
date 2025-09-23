<?php

declare(strict_types = 1);

namespace Consistence\Sentry\SymfonyBundle\DependencyInjection;

use Consistence\Sentry\SymfonyBundle\Annotation\Add;
use Consistence\Sentry\SymfonyBundle\Annotation\Contains;
use Consistence\Sentry\SymfonyBundle\Annotation\Get;
use Consistence\Sentry\SymfonyBundle\Annotation\Remove;
use Consistence\Sentry\SymfonyBundle\Annotation\Set;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

class Configuration implements \Symfony\Component\Config\Definition\ConfigurationInterface
{

	public const DEFAULT_GENERATED_FILES_DIR_NAME = 'sentry';

	public const PARAMETER_GENERATED_FILES_DIR = 'generated_files_dir';
	public const PARAMETER_METHOD_ANNOTATIONS_MAP = 'method_annotations_map';

	/** @var string */
	private $rootNode;

	/** @var string */
	private $kernelCacheDir;

	public function __construct(
		string $rootNode,
		string $kernelCacheDir
	)
	{
		$this->rootNode = $rootNode;
		$this->kernelCacheDir = $kernelCacheDir;
	}

	public function getConfigTreeBuilder(): TreeBuilder
	{
		$treeBuilder = new TreeBuilder($this->rootNode);
		$rootNode = $treeBuilder->getRootNode();

		$rootNode
			->children()
				->scalarNode(self::PARAMETER_GENERATED_FILES_DIR)
					->defaultValue($this->normalizePathIfPossible(
						$this->kernelCacheDir . '/' . self::DEFAULT_GENERATED_FILES_DIR_NAME
					))
					->beforeNormalization()
						->ifString()
						->then(function (string $dir): string {
							return $this->normalizePathIfPossible($dir);
						})
						->end()
					->end()
				->arrayNode(self::PARAMETER_METHOD_ANNOTATIONS_MAP)
					->defaultValue([
						Add::class => 'add',
						Contains::class => 'contains',
						Get::class => 'get',
						Remove::class => 'remove',
						Set::class => 'set',
					])
					->prototype('scalar')
						->end()
					->end()
				->end()
			->end();

		return $treeBuilder;
	}

	private function normalizePathIfPossible(string $path): string
	{
		$realPath = realpath($path);
		if ($realPath === false) {
			return $path;
		}

		return $realPath;
	}

}
