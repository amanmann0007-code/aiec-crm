# Deploy AIEC CRM on Plesk Obsidian for Windows

This guide covers this repository's Laravel 8 CRM on a Windows Plesk Obsidian server. The project requires PHP (`composer.json` allows PHP 7.3 or PHP 8.x), MySQL or MariaDB, and Composer. Use the PHP executable/version configured for the domain when running Composer and Artisan commands.

## 1. Create the site and database in Plesk

1. In **Websites & Domains**, create or open the CRM subdomain (for example, `crm.example.com`). Enable PHP for it and issue an SSL certificate.
2. Create a MySQL/MariaDB database and database user in Plesk. Copy the database name, user, password, and server address shown by Plesk; do not assume the server is `127.0.0.1` on shared hosting.
3. Upload or deploy this repository into a directory outside the web root when possible, for example:

   ```text
   D:\INETPUB\VHOSTS\example.com\crm.example.com\aiec-crm
   ```

   The Git branch prepared for this deployment is `MMSEI`.
4. In the domain's **Hosting Settings**, set **Document root** to the repository's `public` folder, for example `aiec-crm\public`. Do not point the site at the repository root: it contains `.env`, application code, and other files that must not be served directly. Plesk's Laravel Toolkit can scan or register an existing Laravel app after its document root points to `public`.

Plesk for Windows serves sites through IIS. This repository includes Laravel's Apache `.htaccess`, which IIS does not use. If Laravel Toolkit or the server has not already configured URL rewriting, install/enable the IIS URL Rewrite module and put a `web.config` in `public` with this rule:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<configuration>
  <system.webServer>
    <rewrite>
      <rules>
        <rule name="Laravel front controller" stopProcessing="true">
          <match url=".*" />
          <conditions logicalGrouping="MatchAll">
            <add input="{REQUEST_FILENAME}" matchType="IsFile" negate="true" />
            <add input="{REQUEST_FILENAME}" matchType="IsDirectory" negate="true" />
          </conditions>
          <action type="Rewrite" url="index.php" />
        </rule>
      </rules>
    </rewrite>
  </system.webServer>
</configuration>
```

If Plesk rejects `web.config`, ask the hosting provider to enable URL Rewrite or configure the equivalent site-level IIS rule. Do not expose the repository root as a workaround.

## 2. Set PHP and Windows permissions

In **Websites & Domains > PHP Settings**, select a PHP version compatible with the locked Composer dependencies. Enable the PHP extensions required by Composer/Laravel; at minimum check the platform requirements with `composer check-platform-reqs --no-dev` after installing dependencies. Make sure the selected web PHP handler and the PHP CLI used for commands are compatible.

The subscription's system user must be able to write to these paths:

- `storage\` (including `storage\logs`, `storage\framework`, and `storage\app`)
- `bootstrap\cache\`
- `vendor\` while Composer is installing or updating dependencies

The IIS/PHP application identity needs read access to the application and write access to Laravel's runtime folders (`storage` and `bootstrap\cache`). Use the subscription user/application identity shown by Plesk or ask the host to repair the permissions. Do not grant `Everyone` Full Control.

## 3. Configure `.env`

Create `.env` from `.env.example` in the repository root. Keep `.env` out of Git and never upload a development machine's `.env` to production. Set production values, using the credentials supplied by Plesk:

```env
APP_NAME="AIEC CRM"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://crm.example.com

DB_CONNECTION=mysql
DB_HOST=<database-host-from-plesk>
DB_PORT=3306
DB_DATABASE=<plesk-database-name>
DB_USERNAME=<plesk-database-user>
DB_PASSWORD=<strong-database-password>

