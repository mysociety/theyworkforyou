<hr>
<h3><?= gettext('Email alerts') ?></h3>

<?php $total_alerts = isset($alerts) ? count($alerts) : 0; ?>
<p><?= sprintf(gettext('You currently have %d email alerts'), $total_alerts) ?></p>

<a href="/alert/" class="button radius">Check here to see your alerts</a>

<form action="<?= $actionurl ?>" method="POST">
    <!-- No need to reference $alert['token'] here, as you're deleting all alerts -->
    <input type="submit" class="button button--negative small js-confirm-delete" name="action" value="<?= gettext('Delete all alerts') ?>" aria-label="Delete all keywords and representatives alerts">
</form>
