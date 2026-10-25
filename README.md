# InvenTrack — Inventory Sales & Tracker System

A complete, beginner-friendly full-stack web application for inventory management built with PHP, MySQL, HTML, CSS, and JavaScript.

---

## 📁 Folder Structure

```
inventory_system/
├── index.php               ← Login / Register page
├── app.php                 ← Main application (dashboard, all pages)
├── database.sql            ← Database setup script
├── README.md               ← This file
│
├── includes/
│   └── config.php          ← DB connection, session, helpers
│
├── api/
│   ├── auth.php            ← Login, register, logout endpoints
│   ├── products.php        ← Product CRUD endpoints
│   └── sales.php           ← Sales recording, dashboard data
│
└── assets/
    ├── css/
    │   └── style.css       ← All styles (dark theme, responsive)
    └── js/
        └── app.js          ← All frontend logic (Fetch API)
```

---

## 🛠️ Requirements

- **XAMPP** (PHP 8.0+, Apache, MySQL) — Download from https://www.apachefriends.org
- A modern web browser (Chrome, Firefox, Edge)

---

## 🚀 Step-by-Step Setup with XAMPP

### Step 1 — Install XAMPP
1. Download and install XAMPP from https://www.apachefriends.org
2. Open **XAMPP Control Panel**
3. Click **Start** next to **Apache** and **MySQL**

### Step 2 — Copy Project Files
1. Navigate to your XAMPP installation folder:
   - **Windows**: `C:\xampp\htdocs\`
   - **Mac/Linux**: `/Applications/XAMPP/htdocs/` or `/opt/lampp/htdocs/`
2. Copy the entire project folder into `htdocs/`. You can keep its current name or rename it to `inventory_system`.

Your final path should look like:
```
C:\xampp\htdocs\inventory_system_fixed\
```

### Step 3 — Set Up the Database
1. Open your browser and go to: http://localhost/phpmyadmin
2. Click **"New"** in the left sidebar to create a new database
   - OR click the **SQL** tab at the top
3. Click **Import** in the top menu
4. Click **Choose File** and select the `database.sql` file in the project folder
5. Click **Go** / **Import**

The database `inventory_db` will be created with all tables and sample data.

**Alternative (using SQL tab):**
1. In phpMyAdmin, click the **SQL** tab
2. Paste the entire contents of `database.sql`
3. Click **Go**

### Step 4 — Configure Database (if needed)
Open `includes/config.php` and update if your MySQL credentials differ:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');       // Your MySQL username
define('DB_PASS', '');           // Your MySQL password (empty by default in XAMPP)
define('DB_NAME', 'inventory_db');
```

### Step 5 — Run the Application
Open your browser and go to the matching folder name. For example:
```
http://localhost/inventory_system_fixed/
```

### Step 6 — Log In
Use the pre-created admin account:
- **Email**: `admin@inventory.com`
- **Password**: `password`

---

## 🔑 Default Login Credentials

| Field    | Value                  |
|----------|------------------------|
| Email    | admin@inventory.com    |
| Password | password               |

> ⚠️ Change the password after first login in a production environment!

---

## ✨ Features Overview

### 🏠 Dashboard
- Real-time stats: total products, total revenue, total sales, low-stock count, today's revenue
- Revenue bar chart (last 7 days) powered by Chart.js
- Top 5 products doughnut chart
- Recent 8 transactions table
- Low-stock alert banner

### 📦 Product Management
- Add new products with name, category, price, quantity, and alert threshold
- Edit any product's details
- Delete products (cascades to sales records)
- Search by name or filter by category in real-time
- Stock badges (OK / Low)

### 🛒 Sales Management
- Record a sale (select product → enter quantity → customer name → date)
- Auto-deducts stock on sale
- Live price calculation
- Generates a printable receipt after each sale
- Filter sales by date range and search
- Export all/filtered sales as CSV

### 📊 Inventory Tracker
- Visual stock level bar for every product
- Status badges: In Stock / Low Stock / Out of Stock
- Summary counters (total, good, low, out)
- Per-product stock history (adds, sales, adjustments)

---

## 🔒 Security Features

| Feature                  | Implementation                              |
|--------------------------|---------------------------------------------|
| Password hashing         | `password_hash()` with bcrypt (cost 12)     |
| SQL injection prevention | PDO prepared statements (all queries)        |
| Session management       | PHP native sessions with `session_start()`  |
| XSS prevention           | `htmlspecialchars()` server-side, `escHtml()` client-side |
| Input validation         | Server-side validation on every endpoint    |
| Auth gate                | `requireLogin()` called at top of every API  |
| Race condition prevention| MySQL `FOR UPDATE` transaction lock for sales|

---

## 🌐 API Endpoints Reference

### Auth (`api/auth.php`)
| Method | Action     | Description            |
|--------|------------|------------------------|
| POST   | login      | Authenticate user      |
| POST   | register   | Create new account     |
| GET    | logout     | Destroy session        |

### Products (`api/products.php`)
| Method | Action       | Description             |
|--------|--------------|-------------------------|
| GET    | list         | Get all products        |
| GET    | get          | Get single product      |
| POST   | add          | Create product          |
| POST   | edit         | Update product          |
| POST   | delete       | Delete product          |
| GET    | categories   | List unique categories  |
| GET    | stock_history| Get stock change log    |

### Sales (`api/sales.php`)
| Method | Action     | Description                   |
|--------|------------|-------------------------------|
| GET    | list       | Get all sales (with filter)   |
| POST   | record     | Record a new sale             |
| GET    | dashboard  | Get dashboard statistics      |
| GET    | export     | Download CSV export           |

---

## 🐛 Troubleshooting

**"Database connection failed"**
→ Make sure MySQL is running in XAMPP Control Panel.
→ Check credentials in `includes/config.php`.

**Blank page / 500 error**
→ Enable PHP error display: add `ini_set('display_errors', 1);` temporarily to `config.php`.
→ Check Apache error log in XAMPP.

**"Page not found" on login**
→ Open the URL using the project folder name inside `htdocs` (for example, `http://localhost/inventory_system_fixed/`).

**Charts not showing**
→ Check browser console for Chart.js CDN errors.
→ Ensure internet access for CDN or download Chart.js locally.

---

## 🔧 Customisation Tips

- **Change low stock threshold per product**: Edit the "Alert At" field when adding/editing a product.
- **Add new pages**: Create a new `page-xxx` div in `app.php`, add a nav item, and handle it in `showPage()` in `app.js`.
- **Change currency**: Replace `₹` with your currency symbol in `formatRupee()` in `app.js`.
- **Add more categories**: Update the `<datalist>` in `app.php` or add them via the product form.

---

## 📜 Tech Stack

| Layer      | Technology                      |
|------------|---------------------------------|
| Frontend   | HTML5, CSS3, Vanilla JavaScript |
| Charts     | Chart.js 4.x (CDN)              |
| Backend    | PHP 8.0+                        |
| Database   | MySQL 5.7+ / MariaDB            |
| Server     | Apache (via XAMPP)              |
| Fonts      | Google Fonts (Space Mono + DM Sans) |

---

*Built as a beginner-friendly, ready-to-run local application. No frameworks, no build tools — just drop it in XAMPP and go.*
