# Manual Test Instructions (curl)

## Signup
curl -X POST http://localhost:3000/api/auth/signup -H "Content-Type: application/json" -d '{"name":"Alice","email":"alice@example.com","password":"pass1234"}'

## Login
curl -X POST http://localhost:3000/api/auth/login -H "Content-Type: application/json" -d '{"email":"alice@example.com","password":"pass1234"}'

## Admin login
curl -X POST http://localhost:3000/api/auth/login -H "Content-Type: application/json" -d '{"email":"admin@example.com","password":"AdminPass123!"}'

## Create task (admin)
curl -X POST http://localhost:3000/api/admin/tasks -H "Authorization: Bearer <TOKEN>" -F "title=Test Task" -F "assigned_to=2" -F "admin_file=@/path/to/file.pdf"

## Get admin summary
curl -X GET http://localhost:3000/api/admin/summary -H "Authorization: Bearer <TOKEN>"

## User get tasks
curl -X GET http://localhost:3000/api/user/tasks -H "Authorization: Bearer <USER_TOKEN>"

## Upload completion (user)
curl -X POST http://localhost:3000/api/user/tasks/1/upload -H "Authorization: Bearer <USER_TOKEN>" -F "file=@/path/to/file.zip"

## Forgot password
curl -X POST http://localhost:3000/api/auth/forgot -H "Content-Type: application/json" -d '{"email":"alice@example.com"}'

## Reset password
curl -X POST http://localhost:3000/api/auth/reset -H "Content-Type: application/json" -d '{"token":"<TOKEN_FROM_EMAIL>","password":"newpass123"}'
