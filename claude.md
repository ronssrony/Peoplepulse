You are a senior Laravel + Inertia (Vue 3) architect building an internal enterprise system.

Project Name: PeoplePulse – People Operations Management System (POMS)

This project is already installed with:

Laravel (latest LTS)

Inertia.js

Vue 3

Authentication already working

Your task is to design and implement Module A: Attendance Tracking following clean architecture and best practices.

🔹 CORE GOALS

Centralized attendance tracking

Accurate net working hour calculation

Role-based and department-based visibility

Auditability and transparency

Clean UX for internal users

🔹 EXISTING USER FIELDS (DO NOT REMOVE)

User table already has:

employee_id

name

department

sub_department

designation

email

role (admin | manager | user)

You may add additional fields if necessary, but must explain why.

🔹 ROLES & VISIBILITY RULES
Admin

Can see all employees

Can see all attendance records

Can override attendance (with audit log)

Manager

Can see employees:

In their department

AND if they are assigned as manager of a sub-department, they can see all users in that sub-department

Cannot see users outside their scope

Can request or suggest corrections (not silently override)

User

Can see only their own attendance

Can clock in / clock out

Cannot edit past records

Implement this using:

Laravel Policies

Query scopes

Clean authorization logic

🔹 MODULE A: ATTENDANCE TRACKING
Functional Requirements
1️⃣ Clock In / Clock Out

Single daily record per user

Prevent double clock-in

Clock-out only allowed after clock-in

Store IP address & user agent

2️⃣ Auto Calculation

System must calculate:

Gross hours = clock_out - clock_in

Break duration (configurable, default 1 hour)

Net Effective Hours

Store:

gross_minutes

break_minutes

net_minutes

3️⃣ Late Detection

Configurable office start time

Mark is_late = true if clock-in is after allowed time

4️⃣ Weekend Definition (Per User)

Each user can have:

Fri/Sat

Sat/Sun

Fri only

This must be:

Configurable per user

Used to prevent attendance on weekends

Stored cleanly in DB (not hardcoded)

5️⃣ Audit Logs

Any manual change must be logged

Track:

who changed

old values

new values

reason

timestamp

🔹 DATABASE DESIGN (YOU MUST CREATE)

Provide:

Migrations

Indexes

Foreign keys

Tables to include:

attendances

attendance_audit_logs

(any helper tables if needed)

Explain schema decisions briefly.

🔹 BACKEND STRUCTURE (IMPORTANT)

Use Clean Architecture:

Controllers → thin

Business logic → Service classes

Authorization → Policies

Queries → Scopes or Query Objects

Required files:

AttendanceController

AttendanceService

AttendancePolicy

Models with relationships

Request validation classes

🔹 FRONTEND (INERTIA + VUE 3)

Build clean internal UI pages:

User Dashboard

Today’s status

Clock In / Clock Out buttons

Time worked today

Late indicator

Manager Dashboard

Department attendance summary

List of employees under scope

Filters (date, sub-department)

Admin Dashboard

All employees

Global attendance table

Manual override modal (with reason input)

UX Guidelines:

Minimal clicks

Clear status badges

Disabled actions when not allowed

Error messages human-readable

🔹 CONFIGURATION

Add config files for:

Office start time

Default break duration

Late grace period

🔹 OUTPUT FORMAT REQUIREMENTS

You MUST output:

Database migrations

Models & relationships

Policies

Services (business logic)

Controllers

Vue (Inertia) pages

Brief explanation of flow

Any assumptions made

Code must be:

Production-ready

Laravel-idiomatic

Easy to extend for leave management later
