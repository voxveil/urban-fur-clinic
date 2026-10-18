# Urban Fur Clinic

## Run with XAMPP

1. Start Apache and MySQL in the XAMPP Control Panel.
2. Open `http://localhost/test%20again/index.php`.
3. On the first visit, create the administrator account on the setup page, then
   sign in.

The app uses PHP MySQLi (not PDO) and creates its database and tables from
`database.sql` when it starts. By default it connects to `127.0.0.1` as `root`
with an empty password and uses the `urbanfurclinic_app` database. Set
`DB_HOST`, `DB_NAME`, `DB_USER`, and `DB_PASSWORD` in the PHP environment to use
different credentials.

Password recovery uses a six-digit code with a ten-minute expiry, limited
verification attempts, and a one-time password reset. Email delivery uses PHP
`mail()`: configure XAMPP's bundled `sendmail.ini` with your SMTP host, port,
TLS mode, username, and password; enable its `sendmail.exe -t` command with
`sendmail_path` in `php.ini`; set `MAIL_FROM` in Apache's environment; and
restart Apache. Phone delivery posts JSON `{ "to", "from", "message" }` to an
HTTPS SMS gateway URL with a Bearer token; configure `SMS_GATEWAY_URL`,
`SMS_GATEWAY_TOKEN`, and `SMS_GATEWAY_FROM` in Apache's environment. Do not put
provider credentials in source files. The recovery page reports a delivery
configuration error rather than claiming that a code was sent when delivery
isn't configured.

Administrator-created accounts need a recovery email or mobile number. The
first administrator can provide either contact during setup; existing staff
can add or update their contact details under Settings → User Accounts.

The login, administrator setup, clinic records, appointment workflow, inventory,
billing/POS checkout, dashboard, and reports are connected to MySQL through a
session-protected JSON API. The API uses MySQLi prepared statements, validates
foreign-key relationships, protects write requests with a session CSRF token,
and records checkout invoices and inventory stock changes in a database
transaction. Demo rows and photos have been removed from the clinic screens.

Clinic details and appointment hours are stored in `clinic_settings`; additional
preferences and audit history are stored in `clinic_preferences` and `audit_logs`.
Administrators can configure service categories/prices, tax (the initial rate is
0% to preserve the clinic's no-tax checkout behavior), accepted payment
methods, inventory alert defaults, appointment reminders, report defaults and
export formats, notification preferences, theme color, and backup frequency.
The configured tax rate and accepted payment methods are applied to POS checkout.
Pay Later transactions create pending invoices and appear in the outstanding-
credit report. Reports can be filtered daily, weekly, monthly, or by custom date
range. Inventory, client, and patient date filters use record creation dates;
inventory quantities are current stock, not historical snapshots.

User accounts can be assigned Admin, Vet, Receptionist, or Cashier roles. Admin
has full access; Vet has clinical and appointment access; Receptionist has
client, patient, and appointment workflows; Cashier has billing, inventory, and
reports. Existing legacy `Staff` accounts retain their original broad access.
Email/SMS preferences are saved but require a messaging provider for
delivery. Backup frequency is saved as a preference; automated backup scheduling
requires a separate backup service.

Administrators can create individual employee login accounts from the
`User Accounts` option inside Settings. Each account has a unique username, a
password of at least 12 characters, and an access role. Accounts can be edited,
deactivated/reactivated, or given a new password;
at least one active administrator is always required. Deactivated users are
denied access when they next load a page or make an API request.

Pet owners use the separate client portal at `client-auth.php` to register or
sign in with their email address or username. Client accounts are linked to a
client profile (including an existing unclaimed clinic profile with the same
email), and client portal requests are scoped to that profile's pets.
Owners can manage pet details, request appointments (which remain Pending until
the clinic confirms them), view available medical records, and update profile
details or passwords. The client portal does not use staff management pages or
the staff API. Appointment requests offer open clinic time slots for the selected
date; active requests and confirmed visits reserve their time slot. Staff can use
the month calendar or list view in Appointments, move active bookings by dragging
the appointment grip to a new date, and advance Pending requests to confirmation.

Register clients before adding their pets. Add services and inventory items
before scheduling appointments or using the point of sale. Records are stored
in the ` urbanfurclinic` database and remain after a page refresh.

The login page uses the clinic logo at `assets/clinic-logo.png`; it has no
separate animal photo or illustration below the branding.
