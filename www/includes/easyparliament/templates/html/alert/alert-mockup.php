<!-- TODO for me: For this mockup I'm not worrying to much for the hierarchy of the headings -->
<div class="mockup-internal-comment">
  <h4>/alert</h4>
  Initial view for alert page. This assumes the user has already created some group alerts
</div>

<div class="alert-page-header">
  <h2>Keywords alerts</h2>
  <!-- Go to Create alert page -->
  <a class="button" href="/create-alert-page">
    Create new alert
    <i aria-hidden="true" role="img" class="fi-megaphone"></i>
  </a>
</div>

<!-- The groups alerts should be sorted by default from most recent mention to oldest one -->
<!-- Future functionality: The groups alerts can be sorted alphabetically-->

<div class="accordion">
  <div class="accordion-item">
    <button class="accordion-button" href="#accordion-content-1" aria-expanded="false">
      <div class="accordion-button--content">
        <!-- This is the name of the alert group name -->
        <span class="content-title">Mental Health</span>
        <!-- display mentions for the whole group this week. If there are no mention then it doesn't get display -->
        <span class="content-subtitle">30 mentions this week</span>
      </div>
      <i aria-hidden="true" role="img" class="fi-plus"></i>
    </button>
    <div id="accordion-content-1" class="accordion-content" aria-hidden="true" role="img">
      <div class="accordion-content-header">
        <div class="alert-controller-wrapper">
          <button class="button small alert-edit-button">
            <span>Edit alert</span>
            <i aria-hidden="true" class="fi-page-edit"></i>
          </button>
          <button class="button small display-none">Discard changes</button>
          <button class="button small">
            <span>Suspend alert</span>
            <i aria-hidden="true" class="fi-pause"></i>
          </button>
          <button class="button small red">
            <span>Delete alert</span>
            <i aria-hidden="true" class="fi-trash"></i>
          </button>
        </div>
        <dl class="alert-meta-info">
            <!-- display mentions for the whole group this week. If there are no mention then it doesn't get display -->
             <div class="content-header-item">
               <dt>This week</dt>
               <dd>30 mentions</dd>
             </div>
            <!-- Endif -->
  
            <div class="content-header-item">
              <dt>Date of last mention</dt>
              <dd>30 May 2024</dd>
            </div>
  
            <!-- Takes you to the result page of this query -->
            <a href="" class="button small">See results for this alert</a>
          </dl>
      </div>
  
      <hr>
  
      <div class="keyword-list alert-page-subsection">
        <h3 class="heading-with-bold-word">Keywords <span class="bold">included</span> in this alert:</h3>
        <ul>
          <li class="label label--primary-light">Keyword 1 
            <i aria-hidden="true" role="img" class="fi-x"></i></li>
          <li class="label label--primary-light">Keyword 2 
            <i aria-hidden="true" role="img" class="fi-x"></i></li>
          <li class="label label--primary-light">Keyword 3 
            <i aria-hidden="true" role="img" class="fi-x"></i></li>
          <li class="label label--primary-light">Keyword 4 
            <i aria-hidden="true" role="img" class="fi-x"></i></li>
          <li class="label label--primary-light">Keyword 4 
            <i aria-hidden="true" role="img" class="fi-x"></i></li>
          <li class="label label--primary-light">Keyword 4 
            <i aria-hidden="true" role="img" class="fi-x"></i></li>
          <li class="label label--primary-light">Keyword 4 
            <i aria-hidden="true" role="img" class="fi-x"></i></li>
        </ul>
        <div class="add-remove-tool display-none">
          <input type="text" placeholder="e.g.'freedom of information'">
          <button type="submit" class="prefix">add</button>
        </div>
      </div>
  
      <div class="keyword-list excluded-keywords alert-page-subsection">
        <h3 class="heading-with-bold-word">Keywords <span class="bold">excluded</span> in this alert:</h3>
        <ul>
          <li class="label label--red">Keyword 1 
            <i aria-hidden="true" role="img" class="fi-x"></i></li>
          <li class="label label--red">Keyword 2 
            <i aria-hidden="true" role="img" class="fi-x"></i></li>
          <li class="label label--red">Keyword 3 
            <i aria-hidden="true" role="img" class="fi-x"></i></li>
          <li class="label label--red">Keyword 4 
            <i aria-hidden="true" role="img" class="fi-x"></i></li>
        </ul>
        <div class="add-remove-tool display-none">
          <input type="text" placeholder="e.g.'freedom of information'">
          <button type="submit" class="prefix">add</button>
        </div>
      </div>
  
      <div class="keyword-list alert-page-subsection">
        <h3 class="display-none"><label for="sections">Which section should this alert apply to?</label></h3>
        <select name="sections" id="sections" class="display-none">
          <option value="uk-parliament">All sections</option>
          <option value="uk-parliament">UK Parliament</option>
          <option value="scottish-parliament">Scottish Parliament</option>
        </select>
        <h3 class="heading-with-bold-word">Which <span class="bold">section</span> should this alert apply to:</h3>
        <ul>
          <li class="label label--red">All sections
            <i aria-hidden="true" role="img" class="fi-x"></i></li>
        </ul>
      </div>
  
      <!-- Only to be displayed if there is a person in this query -->
      <div class="keyword-list alert-page-subsection">
        <h3 class="heading-with-bold-word">This alert applies to the following <span class="bold">representative</span></h3>
        <ul>
          <li class="label label--primary-light">Keir Starmer 
            <i aria-hidden="true" role="img" class="fi-x"></i></li>
        </ul>
        <div class="add-remove-tool display-none">
          <input type="text" placeholder="e.g.'freedom of information'">
          <button type="submit" class="prefix">add</button>
        </div>
      </div>
  
      <button class="display-none" style="margin: -1rem 0rem 3rem;">Save changes</button>
      <button class="display-none" style="margin: -1rem 0rem 3rem;">Discard changes</button>
  
    </div>
  </div>
  
  <div class="accordion-item">
    <button class="accordion-button" href="#accordion-content-2" aria-expanded="false">
      <span>Group name 2</span>
      <i aria-hidden="true" role="img" class="fi-plus"></i>
    </button>
    <div id="accordion-content-2" class="accordion-content" aria-hidden="true" role="img">
      <p>ero eos et accusamus et iusto odio dignissimos ducimus qui blanditiis praesentium voluptatum deleniti atque corrupti quos dolores et quas molestias excepturi sint occaecati cupiditate non provident, similique sunt in culpa qui officia deserunt mollitia animi, id est laborum et dolorum fuga. Et harum quidem rerum facilis est et expedita distinctio. Nam libero tempore, cum soluta nobis est eligendi optio cumque n</p>
    </div>
  </div>
  
  <div class="accordion-item" style="margin-bottom: 3rem;">
    <button class="accordion-button" href="#accordion-content-3" aria-expanded="false">
      <span>Group name 3</span>
      <i aria-hidden="true" role="img" class="fi-plus"></i>
    </button>
    <div id="accordion-content-3" class="accordion-content" aria-hidden="true" role="img">
      <p>ero eos et accusamus et iusto odio dignissimos ducimus qui blanditiis praesentium voluptatum deleniti atque corrupti quos dolores et quas molestias excepturi sint occaecati cupiditate non provident, similique sunt in culpa qui officia deserunt mollitia animi, id est laborum et dolorum fuga. Et harum quidem rerum facilis est et expedita distinctio. Nam libero tempore, cum soluta nobis est eligendi optio cumque n</p>
    </div>
  </div>

  <hr>

  <div class="alert-page-header alert-page-section">
    <div>
      <h2>Representative alerts</h2>
      <?php if ($current_mp) { ?>
        <ul class="alerts-manage__list">
            <li>
                <?= sprintf(gettext('You are not subscribed to an alert for your current MP, %s'), $current_mp->full_name()) ?>.
                <form action="<?= $actionurl ?>" method="post">
                    <input type="hidden" name="t" value="<?=_htmlspecialchars($token)?>">
                    <input type="hidden" name="pid" value="<?= $current_mp->person_id() ?>">
                    <input type="submit" class="button" value="<?= gettext('Subscribe') ?>">
                </form>
            </li>
        </ul>
      <?php } ?>
      <p>You haven't created any keyword alert.</p>
    </div>
    <a class="button">
      Create new MP alert
      <i aria-hidden="true" role="img" class="fi-megaphone"></i>
    </a>
