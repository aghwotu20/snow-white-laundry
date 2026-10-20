# Deploy Snow White Laundry to Shared cPanel Hosting (Step-by-Step Guide)

This application is built specifically for **zero-friction cPanel deployment** (PHP 8.0+ & MySQL/MariaDB on Apache with `.htaccess`). **No SSH, Composer, or Node.js terminal commands are required!**

---

## Step 1: Create Your MySQL Database & User in cPanel
1. Log into your **cPanel** control panel.
2. Click **MySQL® Database Wizard** (or **MySQL® Databases**).
3. Create a new database (for example: `cpaneluser_snowwhite`).
4. Create a database user (for example: `cpaneluser_admin`) with a strong password.
5. Add the user to the database and tick **ALL PRIVILEGES**, then click **Make Changes**.

---

## Step 2: Manually Upload the SQL Database File in phpMyAdmin
1. Open **cPanel &rarr; phpMyAdmin**.
2. Click your newly created database (`cpaneluser_snowwhite`) on the left sidebar.
3. Click the **Import** tab at the top.
4. Click **Choose File** and select:
   - `database/snow_white_laundry.sql`
5. Scroll down and click **Import / Go**. All 9 tables, services, FAQs, website settings, and default portal accounts will load automatically!

---

## Step 3: Upload Website Files to `public_html`
1. Open **cPanel &rarr; File Manager** and navigate into **`public_html`** (ensure *Settings &rarr; Show Hidden Files (dotfiles)* is enabled so `.htaccess` is included).
2. Upload **`snow-white-laundry-cpanel-revision-1.zip`** into `public_html`, right-click it, and choose **Extract** (then delete the `.zip` archive).
3. Ensure the `uploads/` folder (`uploads/branding` and `uploads/passports`) has **`0755`** permissions so Passport Photographs, Website Logos, and Favicons save smoothly.

---

## Step 4: Connect Your cPanel Database Credentials (Choose Option A or B)

### Option A — Instant Browser Setup Wizard (Easiest!)
1. Visit `https://snowwhitelaundry.com.ng/install.php` in your browser.
2. Enter `localhost`, your cPanel Database Name, Database Username, and Password, then click **Test Connection & Launch Website**.
3. Once connected, `install.php` automatically locks itself for security!

### Option B — Manual Config File Edit
1. In **cPanel File Manager**, open `config/config.php` and update:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_PORT', '3306');
   define('DB_NAME', 'yourcpanel_dbname');
   define('DB_USER', 'yourcpanel_dbuser');
   define('DB_PASS', 'your_strong_password');
   ```

---

## Step 5: Log In & Go Live!

Visit `https://snowwhitelaundry.com.ng/login.php`:

| Portal Role | Default Email | Default Password |
| :--- | :--- | :--- |
| **Administrator** | `admin@snowwhitelaundry.com.ng` | `Admin@123` |
| **Staff Member #1** | `staff@snowwhitelaundry.com.ng` | `Staff@123` |
| **Staff Member #2** | `dispatch@snowwhitelaundry.com.ng` | `Staff@123` |
| **Visitor / Customer** | `customer@snowwhitelaundry.com.ng` | `Customer@123` |

> **Important Security Note:** Log into the **Admin Dashboard &rarr; My Profile & Photo** immediately after deployment to change the default password, upload your official brand Logo & Favicon in **Website Settings**, and paste your live **Paystack Public & Secret Keys** (`pk_live_...` and `sk_live_...`).
