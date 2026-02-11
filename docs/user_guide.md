# User Guide: Item Request System

## Overview

The Item Request System allows employees to submit requests for items (cleaning supplies, uniforms, PPE, clinic items, etc.) and have them approved by their department's manager or supervisor.

---

## User Roles

| Role         | Who                  | Capabilities                                           |
| ------------ | -------------------- | ------------------------------------------------------ |
| **Staff**    | All employees        | Create requests, view own department's requests, print |
| **Approver** | Managers/Supervisors | All staff capabilities + Approve/Reject/Undo requests  |

---

## Pages

### 1. Login (`auth/login.php`)

- Enter your company credentials (username & password)
- System automatically detects your department and role

### 2. Dashboard (`index.php`)

- **Statistics Cards**: Total, Pending, Approved, Rejected counts
- **Request List**: Filterable table of requests
- **Filters**: Search by ID, Name, Date, Area, Status
- **Actions**:
  - Click "New Request" to create
  - Click row to view details
  - Approvers see Approve/Reject buttons

### 3. Create Request (`create.php`)

- **Auto-filled**: Your name, employee ID, department
- **Required Fields**:
  - Assigned Area (P1, P2, P3, P4)
  - Date Needed
  - Time Needed
- **Add Items**: Select category → Search item → Enter quantity
- **Salary Deduction**: Checkbox appears for priced items (e.g., uniforms)
- **Submit**: Creates a Pending request

### 4. View Request (`view.php`)

- Full details of a single request
- Shows: Requestor info, items list, status, approval history
- **Print button**: Generates PDF
- Approvers can Approve/Reject from here

### 5. Batch Print (`print_batch.php`)

- Exports current filtered list to printable PDF format
- Access via PDF icon on Dashboard

---

## Process Flow

```
Employee Login
     ↓
Dashboard (View Department Requests)
     ↓
Create New Request
     ↓
Add Items + Submit
     ↓
Status: PENDING
     ↓
Manager/Supervisor Reviews
     ↓
Approve → APPROVED
   or
Reject → REJECTED (can Undo to Pending)
```

---

## Approval Rules

1. Approvers must have "Manager" or "Supervisor" in their job title
2. Approvers must be registered in the Approvers database table
3. Approvers can only act on requests from their own department
4. Approved requests are final (no undo)
5. Rejected requests can be reverted to Pending via "Undo"

---

## Item Categories

- Cleaning Chemical
- Cleaning Material
- Uniform & PPEs
- Clinic Item
- Safety Item
- Services

---

_End of User Guide_
