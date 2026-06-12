# AIEC CRM

AIEC CRM is a Laravel 8 customer relationship management system for immigration and education consultancy workflows. It manages leads, customers, counselor assignment, follow-ups, process timelines, remarks, fees, documents, bell notifications, Google Chat notifications, and public customer self-entry forms.

## Tech Stack

- PHP `^7.3|^8.0`
- Laravel `^8.75`
- MySQL or MariaDB
- Bootstrap UI with local vendor assets
- Laravel Sanctum for API authentication
- Google Chat webhook integration

## Main Features

- Role-based CRM for `admin`, `director`, `receptionist`, `counselor`, and `telecaller`.
- Customer and lead management with assignment to counselor and/or telecaller.
- Public customer entry form at `/customer-entry` for customers to submit their own details without logging in.
- Receptionist review queue called **Tab Entries** for converting public submissions into assigned customers.
- Telecaller lead flow with **Visiting Client** queue.
- New case queue with badge counters.
- Follow-up dashboard and My Follow-Ups pages with overdue/today/upcoming separation.
- Customer remarks chat with `@user` tagging, status updates, and latest remarks first.
- Custom bell notifications with sound and customer deep links.
- Google Chat notifications with admin-controlled checkbox settings.
- Admin-managed process timelines by visa type.
- Customer process tiles with completion status and Dropout automation.
- Customer document uploads with drag-and-drop and mandatory document names.
- Customer fees ledger with collected, refund, and net total calculations.
- Dashboard status/process charts, date filters, quick ranges, and admin-only fee totals.
- Global quick search and deep search across customer details, remarks, status, and process timelines.

## Role Overview

### Admin

- Full dashboard visibility.
- User management.
- Process timeline management.
- Qualification management.
- Google Chat notification settings.
- Customer create/edit access.
- Activity logs.
- Fee totals on dashboard.

### Director

- Customer visibility and process timeline controls.
- Can reopen completed process tiles.
- Can create customers.

### Receptionist

- Can create customers.
- Reviews **Tab Entries** from the public customer entry form.
- Reviews **Visiting Client** leads and marks them Ready.
- Sees dashboard stats broadly like admin, without admin-only settings.

### Counselor

- Sees assigned customers.
- Can edit assigned customer info.
- Can add remarks, fees, follow-ups, documents, and complete process tiles.

### Telecaller

- Can add leads.
- Lead status options are limited to `will visit` and `interested`.
- Sees own **Visiting Client** leads without the Ready button.
- Does not see fee-entry controls.

## Local Setup

1. Install PHP dependencies:

```bash
composer install
```

2. Copy the environment file:

```bash
copy .env.example .env
```

3. Generate the app key:

```bash
php artisan key:generate
```

4. Configure database settings in `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=aiec_crm
DB_USERNAME=root
DB_PASSWORD=
```

5. Configure company branding in [config/company.php](/C:/wamp64/www/aiec-crm/config/company.php):

```php
'name' => 'AIEC Institute',
'crm_name' => 'AIEC CRM',
'tagline' => 'Customer Relationship Management',
'subtitle' => 'Study Abroad Lally Infosys',
'logo_path' => 'images/aiec-logo.png',
'icon_path' => 'images/aiec-icon.svg',
'address_lines' => [
    'Address line 1',
    'Address line 2',
],
'phone' => '',
'email' => '',
'website' => 'https://example.com',
```

Logo and icon paths are relative to the Laravel `public` directory.

6. Run migrations:

```bash
php artisan migrate
```

7. Create the storage symlink:

```bash
php artisan storage:link
```

8. Build/refresh app caches after configuration changes:

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Running Locally

If the project is served through WAMP under `C:\wamp64\www\aiec-crm`, use:

```text
http://localhost/aiec-crm/public
```

If your virtual host points directly to the Laravel `public` directory, use:

```text
http://localhost
```

## Public Customer Entry Link

Customers can submit their own details without logging in:

```text
http://localhost/aiec-crm/public/customer-entry
```

With a virtual host pointed to `public`:

```text
http://localhost/customer-entry
```

Submitted entries appear for receptionist under **Tab Entries**. Receptionist can click **Submit**, review/edit the prefilled customer form, and assign counselor or telecaller.

## Google Chat Notifications

Set the webhook in `.env`:

```env
GOOGLE_CHAT_WEBHOOK=
GOOGLE_CHAT_VERIFY_SSL=false
```

Admin can control what is allowed to send to Google Chat from:

```text
Google Chat
```

Available settings include:

- Remarks and status updates
- New case assigned
- Customer updated
- User tagged in remark
- Follow-up reminder
- Other bell notifications
- Activity logs

Unchecked notification types still remain inside CRM bell notifications, but they do not post to Google Chat.

## Realtime Notifications

The CRM uses a custom polling-based notification system rather than Pusher. Bell notifications:

- Show unread badge counts.
- Play a notification sound.
- Open the related customer page when clicked.
- Can be mirrored to Google Chat based on admin settings.

## Customer Process Timelines

Admin can configure process tiles per visa type from **Process Timelines**.

Process behavior:

- Counselor, director, and admin can complete process tiles.
- Counselor completion asks for confirmation.
- Only admin and director can reopen a completed tile.
- `plan drop` and `not eligible` automatically complete the `Dropout` process.
- Dashboard process chart counts the latest process stage per customer, including `Not started`.

## Fees

Customer pages include a **Fees** section under remarks:

- Any non-telecaller user can add fees.
- Entry requires amount and purpose.
- Purpose containing `refund` is treated as a refund.
- Refunds display with a minus sign and subtract from totals.
- Fee/refund entries also appear in the remarks chat.
- Admin dashboard shows date-filtered collected, refund, and net fees.

## Documents

Customer documents support drag-and-drop upload.

Allowed file types:

- JPG
- JPEG
- PNG
- PDF

Each upload requires a document name for future retrieval.

## Useful Artisan Commands

```bash
php artisan migrate
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan test
```

## Testing

Run:

```bash
php artisan test
```

Current smoke tests include:

- Unit example test
- Root redirects guest users to login

## Notes

- Do not commit `.env`.
- Keep `APP_URL` aligned with your WAMP or virtual host URL.
- Run `php artisan config:cache` after changing `.env` or `config/*.php`.
- Run `php artisan route:cache` after adding or changing routes.
- Run `php artisan view:cache` after Blade changes when deploying locally.
