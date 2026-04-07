# 📚 Complete Technical Q&A: Scolarity Pay SaaS

> **Purpose**: Exhaustive technical reference covering all aspects of the project architecture, implementation, and operational workflows. Use this to explain any technical decision, component behavior, or system flow.

---

## 1. Architecture & Design Patterns

### Q1: Why multi-tenant architecture instead of single-tenant?
**A:** Multi-tenant design isolates each Institution (educational organization) with its own data scope:
- **Institution**: Root tenant entity representing a school network
- **Annexe**: Branch/location operational units under Institution
- **Users/Students**: Scoped to Annexes, permissions controlled via `user_annexes` pivot table
- **Benefits**: Cost efficiency (shared infrastructure), scalability (add institutions without deployment), data isolation (Institution A cannot see Institution B data), flexible pricing (per-institution configuration)
- **Implementation**: `SetActiveAnnexe` middleware injects current user's annexed context into each request; queries filtered by `annexe_id` at model level

### Q2: How does role-based access control (RBAC) work?
**A:** Three-layer permission system:
1. **Roles**: Define permission bundles (Admin, Director, Accountant, etc.)
2. **Permissions**: Atomic actions (create_student, view_payments, send_reminders)
3. **User-Annexe-Role Junction** (`user_annexes` table): Users assigned roles per annexe (User A = Admin at Annexe 1, Accountant at Annexe 2)
- `hasPermission($permission)` method checks if user has permission in active annexe
- Middleware `CheckPermission` guards routes: `middleware('permission:view_students')`
- `Scope=[institution|annexe]`: Institution-scoped permissions apply globally; annexe-scoped permissions require specific annexe context

