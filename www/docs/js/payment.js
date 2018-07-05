document.getElementById('id_charitable_tick').addEventListener('click', function(e) {
    if (this.checked) {
        document.getElementById('charitable-qns').style.display = 'block';
    } else {
        document.getElementById('charitable-qns').style.display = 'none';
    }
});
document.getElementById('id_charitable_0').addEventListener('change', function(e) {
    document.getElementById('charitable-neither').style.display = 'none';
    document.getElementById('charitable-desc').style.display = 'none';
    document.getElementById('charity-number').style.display = 'block';
});
document.getElementById('id_charitable_1').addEventListener('change', function(e) {
    document.getElementById('charity-number').style.display = 'none';
    document.getElementById('charitable-neither').style.display = 'none';
    document.getElementById('charitable-desc').style.display = 'block';
});
document.getElementById('id_charitable_2').addEventListener('change', function(e) {
    document.getElementById('charitable-neither').style.display = 'block';
    document.getElementById('charity-number').style.display = 'none';
    document.getElementById('charitable-desc').style.display = 'none';
});

var stripe_key = document.getElementById('js-payment').getAttribute('data-key');
var handler = StripeCheckout.configure({
  key: stripe_key,
  image: 'https://s3.amazonaws.com/stripe-uploads/acct_19EbqNIbP0iBLddtmerchant-icon-1479145884111-mysociety-wheel-logo.png',
  locale: 'auto',
  token: function(token) {
    var form = document.getElementById('signup_form');
    form.stripeToken.value = token.id;
    form.submit();
  }
});

var stripeButton = document.getElementById('customButton');
stripeButton && stripeButton.addEventListener('click', function(e) {
  // Already got a token from Stripe (so password mismatch error or somesuch)
  var form = document.getElementById('signup_form');
  if (form.stripeToken.value) {
      return;
  }
  e.preventDefault();

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

  var errors = 0;
  var plan = document.querySelector('input[name=plan]:checked');
  errors += err_highlight(document.querySelector('label[for=id_plan_0]'), !plan);
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

  plan = plan.value;
  var num = 20;
  if (plan === 'twfy-5k') {
    num = 50;
  } else if (plan === 'twfy-10k') {
    num = 100;
  } else if (plan === 'twfy-0k') {
    num = 300;
  }
  if (ctick) {
    c = c.value;
    if (c === 'c' || c === 'i') {
      if (num === 20) {
        num = 0;
      } else {
        num = num / 2;
      }
    }
  }
  if (num === 0 || document.getElementById('js-payment').getAttribute('data-has-payment-data')) {
    form.submit();
    return;
  }

  var email = document.getElementById('js-payment').getAttribute('data-email');

  handler.open({
    name: 'mySociety',
    description: 'Subscribing to plan ' + plan,
    zipCode: true,
    currency: 'gbp',
    allowRememberMe: false,
    email: email,
    amount: num * 100
  });
});

window.addEventListener('popstate', function() {
  handler.close();
});
