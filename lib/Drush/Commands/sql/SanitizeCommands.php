<?php

namespace Drush\Commands\sql;

use Consolidation\AnnotatedCommand\Events\CustomEventAwareInterface;
use Consolidation\AnnotatedCommand\Events\CustomEventAwareTrait;
use Drush\Commands\DrushCommands;
use Drush\Sql\SqlTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SanitizeCommands extends DrushCommands implements CustomEventAwareInterface {

  use CustomEventAwareTrait;

  /**
   * Sanitize the database by removing or obfuscating user data.
   *
   * Commandfiles may add custom operations by implementing:
   * - @hook_on-event sql-sanitize-message
   *     Display summary to user before confirmation.
   * - @hook post-command sql-sanitize
   *     Run queries or call APIs to perform sanitizing
   *
   * @command sql-sanitize
   *
   * @bootstrap DRUSH_BOOTSTRAP_DRUPAL_CONFIGURATION
   * @description Run sanitization operations on the current database.
   * @option db-prefix Enable replacement of braces in sanitize queries.
   * @option db-url A Drupal 6 style database URL. E.g.,
   *   mysql://root:pass@127.0.0.1/db
   * @option sanitize-email The pattern for test email addresses in the
   *   sanitization operation, or "no" to keep email addresses unchanged. May
   *   contain replacement patterns %uid, %mail or %name.
   * @option sanitize-password The password to assign to all accounts in the
   *   sanitization operation, or "no" to keep passwords unchanged.
   * @option whitelist-fields A comma delimited list of fields exempt from sanitization.
   * @aliases sqlsan
   * @usage drush sql-sanitize --sanitize-password=no
   *   Sanitize database without modifying any passwords.
   * @usage drush sql-sanitize --whitelist-fields=field_biography,field_phone_number
   *   Sanitizes database but exempts two user fields from modification.
   */
  public function sanitize($options = ['db-prefix' => FALSE, 'db-url' => '', 'sanitize-email' => 'user+%uid@localhost.localdomain', 'sanitize-password' => 'password', 'whitelist-fields' => '']) {
    /**
     * In order to present only one prompt, collect all confirmations from
     * commandfiles and present at once. Hook implementations should change
     * $messages by reference. In order to actually sanitize, implement
     * a method with annotation: @hook post-command sql-sanitize. For example,
     * \Drush\Commands\sql\SanitizeSessionsCommands::sanitize
     */
    $messages = [];
    $handlers = $this->getCustomEventHandlers('sql-sanitize-confirms');
    foreach ($handlers as $handler) {
      $handler($messages, $this->input());
    }
    if (!empty($messages)) {
      drush_print(dt('The following operations will be performed:'));
      foreach ($messages as $message) {
        drush_print('* '. $message);
      }
    }
    if (!drush_confirm(dt('Do you really want to sanitize the current database?'))) {
      return drush_user_abort();
    }

    // All sanitizing operations defined in post-command hooks, including Drush
    // core sanitizing routines.
  }
}

