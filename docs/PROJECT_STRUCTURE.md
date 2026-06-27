# JJM Brain HRMS - Project Structure Guide

## 1. Core Rule

The project has two separate areas:

```text
1. Super Master Admin
   Built with Filament.
   Used only by Super Admin.

2. HRMS Application
   Built with normal Laravel, Blade, Livewire, and Tailwind.
   Used by HR users and employees.
```

Filament should only be used for the Super Master configuration panel.

Do not use Filament for HR or employee working screens.

Developer flow reference:

```text
docs/PAYROLL_LEAVE_FLOW.md
  Explains Payroll and Leave stages, actors, roles, status changes, and code owners.
```

---

## 2. Main URLs

```text
/admin                Super Master Admin Panel
/                     Shared login page
/dashboard            HR dashboard
/employee/dashboard   Employee dashboard
```

Access rules:

```text
users.is_admin = true
  Can access /admin

users.is_hr = true
  Can access /dashboard

users.is_admin = false and users.is_hr = false
  Employee user, can access /employee/dashboard
```

Super Admin users should log in from `/admin`.

HR users and employees should log in from `/`.

---

## 3. Route Structure

Keep route files small and separated by user area.

```text
routes/
  web.php          Includes the route files only
  auth.php         Shared login/logout routes
  hr.php           HR working routes
  employee.php     Employee self-service routes
```

Current responsibility:

```text
routes/auth.php
  /
  /logout

routes/hr.php
  /dashboard

routes/employee.php
  /employee/dashboard
```

When adding new pages:

```text
HR pages go in routes/hr.php
Employee pages go in routes/employee.php
Shared auth pages go in routes/auth.php
```

---

## 4. App Folder Structure

Recommended structure:

```text
app/
  Filament/
    Resources/
    Pages/
    Widgets/

  Http/
    Controllers/
      Hr/
      Employee/
    Middleware/

  Livewire/
    Auth/
    Hr/
    Employee/

  Models/

  Services/
    Hr/
    Employees/
    Attendance/
    Leave/
    Payroll/
    Documents/
    Workflows/

  Actions/
    Employees/
    Attendance/
    Leave/
    Payroll/
    Documents/

  Enums/

  Support/
    Access/
    Codes/
```

---

## 5. Filament Structure

Filament is only for the Super Master Admin Panel.

Current path:

```text
app/Filament/Resources/
  DepartmentStreams/
  EmploymentTypes/
  HrScopeAssignments/
  OrgUnits/
  Users/
```

Use Filament for:

```text
Organization Units
Department Streams
Employment Types
Users
HR Scope Assignments
System master data
System configuration
```

Do not use Filament for:

```text
HR employee management
Employee self-service
Attendance operations
Leave applications
Payroll operations
Document upload screens
```

Those should be normal Laravel/Livewire pages.

---

## 6. Livewire Structure

Livewire components should be grouped by user area and module.

Recommended:

```text
app/Livewire/
  Auth/
    Login.php

  Hr/
    Employees/
      ListEmployees.php
      CreateEmployee.php
      EditEmployee.php
    Attendance/
    Leave/
    Payroll/
    Documents/
    Transfers/
    Reports/

  Employee/
    Profile/
    Attendance/
    Leave/
    Documents/
    Payslips/
```

Matching Blade views:

```text
resources/views/livewire/
  auth/
    login.blade.php

  hr/
    employees/
    attendance/
    leave/
    payroll/
    documents/

  employee/
    profile/
    attendance/
    leave/
    documents/
```

Rule:

```text
If the screen is interactive, use Livewire.
If the screen is only a simple static page, use a normal Blade view.
```

---

## 7. View Structure

Recommended:

```text
resources/views/
  components/
    layouts/
      app.blade.php
    ui/

  auth/
    login.blade.php

  hr/
    dashboard.blade.php
    employees/
    attendance/
    leave/
    payroll/
    documents/
    transfers/
    reports/

  employee/
    dashboard.blade.php
    profile/
    attendance/
    leave/
    documents/
    payslips/

  livewire/
    auth/
    hr/
    employee/
```

Layout rule:

```text
Use resources/views/components/layouts/app.blade.php as the shared base layout.
Later, create separate HR/Employee layouts only if the UI becomes different enough.
```

---

## 8. Models

Models live in:

```text
app/Models/
```

Current foundation models:

