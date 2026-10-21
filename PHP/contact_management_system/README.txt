CONTACT MANAGEMENT SYSTEM
=========================

Technologies:
- HTML: forms and page structure
- CSS: responsive layout and visual design
- JavaScript: instant contact search
- PHP: processing forms, validation, CRUD operations
- JSON file: simple local demo storage

REQUIREMENTS
------------
- PHP 8.1+ recommended
- XAMPP, WAMP, MAMP, or PHP's built-in development server

RUN WITH XAMPP (WINDOWS)
------------------------
1. Extract the folder.
2. Copy the contact_management_system folder into:
   C:\xampp\htdocs\
3. Start Apache in the XAMPP Control Panel.
4. Open:
   http://localhost/contact_management_system/

RUN WITH PHP BUILT-IN SERVER
----------------------------
1. Open a terminal in this folder.
2. Run:
   php -S 127.0.0.1:8000
3. Visit:
   http://127.0.0.1:8000

FEATURES
--------
- Add contacts
- List contacts
- Edit contacts
- Delete contacts with confirmation
- Search contacts in the browser
- Server-side validation
- CSRF token check
- Escaped output
- JSON file storage
- Responsive interface

LEARNING PHP CONCEPTS
---------------------
1. Variables:
   $appName = "Contact Management System"; // string
   $maxNameLength = 100;                   // integer
   $isReady = true;                        // boolean
   $taxRate = 0.0;                          // float
   $sampleValues = ["PHP", 12, true, 2.5]; // array

2. Superglobals:
   $_POST, $_GET, $_SERVER, $_SESSION

3. Conditions:
   if / elseif / else

4. Loops:
   foreach

5. Functions:
   loadContacts(), saveContacts(), e(), findContactIndex()

6. Data types:
   string, int, float, bool, array
   null may also be used in PHP.

IMPORTANT LIMITATIONS AND SECURITY
----------------------------------
This is a beginner learning demo, not a production-ready CRM.
- JSON storage is not ideal for concurrent users or large datasets.
- The data folder must be writable by the PHP process.
- Add login and authorization before hosting real contact information.
- Do not store real personal data in a public demo.
- For a real application, use MySQL with PDO or Laravel/Eloquent,
  proper access control, backups, and private storage where needed.
- Use HTTPS in production.
- Keep secrets out of Git.
