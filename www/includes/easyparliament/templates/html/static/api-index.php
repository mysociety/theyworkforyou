<div class="hero-api">
    <div class="row">
        <div class="hero-api__container">
            <h1>Easy access to parliamentary information </h1>
            <p class="lead">
            for your web project, campaign, research or news story 
            </p>
          <?php if ($subscription) { ?>
            <a href="/api/key" class="button">Manage your keys and payments &rarr;</a>
          <?php } else { ?>
            <a href="/api/key" class="button">Sign up &rarr;</a>
          <?php } ?>
        </div>
    </div>
</div>

<div class="intro">
    <div class="row">
        <div class="intro__header">
            <h2>Do you need to&hellip;</h2>
        </div>
        
        <div class="intro__col">Display tailored content that reflects which constituency a user is in?</div>
        <div class="intro__col">Analyse spoken parliamentary debates, in bulk?</div>
        <div class="intro__col">Publish accurate information about MPs, MLAs or MSPs?</div>
        <div class="intro__col">Find a quick and easy way to integrate parliamentary data in your project?</div>
    </div>
</div>

<div class="try-api">
    <div class="row collapse">
        <form action="/api/docs/getMP?#output">
            <div class="mp-search__form">
                <label for="postcode" class="api-search-label">Try it out - enter a postcode</label>
                <div class="medium-4 columns">
                    <input type="hidden" name="output" value="json">
                    <input name="postcode" id="postcode" class="homepage-search__input" type="text" />
                </div>
                <div class="medium-3 columns">
                    <input type="submit" value="Search &rarr;" class="button homepage-search__button" />
                </div>
            </div>

        </form>
    </div>
</div>

<div class="simple-api">
    <div class="row">
        <div class="simple-api__header text-center">
            <h2>TheyWorkForYou's API makes it all much simpler</h2>
        </div>
    
        <div class="medium-6 columns">
            <i class="simple-api__icon medium-3 small-3 columns"><img src="/style/img/api-page/icon-data.svg" alt=""></i>    
            <p class="medium-9 small-9 columns">An easy way to access data from the UK's parliaments and regional assemblies</p>
        </div>
        <div class="medium-6 columns">
            <i class="simple-api__icon medium-3 small-3 columns"><img src="/style/img/api-page/icon-self-service.svg" alt=""></i>    
            <p class="medium-9 small-9 columns">Self-service subscription: manage keys and payments online</p>    
        
        </div>
    </div>
    <div class="row">
        <div class="medium-6 columns">
            <i class="simple-api__icon medium-3 small-3 columns"><img src="/style/img/api-page/icon-quota.svg" alt=""></i>    
            <p class="medium-9 small-9 columns">Adjust your quota as you need it, or cancel at any time</p>    
        
        </div>
        <div class="medium-6 columns">
            <i class="simple-api__icon medium-3 small-3 columns"><img src="/style/img/api-page/icon-rates.svg" alt=""></i>    
            <p class="medium-9 small-9 columns">Reduced rates/free of charge for non-profit or charitable projects</p>    
        
        </div>
    </div>

    <div class="simple-api__info">
        <div class="row">
            <div class="simple-api__card">
                <div class="simple-api__card-header">
                    <h3>What is an API?</h3>
                </div>
                <div class="simple-api__card-body">
                    <p>API (Application Programming Interface). Is a program that allows you to query a database  — in this case, TheyWorkForYou's vast amount of parliamentary data, updated daily — for information.</p>
                    <p>No more manual searching, spreadsheet formatting or out of date information — let the API bring you the data you need, so you can get straight to work.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="simple-api__cta">
        <div class="row">
            <p>Plans start from £20/mth. <a href="/api/docs/#plans">See plans</a></p>
        </div>
    </div>
    
</div>

<?php include 'api-examples.php'; ?>

<div class="projects">
    <div class="row text-center">
        <h2>TheyWorkForYou's API powers projects like these:</h2>
    </div>

    <div class="row">
        <div class="projects__col">
            <p>British Canoeing's Clear Waters campaign encourages supporters to sign a petition and then displays on a map how many have signed from each constituency.</p>
            <p><a href="https://clearaccessclearwaters.org.uk/">See it in action</a></p>
            <img src="/style/img/api-page/british-canoeing-screenshot.png" alt="">
            <img src="/style/img/api-page/british-canoeing-logo.png" class="projects-logo" alt="">
        </div>
        <div class="projects__col">
            <p>The Centre for Analysis of Risk and Regulation (CARR) analysed parliamentary speeches over 50 years as part of their research into whether there had been a rise in the use of statistics and numbers in political discourse.</p>
            <p><a href="https://www.lse.ac.uk/accounting/assets/Documents/news/carr-report-for-UKSA-final.pdf">Find out more (PDF)</a></p>
            <img src="/style/img/api-page/carr-screenshot.png" alt="">
            <img src="/style/img/api-page/carr-logo.png" class="projects-logo" alt="">
        </div>
        <div class="projects__col">
            <p>Carbon Brief looked into the use of the phrases "climate change", "global warming" and "greenhouse effect" by different political parties as well as individual MPs for this longform analysis.</p>
            <p><a href="https://www.carbonbrief.org/analysis-the-uk-politicians-who-talk-the-most-about-climate-change">Find out more</a></p>
            <img src="/style/img/api-page/CarbonBrief-screenshot.png" alt="">
            <img src="/style/img/api-page/CarbonBrief-logo.png" class="projects-logo" alt="">
        </div>
        <div class="projects__col">
            <p>Money Advice Trust's StopTheKnock campaign used the API as one piece of the code behind this map project, showing the number of times council bailiffs were sent to collect on debts within each area.</p>
            <p><a href="https://www.stoptheknock.org/">See it in action</a></p>
            <img src="/style/img/api-page/stop-the-knock-screenshot.png" alt="">
            <img src="/style/img/api-page/stop-the-knock-logo.png" class="projects-logo" alt="">
        </div>
    </div>
</div>

<div class="quote-block text-center">

    <div class="quote-wrap">
        <p>“The API is a very useful tool that has saved us days of development work. It is the backbone to our online petition process.”</p> 
        <p> <cite>Head of Digital, British Canoeing</cite></p>
    </div>
    
</div>

<div class="get-started text-center">
    <div class="row">
      <?php if ($subscription) { ?>
        <a href="key" class="button">Manage your keys and payments &rarr;</a>
      <?php } else { ?>
        <p>Get started.</p>
        <a href="key" class="button">Sign up</a>
      <?php } ?>
    </div>

    <div class="row">
        <div class="get-started__list-wrap">
            <ul>
                <li><a href="/api/docs">Documentation</a></li>
                <li><a href="/api/terms">Terms and conditions</a></li>
            </ul>
        </div>
    </div>
</div>
