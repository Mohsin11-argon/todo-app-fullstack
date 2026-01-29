# Curl & PowerShell examples to test endpoints

Replace `<TOKEN>`, `<ADMIN_TOKEN>`, and `<USER_TOKEN>` with the JWT received from login responses.

-- Signup
curl -X POST http://localhost:3000/api/auth/signup -H "Content-Type: application/json" -d '{"name":"Alice","email":"alice@example.com","password":"pass1234"}'

# PowerShell
# $body = @{ name='Alice'; email='alice@example.com'; password='pass1234' } | ConvertTo-Json; Invoke-RestMethod -Uri http://localhost:3000/api/auth/signup -Method Post -Body $body -ContentType 'application/json'

-- Login (get token)
curl -X POST http://localhost:3000/api/auth/login -H "Content-Type: application/json" -d '{"email":"alice@example.com","password":"pass1234"}'

# PowerShell
# $body = @{ email='alice@example.com'; password='pass1234' } | ConvertTo-Json; $r = Invoke-RestMethod -Uri http://localhost:3000/api/auth/login -Method Post -Body $body -ContentType 'application/json'; $r.token

-- Me (authenticated)
curl -H "Authorization: Bearer <TOKEN>" http://localhost:3000/api/auth/me

-- Forgot password
curl -X POST http://localhost:3000/api/auth/forgot -H "Content-Type: application/json" -d '{"email":"alice@example.com"}'

-- Reset password
curl -X POST http://localhost:3000/api/auth/reset -H "Content-Type: application/json" -d '{"token":"<TOKEN_FROM_EMAIL>","password":"newpass123"}'

-- Admin: Get users (requires admin token)
curl -H "Authorization: Bearer <ADMIN_TOKEN>" http://localhost:3000/api/admin/users

-- Admin: Summary
curl -H "Authorization: Bearer <ADMIN_TOKEN>" http://localhost:3000/api/admin/summary

-- Admin: Create task with file (multipart)
curl -X POST http://localhost:3000/api/admin/tasks -H "Authorization: Bearer <ADMIN_TOKEN>" -F "title=Test Task" -F "assigned_to=2" -F "admin_file=@C:/path/to/file.pdf"

-- User: Get my tasks
curl -H "Authorization: Bearer <USER_TOKEN>" http://localhost:3000/api/user/tasks

-- User: Upload completion file
curl -X POST http://localhost:3000/api/user/tasks/1/upload -H "Authorization: Bearer <USER_TOKEN>" -F "file=@C:/path/to/completion.zip"

-- User: Update status
curl -X PATCH http://localhost:3000/api/user/tasks/1/status -H "Content-Type: application/json" -H "Authorization: Bearer <USER_TOKEN>" -d '{"status":"in_progress"}'

-- Download admin file (will trigger download)
curl -H "Authorization: Bearer <USER_TOKEN>" -OJ http://localhost:3000/api/user/tasks/1/admin-file

Notes:
- For file uploads on Windows, ensure the path is correct and accessible.
- Use `jq` (Linux/mac) or PowerShell parsing to extract tokens when scripting: e.g., in Bash: TOKEN=$(curl -s ... | jq -r .token)
- In PowerShell: $r = Invoke-RestMethod -Method Post -Uri ...; $r.token
