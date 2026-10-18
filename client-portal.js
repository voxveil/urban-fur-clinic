(() => {
  let csrfToken = '';
  const cache = { pets: [], services: [] };
  const breedOptions = {
    Dog: ['Aspin', 'Labrador Retriever', 'Golden Retriever', 'German Shepherd', 'Poodle', 'Beagle', 'Shih Tzu', 'Chihuahua', 'Pomeranian', 'Husky', 'Other'],
    Cat: ['Persian', 'Siamese', 'British Shorthair', 'Maine Coon', 'Ragdoll', 'Bengal', 'American Shorthair', 'Scottish Fold', 'Domestic Shorthair', 'Other'],
    Other: ['Other']
  };
  function updatePetBreeds() {
    const species = $('#petSpecies').value;
    const breeds = breedOptions[species] || breedOptions.Other;
    $('#petBreed').innerHTML = '<option value="">Choose breed</option>' + breeds.map(breed => `<option value="${escapeHtml(breed)}">${escapeHtml(breed)}</option>`).join('');
    $('#petSpeciesOtherWrap').hidden = species !== 'Other';
    $('#petSpeciesOther').required = species === 'Other';
    updateCustomBreed();
  }
  function updateCustomBreed() {
    const custom = $('#petBreed').value === 'Other';
    $('#petBreedOtherWrap').hidden = !custom;
    $('#petBreedOther').required = custom;
  }
  const $ = (selector, root = document) => root.querySelector(selector);
  const topActions = $('.client-top-actions');
  if (topActions && !$('.portal-mobile-logout', topActions)) {
    const logoutLink = document.createElement('a');
    logoutLink.className = 'portal-mobile-logout';
    logoutLink.href = 'client-logout.php';
    logoutLink.setAttribute('aria-label', 'Log out');
    logoutLink.title = 'Log out';
    logoutLink.innerHTML = '<i class="fa-solid fa-right-from-bracket" aria-hidden="true"></i>';
    topActions.append(logoutLink);
  }
  const escapeHtml = value => String(value ?? '').replace(/[&<>"']/g, character => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' })[character]);
  const dateTime = value => value ? new Date(String(value).replace(' ', 'T')).toLocaleString(undefined, { dateStyle: 'medium', timeStyle: 'short' }) : 'Date to be confirmed';
  const dateOnly = value => value ? new Date(`${value}T12:00:00`).toLocaleDateString(undefined, { dateStyle: 'medium' }) : '—';
  const statusName = value => value === 'Scheduled' ? 'Confirmed' : value;
  const statusBadge = value => `<span class="status-badge ${escapeHtml(String(value).toLowerCase().replaceAll(' ', '-'))}">${escapeHtml(statusName(value))}</span>`;
  const showNotice = (message, error = false) => {
    const notice = $('#pageNotice'); notice.textContent = message; notice.className = `notice ${error ? 'error' : 'success'}`; notice.hidden = false;
    notice.scrollIntoView({ behavior: 'smooth', block: 'nearest' }); window.setTimeout(() => { notice.hidden = true; }, 5000);
  };
  async function request(resource, action = '', data = null, query = {}) {
    const options = { credentials: 'same-origin', headers: {} };
    let url = `client-api.php?resource=${encodeURIComponent(resource)}`;
    const queryString = new URLSearchParams(query).toString(); if (queryString) url += `&${queryString}`;
    if (data) {
      if (!csrfToken) {
        const tokenResponse = await fetch('client-api.php?resource=token', { credentials: 'same-origin' });
        const tokenData = await tokenResponse.json(); if (!tokenResponse.ok) throw new Error(tokenData.error || 'Please sign in again.'); csrfToken = tokenData.csrfToken;
      }
      options.method = 'POST'; options.headers['Content-Type'] = 'application/json'; options.headers['X-CSRF-Token'] = csrfToken; options.body = JSON.stringify({ ...data, action });
    }
    const response = await fetch(url, options);
    let body; try { body = await response.json(); } catch { throw new Error('The clinic portal returned an invalid response.'); }
    if (!response.ok) { if (response.status === 419) csrfToken = ''; throw new Error(body.error || 'The request could not be completed.'); }
    return body;
  }
  function activateView(view) {
    document.querySelectorAll('.portal-view').forEach(section => section.classList.toggle('active', section.id === `view-${view}`));
    document.querySelectorAll('.portal-link').forEach(link => link.classList.toggle('active', link.dataset.view === view));
    if (view === 'pets') loadPets(); if (view === 'appointments') { loadPets(); loadServices(); loadAppointments(); }
    if (view === 'records') loadRecords(); if (view === 'profile') loadProfile();
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }
  document.querySelectorAll('.portal-link').forEach(link => link.addEventListener('click', () => activateView(link.dataset.view)));
  document.querySelectorAll('[data-open-view]').forEach(link => link.addEventListener('click', () => activateView(link.dataset.openView)));

  async function loadDashboard() {
    const data = await request('dashboard');
    $('#welcomeName').textContent = (data.profile.full_name || '').trim().split(/\s+/)[0] || 'there';
    $('#petCount').textContent = Number(data.counts.pets).toLocaleString(); $('#appointmentCount').textContent = Number(data.counts.upcoming).toLocaleString(); $('#visitCount').textContent = Number(data.counts.visits).toLocaleString();
    $('#dashboardUpcoming').innerHTML = data.upcoming.length ? data.upcoming.map(item => `<div class="appointment-item"><span class="list-icon"><i class="fa-regular fa-calendar"></i></span><div class="list-copy"><strong>${escapeHtml(item.pet_code)} · ${escapeHtml(item.pet_name)} · ${escapeHtml(item.service)}</strong><small>${escapeHtml(dateTime(item.appointment_at))}</small></div>${statusBadge(item.status)}</div>`).join('') : '<p class="empty-state">No upcoming visits yet. Request an appointment when your pet is ready.</p>';
    $('#dashboardRecent').innerHTML = data.recent.length ? data.recent.map(item => `<div class="recent-item"><span class="recent-dot"></span><div><strong>${escapeHtml(item.pet_code)} · ${escapeHtml(item.pet_name)} · ${escapeHtml(item.diagnosis)}</strong><small>${escapeHtml(dateOnly(item.recorded_on))}</small></div></div>`).join('') : '<p class="empty-state">Your pet’s care records will appear here after a clinic visit.</p>';
  }
  let calendarMonth = new Date(new Date().getFullYear(), new Date().getMonth(), 1);
  const monthKey = date => `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}`;
  function dateKey(date) {
    return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
  }
  async function loadClientCalendar() {
    const month = monthKey(calendarMonth);
    const [{ data: closures }, { data: availabilityByDate }] = await Promise.all([
      request('closures', '', null, { month }),
      request('calendar-availability', '', null, { month })
    ]);
    const year = calendarMonth.getFullYear();
    const monthIndex = calendarMonth.getMonth();
    const firstWeekday = (new Date(year, monthIndex, 1).getDay() + 6) % 7;
    const daysInMonth = new Date(year, monthIndex + 1, 0).getDate();
    const today = dateKey(new Date());
    $('#clientCalendarMonth').textContent = calendarMonth.toLocaleDateString(undefined, { month: 'long', year: 'numeric' });
    const cells = Array.from({ length: firstWeekday }, () => '<span class="calendar-day calendar-day-empty" aria-hidden="true"></span>');
    for (let day = 1; day <= daysInMonth; day += 1) {
      const date = `${month}-${String(day).padStart(2, '0')}`;
      const closure = closures.find(item => item.start_date <= date && item.end_date >= date);
      const dayAvailability = availabilityByDate[date] || { available: 0, closed: false, booked: 0 };
      const isPast = date < today;
      const classes = ['calendar-day'];
      if (closure) classes.push('calendar-day-closed');
      if (date === today) classes.push('calendar-day-today');
      if (dayAvailability.booked && !dayAvailability.available) classes.push('calendar-day-booked');
      const dayStatus = closure || dayAvailability.closed ? `Closed${closure?.reason ? `: ${closure.reason}` : ''}` : dayAvailability.available > 0 ? `${dayAvailability.available} time${dayAvailability.available === 1 ? '' : 's'} available` : 'Fully booked';
      cells.push(`<button type="button" class="${classes.join(' ')}" data-calendar-date="${date}" ${closure || dayAvailability.closed || dayAvailability.available < 1 || isPast ? 'disabled' : ''} title="${escapeHtml(dayStatus)}">${day}</button>`);
    }
    $('#clientCalendarDays').innerHTML = cells.join('');
  }
  $('#clientCalendarPrevious').addEventListener('click', () => {
    calendarMonth = new Date(calendarMonth.getFullYear(), calendarMonth.getMonth() - 1, 1);
    loadClientCalendar().catch(error => showNotice(error.message, true));
  });
  $('#clientCalendarNext').addEventListener('click', () => {
    calendarMonth = new Date(calendarMonth.getFullYear(), calendarMonth.getMonth() + 1, 1);
    loadClientCalendar().catch(error => showNotice(error.message, true));
  });
  $('#clientCalendarDays').addEventListener('click', event => {
    const button = event.target.closest('[data-calendar-date]');
    if (!button || button.disabled) return;
    $('#appointmentDate').value = button.dataset.calendarDate;
    loadAvailability();
  });
  async function loadPets() {
    const { data } = await request('pets'); cache.pets = data;
    $('#petGrid').innerHTML = data.length ? data.map(pet => `<article class="pet-card"><div class="pet-card-top"><span class="pet-avatar"><i class="fa-solid fa-paw"></i></span><span class="pet-card-tag">${escapeHtml(pet.pet_code)}</span></div><h2>${escapeHtml(pet.name)}</h2><p>${escapeHtml(pet.breed || 'Breed not recorded')} · ${escapeHtml(pet.species)}</p><div class="pet-details"><span><small>SEX</small>${escapeHtml(pet.gender || 'Not listed')}</span><span><small>AGE</small>${pet.age === null ? 'Not listed' : `${Number(pet.age)} ${Number(pet.age) === 1 ? 'year' : 'years'}`}</span><span><small>LIFE STATUS</small>${escapeHtml(pet.life_status || 'Alive')}</span></div>${pet.notes ? `<p class="pet-note">${escapeHtml(pet.notes)}</p>` : ''}<button class="text-link" data-pet-records>View Visit History <i class="fa-solid fa-arrow-right"></i></button></article>`).join('') : '<div class="empty-card"><span class="pet-avatar"><i class="fa-solid fa-paw"></i></span><h2>No pets added yet</h2><p>Add your pet below to request visits and keep their care history organized.</p></div>';
    $('#appointmentPet').innerHTML = '<option value="">Choose a pet</option>' + data.filter(pet => pet.life_status === 'Alive').map(pet => `<option value="${Number(pet.id)}">${escapeHtml(pet.pet_code)} · ${escapeHtml(pet.name)}</option>`).join('');
    $('#petGrid').querySelectorAll('[data-pet-records]').forEach(button => button.addEventListener('click', () => activateView('records')));
  }
  async function loadServices() {
    const { data } = await request('services'); cache.services = data;
    $('#appointmentServices').innerHTML = data.map(service => `<label class="service-checkbox"><input type="checkbox" name="service_ids" value="${Number(service.id)}"><span>${escapeHtml(service.name)}</span></label>`).join('') || '<p class="muted">No services are currently available.</p>';
  }
  async function loadAvailability() {
    const date = $('#appointmentDate').value; const timeSelect = $('#appointmentTime'); const message = $('#availabilityMessage');
    timeSelect.disabled = true; timeSelect.innerHTML = '<option value="">Loading available times…</option>';
    if (!date) { timeSelect.innerHTML = '<option value="">Choose a date first</option>'; message.textContent = 'Choose a date to see available clinic times.'; return; }
    try {
      const result = await request('availability', '', null, { date });
      const { data } = result;
      if (result.closed) {
        timeSelect.innerHTML = '<option value="">Clinic closed</option>';
        message.textContent = result.message;
        return;
      }
      timeSelect.innerHTML = data.length ? '<option value="">Choose an available time</option>' + data.map(time => `<option value="${escapeHtml(time)}">${escapeHtml(new Date(`2000-01-01T${time}`).toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' }))}</option>`).join('') : '<option value="">No times available</option>';
      timeSelect.disabled = data.length === 0; message.textContent = data.length ? `${data.length} available time${data.length === 1 ? '' : 's'} · requests need clinic confirmation.` : 'No appointment times are available on this date. Try another day.';
    } catch (error) { timeSelect.innerHTML = '<option value="">Unable to load times</option>'; message.textContent = error.message; }
  }
  async function loadAppointments() {
    const { data } = await request('appointments');
    $('#appointmentRows').innerHTML = data.length ? data.map(item => `<tr><td>${escapeHtml(dateTime(item.appointment_at))}</td><td><small>${escapeHtml(item.pet_code)}</small><br>${escapeHtml(item.pet_name)}</td><td>${escapeHtml(item.service)}${item.purpose ? `<small class="table-subtitle">${escapeHtml(item.purpose)}</small>` : ''}</td><td>${escapeHtml(item.veterinarian)}</td><td>${statusBadge(item.status)}</td></tr>`).join('') : '<tr><td colspan="5" class="empty-table">No appointments yet. You can request one above.</td></tr>';
  }
  async function loadRecords() {
    const { data } = await request('medical-records');
    $('#recordList').innerHTML = data.length ? data.map(record => `<article class="record-card"><div class="record-date"><i class="fa-regular fa-calendar"></i> ${escapeHtml(dateOnly(record.recorded_on))}</div><div class="record-heading"><span class="summary-icon visit-icon"><i class="fa-solid fa-heart-pulse"></i></span><div><h2>${escapeHtml(record.pet_code)} · ${escapeHtml(record.pet_name)}</h2><p>${escapeHtml(record.veterinarian)}</p></div></div><div class="record-field"><small>DIAGNOSIS</small><p>${escapeHtml(record.diagnosis)}</p></div><div class="record-field"><small>TREATMENT</small><p>${escapeHtml(record.treatment || 'No treatment details recorded.')}</p></div><div class="record-field"><small>PRESCRIPTION</small><p>${escapeHtml(record.prescription || 'No prescription recorded.')}</p></div>${record.notes ? `<div class="record-field"><small>ADDITIONAL NOTES</small><p>${escapeHtml(record.notes)}</p></div>` : ''}</article>`).join('') : '<div class="empty-card"><span class="summary-icon visit-icon"><i class="fa-solid fa-heart-pulse"></i></span><h2>No visit history yet</h2><p>Records from completed clinic visits will be available here.</p></div>';
  }
  async function loadProfile() {
    const { data } = await request('profile'); const form = $('#profileForm');
    for (const key of ['username', 'full_name', 'email', 'phone', 'address']) form.elements[key].value = data[key] || '';
    paintClientProfile(data.profile_image || '');
  }
  let clientProfileImage = '';
  function paintClientProfile(image) {
    clientProfileImage = image || '';
    const preview = $('#clientProfilePreview'); const initial = $('#clientProfileInitial');
    preview.hidden = !clientProfileImage; preview.src = clientProfileImage || '';
    initial.hidden = Boolean(clientProfileImage);
    const avatar = document.querySelector('.portal-topbar .avatar');
    avatar.innerHTML = clientProfileImage ? `<img src="${escapeHtml(clientProfileImage)}" alt="">` : escapeHtml($('#profileForm [name="full_name"]').value.trim().charAt(0) || 'U');
  }
  async function loadNotifications() {
    const { data } = await request('notifications');
    const unread = data.filter(item => !Number(item.is_read)).length;
    const count = $('#clientNotificationCount');
    count.hidden = unread === 0;
    count.textContent = unread > 99 ? '99+' : String(unread);
    $('#clientNotificationList').innerHTML = data.length ? data.map(item => `<article class="client-notification-item${Number(item.is_read) ? '' : ' unread'}"><strong>${escapeHtml(item.title)}</strong><p>${escapeHtml(item.message)}</p><small>${escapeHtml(dateTime(item.created_at))}</small></article>`).join('') : '<p class="muted">No notifications yet.</p>';
  }
  $('#clientNotificationButton').addEventListener('click', async () => {
    const popover = $('#clientNotificationPopover');
    const opening = popover.hidden;
    popover.hidden = !opening;
    $('#clientNotificationButton').setAttribute('aria-expanded', String(opening));
    if (opening) {
      try {
        await request('notifications', 'read', {});
        await loadNotifications();
      } catch (error) { showNotice(error.message, true); }
    }
  });
  function serialize(form) {
    const values = {};
    for (const [key, value] of new FormData(form).entries()) {
      if (Object.prototype.hasOwnProperty.call(values, key)) values[key] = Array.isArray(values[key]) ? [...values[key], value] : [values[key], value];
      else values[key] = value;
    }
    if (Object.prototype.hasOwnProperty.call(values, 'service_ids') && !Array.isArray(values.service_ids)) values.service_ids = [values.service_ids];
    return values;
  }
  $('#petSpecies').addEventListener('change', () => {
    $('#petBreedOther').value = '';
    updatePetBreeds();
  });
  $('#petBreed').addEventListener('change', updateCustomBreed);
  $('#petForm').addEventListener('submit', async event => {
    event.preventDefault(); const button = event.submitter; button.disabled = true;
    const form = event.currentTarget; const nameInput = form.elements.name;
    nameInput.setCustomValidity(/^[\p{L}]+(?:[ '\u2019-][\p{L}]+)*$/u.test(nameInput.value.trim()) ? '' : 'Pet name may contain letters only, with spaces, apostrophes, or hyphens between words.');
    if (!form.reportValidity()) { button.disabled = false; return; }
    try { await request('pets', 'create', serialize(form)); form.reset(); updatePetBreeds(); await loadPets(); await loadDashboard(); showNotice('Pet added to your account.'); }
    catch (error) { showNotice(error.message, true); } finally { button.disabled = false; }
  });
  $('#appointmentForm').addEventListener('submit', async event => {
    event.preventDefault(); const button = event.submitter; button.disabled = true;
    try {
      const form = event.currentTarget;
      if (!form.querySelector('input[name="service_ids"]:checked')) throw new Error('Select at least one service.');
      await request('appointments', 'create', serialize(form)); form.reset(); await Promise.all([loadAppointments(), loadDashboard()]); await loadAvailability(); showNotice('Appointment request sent. It will show as Pending until confirmed by the clinic.');
    }
    catch (error) { showNotice(error.message, true); } finally { button.disabled = false; }
  });
  $('#appointmentDate').min = new Date(Date.now() - new Date().getTimezoneOffset() * 60000).toISOString().slice(0, 10);
  updatePetBreeds();
  $('#appointmentDate').addEventListener('change', loadAvailability);
  $('#appointmentDate').addEventListener('change', () => {
    if ($('#appointmentDate').value) {
      const chosen = new Date(`${$('#appointmentDate').value}T12:00:00`);
      calendarMonth = new Date(chosen.getFullYear(), chosen.getMonth(), 1);
      loadClientCalendar().catch(error => showNotice(error.message, true));
    }
  });
  const updateClientClock = () => {
    const now = new Date();
    $('#clientCurrentDate').textContent = new Intl.DateTimeFormat(undefined, { dateStyle: 'medium' }).format(now);
    $('#clientCurrentTime').textContent = new Intl.DateTimeFormat(undefined, { timeStyle: 'short' }).format(now);
  };
  updateClientClock();
  window.setInterval(updateClientClock, 30000);
  $('#profileForm [name="full_name"]').addEventListener('input', event => {
    event.target.value = event.target.value.replace(/[^\p{L} '\u2019-]/gu, '');
  });
  $('#profileForm [name="phone"]').addEventListener('input', event => {
    event.target.value = event.target.value.replace(/\D/g, '').slice(0, 11);
  });
  $('#clientProfilePhoto').addEventListener('change', event => {
    const file = event.target.files?.[0]; if (!file) return;
    if (file.size > 1048576 || !['image/png', 'image/jpeg', 'image/webp'].includes(file.type)) { showNotice('Choose a PNG, JPG, or WebP image up to 1 MB.', true); event.target.value = ''; return; }
    const reader = new FileReader(); reader.onload = () => paintClientProfile(String(reader.result)); reader.readAsDataURL(file);
  });
  $('#profileForm').addEventListener('submit', async event => {
    event.preventDefault(); const button = event.submitter; button.disabled = true;
    const form = event.currentTarget;
    const name = form.elements.full_name;
    name.setCustomValidity(/^[\p{L}]+(?:[ '\u2019-][\p{L}]+)*$/u.test(name.value.trim()) ? '' : 'Name may contain letters only, with spaces, apostrophes, or hyphens between words.');
    if (!form.reportValidity()) { button.disabled = false; return; }
    try {
      await request('profile', 'update', { ...serialize(form), profile_image: clientProfileImage });
      document.querySelector('.portal-topbar .welcome strong').textContent = name.value;
      showNotice('Your profile has been updated.');
    }
    catch (error) { showNotice(error.message, true); } finally { button.disabled = false; }
  });
  $('#passwordForm').addEventListener('submit', async event => {
    event.preventDefault(); const form = event.currentTarget; const button = event.submitter; button.disabled = true;
    try { await request('profile', 'password', serialize(form)); form.reset(); showNotice('Your password has been changed.'); }
    catch (error) { showNotice(error.message, true); } finally { button.disabled = false; }
  });
  loadDashboard().catch(error => showNotice(error.message, true));
  loadProfile().catch(error => showNotice(error.message, true));
  loadClientCalendar().catch(error => showNotice(error.message, true));
  loadNotifications().catch(error => showNotice(error.message, true));
  window.setInterval(() => {
    loadClientCalendar().catch(error => showNotice(error.message, true));
    if ($('#appointmentDate').value) loadAvailability().catch(error => showNotice(error.message, true));
  }, 30000);
  window.setInterval(() => loadNotifications().catch(error => showNotice(error.message, true)), 30000);
})();