### Q3: What is the difference between `sanctuary` (CSRF exclusion) and route middlewares?
**A:**
- **CSRF Token**: Protects against cross-site request forgery; excluded from `/api/*` routes because API uses Sanctum token auth
- **Webhook Route** (`/api/payplus/webhook`): Also CSRF-exempt because external PayPlus server cannot include user's CSRF token
- **Route Middlewares**: Group protection (auth:sanctum checks JWT), permission checks (CheckPermission), context injection (SetActiveAnnexe)
- **Middleware Stack Order** (Global → Group → Route):
  1. Global: VerifyCsrfToken (except api/*, webhook)
  2. API group: auth:sanctum, SetActiveAnnexe, SetActiveSchoolYear
  3. Per-route: CheckPermission, EnforceSchoolYearAccess

### Q4: Why is UUID used instead of auto-increment IDs?
**A:**
- **Distributed systems**: UUIDs work across multiple database replicas without coordination
- **Security**: Hard to guess or enumerate resources (no sequential ID exposure)
- **Flexibility**: Can generate IDs client-side, useful for offline-first apps
- **Implementation**: `use HasUuids trait` on models (Institution, User, Student, PaymentLink, etc.)
- **Trade-off**: Larger database footprint (~16 bytes vs 8 bytes); indexed queries slightly slower but mitigated by modern DBs

---

## 2. Data Models & Relationships

### Q5: Explain the Institution → Annexe → User/Student hierarchy
**A:**
```
Institution (root tenant)
├── Annexe_1 (branch location)
│   ├── User_A (Admin) → roles [Admin, Director]
│   ├── User_B (Accountant) → roles [Accountant]
│   └── Student_1 → Enrollment → PaymentLink → Installment → Payment
├── Annexe_2 (another branch)
│   └── User_A (different role) → roles [Accountant]
└── User_C (global institution admin)
```
- **Institution**: No multi-tenancy isolation; represents entire school network
- **Annexe**: Primary data partition; users/students belong to specific annexes
- **User-Annexe-Role**: `user_annexes` junction table allows users multiple roles across annexes
- **Query Filtering**: `User::where('annexe_id', $activeAnnexeId)` ensures data scoping

### Q6: What is the Enrollment model and why is it separate from Student?
**A:**
- **Student**: Demographic entity (name, specialization, status=active|inactive); created once
- **Enrollment**: Annual registration per school year; tracks financial obligations
  - `tuition_amount`: Total owed for school_year (from LevelFee)
  - `amount_paid`: Cumulative payments received
  - `status`: active|completed|abandoned (reflects registration lifecycle)
  - `school_year_id`: Links to school year context
  - `level_fee_id`: Pricing reference (determines tuition_amount)
- **Relationship**: `Student::enrollments()` returns multiple (one per school year)
- **Use Case**: Student enrolls in Level 1 2025-2026 (Enrollment_1) → transfers to Level 2 2026-2027 (Enrollment_2)

### Q7: How do PaymentLink, Installment, and Payment relate?
**A:**
```
Student → Enrollment → PaymentLink (single payment request)
                           └── Installment_1 (tranche 1)
                           │   └── Payment_1 (actual transaction, MTN)
                           │   └── Payment_2 (retry, Moov)
                           └── Installment_2 (tranche 2)
                               └── Payment_3 (completed)
```
- **PaymentLink**: Single request token; issued per enrollment or custom. Has `type=[tuition|registration|other]`, `status=[active|used|expired]`, unique token
- **Installment**: Payment tranche within link; tracks `due_date`, `amount`, `amount_paid`, `reminder_count`
- **Payment**: Actual transaction; has `status=[pending|success|failed]`, `method=[mtn|moov]`, `payplus_transaction_id`, `paid_at`
- **Foreign Keys**: `Payment.installment_id` → `Installment.id` → `PaymentLink.id` → `Student.id`

### Q8: What is LevelFee and how does the pricing fallback work?
**A:**
- **LevelFee**: Pricing matrix storing tuition amount per school year, study level, and specialization
- **Fields**: `study_level_id`, `specialization_id`, `school_year_id`, `amount` (in local currency)
- **Fallback Strategy** (`LevelFee::resolve()`):
  1. Try: Level + Specialization + SchoolYear (specific)
  2. Fallback: Level + SchoolYear (generic, for all specializations)
  3. Fallback: Level only (last resort)
- **Use Case**: Flexible pricing without creating 100s of fee records; reduces DB bloat
- **Applied To**: `Enrollment` uses `level_fee_id` to determine `tuition_amount` at creation time

### Q9: What does the Reminder model do?
**A:**
- **Fields**: `annexe_id`, `days_before`, `message`, `is_active`, `scope=[tuition|registration|custom]`
- **Purpose**: Recurring payment reminders sent to students approaching due dates
- **Logic**: 
  - Admin creates Reminder (e.g., "Send 7 days before installment due date")
  - Scheduled command `CheckPaymentDueDates` runs daily, finds installments due in N days
  - Dispatches `SendReminderJob` for each installment
  - Job sends email (via `PaymentLinkMail` / `StudentOtpMail`) and updates `Installment::last_reminder_sent_at`, `reminder_count++`
- **Message Variables**: `{student_name}`, `{amount_due}`, `{due_date}`, `{annexe_name}` (parsed in Vue/frontend or job)

### Q10: What is the Notification model?
**A:**
- **Fields**: `notifiable_type`, `notifiable_id` (polymorphic), `type`, `message`, `data` (JSON), `read_at`
- **Purpose**: Event-based notifications (payment success, reminder sent, enrollment approved)
- **Usage**: Stored in DB for viewing in admin dashboard; complementary to email reminders
- **Lifecycle**:
  1. Event triggered (e.g., Payment status changes to 'success')
  2. Notification record created (may also trigger email job)
  3. User views notification → `read_at` timestamp set
  4. Optionally broadcast to dashboard (WebSocket, real-time)
- **Polymorphic**: Can notify any model (User, Student, Annexe)

---

## 3. Payment Processing System

### Q11: How does the payment checkout flow work?
**A:**
```
Frontend (PublicPaymentPage.vue)
  ↓ clicks "Pay" button
  → POST /api/payments/public/checkout
      {
        "reference": "PL_abc123",
        "installment_ids": [1, 2],
        "amount": 50000,
        "phone": "77XXXXXXXX"
      }
  ↓
Backend (PaymentController::publicCheckout)
  → validate reference exists (PaymentLink)
  → validate installments belong to link
  → call PayPlusService::launchPayment()
      {
        "phonenumber": "77XXXXXXXX",
        "amount": 50000,
        "refe": "PL_abc123",
        "redirect_url": "https://app/payment/{token}",
        "callback": "https://app/api/payplus/webhook"
      }
  → PayPlus returns { "token", "request_id", "status" }
  ↓
Database update: Payment record
  {
    "installment_id": 1,
    "status": "pending",
    "method": "mtn|moov",
    "payplus_transaction_id": "token",
    "amount": 50000,
    "metadata": {...}
  }
  ↓
Response to frontend:
  { "redirect_url": "https://payplus.payment.page?token=..." }
  ↓
Frontend redirects user to PayPlus hosted payment page
  → User enters PIN (USSD or web)
  → PayPlus processes payment
```

### Q12: What are the three payment confirmation mechanisms?
**A:**
1. **Polling** (Frontend → Backend):
   - Frontend: `PaymentLinkPage.vue` starts polling every 5 seconds
   - Endpoint: `GET /api/payments/check/{reference}`
   - Returns: `{ "status": "pending|success|failed", "message": "..." }`
   - Timeout: MAX_TRIES=24, so 24 × 5sec = 2 minutes before giving up
   - Pros: Works even if webhook fails; real-time UX
   - Cons: Inefficient if many concurrent payers

2. **Webhook** (PayPlus → Backend):
   - PayPlus POSTs to: `POST /api/payplus/webhook`
   - Payload: `{ "refe": "PL_abc123", "response_code": "00|01", "status": "success|failed" }`
   - Backend: Verifies signature, updates Payment record, marks Installment as paid
   - CSRF exempt: Decorated with `@withoutMiddleware('VerifyCsrfToken')`
   - Pros: Authoritative; server-to-server guarantee
   - Cons: Network issues; PayPlus server might not have internet

3. **Return URL** (PayPlus → Frontend redirect):
   - After user completes payment on PayPlus page, redirected to: `GET /payment/{token}`
   - Backend returns PaymentCheckPage (Vue component), which auto-polls
   - Redundancy: If polling times out, user can manually refresh; return URL also refreshes
   - Pros: UX confirmation; user sees success/failure page
   - Cons: User might close browser before being redirected

### Q13: Why use three confirmation mechanisms instead of one?
**A:**
- **Reliability**: At least one mechanism will succeed (payment confirmed)
  - Polling catches: webhook network failure, browser back-button scenarios
  - Webhook catches: polling timeout, user closes browser
  - Return URL catches: both services working together
- **UX**: Polling provides immediate feedback (5s polling loop); users don't wait minutes for webhook
- **Compliance**: Some payment gateways require all three for PCI/security standards
- **Manual Reconciliation**: If all three fail (rare), webhook data can be replayed manually

### Q14: What happens when a payment status changes to 'success'?
**A:**
```
Payment.status = 'success' → triggered by webhook or polling result
  ↓
PaymentController::checkStatus() or payplusWebhook()
  → Update Payment record: status='success', paid_at=now()
  ↓
Sync derived amounts:
  → Installment.amount_paid += Payment.amount
  → if (Installment.amount_paid >= Installment.amount)
      → Installment.status = 'paid'
  ↓
  → PaymentLink.refreshStatus()
      → if (all installments paid)
        → PaymentLink.status = 'used'
      → else if (any installment paid)
        → PaymentLink.status = 'partially_paid'
  ↓
  → Enrollment.amount_paid += Payment.amount
      → if (Enrollment.amount_paid >= Enrollment.tuition_amount)
        → Enrollment.status = 'completed'
  ↓
Create Notification:
  → type='payment_success'
  → message='Payment of 50,000 XOF confirmed'
  → notifiable=Student or User
  ↓
Optionally send email:
  → PaymentSuccessMail dispatched to Student
  ↓
Log payment: Log::info("Payment {id} completed by {method}")
```

### Q15: How does PayPlus service integration work?
**A:**
- **Config**: `config/payplus.php` stores mode=[test|live], api_username, api_password, api_endpoint
- **Service Class**: `app/Services/PayPlusService.php`
  - `launchPayment($data)`: Calls PayPlus API, returns { token, request_id, status }
  - `verify($token)`: Polls PayPlus API for transaction status using token
- **Authentication**: Basic auth (credentials from config)
- **Methods**: 
  - `POST /launcher`: Initiate payment, get token
  - `GET /transactionstatus`: Query payment status (token-based)
- **Response Codes**: 
  - "00" = Success
  - "01" = Failed/Declined
  - "02" = Pending (still processing)
- **Rate Limiting**: None implemented (add yourself if PayPlus restricts)
- **Retry Logic**: Polling retries up to 24 times; webhook is single-attempt

### Q16: What is the difference between 'pending', 'success', and 'failed' payment statuses?
**A:**
- **pending**: Payment initiated; awaiting user USSD PIN or gateway callback
  - Duration: Minutes (polls for 2 minutes)
  - Reason: PayPlus processing, network delay, user hasn't entered PIN
  - Resolution: Polling retries; webhook callback updates on completion
  
- **success**: Payment confirmed by PayPlus
  - Duration: Permanent (unless refund issued separately)
  - Actions: Installment marked paid, amounts synced, notification sent
  - Cascade: Enrollment amounts updated; PaymentLink status updated
  
- **failed**: Payment declined or expired
  - Reason: Insufficient balance, wrong PIN, transaction timeout (>2min on PayPlus server)
  - Recovery: Student initiates new payment link; old Payment record remains for audit
  - Retry: No automatic retry; student must manually restart checkout

---

## 4. Reminder System

### Q17: How does the reminder system work end-to-end?
**A:**
```
Admin creates Reminder:
  → Annexe_1
  → days_before=7
  → message="Payment due in 7 days: {amount_due} XOF, due {due_date}"
  → is_active=true
  ↓
Daily cronjob (Laravel scheduler):
  → runs: php artisan schedule:run
  ↓
Schedule captures: CheckPaymentDueDatesJob::dispatch()
  ↓
Job executes: CheckPaymentDueDatesJob
  → Find target_date = today + 7 days
  → Query all Reminders with days_before=7 in active annexes
  → For each reminder:
      → Find Installments where due_date = target_date AND amount_paid < amount
      → Group by Student
      ↓
Console command: SendScheduledReminders
  → Iterates found installments
  → For each installment:
      → SendReminderJob::dispatch($installment)
  ↓
Job executes: SendReminderJob (ShouldQueue)
  → Build email body (parse message template with variables)
  → Get student email from Enrollment/User relation
  → Send: Mail::to($student_email)->send(new StudentReminderMail(...))
  → Update tracking:
      → Installment.reminder_count++
      → Installment.last_reminder_sent_at = now()
```

### Q18: Can reminders be scheduled daily, or only on fixed intervals?
**A:**
- **Fixed Intervals Only** (current implementation):
  - Each Reminder has `days_before` (7, 3, 1, etc.)
  - Scheduler runs `php artisan schedule:run` (typically every minute via cronjob)
  - Job checks: send reminders for installments due in exactly N days
  - Result: Reminders sent once per installment per N-day interval

- **To Add Daily Reminders**:
  - Add column: `Reminder.repeat_daily` boolean
  - Modify query: `Installment::whereDate('due_date', '>=', today())` instead of exact match
  - Add check: `reminder_count < max_reminders` (e.g., limit to 3 total)
  - Track: `last_reminder_sent_at` to avoid sending twice same day

- **Current Limitation**: No rate limiting; if cronjob runs multiple times/minute, reminders sent multiple times (mitigated by `last_reminder_sent_at` check in production)

### Q19: Where do reminder message templates come from?
**A:**
- **Storage**: `Reminder.message` field (database, hardcoded by admin)
- **Variables Supported**:
  - `{student_name}`: Student full name
  - `{amount_due}`: Installment remaining amount
  - `{due_date}`: Installment due date (formatted)
  - `{annexe_name}`: Registering annexe name
  - `{school_year}`: Current school year label
- **Example**: "Bonjour {student_name}, votre scolarité de {amount_due} XOF est due le {due_date}. Veuillez payer ASAP."
- **Parsing**: Done in `SendReminderJob`:
  ```php
  $body = $reminder->message;
  $body = str_replace('{student_name}', $student->name, $body);
  // ... etc
  ```
- **Email Sending**: Uses `StudentReminderMail` mailable, which renders body in template

### Q20: What is the --dry-run flag in the reminders:send command?
**A:**
- **Purpose**: Preview which reminders **would be** sent without actually sending
- **Usage**: `php artisan reminders:send --dry-run`
- **Output**: Lists installments and reminder recipients, but no emails dispatched
- **Use Case**: 
  - Test configuration before activating in production
  - Audit: Who will receive reminders? How many?
  - Debugging: Verify reminder logic without side effects
- **Implementation**: 
  ```php
  if ($this->option('dry-run')) {
      $this->line("Would send {$count} reminders");
      return;
  }
  // actual dispatch
  ```

---

## 5. Authentication & Authorization

### Q21: How does student OTP login work?
**A:**
```
Student enters phone number on login page
  ↓
POST /api/student/request-otp
  → Validate phone format
  → Generate random 6-digit OTP code
  → Store: StudentOtp record (phone, code, created_at, expires_at=+10min)
  → Send: Email/SMS with OTP (currently email via StudentOtpMail)
  → Return: { "reference": "ref_123", "expires_in": 600 }
  ↓
Student enters OTP code
  ↓
POST /api/student/verify-otp
  {
    "reference": "ref_123",
    "otp_code": "123456"
  }
  ↓
Backend:
  → Find StudentOtp where reference & code match
  → Validate: not expired (created_at > 10 min ago)
  → Delete StudentOtp (single-use)
  → Create auth token (JWT or session)
  → Return: { "token": "eyJ...", "student": {...} }
  ↓
Frontend:
  → Store token in localStorage
  → Include in Authorization header: GET /api/student/profile
    → Middleware StudentAuthMiddleware validates token
    → Returns student profile
```

### Q22: How does Sanctum JWT authentication work for admins?
**A:**
- **Login**:
  - `POST /api/login` with email + password
  - Backend verifies credentials via `Auth::attempt()`
  - `user->createToken('api-token')` creates personal access token (stored in `personal_access_tokens` table)
  - Returns: `{ "token": "xxx", "user": {...} }`
- **Subsequent Requests**:
  - Frontend includes: `Authorization: Bearer xxx`
  - Middleware `auth:sanctum` verifies token against `personal_access_tokens` table
  - Token bound to user; stolen token = compromised account
  - No expiry on personal access tokens (add `expires_at` column if needed)
- **Logout**:
  - `POST /api/logout` deletes token from `personal_access_tokens`
  - Token becomes invalid (delete not revoke; old token still works until deletion confirmed)
- **Refresh**:
  - No built-in refresh mechanism; create new token after each login or extend `expires_at`

### Q23: What is the difference between admin and student authentication?
**A:**

| Aspect | Admin (Sanctum JWT) | Student (OTP) |
|--------|-------------------|---------------|
| **Credential Type** | Email + Password | Phone + OTP |
| **Token Storage** | `personal_access_tokens` table | Session/LocalStorage (JWT) |
| **Expiry** | None (persistent) | 10 minutes (OTP), configurable session duration |
| **Use Case** | Dashboard, full system access | View payments, download receipts |
| **Permissions** | Role-based (RBAC system) | Student-scoped (own data only) |
| **Middleware** | `auth:sanctum`, `CheckPermission` | `student.auth`, implicit student scope |
| **Security** | Password hashing, session hijacking prevention | OTP prevents brute force, no passwords |

### Q24: Why is user_annexes a pivot table and not just a foreign key?
**A:**
- **Single FK Design (Naive)**:
  ```php
  User → Annexe (one-to-one)
  ```
  - Problem: User can only work at one annexe
  - Real use case: Accountant works at both HQ and Branch; Director oversees all annexes
  - Doesn't scale

- **Pivot Table Design (Actual)**:
  ```php
  User ←→ user_annexes ←→ Annexe
          (+ role_id)
  ```
  - User A → [Annexe 1 (Admin), Annexe 2 (Accountant), Annexe 3 (Viewer)]
  - Each user-annexe relationship stores specific role
  - Query: `User::where('id', 1)->annexes()->where('role_id', Admin)->first()`

- **Implementation**:
  ```php
  // User model
  public function annexes() {
      return $this->belongsToMany(Annexe::class, 'user_annexes')
                  ->withPivot('role_id', 'created_at');
  }
  
  // Accessing
  $user->annexes; // [Annexe 1, 2, 3]
  $user->pivot->role_id; // current role at this annexe
  ```

### Q25: How does permission checking work in controllers?
**A:**
```php
// Route definition
Route::post('/students', [StudentController::class, 'store'])
    ->middleware('permission:create_student');

// Middleware execution
// CheckPermission middleware:
public function handle($request, $next, $permission) {
    $user = Auth::user();
    $annexe = $request->active_annexe; // injected by SetActiveAnnexe
    
    if (!$user->hasPermission($permission, $annexe)) {
        return abort(403, 'Unauthorized');
    }
    
    return $next($request);
}

// User model
public function hasPermission($permission, $annexe = null) {
    $annexe = $annexe ?? Auth::user()->active_annexe;
    
    // Find role in user_annexes for this annexe
    $roleAtAnnexe = $this->annexes()
        ->where('user_annexes.annexe_id', $annexe->id)
        ->first()
        ->pivot
        ->role_id;
    
    // Check if role has permission
    $role = Role::find($roleAtAnnexe);
    return $role->permissions()->where('slug', $permission)->exists();
}
```

---

## 6. Middleware & Request Lifecycle

### Q26: What is SetActiveAnnexe middleware and why is it needed?
**A:**
- **Purpose**: Inject current user's active annexe into request context
- **Problem It Solves**:
  - User works at multiple annexes; which one is "active" for this request?
  - Query filter: `Student::where('annexe_id', ???)` without knowing context
  - Without it: queries either hardcoded annexe_id or ambiguous
- **Implementation**:
  ```php
  public function handle(Request $request, Closure $next) {
      $user = Auth::user();
      $activeAnnexeId = $request->header('X-Annexe-Id') 
                        ?? session('active_annexe') 
                        ?? $user->annexes()->first()->id;
      
      $request->active_annexe = Annexe::find($activeAnnexeId);
      return $next($request);
  }
  ```
- **Usage**: Frontend sends `X-Annexe-Id` header OR session stores active annexe
- **Scoping**: All queries in controllers use `$request->active_annexe->id` as filter
- **Order**: Applied early in middleware stack (before `CheckPermission`)

### Q27: What does SetActiveSchoolYear middleware do?
**A:**
- **Purpose**: Inject current school year into request context
- **Problem**: 
  - System supports multiple school years (2025-2026, 2026-2027, etc.)
  - Queries like `Enrollment::where('school_year', ???)` ambiguous
  - Prevents cross-school-year data leakage
- **Logic**:
  ```php
  $activeSchoolYear = SchoolYear::where('is_active', true)->first()
                                  ?? session('school_year')
                                  ?? SchoolYear::latest()->first();
  $request->active_school_year = $activeSchoolYear;
  ```
- **Usage**: Controllers filter by `$request->active_school_year->id`
- **Admin Control**: Admin can toggle `school_year.is_active` to freeze/unlock year transitions

### Q28: What is the order of middleware execution?
**A:**
```
Global Middleware (app/Http/Kernel.php, global array)
├── StartSession (session handling)
├── EncryptCookies (encryption)
└── ... (8 more)
    ↓
Route Group Middleware (api group)
├── auth:sanctum (verify token)
├── SetActiveAnnexe (inject annexe)
├── SetActiveSchoolYear (inject year)
└── ... (throttle, etc.)
    ↓
Per-Route Middleware (route definition)
├── permission:create_student ← CheckPermission
├── EnforceSchoolYearAccess
└── ... (custom guards)
    ↓
Controller Action
    ↓
Response sent through middleware stack (reversed order)
```

### Q29: What does VerifyCsrfToken middleware exclude and why?
**A:**
- **CSRF**: Cross-Site Request Forgery protection; requires `csrf_token` header
- **Excluded Routes**:
  ```php
  protected $except = [
      'api/*',              // All API routes (use Sanctum JWT instead)
      'api/payplus/webhook' // PayPlus server cannot include CSRF token
  ];
  ```
- **Why API?**: 
  - API uses stateless authentication (JWT in header)
  - User is identified by token, not session cookie
  - CSRF attacks assume session-based auth; tokens prevent impersonation
- **Why Webhook?**: 
  - External PayPlus server initiates request
  - No way to inject user's CSRF token (not available to PayPlus)
  - Signature verification + token validation replaces CSRF check
- **Web Routes** (not excluded): Require `csrf_token` in form/header (Laravel automatically includes in forms)

### Q30: What is EnforceSchoolYearAccess middleware?
**A:**
- **Purpose**: Lock/unlock system based on school year state
- **Scenarios**:
  - School year locked: Prevent students from paying; admin only
  - School year active: Allow both admin and students
  - School year archived: Read-only mode
- **Implementation**:
  ```php
  $year = $request->active_school_year;
  if ($year->status === 'locked' && $user->role !== 'admin') {
      abort(403, 'School year locked for students');
  }
  ```
- **Applied To**: Routes that modify data (POST/PUT/DELETE)
- **Bypass**: Can be disabled per route: `->withoutMiddleware('EnforceSchoolYearAccess')`

---

## 7. Docker & Containerization

### Q31: Why is `DB_HOST=db` instead of `saas-db-prod` (the container_name)?
**A:**
- **Docker Compose Service Name**:
  - In `docker-compose.yml`, service is named `db` (the key under `services:`)
  - Docker DNS resolver maps `db` hostname to container IP
  - Containers can ping `db` and reach the database
  
- **Container Name** (`container_name: saas-db-prod`):
  - Human-readable name for `docker ps` output
  - Does NOT participate in Docker DNS resolution between containers
  - Used for external references (e.g., `docker exec saas-db-prod bash`)
  
- **Correct Configuration**:
  ```yaml
  services:
    db:  # ← This is the hostname
      container_name: saas-db-prod  # ← For admin convenience
      image: mysql:8.0
      environment:
        MYSQL_DATABASE: scolarity_pay
  ```
  
- **Connection String**: `DB_HOST=db` (service name, not container_name)
- **External Connection** (laptop → docker): `localhost:3306` (port mapped via `ports: 3306:3306`)

### Q32: What are the five services in docker-compose and their purposes?
**A:**

| Service | Image | Purpose | Port |
|---------|-------|---------|------|
| **app** | PHP-FPM custom | Laravel application server | 9000 (internal) |
| **nginx** | nginx:alpine | Reverse proxy, static file serving | 8000:80 (public) |
| **db** | mysql:8.0 | Relational database | 3306:3306 |
| **redis** | redis:alpine | Cache, queue, session driver | 6379:6379 |
| **worker** / **background-tasks** | PHP-FPM custom | Queue job consumer | None (internal) |

- **Interconnectivity**: 
  - `app` ↔ `db` (Laravel connects to MySQL)
  - `app` ↔ `redis` (cache/session/queue)
  - `nginx` → `app` (forwards HTTP to FastCGI)
  - `worker` ↔ `redis` (consumes jobs from queue)

### Q33: How does port mapping work (8000:80)?
**A:**
- **Syntax**: `host_port:container_port`
- **Port 8000 (Host Machine)**:
  - Your laptop/server running Docker
  - Access via: `http://localhost:8000`
- **Port 80 (Container)**:
  - nginx inside container listens on 80
  - Docker daemon forwards port 8000 traffic to container port 80
- **Flow**:
  ```
  Browser: curl http://localhost:8000
     ↓ (Docker daemon port forwarding)
  nginx container: localhost:80
     ↓ (FastCGI proxy)
  app container (PHP-FPM): port 9000
  ```
- **Why Not Port 80 Host?**:
  - Requires root/sudo on Linux
  - Port 80 already in use by Apache/another service
  - Changing to 8000 avoids conflicts, still accessible

### Q34: What is the purpose of the worker/background-tasks service?
**A:**
- **Purpose**: Run queued jobs asynchronously instead of blocking HTTP requests
- **Job Types in This Project**:
  - `SendReminderJob`: Sends email to student
  - `CheckDueDatesJob`: Checks approaching installment due dates
  - Any future queued jobs
- **Without Worker** (QUEUE_CONNECTION=sync):
  - Payment checkout → creates job → immediately executes in same request
  - User waits for email sending, sync before response
  - Long-running jobs block HTTP request (timeout risk)
- **With Worker** (QUEUE_CONNECTION=redis):
  - Payment checkout → creates job → pushed to Redis queue → returns immediately
  - Worker service continuously polls Redis
  - When job received, executes asynchronously
  - User gets instant response; job runs in background
- **Commands**:
  - Start worker: `docker-compose exec worker php artisan queue:work`
  - Monitor: `docker-compose logs worker`
  - Restart: `docker-compose restart worker`

### Q35: Why does the project use Redis for queuing instead of database?
**A:**
- **Database Queue** (QUEUE_CONNECTION=database):
  - Stores jobs in jobs table
  - Simpler setup (no Redis needed)
  - Slower polling (queries jobs table each tick)
  - Risk: DB becomes bottleneck with many jobs
  
- **Redis Queue** (QUEUE_CONNECTION=redis):
  - Stores jobs in Redis (in-memory, fast)
  - Lower CPU/I/O overhead
  - Better performance at scale (100s of jobs/sec)
  - Atomic operations (LPUSH/RPOP)
  - Clear separation: app ↔ Redis ↔ worker
  
- **Current Setup**: Database queue (dev convenience)
- **Product Ready**: Switch to Redis for performance:
  ```php
  // .env (production)
  QUEUE_CONNECTION=redis
  REDIS_HOST=redis
  REDIS_PORT=6379
  ```

### Q36: How do you run console commands in Docker?
**A:**
```bash
# Basic command
docker-compose exec app php artisan migrate

# With arguments
docker-compose exec app php artisan reminders:send --dry-run

# As background process
docker-compose exec -d app php artisan queue:work

# Inside container shell
docker-compose exec app bash
# then: php artisan command

# View output
docker-compose logs app -f --tail=100

# For worker specifically
docker-compose logs worker -f
```

---

## 8. Environment & Configuration

### Q37: What are the critical environment variables for local development?
**A:**
```env
APP_NAME=Scolarity Pay
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_HOST=db  # Docker service name
DB_DATABASE=scolarity_pay
DB_USERNAME=root
DB_PASSWORD=secret

CACHE_STORE=database  # Can be redis for production
QUEUE_CONNECTION=sync  # Can be redis + worker

MAIL_MAILER=smtp
MAIL_HOST=smtp-relay.brevo.com
MAIL_PORT=587
MAIL_USERNAME=postmaster@...
MAIL_PASSWORD=...

PAYPLUS_MODE=test
PAYPLUS_USERNAME=...
PAYPLUS_PASSWORD=...

SESSION_DRIVER=cookie  # or redis
SANCTUM_STATEFUL_DOMAINS=localhost:8000

REDIS_HOST=redis  # Only if using Redis
REDIS_PORT=6379
```

### Q38: What are the critical environment variables for production?
**A:**
```env
APP_ENV=production
APP_DEBUG=false  # Never true in prod
APP_URL=https://app.scolarity-pay.com

DB_HOST=prod-mysql-server  # External DB IP/domain
DB_PASSWORD=<very-strong-password>
DB_PORT=3306

CACHE_STORE=redis  # In-memory caching
QUEUE_CONNECTION=redis  # Background jobs
SESSION_DRIVER=redis  # Distributed sessions

MAIL_MAILER=smtp
MAIL_HOST=smtp-relay.brevo.com
MAIL_PORT=587
MAIL_USERNAME=<prod-key>
MAIL_PASSWORD=<prod-password>

PAYPLUS_MODE=live  # Real payments
PAYPLUS_USERNAME=<prod-credentials>
PAYPLUS_PASSWORD=<prod-password>

REDIS_HOST=<redis-server-ip>
REDIS_PASSWORD=<redis-password>  # For authentication

STORAGE_VISIBILITY=private
LOG_CHANNEL=stack  # Centralized logging

HTTPS_ONLY=true  # Force HTTPS
TRUSTED_PROXIES=<load-balancer-ip>
```

### Q39: Why is APP_DEBUG=true dangerous in production?
**A:**
- **Danger**: Stack traces with source code, file paths, credentials visible to users
- **Attack Surface**:
  - File paths reveal system architecture
  - Variable values may include database passwords
  - Database query logic exposed
  - Third-party API keys visible in errors
- **Production**: APP_DEBUG=false + centralized logging to file/Sentry
- **Error Handling**: Users see generic "500 Server Error" message; details logged privately
- **Recovery**: Use Laravel's exception handler to customize error response

### Q40: How are database credentials managed securely?
**A:**
- **Local**: `.env` file (in `.gitignore`, not committed)
- **Production Options**:
  1. **Environment Variables** (Recommended)
     - Set in Docker environment or server OS
     - Not in code; sourced at runtime
     - Rotatable without redeployment
  
  2. **Secrets Management** (AWS Secrets Manager, HashiCorp Vault)
     - Centralized secret storage
     - Audit logging
     - Automatic rotation
     - Access control
  
  3. **.env.production** (Not Recommended)
     - Risk: accidentally committed
     - Hard to rotate
  
- **Implementation**: `config/database.php` reads from `env('DB_PASSWORD')`
  ```php
  'mysql' => [
      'host' => env('DB_HOST', 'db'),
      'password' => env('DB_PASSWORD'),  // Sourced from environment
  ],
  ```

---

## 9. API Routes & Controllers

### Q41: What are the public API endpoints (no authentication required)?
**A:**

| Endpoint | Method | Purpose |
|----------|--------|---------|
| `/api/payments/public/checkout` | POST | Initiate payment for public user |
| `/api/payments/check/{reference}` | GET | Poll payment status (polling mechanism) |
| `/api/payplus/return` | GET | PayPlus redirect after payment |
| `/api/payplus/webhook` | POST | PayPlus callback (server-to-server) |
| `/payment/{token}` | GET | Display payment result page (Vue return URL) |
| `/api/student/request-otp` | POST | Request OTP login code |
| `/api/student/verify-otp` | POST | Verify OTP, get session token |
| `/api/student/profile` | GET | View student profile (requires student.auth) |
| `/api/student/payment-links` | GET | List student's payment links (requires student.auth) |
| `/api/student/payments` | GET | List student's payment history (requires student.auth) |

- **CSRF Exempt**: webhook, payplus/return (external origin)
- **Rate Limiting**: None (add yourself to prevent abuse)
- **Why Checkout Public?**: Allow unauthenticated users (parents, external payers) to pay child's tuition

### Q42: What are the protected admin endpoints?
**A:**
```
GET/POST/PUT/DELETE /api/admin/users              (CRUD users)
GET/POST/PUT/DELETE /api/admin/students           (CRUD students)
GET/POST/PUT/DELETE /api/admin/annexes            (CRUD annexes)
GET/POST/PUT/DELETE /api/admin/level-fees         (CRUD tuition fees)
GET/POST/PUT/DELETE /api/admin/enrollments        (CRUD enrollments)
GET/POST/PUT/DELETE /api/admin/payment-links      (CRUD payment links)
GET/POST/PUT/DELETE /api/admin/reminders          (CRUD reminders)
GET/POST/PUT/DELETE /api/admin/school-years       (CRUD school years)
POST /api/admin/students/{id}/send-otp            (Generate + send OTP)
POST /api/admin/users/{id}/reset-password         (Password reset)
POST /api/admin/payments/check-due-dates          (Manual run of JobCheckDueDates)
GET /api/admin/dashboard/statistics               (Dashboard KPIs)
GET /api/admin/reports/collections                (Payment collection report)
GET /api/admin/reports/outstanding                (Outstanding tuition report)
```

- **Authentication**: `auth:sanctum` (verify JWT token)
- **Authorization**: `permission:create_users`, `permission:view_reports`, etc.
- **Active Annexe**: Scoped to `request()->active_annexe` (multi-tenant filter)

### Q43: What PaymentController methods exist and what do they do?
**A:**

| Method | Route | Purpose |
|--------|-------|---------|
| `publicCheckout()` | POST /api/payments/public/checkout | Initiate payment via PayPlus |
| `checkStatus($reference)` | GET /api/payments/check/{reference} | Poll payment status (frontend polling) |
| `payplusReturn()` | GET /api/payplus/return | Handle PayPlus redirect after payment |
| `payplusWebhook()` | POST /api/payplus/webhook | Receive PayPlus callback notification |
| `store()` | POST /api/admin/payments | Create manual payment entry |
| `show($id)` | GET /api/admin/payments/{id} | View payment details |
| `index()` | GET /api/admin/payments | List payments with filters |
| `approve()` | POST /api/admin/payments/{id}/approve | Admin approve pending payment |
| `fail()` | POST /api/admin/payments/{id}/fail | Admin mark as failed |
| `refund()` | POST /api/admin/payments/{id}/refund | Issue refund |

### Q44: What is the difference between payplusReturn and payplusWebhook?
**A:**

| Aspect | Return URL | Webhook |
|--------|-----------|---------|
| **Initiated By** | User's browser redirect | PayPlus server |
| **URL** | GET /api/payplus/return?token=xxx | POST /api/payplus/webhook |
| **Payload** | Query parameters (URL) | JSON body in POST |
| **User Visible** | Yes (browser redirects) | No (backend background) |
| **Purpose** | User feedback page | Database state update |
| **Reliability** | Low (user may close browser) | High (server-to-server) |
| **Timing** | Immediate (same request) | Delayed (callback queue) |
| **Idempotency** | Important (user may refresh page) | Required (retry mechanism) |
| **Error Recovery** | Polling continues | Webhook automatic retry |

- **Both Can Fail**: 
  - Return URL fails: Browser closes, network error
  - Webhook fails: PayPlus server unreachable, etc.
  - Solution: Polling survives both failures

### Q45: How does StudentController handle enrollment and payment tracking?
**A:**
```php
// GET /api/admin/students/{id}/profile
public function show($id) {
    $student = Student::with([
        'enrollments.levelFee',
        'enrollments.paymentLinks.installments.payments',
        'annexe'
    ])->find($id);
    
    return [
        'student' => $student,
        'outstanding_balance' => $student->enrollments()
            ->where('status', 'active')
            ->sum('tuition_amount') 
            - $student->enrollments()->sum('amount_paid'),
        'payment_history' => $student->payments()
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
    ];
}

// POST /api/admin/students/{id}/send-otp
public function sendOtp($id) {
    $student = Student::find($id);
    $otp = Otp::generate($student->phone);  // Generate 6-digit
    
    Mail::to($student->email)->send(new StudentOtpMail($otp));
    // or SMS::to($student->phone)->send($otp);
    
    return ['message' => 'OTP sent'];
}

// GET /api/admin/students/{id}/enrollments
public function enrollments($id) {
    return Student::find($id)
        ->enrollments()
        ->with('paymentLinks.installments.payments')
        ->get();
}
```

---

## 10. Frontend Integration & State Management

### Q46: How does the frontend payment flow work in PaymentLinkPage.vue?
**A:**
```javascript
// User loads page with token: /payment/pl_abc123

import { ref, onMounted } from 'vue'
import paymentService from '@/services/paymentService'

const POLL_INTERVAL_MS = 5000  // 5 seconds
const POLL_MAX_TRIES = 24      // 2 minutes total
const payment = ref(null)
let pollCount = 0
let pollTimer = null

onMounted(async () => {
    const token = route.params.token
    
    // Fetch payment link details
    payment.value = await paymentService.getPaymentLink(token)
    
    // Start polling loop
    startPolling(payment.value.reference)
})

function startPolling(reference) {
    pollTimer = setInterval(async () => {
        pollCount++
        
        try {
            const status = await paymentService.checkStatus(reference)
            
            if (status.status === 'success') {
                clearInterval(pollTimer)
                payment.value.status = 'paid'
                // Show success message, redirect to dashboard
                router.push('/student/dashboard')
                
            } else if (status.status === 'failed') {
                clearInterval(pollTimer)
                payment.value.status = 'failed'
                // Show error, offer retry
                
            } else if (pollCount >= POLL_MAX_TRIES) {
                clearInterval(pollTimer)
                payment.value.status = 'timeout'
                // Show: "Payment pending; check back later"
            }
        } catch (error) {
            console.error('Poll error:', error)
            // Continue polling despite error
        }
    }, POLL_INTERVAL_MS)
}

onBeforeUnmount(() => {
    clearInterval(pollTimer)  // Clean up timer on page leave
})
```

- **Timer**: Polls every 5 seconds, maximum 24 attempts = 120 seconds = 2 minutes timeout
- **Cleanup**: Clears timer on page unmount to avoid background polling

### Q47: How is the payment reference token generated and used?
**A:**
- **Generation** (Backend):
  ```php
  // PaymentLinkController::store()
  $token = Str::random(32);  // 32-character random string
  
  $paymentLink = PaymentLink::create([
      'token' => $token,
      'reference' => 'PL_' . strtoupper(Str::random(8)),
      'student_id' => $student_id,
      // ...
  ]);
  
  // Return: /payment/{token}
  return "https://app.scolarity-pay.com/payment/{$token}";
  ```

- **Use Cases**:
  - **Token** (URL parameter): `/payment/{token}` identifies payment link for frontend
  - **Reference**: Public-facing ID for student communication (payment reminders, receipts)
  
- **Frontend** (PaymentLinkPage.vue):
  - Receives token from route: `route.params.token`
  - Fetches payment details: `GET /api/payments/public/{token}`
  - Starts polling: `GET /api/payments/check/{reference}`

- **Uniqueness**: Token must be unique + unguessable (prevent enumeration)
- **Expiry**: Can add `expires_at` column; check before processing

### Q48: Where is the Sanctum token stored on frontend?
**A:**
```javascript
// After login: POST /api/login → response.token
const response = await fetch('/api/login', {
    method: 'POST',
    body: JSON.stringify({ email, password })
})

const { token, user } = await response.json()

// Storage options:
// 1. LocalStorage (persistent but XSS-vulnerable)
localStorage.setItem('auth_token', token)

// 2. SessionStorage (cleared on browser close, slightly safer)
sessionStorage.setItem('auth_token', token)

// 3. Httponly Cookie (safest, immune to XSS)
// Set by backend: response.cookie('auth_token', token, { httpOnly: true })

// Usage in API calls
export default {
    async request(url, options = {}) {
        const token = localStorage.getItem('auth_token')
        
        return fetch(url, {
            ...options,
            headers: {
                ...options.headers,
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json'
            }
        })
    }
}
```

### Q49: Why is there a separate paymentService in Vue?
**A:**
- **Centralized API Calls**: All API logic in one place
- **Error Handling**: Consistent error formatting
- **Token Management**: Auto-injects auth token in headers
- **Request/Response Transformation**: Converts between Vue data and API format

```javascript
// services/paymentService.js
export default {
    async createPublicCheckout(data) {
        return fetch('/api/payments/public/checkout', {
            method: 'POST',
            body: JSON.stringify(data)
        }).then(r => r.json())
    },
    
    async checkStatus(reference) {
        return fetch(`/api/payments/check/${reference}`)
            .then(r => r.json())
    },
    
    async getPaymentLink(token) {
        return fetch(`/api/payments/public/${token}`)
            .then(r => r.json())
    }
}
```

---

## 11. Error Handling & Logging

### Q50: How are errors logged in the payment system?
**A:**
```php
// PaymentController::publicCheckout()
Log::info('Payment checkout initiated', [
    'payment_link_id' => $paymentLink->id,
    'student_id' => $student->id,
    'amount' => $amount,
    'method' => $request->method
]);

try {
    $response = PayPlusService::launchPayment($data);
    
    Log::info('Payment launched on PayPlus', [
        'reference' => $data['refe'],
        'payplus_token' => $response['token'],
        'payplus_request_id' => $response['request_id']
    ]);
    
} catch (PayPlusException $e) {
    Log::error('PayPlus payment failed', [
        'reference' => $data['refe'],
        'error_code' => $e->getCode(),
        'error_message' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
    
    return response()->json(['error' => 'Payment initiation failed'], 500);
}

// PaymentController::checkStatus()
Log::debug('Polling payment status', [
    'reference' => $reference,
    'poll_attempt' => $pollCount
]);

// PaymentController::payplusWebhook()
Log::info('Webhook received from PayPlus', [
    'reference' => $request->refe,
    'response_code' => $request->response_code,
    'timestamp' => now()
]);
```

### Q51: How are logs stored and accessed?
**A:**
- **Configuration**: `config/logging.php`
- **Channels**: 
  - `single`: Single file (storage/logs/laravel.log)
  - `daily`: Daily rotated files (laravel-2026-03-31.log)
  - `stack`: Multiple channels (file + syslog)
  - `sentry`: External error tracking (SaaS option)

```bash
# View logs in real-time
tail -f storage/logs/laravel.log

# Filter by keyword
grep -i "error" storage/logs/laravel.log

# Recent 100 lines
tail -100 storage/logs/laravel.log

# Via Docker
docker-compose exec app tail -f storage/logs/laravel.log

# Via Sentry (production)
# Log::error() automatically sent to Sentry dashboard
# View at: sentry.io → project dashboard
```

### Q52: What exceptions are caught in payment flows?
**A:**

| Exception | Cause | Handling |
|-----------|-------|----------|
| **PayPlusException** | PayPlus API error | Log error, return 500 to user |
| **ValidationException** | Invalid input (phone, amount) | Return 422 with errors |
| **ModelNotFoundException** | PaymentLink/installment not found | Return 404 |
| **AuthenticationException** | Token invalid/expired | Return 401, clear session |
| **AuthorizationException** | Permission denied | Return 403, log security event |
| **ThrottleRequestsException** | Rate limit exceeded | Return 429, suggest retry |
| **TokenMismatchException** | CSRF token invalid | Return 419, retry with new token |

- **Global Exception Handler**: `app/Exceptions/Handler.php`
  ```php
  public function register() {
      $this->reportable(function (PayPlusException $e) {
          Log::error('PayPlus integration error', ['exception' => $e]);
          // Notify admin
      });
      
      $this->renderable(function (PayPlusException $e) {
          return response()->json([
              'error' => 'Payment service temporarily unavailable',
              'reference' => 'ERR_' . uniqid()
          ], 503);
      });
  }
  ```

### Q53: How are database transaction rollbacks used?
**A:**
```php
// PaymentController::payplusWebhook()
DB::transaction(function () use ($request) {
    // All these queries rollback if any fails
    
    $payment = Payment::find($paymentId);
    $payment->status = 'success';
    $payment->paid_at = now();
    $payment->save();
    
    $installment = Installment::find($payment->installment_id);
    $installment->amount_paid += $payment->amount;
    $installment->save();  // ← If this fails, everything rolls back
    
    $paymentLink = PaymentLink::find($installment->payment_link_id);
    $paymentLink->refreshStatus();
    $paymentLink->save();
    
    Notification::create([
        'type' => 'payment_success',
        'notifiable_type' => Student::class,
        'notifiable_id' => $paymentLink->student_id
    ]);
    
    // If any operations fail, ALL changes rejected
    // Database remains consistent
});
```

- **Benefit**: Ensures consistency (payment + installment + link all updated, or none)
- **Drawback**: Slower (locks rows); keep transactions small

---

## 12. Deployment & Production Considerations

### Q54: How do you deploy this project to production?
**A:**
```bash
# 1. Prepare server (Ubuntu 20.04)
ssh user@production-server

# 2. Install dependencies
apt-get update && apt-get install docker.io docker-compose git php composer

# 3. Clone repository
git clone https://github.com/your-org/scolarity-pay.git
cd scolarity-pay

# 4. Build Docker images
docker-compose -f docker-compose.prod.yml build

# 5. Set environment variables
cp .env.example .env.production
# Edit: DB_HOST, PAYPLUS_MODE=live, MAIL credentials, etc.

# 6. Run migrations in container
docker-compose -f docker-compose.prod.yml exec app php artisan migrate --force

# 7. Start services
docker-compose -f docker-compose.prod.yml up -d

# 8. Verify
docker-compose ps  # All services running
curl http://localhost:8000  # App responding

# 9. Monitor logs
docker-compose logs -f app
```

### Q55: What is the difference between docker-compose.yml and docker-compose.prod.yml?
**A:**

| Aspect | Development (yml) | Production (prod.yml) |
|--------|------------------|----------------------|
| **Restart** | no | always (recover from crashes) |
| **Logging** | json-file | syslog (centralized) |
| **Resource Limits** | None | CPU/memory capped |
| **Environment** | .env (local) | .env.production (secrets) |
| **Database** | MySQL in container | External managed DB |
| **Redis** | Container | External managed Redis |
| **Ports** | 8000:80 (localhost) | 80:80 (public internet) |
| **SSL/TLS** | None (http://localhost) | Nginx with SSL certs |
| **Replicas** | 1 app instance | N app instances (load balanced) |
| **Volumes** | Mounted for dev | Docker secrets for prod |
| **Backups** | Manual | Automated daily |

### Q56: How do you handle database backups in production?
**A:**
```bash
# Manual backup (before deployment)
docker-compose exec db mysqldump -u root -psecret scolarity_pay > backup-2026-03-31.sql

# Automated backup (cron job)
0 2 * * * docker-compose exec db mysqldump -u root -psecret scolarity_pay | gzip > /backups/scolarity_pay-$(date +\%Y-\%m-\%d).sql.gz

# Restore from backup
docker-compose exec -T db mysql -u root -psecret scolarity_pay < backup-2026-03-31.sql

# Cloud backup (S3)
docker-compose exec db mysqldump -u root -psecret scolarity_pay | \
    aws s3 cp - s3://my-backup-bucket/scolarity-pay-$(date +%Y-%m-%d).sql.gz

# Verify backup integrity
docker-compose exec -T db mysql -u root -psecret scolarity_pay < backup.sql --check-only
```

### Q57: How do you scale horizontally (multiple app instances)?
**A:**
```yaml
# docker-compose.prod.yml
version: '3.8'
services:
  nginx:
    image: nginx:alpine
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - ./nginx.conf:/etc/nginx/nginx.conf:ro
    depends_on:
      - app1
      - app2
      - app3
    
  app1:
    build: .
    environment:
      - DB_HOST=db
      - QUEUE_CONNECTION=redis
    depends_on:
      - db
      - redis
  
  app2:
    build: .
    # Same as app1
  
  app3:
    build: .
    # Same as app1
  
  worker:
    # Single worker processes all jobs from shared Redis queue
    build: .
    command: php artisan queue:work
    environment:
      - QUEUE_CONNECTION=redis
```

- **Load Balancing**: Nginx distributes requests across app1, app2, app3
- **Sessions**: Stored in Redis (shared, not on individual app)
- **Queue**: Single Redis queue; all workers consume from same queue
- **Database**: Single shared MySQL (bottleneck for writes; add read replicas if needed)

### Q58: How do you handle PayPlus API rate limiting?
**A:**
```php
// app/Services/PayPlusService.php
private $rateLimitDelay = 100; // milliseconds between requests

public function verify($token) {
    // Implement backoff strategy
    $attempts = 0;
    $maxAttempts = 3;
    
    while ($attempts < $maxAttempts) {
        try {
            $response = $this->client->get('/transactionstatus', [
                'token' => $token
            ]);
            
            return $response;
            
        } catch (RateLimitException $e) {
            $attempts++;
            $delay = pow(2, $attempts) * 1000;  // Exponential backoff
            Log::warning("PayPlus rate limited, retrying in {$delay}ms");
            usleep($delay * 1000);
            
            if ($attempts >= $maxAttempts) {
                Log::error("PayPlus API rate limit exceeded after {$maxAttempts} attempts");
                throw $e;
            }
        }
    }
}

// In production, use Redis cache for request throttling
public function shouldThrottle() {
    $key = "payplus:requests:" . now()->minute;
    $count = Cache::increment($key, 1);
    Cache::expire($key, 60);
    
    return $count > 100;  // Max 100 requests/minute
}
```

### Q59: How do you monitor application health in production?
**A:**
```php
// routes/api.php
Route::get('/health', function () {
    $health = [
        'app' => 'OK',
        'database' => 'OK',
        'redis' => 'OK'
    ];
    
    try {
        DB::connection()->getPdo();
    } catch (Exception $e) {
        $health['database'] = 'FAILED: ' . $e->getMessage();
    }
    
    try {
        Cache::connection('redis')->get('ping')  ?? Cache::put('ping', 1);
    } catch (Exception $e) {
        $health['redis'] = 'FAILED: ' . $e->getMessage();
    }
    
    $status = collect($health)->every(fn($v) => $v === 'OK') ? 200 : 503;
    return response()->json($health, $status);
});

// Monitoring via external service (UptimeRobot, Datadog)
// Configure: POST to https://app.scolarity-pay.com/health every 5 minutes
// Alert: If response != 200 for 3 consecutive checks
```

### Q60: How do you perform zero-downtime deployments?
**A:**
```bash
# 1. Build new container image
docker build -t scolarity-pay:v2 .

# 2. Start rolling update (1 instance at a time)
# Kubernetes example:
kubectl set image deployment/api api=scolarity-pay:v2 --record

# Docker Swarm example:
docker service update \
    --image scolarity-pay:v2 \
    --update-parallelism 1 \
    --update-delay 10s \
    scolarity-pay-api

# 3. Health checks ensure old instances handle traffic while new start
# Before shutting down old instance:
#   - Wait for in-flight requests to complete
#   - Drain connection pool
#   - New instance passes health checks

# 4. Rollback if needed
docker service update \
    --image scolarity-pay:v1 \
    scolarity-pay-api
```

- **Downtime**: 0 seconds (users never see service unavailable)
- **Process**: 
  1. Old v1 instance receives requests
  2. v2 instance starts, passes health checks
  3. Load balancer gradually shifts traffic to v2
  4. v1 instance shuts down after requests drain

---

## 13. Advanced Topics

### Q61: How do you implement soft deletes for audit trails?
**A:**
```php
// Models should soft-delete for audit compliance
// app/Models/Payment.php

use Illuminate\Database\Eloquent\SoftDeletes;

class Payment extends Model {
    use SoftDeletes;
    
    protected $dates = ['deleted_at'];  // Track deletion timestamp
}

// Migration
Schema::create('payments', function (Blueprint $table) {
    $table->id();
    // ... columns
    $table->softDeletes();  // Adds deleted_at column
});

// Usage
$payment = Payment::find(1);
$payment->delete();  // Soft delete (deleted_at = now())

// Queries ignore soft-deleted by default
Payment::count();  // Doesn't include deleted
Payment::withTrashed()->count();  // Includes deleted
Payment::onlyTrashed()->count();  // Only deleted
Payment::restore();  // Restore soft-deleted record
```

- **Benefit**: Audit trail (who deleted what, when)
- **Compliance**: Financial records often require retention
- **Recovery**: Can restore accidental deletions

### Q62: How do you handle international payment failures (network issues)?
**A:**
```php
// Webhook retry mechanism (PayPlus sends webhook multiple times)
public function payplusWebhook(Request $request) {
    $reference = $request->refe;
    
    // Idempotency check: has this webhook been processed?
    $existingPayment = Payment::where(
        'payplus_transaction_id', 
        $request->transaction_id
    )->first();
    
    if ($existingPayment && $existingPayment->status === 'success') {
        return response()->json(['status' => 'already_processed'], 200);
    }
    
    // Process payment
    DB::transaction(function () use ($request) {
        // ... update payment status
    });
    
    // Acknowledge receipt (PayPlus stops retrying)
    return response()->json(['status' => 'received'], 200);
}

// Client-side retry in polling
export default {
    async checkStatus(reference, maxRetries = 5) {
        for (let attempt = 1; attempt <= maxRetries; attempt++) {
            try {
                const response = await fetch(`/api/payments/check/${reference}`)
                return response.json()
            } catch (error) {
                if (attempt === maxRetries) throw error
                
                // Exponential backoff
                await new Promise(r => setTimeout(r, Math.pow(2, attempt) * 1000))
            }
        }
    }
}
```

---

## Summary: Master Checklist for Technical Interviews

Use this checklist to ensure you can explain:

- [ ] Multi-tenant architecture (Institution → Annexe → User/Student)
- [ ] RBAC system (Role → Permission → user_annexes junction)
- [ ] Enrollment vs Student vs Payment tracking
- [ ] Three payment confirmation mechanisms (polling, webhook, return URL)
- [ ] PayPlus integration flow
- [ ] Reminder system (scheduler, jobs, email)
- [ ] OTP vs Sanctum authentication
- [ ] SetActiveAnnexe + SetActiveSchoolYear middleware
- [ ] Why VerifyCsrfToken excludes API routes
- [ ] Docker service names vs container names
- [ ] Port mapping (8000:80)
- [ ] Worker service + Redis queue
- [ ] Environment variables (local vs production)
- [ ] Public vs protected API endpoints
- [ ] Frontend polling loop (5s interval, 24 max tries)
- [ ] Error handling + logging
- [ ] Deployment strategies
- [ ] Scaling horizontally (multiple app instances)
- [ ] Zero-downtime deployments
- [ ] Soft deletes + audit trails

---

**Last Updated**: 31 mars 2026  
**Project**: Scolarity Pay SaaS  
**Version**: 1.0  
**Audience**: Technical teams, stakeholders, interviewees