</div>
</div>

<div class="mockup-divider"></div>

<div class="mockup-internal-comment">
  <h4>/alert</h4>
  <ul>
    <li>View if the user doesn't have any keyword alert created.</li>
    <li>It has an alert for Keir Starmer</li>
    <li>When pressing the "Create alert" button for an MP it will just take us EG. https://www.theyworkforyou.com/alert/?alertsearch=Keir+Starmer</li>
  </ul>
</div>

<div class="alert-page-header alert-page-section">
  <div>
    <h2>Keywords alerts</h2>
    <p>You haven't created any keyword alert.</p>
  </div>
  <button class="button">
    Create new alert
    <i aria-hidden="true" role="img" class="fi-megaphone"></i>
  </button>
</div>

<hr>

<div class="alert-page-section">
  <div class="alert-page-header">
    <h2>Representative Alerts</h2>
    <?php if ($current_mp) { ?>
      <ul class="alerts-manage__list">
          <li>
              <?= sprintf(gettext('You are not subscribed to an alert for your current MP, %s'), $current_mp->full_name()) ?>.
              <form action="<?= $actionurl ?>" method="post">
                  <input type="hidden" name="t" value="<?=_htmlspecialchars($token)?>">
                  <input type="hidden" name="pid" value="<?= $current_mp->person_id() ?>">
                  <input type="submit" class="button small" value="<?= gettext('Subscribe') ?>">
              </form>
          </li>
      </ul>
    <?php } ?>
  </div>

  <div class="alert-page-subsection">
    <h3 class="alert-page-subsection--heading">Keir Starmer</h3>

    <p class="alert-page-subsection--subtitle">Alert when Keir Starmer <strong>speaks</strong></p>
    <div>
      <button class="button small">
        <span>Suspend alert</span>
        <i aria-hidden="true" class="fi-pause"></i>
      </button>
      <button class="button small red">
        <span>Delete alert</span>
        <i aria-hidden="true" class="fi-trash"></i>
      </button>
    </div>

    <p class="alert-page-subsection--subtitle">Alert when Keir Starmer is <strong>mentioned</strong></p>
    <button class="button small">
      Create new alert
      <i aria-hidden="true" role="img" class="fi-megaphone"></i>
    </button>
  </div>

