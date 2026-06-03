# JJM Brain HRMS
## Human Resource Management System

> A comprehensive, role-based HR platform built for modern government and enterprise workforce management — covering the full employee lifecycle from onboarding to payroll.

| 3 User Roles | 10+ Core Modules | Full Lifecycle Coverage |
|:---:|:---:|:---:|

---
## Setup

- `composer install`
- `npm install`
- `npm run dev`
- `php artisan migrate`
- `php artisan serve`

---

## What is JJM Brain HRMS?

JJM Brain HRMS is a multi-role, scope-aware HR system designed to handle the complete employee lifecycle — from onboarding and attendance to payroll and service records — across complex organizational hierarchies.

The system is divided into three clearly separated areas:

### ⚙ Super Master Admin
Configures the entire system — org units, users, master data, HR scope assignments. Built with **Filament**. Used exclusively by Super Admin.

### 🗂 HR Panel
Day-to-day HR operations — manage employees, approve leave, process payroll, track attendance. Built with **Laravel + Livewire + Blade + Tailwind**.

### 👤 Employee Portal
Self-service for employees — view payslips, apply for leave, update profile, upload documents. Built with **Laravel + Livewire + Blade + Tailwind**.

---

## User Roles & Access

### Super Admin
- **Login:** `/admin`
- **Flag:** `users.is_admin = true`
- Configure org units & departments
- Manage all users
- Assign HR scopes & permissions
- Set employment types
- System-wide master data

### HR User
- **Login:** `/dashboard`
- **Flag:** `users.is_hr = true`
- Manage employees in their scope
- Process attendance & leave
- Run payroll batches
- Approve workflows
- Generate reports

### Employee
- **Login:** `/employee/dashboard`
- **Flag:** `is_admin = false`, `is_hr = false`
- View & update own profile
- Apply for leave
- View payslips
- Upload personal documents
- Check attendance records

---

## Core Modules

| Module | Description |
|---|---|
| **Employee Management** | Full employee profiles, contacts, family, qualifications, experience |
| **Attendance** | Attendance logging, corrections, approvals and reporting |
| **Leave Management** | Apply, approve, balance tracking across leave types |
| **Payroll** | Payroll batches, items, payslip generation per employee |
| **Documents** | Secure document upload and retrieval for employees and HR |
| **Transfers** | Transfer requests across org units with approval workflows |
| **Service Book** | Complete service history maintained per employee |
| **Workflows** | Configurable approval chains for leave, attendance, transfers |
| **Reports** | HR-level reports across attendance, payroll, headcount |

---

## Employee Lifecycle

JJM Brain HRMS covers every stage of the employee journey — within a single system, with no data silos.

```
[ 01 Onboarding ] → [ 02 Daily Operations ] → [ 03 Payroll ] → [ 04 Growth ] → [ 05 Offboarding ]
```

| Stage | What's Covered |
|---|---|
| **01 · Onboarding** | Profile creation, document upload, org unit assignment |
| **02 · Daily Operations** | Attendance logging, leave applications, corrections |
| **03 · Payroll** | Monthly batch processing, payslip generation |
| **04 · Growth** | Transfers, promotions, service book updates |
| **05 · Offboarding** | Final records, complete service book archival |

---

## Smart HR Scope System

HR users don't see all employees — they only see employees within their assigned scope. This makes JJM Brain HRMS suitable for complex, multi-department, multi-location organisations.

### Scope Dimensions

| Dimension | Description |
|---|---|
| **Org Unit** | HR is scoped to one or more org units |
| **Child Units** | Optionally include all sub-units under the assigned org (`include_child_units = true`) |
| **Dept Stream** | Further filter by department stream |
| **Emp. Type** | Filter by employment type (permanent, contract, etc.) |

### Per-Scope Permissions

Each HR scope assignment carries granular permission flags:

```
can_view  ·  can_create  ·  can_update  ·  can_delete  ·  can_approve
```

---

## Key Features

### 🏢 Multi-Level Org Hierarchy
Supports nested org units with parent-child relationships for large, complex organisations.

### 🔒 Scope-Based Access
HR staff only access the employees they are explicitly scoped to — no data leakage between departments or regions.

### 📋 Full Audit Trail
Every action is logged — who did what, when, on which employee record.

### ⚡ Real-Time Livewire UI
Interactive screens without page reloads — fast, responsive HR operations throughout the system.

### 📊 Payroll Engine
Batch payroll processing with individual payslip generation per employee per pay cycle.

### 🔄 Approval Workflows
Configurable multi-step workflows for leave applications, attendance corrections, and transfers.

---

## Technology Stack

| Layer | Technologies |
|---|---|
| **Backend** | Laravel (PHP), Eloquent ORM, Service + Action pattern |
| **Frontend / UI** | Blade Templates, Livewire (reactive), Tailwind CSS |
| **Admin Panel** | Filament (Super Admin only), resource management, master data config |
| **Database** | MySQL / PostgreSQL, migration-based schema, seeded master data |

---

## Who Is This For?

JJM Brain HRMS is ideal for organisations that need fine-grained HR control across multiple units, teams, and employment categories.

### Government Departments
- Multiple org units / directorates
- Mixed employment types (permanent, contractual, deputation)
- Strict access segregation between HR officers

### Large Enterprises
- Hundreds to thousands of employees
- Multiple HR managers with different scopes
- Centralised payroll with department-level leave management

### Multi-Location Organisations
- Regional offices with local HR teams
- Org hierarchy with child unit support
- Central Super Admin with local HR control

---

## Summary

> **One System. Every HR Need.**

JJM Brain HRMS brings together employee management, attendance, leave, payroll, documents, and workflows — in a single, secure, scope-controlled platform built for real-world HR complexity.

| Role-Based Access | Scope-Aware HR | Full Lifecycle |
|:---:|:---:|:---:|
| **Audit Logged** | **Real-Time UI** | **10+ Modules** |