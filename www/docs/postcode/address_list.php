<form>
<input type="hidden" name="pc" value="<?= htmlspecialchars($pc) ?>">

<p>Please pick your address from the list below:

<p>
<?php
foreach ($addresses as $address) {
    print '<label style="font-size:1rem"><input type="radio" name="address" value="' . $address->slug . '"> ';
    print ucwords(strtolower($address->address));
    print '</label>';
}
?>

<p>
<input class="button" type="submit" value="Look up">

</form>
