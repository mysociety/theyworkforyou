(function() {

function price_cost() {
  var price = document.querySelector('input[name=price]:checked'),
      ctick = document.getElementById('id_charitable_tick'),
      charitable = document.querySelector('input[name=charitable]:checked');
  price = price ? price.value : '';
  ctick = ctick ? ctick.checked : '';
  charitable = charitable ? charitable.value : '';

  var num = 20;
  if (price === 'twfy-5k') {
    num = 50;
  } else if (price === 'twfy-10k') {
    num = 100;
  } else if (price === 'twfy-0k') {
    num = 300;
  }
  if (ctick) {
    if (charitable === 'c' || charitable === 'i') {
      if (num === 20) {
        num = 0;
      } else {
        num = num / 2;
      }
    }
  }
  return num;
}

function need_stripe() {
  var num = price_cost();
  if (num === 0 || document.getElementById('js-payment').getAttribute('data-has-payment-data')) {
    return false;
  }
  return true;
}

function toggle_stripe() {
  var div = document.getElementById('js-payment-needed');
  if (!div) { return; }
  if (need_stripe()) {
    div.style.display = 'block';
  } else {
    div.style.display = 'none';
  }
}

if (document.getElementById('id_price_0')) {
  document.getElementById('id_price_0').addEventListener('change', toggle_stripe);
  document.getElementById('id_price_1').addEventListener('change', toggle_stripe);
  document.getElementById('id_price_2').addEventListener('change', toggle_stripe);
  document.getElementById('id_price_3').addEventListener('change', toggle_stripe);
  document.getElementById('id_charitable_tick').addEventListener('click', function(e) {
    if (this.checked) {
        document.getElementById('charitable-qns').style.display = 'block';
    } else {
        document.getElementById('charitable-qns').style.display = 'none';
    }
    toggle_stripe();
  });
  document.getElementById('id_charitable_0').addEventListener('change', function(e) {
    document.getElementById('charitable-neither').style.display = 'none';
    document.getElementById('charitable-desc').style.display = 'none';
    document.getElementById('charity-number').style.display = 'block';
    toggle_stripe();
  });
  document.getElementById('id_charitable_1').addEventListener('change', function(e) {
    document.getElementById('charity-number').style.display = 'none';
    document.getElementById('charitable-neither').style.display = 'none';
    document.getElementById('charitable-desc').style.display = 'block';
    toggle_stripe();
  });
  document.getElementById('id_charitable_2').addEventListener('change', function(e) {
    document.getElementById('charitable-neither').style.display = 'block';
    document.getElementById('charity-number').style.display = 'none';
    document.getElementById('charitable-desc').style.display = 'none';
    toggle_stripe();
  });
}

toggle_stripe();

var payment_element = document.getElementById('js-payment');
var stripe_key = payment_element.getAttribute('data-key');
var stripe_api_version = payment_element.getAttribute('data-api-version');
var stripe = Stripe(stripe_key, { apiVersion: stripe_api_version });
var elements = stripe.elements();
var card = elements.create('card');
if (document.getElementById('card-element')) {
    card.mount('#card-element');
}

function showError(msg) {
  var displayError = document.getElementById('card-errors');
  displayError.innerHTML = msg ? '<div class="account-form__errors">' + msg + '</div>' : '';
}
card.addEventListener('change', function(event) {
  showError(event.error ? event.error.message : '');
});

function err_highlight(labelElement, err) {
  var $field = $(labelElement).closest('.row');
  if (err) {
    $field.addClass('account-form__field--error');
    return 1;
  } else {
    $field.removeClass('account-form__field--error');
    return 0;
  }
}

function err(field, extra) {
  var f = document.getElementById(field);
  if (!f) {
    return 0;
  }
  f = f.value;
  var label = document.querySelector('label[for=' + field + ']');
  return err_highlight(label, extra !== undefined ? extra && !f : !f);
}

function handleToken(form_id) {
  if (err('id_card_name')) {
    return;
  }

  document.getElementById('customButton').disabled = true;
  document.getElementById('spinner').style.display = 'inline-block';
  stripe.createToken(card, {
    name: document.getElementById('id_card_name').value || ''
  }).then(function(result) {
    if (result.error) {
      document.getElementById('customButton').disabled = false;
      document.getElementById('spinner').style.display = 'none';
      showError(result.error.message);
    } else {
      var form = document.getElementById(form_id);
      form.stripeToken.value = result.token.id;
      form.submit();
    }
  });
}

function card_update(form_id) {
  if (err('id_card_name')) {
    return;
  }

  document.getElementById('customButton').disabled = true;
  document.getElementById('spinner').style.display = 'inline-block';

  var request = new XMLHttpRequest();
  request.open("GET", '/api/update-card');
  request.addEventListener("load", function() {
    var json = JSON.parse(request.responseText);
    stripe.handleCardSetup(
      json.secret, card, {
        payment_method_data: {
          billing_details: {
            name: document.getElementById('id_card_name').value || ''
          }
        }
      }
    ).then(function(result) {
      if (result.error) {
        document.getElementById('customButton').disabled = false;
        document.getElementById('spinner').style.display = 'none';
        showError(result.error.message);
      } else {
        var form = document.getElementById(form_id);
        form.payment_method.value = result.setupIntent.payment_method;
        form.submit();
      }
    });
  });
  request.send();
}

var form = document.getElementById('signup_form');
form && form.addEventListener('submit', function(e) {
  if (this.stripeToken.value) {
      return;
  }
  e.preventDefault();

  var errors = 0;
  var price = document.querySelector('input[name=price]:checked');
  errors += err_highlight(document.querySelector('label[for=id_price_0]'), !price);
  var ctick = document.getElementById('id_charitable_tick').checked;
  var c = document.querySelector('input[name=charitable]:checked');
  errors += err_highlight(document.querySelector('label[for=id_charitable_0]'), ctick && !c);
  errors += err('id_charity_number', ctick && c && c.value === 'c');
  errors += err('id_description', ctick && c && c.value === 'i');
  var tandcs = document.getElementById('id_tandcs_tick');
  errors += tandcs && err_highlight(tandcs.parentNode, !tandcs.checked);
  if (errors) {
    return;
  }

  if (!need_stripe()) {
    document.getElementById('spinner').style.display = 'inline-block';
    this.submit();
    return;
  }

  var form = document.getElementById('signup_form');
  if (document.getElementById('js-payment').getAttribute('data-has-subscription')) {
    card_update('signup_form');
  } else {
    handleToken('signup_form');
  }
});

var form = document.getElementById('update_card_form');
form && form.addEventListener('submit', function(e) {
  e.preventDefault();
  card_update('update_card_form');
});

var form = document.getElementById('declined_form');
form && form.addEventListener('submit', function(e) {
  e.preventDefault();
  card_update('declined_form');
});

})();
