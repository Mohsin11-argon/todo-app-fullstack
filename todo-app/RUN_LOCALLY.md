# Run Locally (Windows PowerShell)

Follow these steps to run the project locally on Windows (PowerShell):

1. Copy example env and edit it:

   Copy-Item .env.example .env
   notepad .env

   Fill DB credentials, JWT secret, and Mailtrap credentials (MAIL_USER/MAIL_PASS). Example:

   DB_HOST=localhost
   DB_USER=root
   DB_PASS=
   DB_NAME=todo-list
   JWT_SECRET=super_secret_here
   MAIL_HOST=smtp.mailtrap.io
   MAIL_PORT=2525
   MAIL_USER=your_mailtrap_user
   MAIL_PASS=your_mailtrap_pass
   UPLOAD_DIR=uploads

2. Create the database and tables (run in PowerShell):

   mysql -u root -p < database.sql

If you are using the original PHP app in this workspace and see "Unknown database 'todo_db'" (like the screenshot you shared), run the compatibility SQL instead:

   mysql -u root -p < database_todo_db.sql

Then seed an admin for the PHP app (run in a browser or CLI):

   # In browser: http://localhost/fulltodo_app/seed_admin.php
   # Or in PowerShell run PHP CLI (if available): php C:\xampp\htdocs\fulltodo_app\seed_admin.php

3. Install Node dependencies:

   cd todo-app; npm install

4. Seed the admin user (use env vars inline in PowerShell):

   # set env for the command and run seed
   $env:ADMIN_EMAIL='admin@example.com'; $env:ADMIN_PASSWORD='AdminPass123!'; npm run seed-admin

5. (Optional) Seed demo user and sample task:

   node scripts/seedTestData.js

6. Start the server:

   npm run dev

7. Verify the server is running:

   curl http://localhost:3000/health
   # or in PowerShell
   Invoke-WebRequest -Uri http://localhost:3000/health

8. Open the simple frontend pages in your browser:

   http://localhost:3000/login.html
   http://localhost:3000/signup.html
   http://localhost:3000/admin.html (admin only)
   http://localhost:3000/user.html (user only)

9. Mailtrap: log into Mailtrap to inspect password reset emails sent by the app.

Notes:
- Ensure `uploads/` directory is writable by your user (it is included in the project).
- Use a strong `JWT_SECRET` in production and do not commit `.env`.
- For production, move uploads outside webroot or use S3 and enable TLS.