</div>

<div class="mockup-divider"></div>

<div class="mockup-internal-comment">
  <h4>/alert</h4>
  <p>The previous block assumes the user doesn't have an alert for his own MP. The next block assumes the user has already an alert for his own MP</p>
</div>

<div class="alert-page-header alert-page-section">
  <div>
    <h2>Keywords alerts</h2>
    <p>You haven't created any keyword alert.</p>
  </div>
  <button class="button">
    Create new alert
    <i aria-hidden="true" role="img" class="fi-megaphone"></i>
  </button>
</div>

<hr>

<div class="alert-page-section">
  <div class="alert-page-header">
    <h2>Representative Alerts</h2>
  </div>

  <!-- Replace Janne Doe with users' MP -->
  <div class="alert-page-subsection">
    <h3 class="alert-page-subsection--heading">Your MP ﹒ Janne Doe</h3>

    <p class="alert-page-subsection--subtitle">Alert when Janne Doe <strong>speaks</strong></p>
    <div>
      <button class="button small">
        <span>Suspend alert</span>
        <i aria-hidden="true" class="fi-pause"></i>
      </button>
      <button class="button small red">
        <span>Delete alert</span>
        <i aria-hidden="true" class="fi-trash"></i>
      </button>
    </div>

    <p class="alert-page-subsection--subtitle">Alert when Janne Doe is <strong>mentioned</strong></p>
    <button class="button small">
      Create new alert
      <i aria-hidden="true" role="img" class="fi-megaphone"></i>
    </button>
  </div>

  <div class="alert-page-subsection">
    <h3 class="alert-page-subsection--heading">Keir Starmer</h3>

    <p class="alert-page-subsection--subtitle">Alert when Keir Starmer <strong>speaks</strong></p>
    <div>
      <button class="button small">
        <span>Suspend alert</span>
        <i aria-hidden="true" class="fi-pause"></i>
      </button>
      <button class="button small red">
        <span>Delete alert</span>
        <i aria-hidden="true" class="fi-trash"></i>
      </button>
    </div>

    <p class="alert-page-subsection--subtitle">Alert when Keir Starmer is <strong>mentioned</strong></p>
    <button class="button small">
      Create new alert
      <i aria-hidden="true" role="img" class="fi-megaphone"></i>
    </button>
  </div>

</div>

<div class="mockup-divider"></div>

<div class="mockup-internal-comment">
  <h4>/create-alert</h4>
  <p>The edit and create alert for keywords has the same workflow.</p>
  <p>I'm assuming we will move to a new page, I just called "/create-alert"</p>
</div>

