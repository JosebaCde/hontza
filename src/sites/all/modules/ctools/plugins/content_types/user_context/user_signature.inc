<?php
// $Id: user_signature.inc,v 1.1.2.1 2010/08/05 22:39:41 merlinofchaos Exp $

/**
 * Plugins are described by creating a $plugin array which will be used
 * by the system that includes this file.
 */
$plugin = array(
  'title' => t('User signature'),
  'icon' => 'icon_user.png',
  'description' => t('The signature of a user.'),
  'required context' => new ctools_context_required(t('User'), 'user'),
  'category' => t('User'),
);

function ctools_user_signature_content_type_render($subtype, $conf, $panel_args, $context) {
  $account = isset($context->data) ? drupal_clone($context->data) : NULL;
  $block = new stdClass();
  $block->module = 'term-list';

  if ($account === FALSE || ($account->access == 0 && !user_access('administer users'))) {
    return NULL;
  }

  $block->content = theme('user_signature', $account->signature);

  return $block;
}

/**
 * Display the administrative title for a panel pane in the drag & drop UI
 */
function ctools_user_signature_content_type_admin_title($subtype, $conf, $context) {
  return t('"@s" user signature', array('@s' => $context->identifier));
}

function ctools_user_signature_content_type_edit_form(&$form, &$form_state) {
  // provide a blank form so we have a place to have context setting.
}
