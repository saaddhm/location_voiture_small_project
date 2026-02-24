(function () {
  'use strict';

  var BASE = '';
  var csrfToken = '';
  var IMG_PLACEHOLDER = 'data:image/svg+xml,' + encodeURIComponent('<svg xmlns="http://www.w3.org/2000/svg" width="400" height="250" viewBox="0 0 400 250"><rect fill="#1a2332" width="400" height="250"/><text x="50%" y="50%" fill="#8b949e" font-size="14" text-anchor="middle" dy=".3em">Voiture</text></svg>');
  window.__imgPlaceholder = IMG_PLACEHOLDER;

  function getCsrfToken() {
    return fetch(BASE + 'api/csrf.php')
      .then(function (r) { return r.json(); })
      .then(function (res) {
        if (res.ok && res.data && res.data.csrf_token) {
          csrfToken = res.data.csrf_token;
          return csrfToken;
        }
        return Promise.reject(new Error('CSRF unavailable'));
      });
  }

  function loadCars(type) {
    var url = BASE + 'api/cars.php';
    if (type) url += '?type=' + encodeURIComponent(type);
    return fetch(url).then(function (r) { return r.json(); });
  }

  function renderCars(cars) {
    var grid = document.getElementById('cars-grid');
    var loading = document.getElementById('cars-loading');
    var errEl = document.getElementById('cars-error');

    loading.hidden = true;
    errEl.hidden = true;
    grid.innerHTML = '';

    if (!cars || cars.length === 0) {
      grid.innerHTML = '<p class="loading-msg">Aucune voiture pour ce filtre.</p>';
      return;
    }

    cars.forEach(function (car) {
      var card = document.createElement('article');
      card.className = 'car-card';
      var imgSrc = car.image ? BASE + car.image : IMG_PLACEHOLDER;
      card.innerHTML =
        '<img class="car-card-image" src="' + imgSrc + '" alt="" loading="lazy" onerror="this.onerror=null;this.src=window.__imgPlaceholder">' +
        '<div class="car-card-body">' +
          '<h3 class="car-card-title">' + escapeHtml(car.name) + '</h3>' +
          '<p class="car-card-meta">' + escapeHtml(car.type) + ' · ' + escapeHtml(car.transmission) + ' · ' + escapeHtml(car.fuel) + '</p>' +
          '<p class="car-card-price">' + formatPrice(car.price_per_day) + ' / jour</p>' +
          '<div class="car-card-actions"><button type="button" class="btn btn-primary btn-book" data-id="' + escapeHtml(String(car.id)) + '" data-name="' + escapeHtml(car.name) + '" data-price="' + escapeHtml(String(car.price_per_day)) + '">Réserver</button></div>' +
        '</div>';
      grid.appendChild(card);
    });

    grid.querySelectorAll('.btn-book').forEach(function (btn) {
      btn.addEventListener('click', openBookingModal.bind(null, {
        id: btn.dataset.id,
        name: btn.dataset.name,
        price_per_day: btn.dataset.price
      }));
    });
  }

  function escapeHtml(s) {
    if (!s) return '';
    var div = document.createElement('div');
    div.textContent = s;
    return div.innerHTML;
  }

  function formatPrice(n) {
    return Number(n).toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' €';
  }

  function showFeedback(el, message, isError) {
    el.textContent = message;
    el.className = 'form-feedback ' + (isError ? 'error' : 'success');
    el.hidden = false;
  }

  // --- Filters ---
  document.querySelectorAll('.filter-btn').forEach(function (btn) {
    btn.addEventListener('click', function () {
      document.querySelectorAll('.filter-btn').forEach(function (b) { b.classList.remove('active'); });
      this.classList.add('active');
      var type = this.getAttribute('data-type') || '';
      document.getElementById('cars-loading').hidden = false;
      document.getElementById('cars-error').hidden = true;
      loadCars(type)
        .then(function (res) {
          if (res.ok) renderCars(res.data);
          else {
            document.getElementById('cars-error').textContent = res.error || 'Erreur';
            document.getElementById('cars-error').hidden = false;
            document.getElementById('cars-grid').innerHTML = '';
          }
        })
        .catch(function () {
          document.getElementById('cars-error').textContent = 'Impossible de charger les voitures.';
          document.getElementById('cars-error').hidden = false;
          document.getElementById('cars-grid').innerHTML = '';
        })
        .finally(function () {
          document.getElementById('cars-loading').hidden = true;
        });
    });
  });

  // --- Booking modal ---
  var modal = document.getElementById('booking-modal');
  var bookingForm = document.getElementById('booking-form');
  var bookingCarId = document.getElementById('booking_car_id');
  var bookingCarTitle = document.getElementById('booking-car-title');
  var bookingTotal = document.getElementById('booking-total');
  var bookingFeedback = document.getElementById('booking-feedback');
  var currentCar = null;

  function openBookingModal(car) {
    currentCar = car;
    bookingCarId.value = car.id;
    bookingCarTitle.textContent = 'Réserver : ' + car.name;
    bookingForm.reset();
    bookingCarId.value = car.id;
    bookingFeedback.hidden = true;
    updateBookingTotal();
    modal.hidden = false;
  }

  function closeBookingModal() {
    modal.hidden = true;
    currentCar = null;
  }

  modal.querySelector('.modal-backdrop').addEventListener('click', closeBookingModal);
  modal.querySelector('.modal-close').addEventListener('click', closeBookingModal);

  function updateBookingTotal() {
    var start = document.getElementById('booking_start').value;
    var end = document.getElementById('booking_end').value;
    if (!currentCar || !start || !end) {
      bookingTotal.textContent = '';
      return;
    }
    var d1 = new Date(start);
    var d2 = new Date(end);
    if (d2 <= d1) {
      bookingTotal.textContent = '';
      return;
    }
    var days = Math.ceil((d2 - d1) / (24 * 60 * 60 * 1000));
    var total = days * parseFloat(currentCar.price_per_day);
    bookingTotal.textContent = days + ' jour(s) · Total : ' + formatPrice(total);
  }

  document.getElementById('booking_start').addEventListener('change', updateBookingTotal);
  document.getElementById('booking_end').addEventListener('change', updateBookingTotal);

  bookingForm.addEventListener('submit', function (e) {
    e.preventDefault();
    bookingFeedback.hidden = true;

    var start = document.getElementById('booking_start').value;
    var end = document.getElementById('booking_end').value;
    if (!start || !end || new Date(end) <= new Date(start)) {
      showFeedback(bookingFeedback, 'Vérifiez les dates.', true);
      return;
    }

    var methodInput = bookingForm.querySelector('input[name="payment_method"]:checked');
    var paymentMethod = methodInput ? methodInput.value : 'online';

    var fd = new FormData(bookingForm);
    fd.append('csrf_token', csrfToken);

    fetch(BASE + 'api/reservations_create.php', {
      method: 'POST',
      body: fd
    })
      .then(function (r) { return r.json(); })
      .then(function (res) {
        if (res.ok) {
          if (paymentMethod === 'online') {
            window.location.href = BASE + 'cmi/1.PaymentRequest.php?reservation_id=' + encodeURIComponent(res.data.id) + '&total_amount=' + encodeURIComponent(res.data.total);
            return;
          }
          showFeedback(bookingFeedback, 'Réservation enregistrée. Total : ' + formatPrice(res.data.total) + '.', false);
          setTimeout(closeBookingModal, 2000);
        } else {
          showFeedback(bookingFeedback, res.error || 'Erreur lors de la réservation.', true);
        }
      })
      .catch(function () {
        showFeedback(bookingFeedback, 'Erreur réseau.', true);
      });
  });

  // --- Contact form ---
  document.getElementById('contact-form').addEventListener('submit', function (e) {
    e.preventDefault();
    var fd = new FormData(this);
    fd.append('csrf_token', csrfToken);
    var feedback = document.getElementById('contact-feedback');
    feedback.hidden = true;

    fetch(BASE + 'api/contact_create.php', {
      method: 'POST',
      body: fd
    })
      .then(function (r) { return r.json(); })
      .then(function (res) {
        if (res.ok) {
          showFeedback(feedback, 'Message envoyé.', false);
          e.target.reset();
        } else {
          showFeedback(feedback, res.error || 'Erreur.', true);
        }
      })
      .catch(function () {
        showFeedback(feedback, 'Erreur réseau.', true);
      });
  });

  // --- Init: CSRF then load cars ---
  getCsrfToken()
    .then(function () {
      document.getElementById('cars-loading').hidden = false;
      return loadCars('');
    })
    .then(function (res) {
      if (res.ok) renderCars(res.data);
      else {
        document.getElementById('cars-error').textContent = res.error || 'Erreur';
        document.getElementById('cars-error').hidden = false;
      }
    })
    .catch(function () {
      document.getElementById('cars-loading').hidden = true;
      document.getElementById('cars-error').textContent = 'Impossible de charger les données.';
      document.getElementById('cars-error').hidden = false;
    })
    .finally(function () {
      document.getElementById('cars-loading').hidden = true;
    });
})();