<div class="alert-page-section">
  <!-- If the the user is editing then -> Edit alert -->
   <!-- When the editing process is different to the Create process, where the Editing Process will skip the step 1, so the result will be the mergeing of step 1 and step 2 and then continue as normal -->
  <div class="alert-creation-steps">
  
  </div>
  
  <h1>Create Alert</h1>

  <form id="create-alert-form">
    <!-- Step 1 -->
    <div class="alert-step" id="step1" role="region" aria-labelledby="step1-header">
      <h2 id="step1-header">Create alert name</h2>
      <label for="name">Name this alert</label>
      <input type="text" id="name" name="name" placeholder="Eg. Freedom of Information" aria-required="true">
      <button type="button" class="next" aria-label="Go to Step 2">Next</button>
    </div>

    <!-- Step 2 -->
    <div class="alert-step" id="step2" role="region" aria-labelledby="step2-header" style="display: none;">
      <h2 id="step2-header">Define alert</h2>

      <div class="alert-page-subsection">
        <label for="keyword">What <strong>keyword</strong> do you want to <strong>include</strong> in this alert:</label>
        <input type="text" id="keyword" name="keyword" aria-required="true" placeholder="Eg. 'Freedom of Information', 'FOI'">
      </div>

      <div class="alert-page-subsection">
        <label for="keyword">What <strong>keyword</strong> do you want to <strong>exclude</strong> in this alert:</label>
        <input type="text" id="keyword" name="keyword" aria-required="true" placeholder="Eg. 'Freedom of Information', 'FOI'">
      </div>

      <div class="alert-page-subsection">
        <label for="select-section">Which <strong>section</strong> should this alert apply to?</label>
        <select name="pets" id="select-section">
          <option value="">All sections</option>
          <option value="uk-parliament">UK Parliament</option>
        </select>
      </div>

      <div class="alert-page-subsection">
        <label for="representative">Do you want this alert to apply to a certain <strong>representative</strong>?</label>
        <input type="text" id="representative" name="representative" aria-required="true">
      </div>

      <button type="button" class="prev" aria-label="Go back to Step 1">Previous</button>
      <button type="button" class="next" aria-label="Go to Step 3">Next</button>
    </div>

    <!-- Step 3 -->
    <div class="alert-step" id="step3" role="region" aria-labelledby="step3-header" style="display: none;">
      <h2 id="step3-header">Adding some extras</h2>
      <div class="keyword-list alert-page-subsection">
        <h3 class="heading-with-bold-word">Current keywords in this alert:</h3>
        <ul>
          <li class="label label--primary-light">Keyword 1 
            <i aria-hidden="true" role="img" class="fi-x"></i></li>
          <li class="label label--primary-light">Keyword 2 
            <i aria-hidden="true" role="img" class="fi-x"></i></li>
          <li class="label label--primary-light">Keyword 3 
            <i aria-hidden="true" role="img" class="fi-x"></i></li>
          <li class="label label--primary-light">Keyword 4 
            <i aria-hidden="true" role="img" class="fi-x"></i></li>
          <li class="label label--primary-light">Keyword 4 
            <i aria-hidden="true" role="img" class="fi-x"></i></li>
          <li class="label label--primary-light">Keyword 4 
            <i aria-hidden="true" role="img" class="fi-x"></i></li>
          <li class="label label--primary-light">Keyword 4 
            <i aria-hidden="true" role="img" class="fi-x"></i></li>
        </ul>
        <div class="add-remove-tool display-none">
          <input type="text" placeholder="e.g.'freedom of information'">
          <button type="submit" class="prefix">add</button>
        </div>
      </div>

      <p>We have also found the following related terms. Pick the ones you'd like to include alert?</p>
      
      <fieldset>
        <legend>Related Terms</legend>
        <div>
          <label><input type="checkbox" name="related_terms" value="term1"> Term 1</label><br>
          <label><input type="checkbox" name="related_terms" value="term2"> Term 2</label><br>
          <label><input type="checkbox" name="related_terms" value="term3"> Term 3</label><br>
          <label><input type="checkbox" name="related_terms" value="term4"> Term 4</label><br>
          <label><input type="checkbox" name="related_terms" value="term5"> Term 5</label><br>
          <label><input type="checkbox" id="add-all"> Add all related terms</label>
        </div>
      </fieldset>

      <div class="mockup-internal-comment">
        <p>The section below will display the numbers for the previous step query</p>
      </div>

      <dl class="alert-meta-info">
        <!-- display mentions for the whole group this week. If there are no mention then it doesn't get display -->
          <div class="content-header-item">
            <dt>This week</dt>
            <dd>30 mentions</dd>
          </div>
        <!-- Endif -->

        <div class="content-header-item">
          <dt>Date of last mention</dt>
          <dd>30 May 2024</dd>
        </div>

        <!-- Takes you to the result page of this query -->
        <a href="" class="button small">See results for this alert</a>
      </dl>

      <button type="button" class="prev" aria-label="Go back to Step 2">Previous</button>
      <button type="button" class="next" aria-label="Go to Step 4">Next</button>
    </div>

    <!-- Step 4 (Review) -->
    <div class="alert-step" id="step4" role="region" aria-labelledby="step4-header" style="display: none;">
      <h2 id="step4-header">Review Your Alert</h2>

      <div class="keyword-list alert-page-subsection">
        <h3 class="heading-with-bold-word">Keywords <span class="bold">included</span> in this alert:</h3>
        <ul>
          <li class="label label--primary-light">Keyword 1 
            <i aria-hidden="true" role="img" class="fi-x"></i></li>
          <li class="label label--primary-light">Keyword 2 
            <i aria-hidden="true" role="img" class="fi-x"></i></li>
          <li class="label label--primary-light">Keyword 3 
            <i aria-hidden="true" role="img" class="fi-x"></i></li>
          <li class="label label--primary-light">Keyword 4 
            <i aria-hidden="true" role="img" class="fi-x"></i></li>
          <li class="label label--primary-light">Keyword 4 
            <i aria-hidden="true" role="img" class="fi-x"></i></li>
          <li class="label label--primary-light">Keyword 4 
            <i aria-hidden="true" role="img" class="fi-x"></i></li>
          <li class="label label--primary-light">Keyword 4 
            <i aria-hidden="true" role="img" class="fi-x"></i></li>
        </ul>
        <div class="add-remove-tool display-none">
          <input type="text" placeholder="e.g.'freedom of information'">
          <button type="submit" class="prefix">add</button>
        </div>
      </div>
  
      <div class="keyword-list excluded-keywords alert-page-subsection">
        <h3 class="heading-with-bold-word">Keywords <span class="bold">excluded</span> in this alert:</h3>
        <ul>
          <li class="label label--red">Keyword 1 
            <i aria-hidden="true" role="img" class="fi-x"></i></li>
          <li class="label label--red">Keyword 2 
            <i aria-hidden="true" role="img" class="fi-x"></i></li>
          <li class="label label--red">Keyword 3 
            <i aria-hidden="true" role="img" class="fi-x"></i></li>
          <li class="label label--red">Keyword 4 
            <i aria-hidden="true" role="img" class="fi-x"></i></li>
        </ul>
        <div class="add-remove-tool display-none">
          <input type="text" placeholder="e.g.'freedom of information'">
          <button type="submit" class="prefix">add</button>
        </div>
      </div>
  
      <div class="keyword-list alert-page-subsection">
        <h3 class="display-none"><label for="sections">Which section should this alert apply to?</label></h3>
        <select name="sections" id="sections" class="display-none">
          <option value="uk-parliament">All sections</option>
          <option value="uk-parliament">UK Parliament</option>
          <option value="scottish-parliament">Scottish Parliament</option>
        </select>
        <h3 class="heading-with-bold-word">Which <span class="bold">section</span> should this alert apply to:</h3>
        <ul>
          <li class="label label--red">All sections
            <i aria-hidden="true" role="img" class="fi-x"></i></li>
        </ul>
      </div>
  
      <!-- Only to be displayed if there is a person in this query -->
      <div class="keyword-list alert-page-subsection">
        <h3 class="heading-with-bold-word">This alert applies to the following <span class="bold">representative</span></h3>
        <ul>
          <li class="label label--primary-light">Keir Starmer 
            <i aria-hidden="true" role="img" class="fi-x"></i></li>
        </ul>
        <div class="add-remove-tool display-none">
          <input type="text" placeholder="e.g.'freedom of information'">
          <button type="submit" class="prefix">add</button>
        </div>
      </div>

      <div class="mockup-internal-comment">
        <p>The section below will display the numbers for the previous step query</p>
      </div>

      <dl class="alert-meta-info">
        <!-- display mentions for the whole group this week. If there are no mention then it doesn't get display -->
          <div class="content-header-item">
            <dt>This week</dt>
            <dd>30 mentions</dd>
          </div>
        <!-- Endif -->

        <div class="content-header-item">
          <dt>Date of last mention</dt>
          <dd>30 May 2024</dd>
        </div>

        <!-- Takes you to the result page of this query -->
        <a href="" class="button small">See results for this alert</a>
      </dl>

      <button type="button" class="prev" aria-label="Go back to Step 3">Go Back</button>
      <button class="button">
        <span>Save alert</span>
        <i aria-hidden="true" class="fi-save"></i>
      </button>
      <button class="button red">
        <span>Delete alert</span>
        <i aria-hidden="true" class="fi-trash"></i>
      </button>
    </div>
  </form>

</div>

<div class="mockup-divider"></div>
