# Migration Guide & aaPanel Troubleshooting

If you are moving the GEMBOK application from regular hosting or XAMPP to a VPS server using **aaPanel**, you may encounter some database or system issues. Here are solutions for the most common problems.

## 1. Disable MySQL Strict Mode (IMPORTANT)
aaPanel by default enables very strict "Strict Mode" for data formats. This often causes database queries to fail.

**Solution:**
1. Log in to **aaPanel Dashboard**.
2. Click the **App Store** menu on the left side.
3. Find the **MySQL** or **MariaDB** you are using, click the **Setting** button.
4. Click the **Configuration** tab.
5. Find the line containing `sql-mode` or `sql_mode`.
6. Change its value to:
   ```ini
   sql-mode=NO_ENGINE_SUBSTITUTION
   ```
   *If this line doesn't exist, you can add it under the `[mysqld]` section.*
7. Click **Save**, then move to the **Service** tab and click **Restart**.

## 2. Case Sensitivity Settings (Table Names)
On Linux (aaPanel), database table names are *case-sensitive*. If your application calls table `Settings` but in the database it's named `settings`, it will cause an error.

**Solution:**
1. In the **Configuration** tab of MySQL aaPanel (as in the steps above).
2. Add this line under `[mysqld]`:
   ```ini
   lower_case_table_names=1
   ```
3. Click **Save** and **Restart** the MySQL service.
   *Note: It is highly recommended to do this BEFORE importing your SQL database.*

## 3. Required PHP Extensions
This application requires several PHP modules to run smoothly. Make sure the following modules are installed:

1. Click **App Store** -> Select the **PHP** you are using (e.g., PHP 8.1/8.2) -> **Setting**.
2. Click the **Install extensions** tab.
3. Make sure the following extensions have green checkmarks (installed):
   - `pdo_mysql` (Required for database)
   - `mysqli`
   - `curl` (Required for MikroTik & WhatsApp API)
   - `gd` (Important for image/captcha processing)
   - `intl`
   - `fileinfo`

## 4. Folder Permissions
The application needs to write log and cache files. If permissions are incorrect, the application may hang or show blank pages.

**Solution:**
Use the aaPanel terminal or File Manager, run the following command in the application root folder:
```bash
# Give permissions to logs folder
chmod -R 775 logs/
chmod -R 775 includes/

# Make sure owner is www (aaPanel web server user)
chown -R www:www .
```

## 5. Check Errors Through Logs
If the application is still showing errors/blank pages, don't guess. Check the provided logs:

1. **Application Logs:** Check files `logs/php_error.log` and `logs/db_error.log`.
2. **aaPanel Logs:** Check in the **Site** menu -> Click domain name -> **Site logs**.

---
*Created to help with smooth migration of GEMBOK ISP Management application.*
