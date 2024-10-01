<!-- TODO for me: For this mockup I'm not worrying to much for the hierarchy of the headings -->
<p class="internal-comment">Initial view for alert page. This assumes the user has already created some group alerts</p>
<div class="alert-page-header">
  <h2>Keywords alerts</h2>
  <button class="button">
    Create new alert
    <i aria-hidden="true" role="img" class="fi-megaphone"></i>
  </button>
</div>

<div class="accordion">
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
        <button class="button small alert">
          <span>Delete alert</span>
          <i aria-hidden="true" class="fi-trash"></i>
        </button>
      </div>
      <dl>
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
          <a href="" class="button">See results for this alert</a>
        </dl>
    </div>

    <hr>

    <div class="keyword-list accordion-section">
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

    <div class="keyword-list excluded-keywords accordion-section">
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

    <div class="keyword-list accordion-section">
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
    <div class="keyword-list accordion-section">
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

<div class="accordion">
  <button class="accordion-button" href="#accordion-content-2" aria-expanded="false">
    <span>Group name 2</span>
    <i aria-hidden="true" role="img" class="fi-plus"></i>
  </button>
  <div id="accordion-content-2" class="accordion-content" aria-hidden="true" role="img">
    <p>ero eos et accusamus et iusto odio dignissimos ducimus qui blanditiis praesentium voluptatum deleniti atque corrupti quos dolores et quas molestias excepturi sint occaecati cupiditate non provident, similique sunt in culpa qui officia deserunt mollitia animi, id est laborum et dolorum fuga. Et harum quidem rerum facilis est et expedita distinctio. Nam libero tempore, cum soluta nobis est eligendi optio cumque n</p>
  </div>
</div>

<div class="accordion" style="margin-bottom: 7rem;">
  <button class="accordion-button" href="#accordion-content-3" aria-expanded="false">
    <span>Group name 3</span>
    <i aria-hidden="true" role="img" class="fi-plus"></i>
  </button>
  <div id="accordion-content-3" class="accordion-content" aria-hidden="true" role="img">
    <p>ero eos et accusamus et iusto odio dignissimos ducimus qui blanditiis praesentium voluptatum deleniti atque corrupti quos dolores et quas molestias excepturi sint occaecati cupiditate non provident, similique sunt in culpa qui officia deserunt mollitia animi, id est laborum et dolorum fuga. Et harum quidem rerum facilis est et expedita distinctio. Nam libero tempore, cum soluta nobis est eligendi optio cumque n</p>
  </div>
</div>
