<form>
<input type="hidden" name="pc" value="<?= htmlspecialchars($pc) ?>">

<p>This postcode is in more than one constituency; please pick your address from the list below:

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
