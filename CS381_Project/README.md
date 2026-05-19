CS381 Project 
YIC Lost & Found Portal 
By : Norah Alhosain & Mariah Alharbi

A web-based Lost & Found system for Yanbu Industrial College (YIC). Students can report lost items and post found items through the portal. Administrators can manage all items, messages, and users through an admin panel.


Setup Instructions

Requirements
- PHP 8.0 or higher
- MySQL 8.0 or higher
- Apache with a local server environment (XAMPP or Laragon)

Steps

1. Copy the project folder into your web server root:
   - XAMPP: `C:/xampp/htdocs/CS381_Project/`
   - Laragon: `C:/laragon/www/CS381_Project/`

2. Import the database using phpMyAdmin or the MySQL CLI:
   mysql -u root -p < database.sql
   This will create the yic_lost_found database with sample data.

3. Configure the database connection — open `includes/db.php` and update the credentials to match your local setup:

   $host= 'localhost';
   $dbname= 'yic_lost_found';
   $username = 'root';
   $password = 'your_password';
   

4. Open the portal in your browser:
   http://localhost/CS381_Project/Pages/index.php
 



Login Credentials

All (( sample )) accounts use the same password: `password`

| Role    | Name            | Email                   |
|---------|-----------------|-------------------------|
| Admin   | Admin User      | admin@rcjy.edu.sa       |
| Student | Norah AlHosain  | 4311085@rcjy.edu.sa     |
| Student | Mariah AlHarbi  | 4311346@rcjy.edu.sa     |



Features

Public (no login required)
- Browse all lost and found items on the home page
- Search items by keyword
- Filter items by category or type (lost/found)
- View full item details

Student (login required)
- Report a lost item with photo upload
- Post a found item with photo upload
- Send a contact message to an item reporter
- Dashboard: view, edit, delete, and resolve your own items
- Profile settings: update name, email, and password

 Admin
- Admin panel with overview statistics
- Manage all lost and found items (edit, delete, change status)
- View and manage all contact messages (mark read, delete)
- Manage all users (change role, delete account)



Security 

CSRF Protection: All POST forms include a hidden CSRF token verified server-side via `verifyCsrf()`. 
GET-based state changes (delete, toggle status) require a CSRF token appended to the URL. 
XSS Prevention: All user-supplied output is escaped using `e()` (`htmlspecialchars` with `ENT_QUOTES, UTF-8`).
SQL Injection: All database queries use PDO prepared statements with parameterised values. 
File Upload Validation :Uploaded images are validated by MIME type using `finfo`.
Password Hashing: Passwords are stored using `password_hash()` with `PASSWORD_DEFAULT` (bcrypt).
Session Security: `session_regenerate_id(true)` is called on every successful login and registration to prevent session fixation. 
Access Control: `requireLogin()` and `requireAdmin()` guard all protected pages. Ownership is checked before any edit or delete action. 


File Structure

CS381_Project/
├── database.sql  # Database schema and sample data
├── README.md 
│
├── includes/
│   ├── db.php # PDO database connection
│   └── auth.php # Auth helpers, XSS escaping, CSRF functions
│
├── Pages/
│   ├── index.php  # Home page — browse and search items
│   ├── report_lost.php  # Form to report a lost item
│   ├── report_found.php # Form to post a found item
│   ├── item_detail.php # Item detail page with contact form
│   ├── edit_item.php # Edit an item
│   ├── delete_item.php # Delete an item 
│   ├── toggle_status.php # Mark item resolved/active 
│   ├── dashboard.php # Student dashboard (my items, profile)
│   ├── admin.php # Admin panel (items, messages, users)
│   ├── login.php # Login page
│   ├── register.php # Registration page
│   ├── logout.php # Destroys session and redirects
│   └── YIC_Logo.png 
│
├── Style/
│   └── style.css     
│
├── JS/
│   └── main.js # Frontend JavaScript
│
└── uploads/
    └── items/ # User-uploaded item images 



Database 

| Table         | Description                           |
|---------------|---------------------------------------|
| `users`       | Registered users (`student` / `admin`)|
| `lost_items`  | Lost item reports                     |
| `found_items` | Found item posts                      |
| `messages`    | Contact messages                      |


Technologies Used

- Backend: PHP 8, PDO (MySQL)
- Database: MySQL 8
- Frontend: HTML5, CSS3, JavaScript
- Server: Apache (XAMPP / WAMP)