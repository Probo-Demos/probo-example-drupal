<?php

declare(strict_types=1);

namespace Drupal\probo_environment\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Renders the Probo build-environment demo page.
 *
 * This is the Drupal port of the single-file PHP demo (probo_test): it reads
 * the container's process environment, filters it the same way Probo's
 * probo-ubuntu/<image>/files/envvars-swap.sh does at container start, and
 * partitions the result into two tables — documented build variables and
 * everything else Probo injected (organization/project secrets and custom
 * variables).
 */
class ProboEnvironmentController extends ControllerBase {

  /**
   * Documented Probo build variables and their descriptions.
   *
   * Any of these present in the visible environment are shown (with live
   * values) in the first table; the rest are still listed as "not set" so the
   * page documents the full catalog. Description strings contain trusted,
   * static markup and are rendered with |raw in the template.
   */
  protected const KNOWN_DESCRIPTIONS = [
    'PROBO_ENVIRONMENT' => 'Set to <code>TRUE</code> when running inside a Probo build container.',
    'BUILD_ID' => 'Unique identifier for the current build.',
    'BUILD_DOMAIN' => "Domain where this build's site is accessible.",
    'BRANCH_NAME' => 'Name of the git branch being built.',
    'BRANCH_LINK' => 'URL to the branch on the VCS provider (GitHub, Bitbucket, etc.).',
    'COMMIT_REF' => 'Full commit hash/reference being built.',
    'COMMIT_LINK' => 'URL to the commit on the VCS provider.',
    'PULL_REQUEST_NAME' => 'Title of the pull request that triggered this build.',
    'PULL_REQUEST_LINK' => 'URL to the pull request on the VCS provider.',
    'SRC_DIR' => 'Path to the source code directory inside the container.',
    'ASSET_DIR' => 'Path to the assets directory inside the container.',
  ];

  /**
   * Builds the demo page render array.
   *
   * @return array
   *   A render array themed by the probo_environment template.
   */
  public function page(): array {
    $visible = $this->visibleEnvironment();

    // First table: documented build variables, in catalog order.
    $build_vars = [];
    foreach (self::KNOWN_DESCRIPTIONS as $name => $description) {
      $present = array_key_exists($name, $visible);
      $build_vars[] = [
        'name' => $name,
        'value' => $present ? $visible[$name] : '',
        'present' => $present,
        'description' => $description,
      ];
      unset($visible[$name]);
    }

    // Second table: everything else Probo injected — secrets and custom
    // variables. Discovered dynamically; there is no fixed list.
    $other_vars = [];
    foreach ($visible as $name => $value) {
      $other_vars[] = [
        'name' => $name,
        'value' => $value,
      ];
    }

    return [
      '#theme' => 'probo_environment',
      '#in_probo' => getenv('PROBO_ENVIRONMENT') === 'TRUE',
      '#drupal_version' => \Drupal::VERSION,
      '#php_version' => PHP_VERSION,
      '#build_vars' => $build_vars,
      '#other_vars' => $other_vars,
      '#attached' => [
        'library' => ['probo_environment/page'],
      ],
      // The environment is read live per request; never cache the page.
      '#cache' => ['max-age' => 0],
    ];
  }

  /**
   * Returns the environment variables Probo exposes, filtered and sorted.
   *
   * Mirrors the filter applied by envvars-swap.sh at container start:
   *
   * @code
   *   compgen -e | grep -Ev '^APACHE_|^(HOME|LANG|PWD|PATH|OLDPWD|SHLVL|_)$'
   * @endcode
   *
   * @return array
   *   Variable name => value, sorted by name.
   */
  protected function visibleEnvironment(): array {
    $env = getenv();
    if (!is_array($env)) {
      $env = $_ENV ?: [];
    }

    $exact_exclude = ['HOME', 'LANG', 'PWD', 'PATH', 'OLDPWD', 'SHLVL', '_'];

    $visible = [];
    foreach ($env as $name => $value) {
      if (str_starts_with($name, 'APACHE_')) {
        continue;
      }
      if (in_array($name, $exact_exclude, TRUE)) {
        continue;
      }
      $visible[$name] = $value;
    }
    ksort($visible);

    return $visible;
  }

}
