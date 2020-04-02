<?php

/**
 * Display the current version of Arcanist.
 */
final class ArcanistVersionWorkflow extends ArcanistWorkflow {

  public function supportsToolset(ArcanistToolset $toolset) {
    return true;
  }

  public function getWorkflowName() {
    return 'version';
  }

  public function getWorkflowInformation() {
    // TOOLSETS: Expand this help.

    $help = pht(<<<EOTEXT
Shows the current version.
EOTEXT
);

    return $this->newWorkflowInformation()
      ->setSynopsis(pht('Show toolset version information.'))
      ->addExample(pht('**version**'))
      ->setHelp($help);
  }

  public function getWorkflowArguments() {
    return array();
  }

  public function runWorkflow() {
    // TOOLSETS: Show the toolset version, not just the "arc" version.

    $console = PhutilConsole::getConsole();

    if (!Filesystem::binaryExists('git')) {
      throw new ArcanistUsageException(
        pht(
          'Cannot display current version without "%s" installed.',
          'git'));
    }

    $roots = array(
      'arcanist' => dirname(phutil_get_library_root('arcanist')),
    );

    foreach ($roots as $lib => $root) {
      $is_git = false;

      $working_copy = ArcanistWorkingCopy::newFromWorkingDirectory($root);
      if ($working_copy) {
        $repository_api = $working_copy->newRepositoryAPI();
        if ($repository_api instanceof ArcanistGitAPI) {
          $is_git = true;
        }
      }

      if (!$is_git) {
        throw new PhutilArgumentUsageException(
          pht(
            'Library "%s" (at "%s") is not a Git working copy, so no version '.
            'information can be provided.',
            $lib,
            Filesystem::readablePath($root)));
      }

      // NOTE: Carefully execute these commands in a way that works on Windows
      // until T8298 is properly fixed. See PHI52.

      list($commit) = $repository_api->execxLocal('log -1 --format=%%H');
      $commit = trim($commit);

      list($timestamp) = $repository_api->execxLocal('log -1 --format=%%ct');
      $timestamp = trim($timestamp);

      $console->writeOut(
        "%s %s (%s)\n",
        $lib,
        $commit,
        date('j M Y', (int)$timestamp));
    }
  }

}