LOG_LEVEL=warning
```

Keep `APP_DEBUG=false` on a public server. Set `GOOGLE_CHAT_WEBHOOK` only if Google Chat notifications are required. Use HTTPS for `APP_URL`.

Generate `APP_KEY` once on first installation, after `.env` exists. Keep the resulting key stable on later deployments; replacing it can invalidate encrypted application data and sessions.

## 4. Install Composer dependencies

Run commands from the repository root. Prefer **Websites & Domains > Laravel > Composer** in Plesk Laravel Toolkit if it is available. Otherwise use the server's command prompt/SSH environment and the Composer/PHP CLI supplied for the selected domain PHP version.

```powershell
composer install --no-dev --optimize-autoloader --no-interaction
```

If this is a first install and `APP_KEY` is blank, generate it once:

```powershell
php artisan key:generate --force
```

For later deployments, keep the existing key.

### Fix for `ZipArchive::extractTo(...hiddeninput.exe): Permission denied`

This error means PHP opened a Composer package archive but Windows refused to create or replace the named file under `vendor\composer\...`. The `vendor` extraction target or its parent directory is commonly read-only, locked by a running worker, or writable by a different Windows identity. The path in the error is under `vendor`; clearing Composer's download cache alone usually does not fix that destination permission.

1. Put the site in maintenance mode in Laravel Toolkit (or briefly stop/recycle its IIS application pool) so requests are not loading `vendor` while it is replaced.
2. In Plesk File Manager or with the host, confirm the subscription user running Composer has **Modify** permission on the project `vendor` directory and its child files. Also confirm the same user can write `storage` and `bootstrap\cache`.
3. If a previous Composer run left a partial `vendor` tree, rename that generated `vendor` directory (for example to `vendor-old`) after the site is quiesced, then rerun the install command above. Composer will recreate it from the committed `composer.lock`. Do not delete `composer.lock` or use `composer update` as a workaround.
4. If the exact file still fails, check that it is not marked read-only, that no PHP/IIS process is holding it open, that the server has free disk space, and that antivirus/endpoint protection has not blocked or quarantined `hiddeninput.exe`. Ask the hosting provider to correct the subscription/application-pool ACL or review the security product log.
5. Only if Composer reports a cache/archive problem as well, run `composer clear-cache` and retry. This clears Composer's cache; it does not repair write access to `vendor`.

Do not solve this by running the website as Administrator or granting broad write access to all server users.

## 5. Initialize the application

Back up the database before applying migrations to an existing site. From the project root, run:

```powershell
php artisan migrate --force
php artisan storage:link
php artisan optimize:clear
php artisan config:cache
```

Do not run `migrate:fresh` on a live database; it deletes all tables. The CRM stores uploaded customer documents on Laravel's `public` disk. Verify that `public\storage` points to `storage\app\public` and that the PHP application identity can write to that storage location. If `storage:link` fails because the hosting account cannot create Windows links, ask the provider to create the link/junction supported by their IIS setup.

### First admin account

On a brand-new database only, this repository's seeder creates demo accounts. Run:

```powershell
php artisan db:seed --force
```

The seeded admin is `admin@test.com` with password `password`. Change that password immediately after the first login and remove or disable unused demo accounts. The seeder also resets the password for any matching seeded email, so do not rerun it on a live database as a routine deployment step.

## 6. Configure scheduled follow-up reminders

This CRM schedules `followups:send-reminders` every ten minutes. Laravel needs its scheduler invoked every minute. In the Plesk subscription's **Scheduled Tasks**, add a task that runs every minute under the subscription/system user:

```text
"<path-to-the-domain-PHP-CLI>" "<absolute-project-path>\artisan" schedule:run
```

Replace both placeholders with paths from the server. Test the task with Plesk's **Run Now** option and check the Laravel log if it fails. The site can otherwise run without scheduled reminders.

## 7. Verify and troubleshoot

- Open `https://crm.example.com/login`, sign in, and change the seeded admin password if you used the seeder.
- If every route except `/` returns IIS 404, check that URL Rewrite is enabled and the rule above is in `public\web.config`.
- If you see a Laravel 500 page, keep `APP_DEBUG=false` and inspect `storage\logs\laravel.log` and the domain's Plesk PHP/IIS logs.
- For `file_put_contents` or cache errors, correct Modify access for the subscription/application identity on `storage` and `bootstrap\cache`.
- For database connection errors, recheck the Plesk-provided database host, database name, username, password, and remote-access policy.
- After changing `.env`, run `php artisan config:clear` and then `php artisan config:cache`.

## References

- [Plesk Obsidian Laravel Toolkit](https://docs.plesk.com/en-US/obsidian/administrator-guide/website-management/laravel-toolkit.80010/)
- [Plesk Obsidian IIS settings](https://docs.plesk.com/en-US/obsidian/administrator-guide/website-management/websites-and-domains/hosting-settings/web-server-settings/iis-web-server-settings.72082/)
- [Plesk Obsidian scheduled tasks](https://docs.plesk.com/en-US/obsidian/administrator-guide/server-administration/scheduling-tasks.64993/)
- [Composer troubleshooting](https://getcomposer.org/doc/articles/troubleshooting.md)
- [Composer CLI](https://getcomposer.org/doc/03-cli.md)
- [Laravel 8 deployment](https://laravel.com/docs/8.x/deployment)
