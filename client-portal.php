<?php
declare(strict_types=1);
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/db.php';
if (!isset($_SESSION['client_user_id'])) { header('Location: client-auth.php'); exit; }
$statement = $mysqli->prepare("SELECT u.full_name, u.profile_image, c.email FROM users u JOIN clients c ON c.account_user_id = u.id WHERE u.id = ? AND u.role = 'Client' AND u.active = 1 AND c.status = 'Active' LIMIT 1");
$userId = (int) $_SESSION['client_user_id'];
$statement->bind_param('i', $userId);
$statement->execute();
$client = $statement->get_result()->fetch_assoc();
$statement->close();
if (!is_array($client)) { unset($_SESSION['client_user_id']); header('Location: client-auth.php?account=inactive'); exit; }
?>
<!doctype html>
<html lang="en">
<head>
  <link rel="icon" type="image/png" href="assets/clinic-logo.png">
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>My Pet Care · Urban Fur Veterinary Clinic</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="client-portal.css?v=8">
  <link rel="stylesheet" href="client-theme.css?v=3">
  <script src="client-portal.js?v=9" defer></script>
</head>
<body class="portal-page">
  <aside class="portal-sidebar">
    <a class="client-brand" href="client-portal.php"><span class="brand-paw"><i class="fa-solid fa-paw"></i></span><span>Urban Fur<small>Pet Parent Portal</small></span></a>
    <nav class="portal-nav" aria-label="Client navigation">
      <button class="portal-link active" data-view="dashboard"><i class="fa-solid fa-house"></i> Overview</button>
      <button class="portal-link" data-view="pets"><i class="fa-solid fa-paw"></i> My Pets</button>
      <button class="portal-link" data-view="appointments"><i class="fa-regular fa-calendar-check"></i> Appointments</button>
      <button class="portal-link" data-view="records"><i class="fa-solid fa-notes-medical"></i> Visit History</button>
      <button class="portal-link" data-view="profile"><i class="fa-regular fa-user"></i> My Profile</button>
    </nav>
    <div class="sidebar-help"><span class="help-icon"><i class="fa-regular fa-heart"></i></span><strong>Here for every paw</strong><p>Your pet’s care details, all in one place.</p></div>
    <a class="portal-logout" href="client-logout.php"><i class="fa-solid fa-arrow-right-from-bracket"></i> Log out</a>
  </aside>
  <main class="portal-main">
    <header class="portal-topbar"><div class="mobile-brand">Urban Fur <span>Pet Parent Portal</span></div><div class="welcome"><span>CLIENT PORTAL</span><strong><?= htmlspecialchars($client['full_name'], ENT_QUOTES, 'UTF-8') ?></strong></div><div class="client-top-actions"><time class="client-clock" id="clientCurrentDateTime" aria-label="Current date and time"><span id="clientCurrentDate"></span><span id="clientCurrentTime"></span></time><div class="client-notifications"><button class="notification-button" id="clientNotificationButton" type="button" aria-label="Notifications" aria-expanded="false"><i class="fa-regular fa-bell"></i><span id="clientNotificationCount" hidden></span></button><div class="notification-popover" id="clientNotificationPopover" hidden><strong>Notifications</strong><div id="clientNotificationList"><p class="muted">Loading notifications…</p></div></div></div><a class="avatar" href="#profile" data-open-view="profile" aria-label="Open profile"><?= htmlspecialchars(strtoupper(mb_substr($client['full_name'], 0, 1, 'UTF-8')), ENT_QUOTES, 'UTF-8') ?></a></div></header>
    <div id="pageNotice" class="notice" role="status" hidden></div>
    <section class="portal-view active" id="view-dashboard">
      <div class="welcome-banner"><div><p class="eyebrow">YOUR PET CARE, SIMPLIFIED</p><h1>Good to see you, <span id="welcomeName"><?= htmlspecialchars(explode(' ', $client['full_name'])[0], ENT_QUOTES, 'UTF-8') ?></span></h1><p>Keep up with your pets’ health and upcoming visits.</p></div><div class="banner-art" aria-hidden="true"><i class="fa-solid fa-paw"></i><i class="fa-solid fa-heart"></i></div></div>
      <div class="summary-grid">
        <article class="summary-card"><span class="summary-icon pets-icon"><i class="fa-solid fa-paw"></i></span><span class="summary-label">My pets</span><strong id="petCount">—</strong><small>In your care</small></article>
        <article class="summary-card"><span class="summary-icon appointment-icon"><i class="fa-regular fa-calendar-check"></i></span><span class="summary-label">Upcoming visits</span><strong id="appointmentCount">—</strong><small>Scheduled or awaiting confirmation</small></article>
        <article class="summary-card"><span class="summary-icon visit-icon"><i class="fa-solid fa-heart-pulse"></i></span><span class="summary-label">Care history</span><strong id="visitCount">—</strong><small>Recorded clinic visits</small></article>
      </div>
      <div class="content-grid">
        <article class="panel"><div class="panel-heading"><div><h2>Upcoming appointments</h2><p>Your next visits at Urban Fur</p></div><button class="text-link" data-open-view="appointments">See appointments <i class="fa-solid fa-arrow-right"></i></button></div><div id="dashboardUpcoming" class="appointment-list"><p class="muted">Loading appointments…</p></div></article>
        <article class="panel"><div class="panel-heading"><div><h2>Recent visits</h2><p>Your pets’ latest care records</p></div><button class="text-link" data-open-view="records">View records <i class="fa-solid fa-arrow-right"></i></button></div><div id="dashboardRecent" class="recent-list"><p class="muted">Loading records…</p></div></article>
      </div>
    </section>
    <section class="portal-view" id="view-pets">
      <div class="section-heading"><div><p class="eyebrow">YOUR FAMILY</p><h1>My Pets</h1><p>View your registered pets and their care details.</p></div></div>
      <div class="pet-grid" id="petGrid"><p class="muted">Loading your pets…</p></div>
      <article class="panel form-panel"><div class="panel-heading"><div><h2>Add a pet</h2><p>Register a pet to request visits and keep their records together.</p></div></div><form id="petForm" class="client-form form-grid">
        <label>Pet name<input name="name" maxlength="100" pattern="[\p{L}]+([ '\u2019\-][\p{L}]+)*" title="Use letters only, with spaces, apostrophes, or hyphens between words." required></label><label>Species<select name="species" id="petSpecies" required><option value="Dog">Dog</option><option value="Cat">Cat</option><option value="Other">Other</option></select></label><label id="petSpeciesOtherWrap" hidden>Specify species<input name="species_other" id="petSpeciesOther" maxlength="60" placeholder="Enter the kind of pet"></label><label>Breed<select name="breed" id="petBreed" required></select></label><label id="petBreedOtherWrap" hidden>Specify breed<input name="breed_other" id="petBreedOther" maxlength="100" placeholder="Enter the breed"></label>
        <label>Sex<select name="gender"><option value="">Prefer not to say</option><option>Female</option><option>Male</option><option>Unknown</option></select></label><label>Date of birth<input name="birth_date" type="date" max="<?= date('Y-m-d') ?>" required></label><label class="wide-field">Notes <span class="optional">Optional</span><input name="notes" maxlength="2000" placeholder="Anything the care team should know"></label>
        <div class="form-actions wide-field"><button class="button primary" type="submit">Add pet</button></div>
      </form></article>
    </section>
    <section class="portal-view" id="view-appointments">
      <div class="section-heading"><div><p class="eyebrow">PLAN THEIR NEXT VISIT</p><h1>Appointments</h1><p>Request a visit and check its status here.</p></div></div>
      <article class="panel form-panel"><div class="panel-heading"><div><h2>Request an appointment</h2><p>Your request will be marked Pending until the clinic confirms it.</p></div></div><form id="appointmentForm" class="client-form form-grid">
        <div class="client-calendar panel-inset wide-field"><div class="client-calendar-heading"><button type="button" id="clientCalendarPrevious" aria-label="Previous month"><i class="fa-solid fa-chevron-left"></i></button><strong id="clientCalendarMonth"></strong><button type="button" id="clientCalendarNext" aria-label="Next month"><i class="fa-solid fa-chevron-right"></i></button></div><div class="client-calendar-grid client-calendar-weekdays" aria-hidden="true"><span>Mon</span><span>Tue</span><span>Wed</span><span>Thu</span><span>Fri</span><span>Sat</span><span>Sun</span></div><div class="client-calendar-grid" id="clientCalendarDays" aria-label="Clinic calendar"><span class="muted">Loading calendar…</span></div><p class="client-calendar-legend"><span class="calendar-legend-open"></span> Available <span class="calendar-legend-booked"></span> Fully booked <span class="calendar-legend-closed"></span> Clinic closed</p></div>
        <label>Pet<select name="pet_id" id="appointmentPet" required><option value="">Choose a pet</option></select></label><fieldset class="service-checkbox-group wide-field"><legend>Services <span class="optional">Select all that apply</span></legend><div id="appointmentServices" class="service-checkboxes"><p class="muted">Loading services…</p></div></fieldset>
        <label>Preferred date<input name="appointment_date" id="appointmentDate" type="date" required><small id="availabilityMessage">Choose a date to see available clinic times.</small></label><label>Available time<select name="appointment_time" id="appointmentTime" required disabled><option value="">Choose a date first</option></select></label><label class="wide-field">Purpose<input name="purpose" maxlength="1000" placeholder="Tell us what your pet needs" required></label>
        <div class="form-actions wide-field"><button class="button primary" type="submit">Request appointment</button></div>
      </form></article>
      <article class="panel"><div class="panel-heading"><div><h2>All appointments</h2><p>Requests and visits for your pets</p></div></div><div class="table-scroll"><table><thead><tr><th>Date &amp; time</th><th>Pet</th><th>Visit</th><th>Veterinarian</th><th>Status</th></tr></thead><tbody id="appointmentRows"><tr><td colspan="5">Loading appointments…</td></tr></tbody></table></div></article>
    </section>
    <section class="portal-view" id="view-records">
      <div class="section-heading"><div><p class="eyebrow">THEIR WELLBEING, AT A GLANCE</p><h1>Visit History</h1><p>Care information recorded by the Urban Fur clinic team.</p></div></div>
      <div id="recordList" class="record-list"><p class="muted">Loading Visit History…</p></div>
    </section>
    <section class="portal-view" id="view-profile">
      <div class="section-heading"><div><p class="eyebrow">YOUR ACCOUNT</p><h1>My Profile</h1><p>Keep your contact information up to date.</p></div></div>
      <article class="panel profile-panel"><div class="panel-heading"><div><h2>Personal information</h2><p>Used by the clinic to contact you about your pets.</p></div></div><form id="profileForm" class="client-form form-grid">
        <div class="client-profile-photo wide-field"><img id="clientProfilePreview" alt="Profile photo preview" hidden><span id="clientProfileInitial" class="avatar"><?= htmlspecialchars(strtoupper(mb_substr($client['full_name'], 0, 1, 'UTF-8')), ENT_QUOTES, 'UTF-8') ?></span><label class="button secondary" for="clientProfilePhoto">Choose photo</label><input id="clientProfilePhoto" type="file" accept="image/png,image/jpeg,image/webp" hidden></div>
        <label>Username<input name="username" maxlength="80" minlength="3" pattern="([A-Za-z0-9_.\-]{3,80}|Walk-in [0-9]{6})" title="Use 3–80 letters, numbers, dots, dashes, or underscores." required></label>
        <label>Full name<input name="full_name" maxlength="150" pattern="[\p{L}]+([ '\u2019\-][\p{L}]+)*" title="Use letters only, with spaces, apostrophes, or hyphens between words." required></label><label>Email address (optional)<input name="email" type="email" maxlength="190"></label><label>Contact number<input name="phone" type="tel" inputmode="numeric" minlength="11" maxlength="11" pattern="09[0-9]{9}" title="Enter 11 digits starting with 09." required></label><label>Address<input name="address" maxlength="255"></label><div class="form-actions wide-field"><button class="button primary" type="submit">Save changes</button></div>
      </form></article>
      <article class="panel profile-panel"><div class="panel-heading"><div><h2>Change password</h2><p>Use at least 8 characters for your new password.</p></div></div><form id="passwordForm" class="client-form form-grid"><label>Current password<input name="current_password" type="password" autocomplete="current-password" required></label><label>New password<input name="new_password" type="password" minlength="8" maxlength="255" autocomplete="new-password" required></label><div class="form-actions wide-field"><button class="button secondary" type="submit">Update password</button></div></form></article>
    </section>
    <footer class="portal-footer">Urban Fur Veterinary Clinic <span>·</span> Caring for every member of your family</footer>
  </main>
</body>
</html>
