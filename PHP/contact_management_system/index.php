<?php
// Contact Management System
// Learning goals: PHP syntax, variables, data types, arrays, functions, conditions,
// loops, form handling, validation, sessions, and safe HTML output.

session_start();

// ---------- PHP VARIABLES & DATA TYPES ----------
$appName = "Contact Management System"; // string
$maxNameLength = 100;                   // integer
$isReady = true;                        // boolean
$taxRate = 0.0;                         // float (example only)
$sampleValues = ["PHP", 12, true, 2.5]; // indexed array with mixed types

// Create a CSRF token for form submissions.
if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

// Store records in a JSON file for a beginner-friendly local demo.
// For a production app, use a database such as MySQL with PDO.
$dataFile = __DIR__ . "/data/contacts.json";

if (!file_exists($dataFile)) {
    file_put_contents($dataFile, "[]", LOCK_EX);
}

// ---------- FUNCTIONS ----------
function e($value): string {
    // Escape output to reduce XSS risk.
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8");
}

function loadContacts(string $file): array {
    $json = file_get_contents($file);
    $records = json_decode($json ?: "[]", true);
    return is_array($records) ? $records : [];
}

function saveContacts(string $file, array $contacts): bool {
    $json = json_encode(array_values($contacts), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    return file_put_contents($file, $json, LOCK_EX) !== false;
}

function findContactIndex(array $contacts, string $id): int {
    foreach ($contacts as $index => $contact) {
        if (($contact["id"] ?? "") === $id) {
            return $index;
        }
    }
    return -1;
}

$contacts = loadContacts($dataFile);
$message = "";
$messageType = "success";
$editingContact = null;

// ---------- HANDLE CREATE / UPDATE / DELETE ----------
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $postedToken = $_POST["csrf_token"] ?? "";

    if (!is_string($postedToken) || !hash_equals($_SESSION["csrf_token"], $postedToken)) {
        http_response_code(403);
        exit("Invalid security token. Refresh the page and try again.");
    }

    $action = $_POST["action"] ?? "";

    if ($action === "save") {
        $id = trim((string)($_POST["id"] ?? ""));
        $name = trim((string)($_POST["name"] ?? ""));
        $email = trim((string)($_POST["email"] ?? ""));
        $phone = trim((string)($_POST["phone"] ?? ""));
        $company = trim((string)($_POST["company"] ?? ""));
        $notes = trim((string)($_POST["notes"] ?? ""));

        // Validation: do not trust browser-only validation.
        if ($name === "" || mb_strlen($name) > $maxNameLength) {
            $message = "Please enter a name (maximum 100 characters).";
            $messageType = "error";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = "Please enter a valid email address.";
            $messageType = "error";
        } elseif ($phone !== "" && !preg_match('/^[0-9+\-\s()]{7,25}$/', $phone)) {
            $message = "Please enter a valid phone number.";
            $messageType = "error";
        } else {
            $now = date("c");

            if ($id !== "") {
                $index = findContactIndex($contacts, $id);

                if ($index === -1) {
                    $message = "Contact not found.";
                    $messageType = "error";
                } else {
                    $contacts[$index]["name"] = $name;
                    $contacts[$index]["email"] = $email;
                    $contacts[$index]["phone"] = $phone;
                    $contacts[$index]["company"] = $company;
                    $contacts[$index]["notes"] = $notes;
                    $contacts[$index]["updated_at"] = $now;

                    if (saveContacts($dataFile, $contacts)) {
                        header("Location: index.php?status=updated");
                        exit;
                    }
                    $message = "Could not save changes. Check data folder permissions.";
                    $messageType = "error";
                }
            } else {
                $newContact = [
                    "id" => bin2hex(random_bytes(8)), // string ID
                    "name" => $name,
                    "email" => $email,
                    "phone" => $phone,
                    "company" => $company,
                    "notes" => $notes,
                    "created_at" => $now
                ];

                $contacts[] = $newContact; // append array item

                if (saveContacts($dataFile, $contacts)) {
                    header("Location: index.php?status=created");
                    exit;
                }
                $message = "Could not save contact. Check data folder permissions.";
                $messageType = "error";
            }
        }
    } elseif ($action === "delete") {
        $id = trim((string)($_POST["id"] ?? ""));
        $index = findContactIndex($contacts, $id);

        if ($index >= 0) {
            array_splice($contacts, $index, 1);

            if (saveContacts($dataFile, $contacts)) {
                header("Location: index.php?status=deleted");
                exit;
            }
            $message = "Could not delete contact.";
            $messageType = "error";
        } else {
            $message = "Contact not found.";
            $messageType = "error";
        }
    }
}

