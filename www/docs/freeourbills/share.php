<?php

# Shared function for FreeOurBills

function signup_form() {
    ?>
<form class="free_our_bills_signup" method="post" action="subscribe">
<input type="hidden" name="posted" value="1">
<p><strong>This campaign can only succeed if normal internet users like you lend a hand.</strong>
Please sign up and we'll send you easy tasks (like emailing your MP, or coming up with some ideas). Together we can improve Parliament!
</p>

<p><label for="email">Your email:</label>
<input type="text" name="email" id="email" value="<?=_htmlspecialchars(get_http_var('email'))?>" size="30">
<br><label for="postcode">Your postcode:</label>
<input type="text" name="postcode" id="postcode" value="<?=_htmlspecialchars(get_http_var('postcode'))?>" size="10">
&nbsp; <input type="submit" class="submit" value="Join up">
</p>
</form>

<?php
}

function freeourbills_styles() {
    ?>
<style type="text/css">
div.main p { margin-left: 3em; }
div.main ul { margin-left: 3em;  }
h2 { text-align: center; }
div#intro.block h3 { margin-left: 0em; }
/* div.block div.blockbody p { margin-left: 3em; } */
label { width: 9em; float: left; }
p#free_our_bills_banner {
    width:50%; margin:0 auto; text-align: center;
    font-style: italic;
}
ul.free_our_bill_reasons {
    list-style-type: disc;
}

p#fob_help {
    font-weight: bold;
    background: #C00;
    color: #fff;
    padding: 0.5em;
    margin-bottom: 0;
    margin-left: 0;
}

div.main .free_our_bills_signup p {
    margin-left: 0;
}
.free_our_bills_signup {
    display: table;
    margin-left: 3em;
    margin-right: 1em;
    margin-bottom: 1em;
    background-color: #b3daff;
    border: solid 2px #c06db3;
    padding: 8px;
}
#warning {
    margin-left: 2em;
    margin-right: 2em;
    margin-top: 1em;
    margin-bottom: 1em;
}
.free_our_bills_confirm {
    text-align: center;
    font-size: 150%;
}
h1.free_our_bills_confirm {
    text-align: center;
    font-size: 200%;
}
h2.free_our_bills_confirm {
    text-align: center;
    font-size: 200%;
}
.free_our_bills_thanks {
    text-align: center;
    font-size: 175%;
    font-weight: bold;
}

</style>
<?php
}
