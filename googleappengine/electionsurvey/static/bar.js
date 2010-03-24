function barSetup(){
    
    //create menu and wrapper
    //var oDiv = document.createElement('div');
    var oText = document.createElement('span');
    var oMenu = document.createElement('ul');
    oMenu.id = 'barmenu';

    document.getElementById('moresites').appendChild(oMenu);
    //oDiv.appendChild(oText);
    //oDiv.appendChild(oMenu);

    //setup click event
    oClick = document.getElementById('moresiteslink');    
    oClick.href="javascript:barShowMenu()";

    //add title
    var oMenuItem = document.createElement('li');
    oMenuItem.className = "menutitle";
    oMenuItem.innerHTML = 'mySociety can help you ...'
    oMenu.appendChild(oMenuItem);
    
    //work out what menu items to add
    var sDomain = document.domain.toLowerCase();
    
    if(sDomain.indexOf('writetothem.com') == -1){
        barAddMenuItem(oMenu, 'Write to your MP', 'http://www.writetothem.com', 'WriteToThem.com', false);
    }

    if(sDomain.indexOf('fixmystreet.com') == -1){
        barAddMenuItem(oMenu, 'Fix something on your street', 'http://www.fixmystreet.com', 'FixMyStreet.com', false);
    }

    if(sDomain.indexOf('whatdotheyknow.com') == -1){
        barAddMenuItem(oMenu, 'Make a Freedom of Information request', 'http://www.whatdotheyknow.com', 'WhatDoTheyKnow.com', false);
    }

    if(sDomain.indexOf('theyworkforyou.com') == -1){
        barAddMenuItem(oMenu, 'Keep tabs on parliament', 'http://www.theyworkforyou.com', 'TheyWorkForYou.com', false);
    }    
    
    if(sDomain.indexOf('pledgebank.com') == -1){
        barAddMenuItem(oMenu, 'Gather support for a campaign', 'http://www.pledgebank.com', 'PledgeBank.com', false);
    }
    
    if(sDomain.indexOf('hearfromyourmp.com') == -1){
        barAddMenuItem(oMenu, 'Get news from your MP', 'http://www.hearfromyourmp.com', 'HearFromYourMP.com', false);
    }

    if(sDomain.indexOf('groupsnearyou.com') == -1){
        barAddMenuItem(oMenu, 'Discover a local email group', 'http://www.groupsnearyou.com', 'GroupsNearYou.com', false);
    }    
    
    if(sDomain.indexOf('hassleme.com') == -1){
        barAddMenuItem(oMenu, 'Get hassled to do something good', 'http://www.hassleme.co.uk', 'HassleMe.co.uk', false);
    }    
    
    //barAddMenuItem(oText, oMenu, 'Sign up to our newsletter', 'https://secure.mysociety.org/admin/lists/mailman/listinfo/news', 'mySociety newsletter - about once a month', true);

    // add newsletter subscribe
    var oNewsletterLabel = document.createElement('li');
    oNewsletterLabel.className = "bardivider";
    oNewsletterLabel.innerHTML = '<label for="txtBarEmail" title="mySociety newsletter - about once a month">Signup to our newsletter</label>';
    oMenu.appendChild(oNewsletterLabel);

    var oNewsletterForm = document.createElement('li');
    oNewsletterForm.className = "bordernodivider";
    oNewsletterForm.innerHTML = '<form method="get" action="https://secure.mysociety.org/admin/lists/mailman/subscribe/news"><input type="text" class="textbox nodivider" name="email" id="txtBarEmail"/><input type="submit" value="Go"/></form>';
    oMenu.appendChild(oNewsletterForm);

    // add class to first menu item
    oMenu.firstChild.className += ' first';

    //make the menu disapear on exit;
}

function barAddMenuItem(oMenu, sText, sLink, sHint, bDivider){

    var sClassName = '';
    if(bDivider == true){
        sClassName = 'bardivider';
    }
    var oMenuItem = document.createElement('li');
    oMenuItem.className = sClassName;
    oMenuItem.innerHTML = '<a href="' + sLink + '?cs=1" title=" ' + sHint + ' ">' + sText + '</a>'
    oMenu.appendChild(oMenuItem);

}

function barAddText(oText, sText, sLink, sHint){
    oText.innerHTML = 'Get more things done: <a id="aFirstLink" href="javascript:barShowMenu()">' + sText + '</a> <a id="aArrow"href="javascript:barShowMenu();"><img src="images/arrow.png"/></a>';
}

function barShowMenu (){
 
    var oMenu = document.getElementById('barmenu');
 
    if(oMenu.style.display == 'block'){
        oMenu.style.display = 'none';
    }else{
        oMenu.style.display = 'block';        
    }
}