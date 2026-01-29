# ToDo App (Node + Express + MySQL)

Simple role-based ToDo app with Admin and User roles.

## Setup

1. Copy `.env.example` to `.env` and fill values (DB connection, JWT secret, Mailtrap credentials, etc.).
2. Create database: `mysql -u root -p < todo-app/database.sql` (or run with your MySQL client)
3. `cd todo-app && npm install`
4. Seed admin: `npm run seed-admin` (helpful env vars: `ADMIN_EMAIL`, `ADMIN_PASSWORD`)
5. Optionally seed demo data: `node scripts/seedTestData.js`
6. Start server: `npm run dev` (nodemon) or `npm start`

## Endpoints

- POST /api/auth/signup
- POST /api/auth/login
- POST /api/auth/forgot
- POST /api/auth/reset
- GET /api/auth/me

- POST /api/admin/tasks (admin only, `multipart/form-data` with `admin_file`)
- GET /api/admin/summary (admin only)
- GET /api/admin/users (admin only)

- GET /api/user/tasks (user only)
- POST /api/user/tasks/:id/upload (user only, file field `file`)
- PATCH /api/user/tasks/:id/status (user only)
- GET /api/user/tasks/:id/admin-file (download admin file, admin or assignee)

## Notes
- Password resets use Mailtrap (configure in .env)
- Files are stored in `uploads/` and served statically on `/uploads/`.
- Admin user is seeded by `scripts/seedAdmin.js` so passwords can be hashed with bcrypt.

## Deployment & Security Notes

- Use strong `JWT_SECRET` and do NOT commit `.env` to source control.
- In production, set `UPLOAD_DIR` outside of the webroot or use cloud storage (S3) and validate file types before serving.
- Use TLS for all connections and configure Mail provider credentials securely.
- Consider running DB migrations with a proper tool (Knex/TypeORM) for larger projects.

## Quick Start (summary)
1. Create DB: `mysql -u root -p < database.sql`
2. Copy `.env.example` -> `.env` and fill values
3. npm install
4. npm run seed-admin
5. node scripts/seedTestData.js (optional)
6. npm run dev

If you want, I can now:
- Add automated tests (Jest + Supertest)
- Convert queries to an ORM/Query builder (Sequelize/Knex)
- Create nicer frontend with React/Vue/Bootstrap

