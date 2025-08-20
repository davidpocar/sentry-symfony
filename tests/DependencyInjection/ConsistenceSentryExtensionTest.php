<?php

declare(strict_types = 1);

namespace Consistence\Sentry\SymfonyBundle\DependencyInjection;

use Consistence\Sentry\SymfonyBundle\Annotation\Add;
use Consistence\Sentry\SymfonyBundle\Annotation\Contains;
use Consistence\Sentry\SymfonyBundle\Annotation\Get;
use Consistence\Sentry\SymfonyBundle\Annotation\Remove;
use Consistence\Sentry\SymfonyBundle\Annotation\Set;
use Generator;
use PHPUnit\Framework\Assert;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

class ConsistenceSentryExtensionTest extends \PHPUnit\Framework\TestCase
{

	private function getTestsDir(): string
	{
		return realpath(__DIR__ . '/..');
	}

	private function getTempDir(): string
	{
		return $this->getTestsDir() . '/temp';
	}

	private function getRootDir(): string
	{
		return $this->getTestsDir();
	}

	private function getCacheDir(): string
	{
		return $this->getTempDir();
	}

	/**
	 * @return mixed[][]|\Generator
	 */
	public function configureContainerParameterDataProvider(): Generator
	{
		yield 'default generated.target_dir' => [
			'configuration' => [],
			'parameterName' => ConsistenceSentryExtension::CONTAINER_PARAMETER_GENERATED_TARGET_DIR,
			'expectedParameterValue' => $this->getCacheDir() . '/sentry',
		];

		yield 'default generated.class_map_target_file' => [
			'configuration' => [],
			'parameterName' => ConsistenceSentryExtension::CONTAINER_PARAMETER_GENERATED_CLASS_MAP_TARGET_FILE,
			'expectedParameterValue' => $this->getCacheDir() . '/sentry/_classMap.php',
		];

		yield 'default annotation.method_annotations_map' => [
			'configuration' => [],
			'parameterName' => ConsistenceSentryExtension::CONTAINER_PARAMETER_ANNOTATION_METHOD_ANNOTATIONS_MAP,
			'expectedParameterValue' => [
				Add::class => 'add',
				Contains::class => 'contains',
				Get::class => 'get',
				Remove::class => 'remove',
				Set::class => 'set',
			],
		];

		yield 'configure generated.target_dir' => [
			'configuration' => [
				'generated_files_dir' => __DIR__,
			],
			'parameterName' => ConsistenceSentryExtension::CONTAINER_PARAMETER_GENERATED_TARGET_DIR,
			'expectedParameterValue' => realpath(__DIR__),
		];

		yield 'configure annotation.method_annotations_map' => (static function (): array {
			$methodAnnotationsMap = [
				Get::class => 'get',
				Set::class => 'set',
			];

			return [
				'configuration' => [
					'method_annotations_map' => $methodAnnotationsMap,
				],
				'parameterName' => ConsistenceSentryExtension::CONTAINER_PARAMETER_ANNOTATION_METHOD_ANNOTATIONS_MAP,
				'expectedParameterValue' => $methodAnnotationsMap,
			];
		})();
	}

	/**
	 * @dataProvider configureContainerParameterDataProvider
	 *
	 * @param mixed[][]|array $configuration
	 * @param string $parameterName
	 * @param mixed $expectedParameterValue
	 */
	public function testConfigureContainerParameter(
		array $configuration,
		string $parameterName,
		$expectedParameterValue
	): void
	{
		$container = $this->createContainer();
		$container->registerExtension(new ConsistenceSentryExtension());

		$this->load($container, $configuration);

		self::assertContainerHasParameter($container, $parameterName);
		Assert::assertSame($expectedParameterValue, $container->getParameter($parameterName));

		$container->compile();
	}

	public function testConfigureGeneratedFilesDirNonExistingDirectoryCreatesDir(): void
	{
		$container = $this->createContainer();
		$container->registerExtension(new ConsistenceSentryExtension());

		$dir = $this->getTempDir() . '/testConfigureGeneratedFilesDirNonExistingDirectoryCreatesDir';
		@rmdir($dir);
		Assert::assertFileNotExists($dir);

		$this->load(
			$container,
			[
				'generated_files_dir' => $dir,
			]
		);

		self::assertContainerHasParameter($container, ConsistenceSentryExtension::CONTAINER_PARAMETER_GENERATED_TARGET_DIR);
		Assert::assertSame(
			realpath($dir),
			$container->getParameter(ConsistenceSentryExtension::CONTAINER_PARAMETER_GENERATED_TARGET_DIR)
		);

		self::assertContainerHasParameter($container, ConsistenceSentryExtension::CONTAINER_PARAMETER_GENERATED_CLASS_MAP_TARGET_FILE);
		Assert::assertSame(
			realpath($dir) . '/_classMap.php',
			$container->getParameter(ConsistenceSentryExtension::CONTAINER_PARAMETER_GENERATED_CLASS_MAP_TARGET_FILE)
		);

		Assert::assertFileExists($dir);

		$container->compile();
	}

	/**
	 * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
	 * @param mixed[][]|array $configuration
	 */
	private function load(ContainerBuilder $container, array $configuration): void
	{
		foreach ($container->getExtensions() as $extension) {
			$extension->load([$configuration], $container);
		}
	}

	private function createContainer(): ContainerBuilder
	{
		$container = new ContainerBuilder(new ParameterBag([]));
		$container->getCompilerPassConfig()->setOptimizationPasses([]);
		$container->getCompilerPassConfig()->setRemovingPasses([]);
		$container->getCompilerPassConfig()->setAfterRemovingPasses([]);

		$container->setParameter('kernel.root_dir', $this->getRootDir());
		$container->setParameter('kernel.cache_dir', $this->getCacheDir());

		return $container;
	}

	private static function assertContainerHasParameter(ContainerBuilder $container, string $parameterName): void
	{
		Assert::assertTrue(
			$container->hasParameter($parameterName),
			sprintf('Container is missing required parameter `%s`.', $parameterName)
		);
	}

}
