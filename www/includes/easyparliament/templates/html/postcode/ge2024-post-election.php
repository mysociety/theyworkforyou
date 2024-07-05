

<?php ob_start(); ?>
    <form class="alerts-form" method="post" action="/alert/by-postcode/">
    <input type="hidden" name="add-alert" value="1">
    <input type="hidden" name="postcode" id="id_postcode" value="<?= strtoupper($data["pc"]) ?>">

    <label for="id_email">Your email address</label>
    <input type="text" name="email" id="id_email">

    <button type="submit" class="button radius">Set up alerts</button>
    </form>
<?php $form = ob_get_clean(); ?>

<?php ob_start(); ?>
<style>
    :target {
        background-color: #ffc;
        padding: 0.5em;
    }
    </style>
    <p><a href='#current'>See your current <?php
        if (isset($mcon) || isset($mreg)) echo 'representatives';
        else echo 'MP';
    ?></a></p>
<?php $rep_link = ob_get_clean(); ?>

<?php
$markdown_file = '../../../markdown/post-election.md';
$Parsedown = new \Parsedown();

$text = file_get_contents($markdown_file);
$html = $Parsedown->text($text);

$html = str_replace("{{ form }}", $form, $html);
$html = str_replace("{{ rep_link }}", $rep_link , $html);
echo $html;
?>


