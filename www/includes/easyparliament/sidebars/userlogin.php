<?php
// This sidebar is on the login page.

$this->block_start(array('id'=>'help', 'title'=>gettext("Why do I need to sign in?")));
?>
<p><?= gettext('Signing in allows you to manage your email alerts, and change your password or email address.') ?></p>

<?php
$this->block_end();
?>
