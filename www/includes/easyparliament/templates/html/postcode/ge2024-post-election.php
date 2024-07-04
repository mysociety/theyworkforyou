

<?php ob_start(); ?>
    <form class="alerts-form" method="post" action="/alert/by-postcode/">
    <input type="hidden" name="add-alert" value="1">
    <input type="hidden" name="postcode" id="id_postcode" value="<?= strtoupper($data["pc"]) ?>">

    <label for="id_email">Your email address</label>
    <input type="text" name="email" id="id_email">

    <button type="submit" class="button radius">Set up alerts</button>
    </form>
<?php $form = ob_get_clean(); ?>


<?php
$markdown_file = '../../../markdown/post-election.md';
$Parsedown = new \Parsedown();

$text = file_get_contents($markdown_file);
$html = $Parsedown->text($text);

$html = str_replace("{{ form }}", $form, $html);

echo $html;
?>


