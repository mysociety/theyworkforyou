<!-- In here I'm assuming we are reusing the same text for donation, but 
probably it would be better not to, because we have more space available.-->
<!--Also by having it separate we could active/deactivate one in case we wanted to.-->
<div class="sidebar__unit__donation">
    <!-- Following the earlier comment we could have a more catchy title -->
    <h3 class="content__title">Donate to mySociety</h3>
    <p class="content__description"><?= $random_banner->content ?></p>
    <a class="button content__button" href="<?= $random_banner->button_link ?>"><?= $random_banner->button_text ?></a>
</div>
