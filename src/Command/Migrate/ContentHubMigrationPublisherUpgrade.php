<?php

namespace Acquia\Console\ContentHub\Command\Migrate;

use Acquia\Console\ContentHub\Command\ContentHubModuleTrait;
use Acquia\Console\ContentHub\Command\Helpers\PlatformCommandExecutionTrait;
use EclipseGc\CommonConsole\Command\PlatformBootStrapCommandInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ContentHubMigrationPublisherUpgrade.
 *
 * @package Acquia\Console\ContentHub\Command\Migration
 */
class ContentHubMigrationPublisherUpgrade extends Command implements PlatformBootStrapCommandInterface {

  use PlatformCommandExecutionTrait;
  use ContentHubModuleTrait;

  /**
   * {@inheritdoc}
   */
  protected static $defaultName = 'ach:migrate:upgrade';

  /**
   * @inheritDoc
   */
  public function getPlatformBootstrapType(): string {
    return 'drupal8';
  }

  /**
   * {@inheritDoc}
   */
  protected function configure() {
    $this->setDescription('Upgrade sites to 2.x version.')
      ->setAliases(['ach-mu'])
      ->addOption(
        'lift-support',
        'ls',
        InputOption::VALUE_NONE,
        'Enable acquia_lift_publisher module.'
      );
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Exception
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $output->writeln('Initiating module upgrade process...');
    $uri = $input->getOption('uri');
    $this->execDrushWithOutput($output, ['cr'], $uri);
    $this->updateDatabases($input, $output);
    $this->execDrushWithOutput($output, ['cr'], $uri);
    if ($input->getOption('lift-support')) {
      $this->enableAcquiaLiftPublisherModule($output);
    }
    $this->upgradePublishers($output, $uri);
  }

  /**
   * Runs database schema updates.
   *
   * @param \Symfony\Component\Console\Output\OutputInterface $output
   *   The output stream to write to.
   *
   * @throws \Exception
   */
  protected function updateDatabases(InputInterface $input, OutputInterface $output): void {
    $output->writeln('Running database updates...');
    $this->execDrushWithOutput($output, ['updatedb', '-y'], $input->getOption('uri'));
  }

  /**
   * Enables acquia_lift_publisher module.
   *
   * @param \Symfony\Component\Console\Output\OutputInterface $output
   *   The output stream to write to.
   */
  protected function enableAcquiaLiftPublisherModule(OutputInterface $output): void {
    $output->writeln('Installing acquia_lift_publisher module...');
    try {
      \Drupal::service('module_installer')->install(['acquia_lift_publisher']);
    }
    catch (\Exception $e) {
      $output->writeln("<error>Module could not be installed. {$e->getMessage()}</error>");
      return;
    }
  }

  /**
   * Identifies if a site is a publisher and runs the publisher upgrade command.
   *
   * @param \Symfony\Component\Console\Output\OutputInterface $output
   *   The output stream to write to.
   * @param string $uri
   *   The uri of the site.
   *
   * @return int
   *   The return code.
   *
   * @throws \Exception
   */
  protected function upgradePublishers(OutputInterface $output, $uri = ''): int {
    if ($this->isPublisher()) {
      $output->writeln('The site is a publisher, enabling acquia_contenthub_publisher...');
      \Drupal::service('module_installer')->install(['acquia_contenthub_publisher']);
    }

    // It is possible that it was already enabled, therefore we need to make
    // sure if that is the case.
    if (\Drupal::moduleHandler()->moduleExists('acquia_contenthub_publisher')) {
      $output->writeln('Running publisher upgrades...');
      $out = $this->execDrushWithOutput($output, ['ach-publisher-upgrade'], $uri);
      if ($out === 0) {
        $output->writeln('Done');
        return 0;
      }
      return 1;
    }
    return 0;
  }

}