```text
User.php
OrgUnit.php
DepartmentStream.php
EmploymentType.php
HrScopeAssignment.php
EmployeeManagerAssignment.php
```

Future models:

```text
Employee.php
EmployeeContact.php
EmployeeFamilyMember.php
EmployeeQualification.php
EmployeeExperience.php
AttendanceLog.php
LeaveApplication.php
Document.php
PayrollBatch.php
PayrollItem.php
ServiceBook.php
TransferRequest.php
```

Rule:

```text
Models should contain relationships, casts, scopes, and simple model helpers.
Large business logic should go into Services or Actions.
```

---

## 9. Services

Services should contain business logic that may be reused by multiple controllers or Livewire components.

Recommended:

```text
app/Services/
  Hr/
    EmployeeAccessService.php

  Employees/
    EmployeeProfileService.php
    EmployeeCodeService.php

  Attendance/
    AttendanceCalculationService.php

  Leave/
    LeaveBalanceService.php
    LeaveApprovalService.php

  Payroll/
    PayrollCalculationService.php

  Documents/
    DocumentStorageService.php

  Workflows/
    WorkflowService.php
```

Example:

```text
EmployeeAccessService
  Decides which employees an HR user can view/create/update/approve.
```

---

## 10. Actions

Actions should contain one focused operation.

Recommended:

```text
app/Actions/
  Employees/
    CreateEmployee.php
    UpdateEmployee.php

  Leave/
    SubmitLeaveApplication.php
    ApproveLeaveApplication.php

  Attendance/
    MarkAttendance.php
    ApproveAttendanceCorrection.php
```

Rule:

```text
Use Actions when a task has clear steps and may be called from different places.
```

Example:

```text
CreateEmployee.php
  Validate final data
  Create user if needed
  Create employee profile
  Assign org unit
  Attach documents if needed
  Write audit log later
```

---

## 11. Middleware

Current middleware:

```text
app/Http/Middleware/
  EnsureHrUser.php
  EnsureEmployeeUser.php
```

Use middleware for area-level access:

```text
EnsureHrUser
  Protects HR dashboard and HR routes.

EnsureEmployeeUser
  Protects employee dashboard and employee routes.
```

Detailed data access should not be handled only by middleware.

Data access should use:

```text
HR scope assignments
Services
Policies later
```

---

## 12. Database Structure

Migrations remain in:

```text
database/migrations/
```

For now Laravel's normal migration folder is fine.

As the project grows, use clear migration names:

```text
create_employees_table
create_employee_contacts_table
create_attendance_logs_table
create_leave_applications_table
create_documents_table
```

Seeders:

```text
database/seeders/
  DatabaseSeeder.php
  OrgUnitSeeder.php
```

Recommended future seeders:

```text
UserSeeder.php
MasterDataSeeder.php
LeaveTypeSeeder.php
DesignationSeeder.php
```

---

## 13. HR Scope Rule

HR access should be based on:

```text
Organization scope
Department stream
Employment type
Explicit employee assignment
```

Current foundation:

```text
hr_scope_assignments
  user_id
  org_unit_id
  include_child_units
  department_stream_id
  employment_type_id
  can_view
  can_create
  can_update
  can_delete
  can_approve
```

Meaning:

```text
include_child_units = false
  HR can manage only the exact selected org unit.

include_child_units = true
  HR can manage the selected org unit and all child org units.
```

Employee access should eventually be resolved by:

```text
app/Services/Hr/EmployeeAccessService.php
```

---

## 14. Naming Rules

Use clear names.

Good:

```text
Hr/Employees/CreateEmployee.php
Employee/Profile/ViewProfile.php
Services/Hr/EmployeeAccessService.php
Actions/Employees/CreateEmployee.php
```

Avoid:

```text
DataManager.php
Helper.php
CommonService.php
ProcessController.php
```

---

## 15. Build Order

Recommended next build order:

```text
1. Employee database tables
2. Employee model relationships
3. HR employee list page
4. HR create employee page
5. Employee dashboard connected to employee profile
6. HR scope filtering
7. Document upload
8. Leave module
9. Attendance module
10. Payroll module
```

---

## 16. Current Philosophy

Keep Super Master configuration and daily HR work separate.

```text
Super Master Admin:
  Configure the system.

HR Panel:
  Operate the system.

Employee Panel:
  Self-service.
```

Keep business logic out of Blade files.

Keep Livewire components focused on screen interaction.

Keep reusable business rules in services/actions.

Keep access control explicit and easy to test.
