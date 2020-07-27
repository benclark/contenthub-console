<?php

namespace Acquia\Console\ContentHub\Command;

use EclipseGc\CommonConsole\Command\PlatformBootStrapCommandInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ContentHubAuditDepcalc.
 *
 * @package Acquia\Console\ContentHub\Command
 */
class ContentHubAuditDepcalc extends Command implements PlatformBootStrapCommandInterface {

  /**
   * {@inheritDoc}
   */
  protected static $defaultName = 'ach:audit:check-depcalc';

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
    $this->setDescription('Check for depcalc module presence.');
    $this->setAliases(['ach-acd']);
  }

  /**
   * {@inheritDoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    if (!\Drupal::moduleHandler()->moduleExists('depcalc')) {
      $output->writeln('<error>Depcalc module is missing from dependencies! please run `composer require drupal/depcalc`.</error>');
      return 1;
    }
    return 0;
  }

}
