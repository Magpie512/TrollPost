-- Assign admin to user named "admin"
UPDATE users
SET isadmin = 1
WHERE username = 'admin';