// Show confirmation after redirect (Post/Redirect/Get pattern).
$status = $_GET["status"] ?? "";
$statusMessages = [
    "created" => "Contact added successfully.",
    "updated" => "Contact updated successfully.",
    "deleted" => "Contact deleted successfully."
];
if (isset($statusMessages[$status])) {
    $message = $statusMessages[$status];
}

// Load contact for editing.
$editId = trim((string)($_GET["edit"] ?? ""));
if ($editId !== "") {
    $editIndex = findContactIndex($contacts, $editId);
    if ($editIndex >= 0) {
        $editingContact = $contacts[$editIndex];
    }
}

// Sort newest-first (string timestamps are ISO 8601).
usort($contacts, function ($a, $b) {
    return strcmp($b["created_at"] ?? "", $a["created_at"] ?? "");
});

$totalContacts = count($contacts); // integer
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($appName) ?></title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<header class="topbar">
    <div class="container topbar-inner">
        <a class="brand" href="index.php"><span class="brand-mark">C</span> ContactDesk</a>
        <span class="top-note">Simple contact organizer</span>
    </div>
</header>

<main class="container">
    <section class="hero">
        <div>
            <p class="eyebrow">YOUR PERSONAL ADDRESS BOOK</p>
            <h1>Keep your connections <span>organized.</span></h1>
            <p class="hero-copy">Save, search, edit, and manage your contacts in one simple place.</p>
        </div>
        <div class="hero-stat">
            <span class="stat-number"><?= e($totalContacts) ?></span>
            <span class="stat-label">Total contacts</span>
        </div>
    </section>

    <?php if ($message !== ""): ?>
        <div class="alert <?= e($messageType) ?>" role="status"><?= e($message) ?></div>
    <?php endif; ?>

    <div class="layout">
        <section class="panel form-panel">
            <div class="panel-heading">
                <div>
                    <p class="eyebrow"><?= $editingContact ? "UPDATE RECORD" : "NEW RECORD" ?></p>
                    <h2><?= $editingContact ? "Edit contact" : "Add a contact" ?></h2>
                </div>
                <span class="heading-icon"><?= $editingContact ? "✎" : "+" ?></span>
            </div>

            <form method="post" action="index.php" id="contactForm">
                <input type="hidden" name="csrf_token" value="<?= e($_SESSION["csrf_token"]) ?>">
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="id" value="<?= e($editingContact["id"] ?? "") ?>">

                <label for="name">Full name <span class="required">*</span></label>
                <input id="name" name="name" type="text" maxlength="100" required
                       placeholder="e.g. Asha Patil" value="<?= e($editingContact["name"] ?? "") ?>">

                <label for="email">Email address <span class="required">*</span></label>
                <input id="email" name="email" type="email" required maxlength="254"
                       placeholder="asha@example.com" value="<?= e($editingContact["email"] ?? "") ?>">

                <div class="form-row">
                    <div>
                        <label for="phone">Phone number</label>
                        <input id="phone" name="phone" type="tel" maxlength="25"
                               placeholder="+91 98765 43210" value="<?= e($editingContact["phone"] ?? "") ?>">
                    </div>
                    <div>
                        <label for="company">Company</label>
                        <input id="company" name="company" type="text" maxlength="100"
                               placeholder="Company name" value="<?= e($editingContact["company"] ?? "") ?>">
                    </div>
                </div>

                <label for="notes">Notes</label>
                <textarea id="notes" name="notes" rows="3" maxlength="1000"
                          placeholder="Where did you meet? Any useful details?"><?= e($editingContact["notes"] ?? "") ?></textarea>

                <div class="form-actions">
                    <button class="btn primary" type="submit"><?= $editingContact ? "Save changes" : "Add contact" ?></button>
                    <?php if ($editingContact): ?>
                        <a class="btn secondary" href="index.php">Cancel</a>
                    <?php endif; ?>
                </div>
                <p class="form-footnote"><span class="required">*</span> Required fields</p>
            </form>
        </section>

        <section class="panel list-panel">
            <div class="list-heading">
                <div>
                    <p class="eyebrow">YOUR DIRECTORY</p>
                    <h2>Contacts <span class="count-pill"><?= e($totalContacts) ?></span></h2>
                </div>
                <div class="search-wrap">
                    <span aria-hidden="true">⌕</span>
                    <input type="search" id="searchInput" placeholder="Search contacts..." aria-label="Search contacts">
                </div>
            </div>

            <?php if (count($contacts) === 0): ?>
                <div class="empty-state" id="emptyState">
                    <div class="empty-icon">◎</div>
                    <h3>No contacts yet</h3>
                    <p>Add your first contact using the form.</p>
                </div>
            <?php else: ?>
                <div class="contact-list" id="contactList">
                    <?php foreach ($contacts as $contact): ?>
                        <article class="contact-card"
                            data-search="<?= e(strtolower(($contact["name"] ?? "") . " " . ($contact["email"] ?? "") . " " . ($contact["phone"] ?? "") . " " . ($contact["company"] ?? ""))) ?>">
                            <div class="avatar" aria-hidden="true"><?= e(strtoupper(mb_substr($contact["name"] ?? "?", 0, 1))) ?></div>
                            <div class="contact-main">
                                <h3><?= e($contact["name"] ?? "") ?></h3>
                                <a class="contact-email" href="mailto:<?= e($contact["email"] ?? "") ?>"><?= e($contact["email"] ?? "") ?></a>
                                <?php if (!empty($contact["phone"])): ?>
                                    <p class="contact-meta"><?= e($contact["phone"]) ?></p>
                                <?php endif; ?>
                                <?php if (!empty($contact["company"])): ?>
                                    <p class="contact-meta"><?= e($contact["company"]) ?></p>
                                <?php endif; ?>
                                <?php if (!empty($contact["notes"])): ?>
                                    <p class="contact-notes"><?= e($contact["notes"]) ?></p>
                                <?php endif; ?>
                            </div>
                            <div class="contact-actions">
                                <a class="icon-btn edit-btn" href="index.php?edit=<?= e($contact["id"]) ?>" aria-label="Edit <?= e($contact["name"]) ?>" title="Edit">✎</a>
                                <form method="post" action="index.php" class="delete-form" onsubmit="return confirm('Delete this contact?');">
                                    <input type="hidden" name="csrf_token" value="<?= e($_SESSION["csrf_token"]) ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= e($contact["id"]) ?>">
                                    <button class="icon-btn delete-btn" type="submit" aria-label="Delete <?= e($contact["name"]) ?>" title="Delete">×</button>
                                </form>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
                <p class="no-results" id="noResults" hidden>No matching contacts found.</p>
            <?php endif; ?>
            <div class="list-footer">Contact details are stored locally in this demo.</div>
        </section>
    </div>

    <section class="learning-box">
        <div class="learning-symbol">PHP</div>
        <div>
            <h3>Learn while you build</h3>
            <p><strong>String:</strong> name &nbsp; <strong>Integer:</strong> totalContacts &nbsp; <strong>Boolean:</strong> isReady &nbsp; <strong>Array:</strong> contacts</p>
            <p>This project demonstrates variables, data types, arrays, functions, loops, conditions, form handling, and CRUD.</p>
        </div>
    </section>

    <footer>ContactDesk · Built with HTML, CSS, JavaScript, and PHP</footer>
</main>
<script src="app.js"></script>
</body>
</html>
