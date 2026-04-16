# CRUD IMG-UP / ADMIN SESSIONS | PHP Study Guide

**A Complete Command Reference for Exam Prep**

---

## Table of Contents

1. [Session Management](#session-management)
2. [Database Operations (PDO)](#database-operations-pdo)
3. [Security Functions](#security-functions)
4. [Control Structures](#control-structures)
5. [File Operations](#file-operations)
6. [HTTP Headers & Redirects](#http-headers--redirects)
7. [String & Data Functions](#string--data-functions)
8. [File-by-File Breakdown](#file-by-file-breakdown)

---

## Session Management

### `session_start()`

- **Purpose**: Starts a new session or resumes an existing one
- **Location Used**: All PHP files that need session access
- **Example**:

```php
session_start();
```

### `session_status()`

- **Purpose**: Returns the current session status
- **Constants**:
  - `PHP_SESSION_NONE` - sessions disabled or not started
  - `PHP_SESSION_ACTIVE` - session active
- **Location**: `connect.php`
- **Example**:

```php
if(session_status() === PHP_SESSION_NONE) {
    session_start();
}
```

### `session_regenerate_id()`

- **Purpose**: Regenerates session ID (security best practice)
- **Parameter**: `true` = delete old session file
- **Location**: `SL.php`, `login.php`
- **Example**:

```php
session_regenerate_id(true);
```

### `session_unset()`

- **Purpose**: Frees all session variables
- **Location**: `logout.php`

### `session_destroy()`

- **Purpose**: Destroys all data registered to a session
- **Location**: `logout.php`, `profile.php`
- **Example**:

```php
session_unset();
session_destroy();
```

### `$_SESSION` Superglobal

- **Purpose**: Stores session variables across page requests
- **Common Keys**:
  - `$_SESSION['user_id']` - logged in user's ID
  - `$_SESSION['username']` - logged in username
  - `$_SESSION['isadmin']` - admin status (0 or 1)
- **Example**:

```php
$_SESSION['user_id'] = $user['id'];
```

---

## Database Operations (PDO)

### PDO Class & Connection

#### `new PDO()`

- **Purpose**: Creates database connection object
- **Parameters**: DSN, username, password, options array
- **Location**: `connect.php`
- **Example**:

```php
$pdo = new PDO($dsn, $user, $password, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_TIMEOUT => 5
]);
```

#### PDO Options Explained

- `PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION`: Throws exceptions on errors
- `PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC`: Returns associative arrays
- `PDO::ATTR_TIMEOUT => 5`: Connection timeout in seconds

### Prepared Statements

#### `$pdo->prepare()`

- **Purpose**: Prepares an SQL statement for execution (prevents SQL injection)
- **Returns**: PDOStatement object
- **Example**:

```php
$stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username");
```

#### `$stmt->execute()`

- **Purpose**: Executes a prepared statement
- **Parameter**: Array of values to bind
- **Returns**: Boolean (true on success)
- **Example**:

```php
$stmt->execute([$username, $email, $hashed]);
```

#### `$stmt->bindParam()`

- **Purpose**: Binds a parameter to a variable name
- **Parameters**: parameter name, variable, optional type
- **Example**:

```php
$stmt->bindParam(':login', $usernameOrEmail);
```

#### `$stmt->bindValue()`

- **Purpose**: Binds a value to a parameter
- **Parameters**: parameter, value, type (optional)
- **Example**:

```php
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
```

### Fetching Results

#### `$stmt->fetch()`

- **Purpose**: Fetches a single row
- **Parameter**: Fetch style (default is PDO::FETCH_ASSOC)
- **Returns**: Array or false if no more rows
- **Example**:

```php
$user = $stmt->fetch(PDO::FETCH_ASSOC);
```

#### `$stmt->fetchAll()`

- **Purpose**: Fetches all remaining rows
- **Returns**: Array of arrays
- **Example**:

```php
$users = $stmt->fetchAll();
```

#### `$pdo->query()`

- **Purpose**: Executes SQL and returns result set (use for simple queries)
- **Example**:

```php
$totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
```

#### `->fetchColumn()`

- **Purpose**: Returns a single column from the next row
- **Example**:

```php
$count = $stmt->fetchColumn();
```

### SQL Placeholder Styles

#### Named Placeholders

```php
"SELECT * FROM users WHERE username = :username OR email = :email"
```

#### Positional Placeholders

```php
"INSERT INTO posts (user_id, name, content) VALUES (?, ?, ?)"
```

---

## Security Functions

### `password_hash()`

- **Purpose**: Creates a password hash using bcrypt algorithm
- **Parameters**: password string, algorithm constant
- **Returns**: Hashed password string (60 chars)
- **Location**: `SL.php`, `profile.php`
- **Example**:

```php
$hashed = password_hash($password, PASSWORD_DEFAULT);
```

### `password_verify()`

- **Purpose**: Verifies a password against a hash
- **Parameters**: plain password, hash
- **Returns**: Boolean
- **Location**: `SL.php`, `profile.php`
- **Example**:

```php
if (password_verify($password, $user['password'])) {
    // Password is correct
}
```

### `htmlspecialchars()`

- **Purpose**: Converts special characters to HTML entities (prevents XSS)
- **Common Characters Converted**:
  - `&` → `&amp;`
  - `<` → `&lt;`
  - `>` → `&gt;`
  - `"` → `&quot;`
  - `'` → `&#039;`
- **Location**: Used in ALL output to HTML
- **Example**:

```php
<?= htmlspecialchars($username) ?>
```

### `filter_var()`

- **Purpose**: Filters a variable with a specified filter
- **Common Filters**:
  - `FILTER_VALIDATE_EMAIL`: Validates email format
- **Location**: `SL.php`, `profile.php`
- **Example**:

```php
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $error = "Invalid email address.";
}
```

### `urlencode()`

- **Purpose**: URL-encodes a string
- **Location**: `SL.php`, `admin.php`
- **Example**:

```php
$url = "admin.php?search=" . urlencode($search);
```

---

## Control Structures

### If Statements

```php
if ($condition) {
    // code
} elseif ($otherCondition) {
    // code
} else {
    // code
}
```

### Alternative Syntax (Used in HTML)

```php
<?php if ($loginError !== ""): ?>
    <p>Error: <?= htmlspecialchars($loginError) ?></p>
<?php endif; ?>
```

### Ternary Operator

```php
$value = $condition ? 'true value' : 'false value';
```

### Null Coalescing Operator `??`

- **Purpose**: Returns first operand if exists and not null, otherwise second
- **Example**:

```php
$username = $_POST['username'] ?? '';
$search = $_GET['search'] ?? '';
```

### While Loop

```php
while ($post = $stmt->fetch()):
    // Process each post
endwhile;
```

### Foreach Loop

```php
foreach ($users as $u):
    // Process each user
endforeach;
```

---

## File Operations

### `file_exists()`

- **Purpose**: Checks if file/directory exists
- **Returns**: Boolean
- **Location**: `process.php`, `admin.php`
- **Example**:

```php
if (file_exists($file['tmp_name'])) {
    // File exists
}
```

### `is_dir()`

- **Purpose**: Checks if path is a directory
- **Location**: `process.php`
- **Example**:

```php
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}
```

### `mkdir()`

- **Purpose**: Creates a directory
- **Parameters**: path, permissions, recursive
- **Location**: `process.php`
- **Example**:

```php
mkdir($uploadDir, 0755, true);
```

### `move_uploaded_file()`

- **Purpose**: Moves an uploaded file to new location
- **Parameters**: temp file path, destination path
- **Returns**: Boolean
- **Location**: `process.php`
- **Example**:

```php
move_uploaded_file($file['tmp_name'], $filename);
```

### `unlink()`

- **Purpose**: Deletes a file
- **Location**: `process.php`, `admin.php`
- **Example**:

```php
unlink($post['image_path']);
```

### `mime_content_type()`

- **Purpose**: Detects MIME type of a file
- **Location**: `process.php`
- **Example**:

```php
$mimeType = mime_content_type($file['tmp_name']);
```

### `pathinfo()`

- **Purpose**: Returns information about a file path
- **Constants**:
  - `PATHINFO_EXTENSION`: Returns file extension
  - `PATHINFO_FILENAME`: Returns filename without extension
- **Example**:

```php
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
```

### `$_FILES` Superglobal

- **Purpose**: Contains uploaded file information
- **Keys**:
  - `name`: Original filename
  - `tmp_name`: Temporary file path
  - `size`: File size in bytes
  - `error`: Upload error code
  - `type`: MIME type
- **Constants**:
  - `UPLOAD_ERR_NO_FILE`: No file uploaded
- **Example**:

```php
if ($_FILES['post_image']['error'] === UPLOAD_ERR_NO_FILE) {
    return null;
}
```

---

## HTTP Headers & Redirects

### `header()`

- **Purpose**: Sends raw HTTP header
- **Location**: All redirect locations
- **Common Uses**:

#### Redirects

```php
header("Location: admin.php");
```

#### Cache Control

```php
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");
```

### `exit` / `exit()`

- **Purpose**: Terminates script execution
- **Location**: After all redirects
- **Example**:

```php
header("Location: index.php");
exit;
```

### `die()`

- **Purpose**: Outputs message and terminates script
- **Location**: `connect.php`, `process.php`
- **Example**:

```php
die("Database connection failed: " . $e->getMessage());
```

---

## String & Data Functions

### `trim()`

- **Purpose**: Removes whitespace from both ends of string
- **Location**: All form input processing
- **Example**:

```php
$username = trim($_POST['username'] ?? '');
```

### `strlen()`

- **Purpose**: Returns string length
- **Location**: Password validation
- **Example**:

```php
if (strlen($password) < 8) {
    $error = "Password must be at least 8 characters.";
}
```

### `strtoupper()`

- **Purpose**: Converts string to uppercase
- **Location**: `admin.php` (avatars)
- **Example**:

```php
$initial = strtoupper(substr($username, 0, 1));
```

### `strtolower()`

- **Purpose**: Converts string to lowercase
- **Location**: `process.php` (file extensions)
- **Example**:

```php
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
```

### `substr()`

- **Purpose**: Returns part of a string
- **Parameters**: string, start position, length (optional)
- **Example**:

```php
$initial = substr($username, 0, 1);
```

### `empty()`

- **Purpose**: Checks if variable is empty
- **Returns**: Boolean
- **Empty Values**: "", 0, "0", NULL, FALSE, array()
- **Example**:

```php
if (empty($token)) {
    return false;
}
```

### `isset()`

- **Purpose**: Checks if variable is set and not NULL
- **Example**:

```php
if (isset($_SESSION['user_id'])) {
    // User is logged in
}
```

### `count()`

- **Purpose**: Counts elements in array
- **Location**: `admin.php`
- **Example**:

```php
$postCount = count($viewedPosts);
```

### `in_array()`

- **Purpose**: Checks if value exists in array
- **Location**: `process.php`
- **Example**:

```php
if (!in_array($mimeType, $allowed)) {
    die("Invalid file type.");
}
```

### `bin2hex()`

- **Purpose**: Converts binary data to hexadecimal
- **Location**: `process.php` (random filename)
- **Example**:

```php
$filename = bin2hex(random_bytes(8)) . '.' . $ext;
```

### `random_bytes()`

- **Purpose**: Generates cryptographically secure random bytes
- **Location**: `process.php`
- **Example**:

```php
bin2hex(random_bytes(8)); // Generates 16 hex characters
```

---

## Superglobal Arrays

### `$_POST`

- **Purpose**: Contains form data sent via POST method
- **Example**:

```php
$username = $_POST['username'] ?? '';
```

### `$_GET`

- **Purpose**: Contains URL query string parameters
- **Example**:

```php
$search = $_GET['search'] ?? '';
```

### `$_SERVER`

- **Purpose**: Contains server and execution environment information
- **Common Keys**:
  - `REQUEST_METHOD`: HTTP method (GET, POST)
  - `HTTP_HOST`: Hostname
- **Example**:

```php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Process form
}
```

### `$_SESSION`

- **Purpose**: Session variables (covered in Session Management)

### `$_FILES`

- **Purpose**: Uploaded file information (covered in File Operations)

---

## Exception Handling

### Try-Catch Block

```php
try {
    $pdo = new PDO($dsn, $user, $password);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
```

### Exception Methods

- `$e->getMessage()`: Returns error message

---

## Type Casting

### `(int)` or `intval()`

- **Purpose**: Converts value to integer
- **Example**:

```php
$postId = (int) $_POST['post_id'];
$userId = intval($_GET['user']);
```

### `(bool)` or `boolval()`

- **Purpose**: Converts value to boolean

---

## File-by-File Breakdown

### **connect.php**

**Purpose**: Database connection configuration

**Key Commands**:

- `session_status()` - Check if session started
- `session_start()` - Start session if needed
- `new PDO()` - Create database connection
- `try-catch` - Exception handling
- `die()` - Terminate on connection failure

**Variables**:

- `$host`, `$db`, `$user`, `$password` - Connection credentials
- `$dsn` - Data Source Name string
- `$pdo` - PDO database object

---

### **SL.php** (Sign In / Sign Up)

**Purpose**: Handles user registration and login

**Key Commands**:

- `require` - Include connect.php
- `define()` - Define reCAPTCHA constants
- `$_SERVER['REQUEST_METHOD']` - Check HTTP method
- `isset()` - Check if form field exists
- `trim()` - Remove whitespace from inputs
- `empty()` - Check if field is empty
- `filter_var()` - Validate email
- `strlen()` - Check password length
- `$pdo->prepare()` - Prepare SQL statement
- `$stmt->execute()` - Execute query
- `$stmt->fetch()` - Get one result
- `password_verify()` - Verify password
- `password_hash()` - Hash password
- `session_regenerate_id()` - Regenerate session ID
- `header()` - Redirect user
- `exit` - Stop script execution
- `htmlspecialchars()` - Escape output

**Flow**:

1. Check if POST request
2. Verify reCAPTCHA
3. Validate input fields
4. For login: Check credentials, start session
5. For register: Check if user exists, hash password, insert into DB

---

### **admin.php**

**Purpose**: Admin panel for managing users and posts

**Key Commands**:

- `session_start()` - Start session
- `require` - Include database connection
- `header()` - Redirect if not admin
- `$_POST['action']` - Determine which action to perform
- `(int)` - Type cast to integer
- `$pdo->prepare()` - Prepare statements
- `$stmt->execute()` - Execute queries
- `$stmt->fetch()` - Get single result
- `$stmt->fetchAll()` - Get all results
- `file_exists()` - Check if image exists
- `unlink()` - Delete image file
- `trim()` - Clean search input
- `urlencode()` - Encode URL parameters
- `$pdo->query()->fetchColumn()` - Get count
- `empty()` - Check if array is empty
- `count()` - Count array elements
- `foreach` - Loop through results
- `strtoupper()` - Convert to uppercase
- `substr()` - Get first character
- `htmlspecialchars()` - Escape output

**Actions**:

- `delete_post` - Remove post and image
- `delete_user` - Remove user (cascades to posts)

---

### **profile.php**

**Purpose**: User profile management

**Key Commands**:

- `session_start()` - Start session
- `require` - Include database
- `isset()` - Check if logged in
- `header()` - Redirect if not logged in
- `$_SERVER['REQUEST_METHOD']` - Check if POST
- `trim()` - Clean inputs
- `password_verify()` - Verify current password
- `empty()` - Check if fields empty
- `filter_var()` - Validate email
- `strlen()` - Check password length
- `password_hash()` - Hash new password
- `$pdo->prepare()` - Prepare statements
- `$stmt->execute()` - Execute updates
- `session_unset()` - Clear session
- `session_destroy()` - Destroy session
- `htmlspecialchars()` - Escape output

**Actions**:

- `update` - Update user profile
- `delete_account` - Delete user account

---

### **process.php**

**Purpose**: Handles post creation, editing, and deletion

**Key Commands**:

- `session_start()` - Start session
- `require` - Include database
- `isset()` - Check if logged in
- `trim()` - Clean input
- `empty()` - Check if file uploaded
- `$_FILES` - Access uploaded file
- `mime_content_type()` - Check file type
- `in_array()` - Validate MIME type
- `is_dir()` - Check if directory exists
- `mkdir()` - Create directory
- `pathinfo()` - Get file extension
- `strtolower()` - Convert extension to lowercase
- `bin2hex()` - Convert binary to hex
- `random_bytes()` - Generate random bytes
- `move_uploaded_file()` - Move uploaded file
- `$pdo->prepare()` - Prepare SQL
- `$stmt->execute()` - Execute query
- `file_exists()` - Check if old image exists
- `unlink()` - Delete old image
- `try-catch` - Handle database errors
- `error_log()` - Log errors
- `die()` - Stop execution on error
- `header()` - Redirect to index
- `exit` - Stop script

**Function**: `handleImageUpload()`

- Validates file type and size
- Creates unique filename
- Moves file to uploads directory
- Returns file path or null

**Actions**:

- `create` - Insert new post
- `edit` - Update post content/image
- `delete` - Remove post and image

---

### **auth.php**

**Purpose**: Authentication check for protected pages

**Key Commands**:

- `session_start()` - Start session
- `header()` - Set cache headers and redirects
- `empty()` - Check if user logged in
- `exit()` - Stop execution after redirect

**Flow**:

1. Start session
2. Set no-cache headers
3. If not logged in → redirect to login
4. If admin → redirect to admin panel
5. Otherwise allow access

---

### **logout.php**

**Purpose**: Logs out user

**Key Commands**:

- `session_start()` - Start session
- `session_unset()` - Clear all session variables
- `session_destroy()` - Destroy session
- `header()` - Redirect to homepage
- `exit` - Stop script

---

### **index.php**

**Purpose**: Main feed page

**Key Commands**:

- `session_start()` - Start session
- `require` / `require_once` - Include files
- `isset()` - Check if logged in
- `htmlspecialchars()` - Escape output
- `$pdo->prepare()` - Prepare query
- `$stmt->bindValue()` - Bind limit parameter
- `$stmt->execute()` - Execute query
- `while` loop - Iterate through posts
- `empty()` - Check if image exists
- Alternative syntax (`:`, `endif`, `endwhile`)

**Flow**:

1. Start session
2. Include header
3. Display header with login status
4. Show post form if logged in
5. Fetch and display recent posts
6. Include sidebar and footer

---

## Common Patterns

### Form Processing Pattern

```php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = trim($_POST['fieldname'] ?? '');
    
    if (empty($input)) {
        $error = "Field is required";
    } else {
        // Process input
    }
}
```

### Database Query Pattern

```php
$stmt = $pdo->prepare("SELECT * FROM table WHERE column = ?");
$stmt->execute([$value]);
$result = $stmt->fetch();
```

### Redirect Pattern

```php
header("Location: page.php");
exit;
```

### Output Escaping Pattern

```php
<?= htmlspecialchars($variable) ?>
```

---

## Security Best Practices Used

1. **Prepared Statements** - Prevent SQL injection
2. **password_hash()** - Secure password storage
3. **htmlspecialchars()** - Prevent XSS attacks
4. **session_regenerate_id()** - Prevent session fixation
5. **MIME type validation** - Prevent file upload attacks
6. **File size limits** - Prevent DOS attacks
7. **Input validation** - Check email format, password length
8. **Type casting** - `(int)` for IDs to prevent injection

---

## Quick Reference Card

### Session

```php
session_start()
$_SESSION['key'] = 'value'
session_regenerate_id(true)
session_unset()
session_destroy()
```

### Database

```php
$pdo = new PDO($dsn, $user, $pass, $options)
$stmt = $pdo->prepare("SQL")
$stmt->execute([$param1, $param2])
$result = $stmt->fetch()
$results = $stmt->fetchAll()
```

### Security

```php
password_hash($password, PASSWORD_DEFAULT)
password_verify($password, $hash)
htmlspecialchars($string)
filter_var($email, FILTER_VALIDATE_EMAIL)
```

### Files

```php
$_FILES['fieldname']['tmp_name']
move_uploaded_file($tmp, $destination)
unlink($filepath)
file_exists($path)
```

### String

```php
trim($string)
strlen($string)
substr($string, $start, $length)
strtoupper($string)
strtolower($string)
```

### Headers

```php
header("Location: page.php")
header("Cache-Control: no-cache")
exit
```

---
