<div class="full-page alerts-header">
    <div class="full-page__row">
        <div class="full-page__unit">

            <h1>Track your <?php if ($data['recent_election']): ?>new<?php endif ?> MP&rsquo;s parliamentary activity</h1>
            <p class="lead">Enter your postcode, and we&rsquo;ll email you every time your MP speaks or submits a written question.</p>

            <form class="alerts-form">
                <p>
                    <label for="id_postcode">Your postcode</label>
                    <input type="text" id="id_postcode">
                </p>
                <p>
                    <label for="id_email">Your email address</label>
                    <input type="text" id="id_email">
                </p>
                <p>
                    <button type="submit" class="button radius">Set up alerts</button>
                </p>
            </form>

        </div>
    </div>
</div>
