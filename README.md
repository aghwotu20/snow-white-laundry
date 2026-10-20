# Snow White Laundry — Official Website & Multi-Role Portal (Revision 1)

Clean, responsive, cPanel-ready PHP + MySQL web platform built for **Snow White Laundry** (`No 3 Kess Rubber Factory Street` | `info@snowwhitelaundry.com.ng` | `07062300865`).

---

## Key Features Included in Revision 1

1. **Public Website (Built to Proposal Design & Brand Palette)**
   - **Home (`index.php`)**: Crisp White, Fresh Ice Blue, Deep Navy & Blue Accent design, click-to-call (`07062300865`), WhatsApp quick chat, featured laundry services, 4-stage garment care workflow, and FAQs.
   - **Services (`services.php`)**: Complete database-driven laundry, dry cleaning, pressing, native attire, and home linen service catalogue.
   - **Pricing & Instant Online Booking (`pricing.php`)**: Real-time Naira cost estimator, doorstep pickup vs. branch drop-off scheduler, and instant **Paystack** checkout or Pay-on-Pickup option.
   - **About Us (`about.php`)**, **Customer FAQs (`faq.php`)**, **Live Order Tracker (`track.php`)**, and **Contact / Service Quote Form (`contact.php`)**.

2. **Unified Role-Based Portal Login (`login.php` & `register.php`)**
   - Dedicated login tabs & dashboards for **Website Visitors / Customers**, **Staff Members**, and **Administrators**.
   - **Passport Photograph & Personal Profile** inside **every** role's dashboard (`visitor/profile.php`, `staff/profile.php`, `admin/profile.php`) with instant preview and server-side MIME/image validation.

3. **Administrator Control Dashboard (`admin/`)**
   - **Website Settings (`admin/settings.php`)**:
     - **Website Logo & Favicon Upload** (live preview + reset to default)
     - **Paystack Integration & Credentials** (`Enable/Disable`, `Test` vs `Live` mode, `Public Key`, `Secret Key`, `Currency`, auto-generated Callback & Webhook URLs)
     - **Maintenance Mode Toggle Button** (instant 1-click switch in both top bar and Website Settings + custom maintenance message while allowing Admins to continue previewing the live site)
   - **Staff Management (`admin/staff.php`)**: Add staff accounts, **Suspend Staff** (blocks login & terminates active sessions immediately), and **Reactivate Staff**.
   - **Orders, Visitors, Services & Quotes Management**.

4. **Manual cPanel MySQL Database Import (`database/snow_white_laundry.sql`)**
   - Ready to import straight into **cPanel &rarr; phpMyAdmin** with zero root-privilege errors.
   - Full step-by-step cPanel deployment guide included in [`DEPLOY-TO-CPANEL.md`](DEPLOY-TO-CPANEL.md).
