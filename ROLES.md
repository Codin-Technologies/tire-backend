# System Roles & Responsibilities

This document defines the user roles available in the Tire Management System and their specific capabilities.

## 1. Administrator `(Administrator)`
**Description**: The Super User with full control over the system.
**Key Responsibilities**:
*   **User Management**: Create/Edit users, reset passwords, assign roles.
*   **System Configuration**: Manage master data (Warehouses, Vehicle Types, Tire Models).
*   **Audit**: View full system audit logs.
*   **All Access**: Can perform any action available to other roles.
**Permissions**: `All Permissions`

## 2. Fleet Manager `(Fleet Manager)`
**Description**: Responsible for the overall tire inventory, costs, and compliance of the fleet.
**Key Responsibilities**:
*   **Inventory Control**: Purchase new tires, move stock between warehouses.
*   **Approvals**: Review and Approve/Reject inspection results submitted by technicians.
*   **Reporting**: View financial reports, tire performance (CPK), and low stock alerts.
*   **Operations**: Can view and oversee all tire operations.
**Permissions**:
*   `view/create/edit stock`
*   `view/perform operations`
*   `view/approve inspections`
*   `view reports`

## 3. Workshop Supervisor `(Workshop Supervisor)`
**Description**: Oversees the daily workshop activities and technical team.
**Key Responsibilities**:
*   **Job Management**: Assign inspection tasks to technicians.
*   **Quality Control**: Review inspection results before final approval.
*   **Inventory**: Manage local stock levels in the workshop.
*   **Technician Oversight**: Monitor operations performed by technicians.
**Permissions**:
*   `view/create/edit stock`
*   `view/perform operations`
*   `view/approve inspections`

## 4. Technician `(Technician)`
**Description**: The hands-on mechanics performing the actual work.
**Key Responsibilities**:
*   **Operations**: Physical tire mounting, dismounting, rotation, and repairs.
*   **Inspections**: Perform routine vehicle and tire inspections using the checklist.
*   **Data Entry**: Record odometer readings, tread depth, and pressure into the system.
*   **View Access**: Can see stock availability to request tires.
**Permissions**:
*   `view stock`
*   `view/perform operations`
*   `view/perform inspections`

## 5. Auditor / Viewer `(Read-Only)`
**Description**: External auditors or stakeholders who need visibility but cannot change data.
**Key Responsibilities**:
*   **Monitoring**: View dashboards, stock levels, and reports.
*   **Verification**: Check inspection history and vehicle status.
**Permissions**:
*   `view stock`
*   `view operations`
*   `view inspections`
*   `view reports`

---

## API Access Matrix (Swagger Reference)

Below is the list of Swagger endpoints enabling each role.

### 🟢 Technician APIs (The "Doers")
These users are logged in on tablets/mobile in the workshop.
*   **Auth**: `POST /api/login`, `GET /api/user`, `POST /api/change-password`
*   **Work**:
    *   `POST /api/operations/mount` (Mount tire to vehicle)
    *   `POST /api/operations/dismount`
    *   `POST /api/operations/rotate`
    *   `POST /api/operations/repair`
    *   `POST /api/operations/validate-tire` (Check compatibility)
*   **Checking**:
    *   `POST /api/inspections` (Start new inspection)
    *   `PUT /api/inspections/{id}` (Submit inspection results)
    *   `GET /api/inspection-checklist` (Get list of things to check)
*   **Read-Only Access**:
    *   `GET /api/stock/tires` (Check inventory)
    *   `GET /api/operations/vehicles/{id}/timeline`

### 🔵 Fleet Manager APIs (The "Approvers")
These users are on laptops/desktops managing the fleet.
*   **All Technician APIs**, PLUS:
*   **Approvals**:
    *   `POST /api/inspections/{id}/approve` (Finalize inspection)
    *   `POST /api/inspections/{id}/reject` (Request re-do)
    *   `POST /api/inspections/{id}/assign` (Give job to tech)
*   **Inventory Management**:
    *   `POST /api/stock/tires` (Register new tire purchase)
    *   `POST /api/stock/movements` (Move tires between warehouses)
    *   `POST /api/vehicles/{id}/retire`
*   **Reporting (Business Intelligence)**:
    *   `GET /api/reports/tire-performance` (CPK Analysis)
    *   `GET /api/reports/low-stock`
    *   `GET /api/reports/inspection-compliance`

### 🔴 Administrator APIs (The "Owners")
*   **All Technician & Manager APIs**, PLUS:
*   **User Management**:
    *   `GET /api/admin/users` (List staff)
    *   `POST /api/admin/users` (Onboard new staff)
    *   `PUT /api/admin/users/{id}` (Update roles)
    *   `DELETE /api/admin/users/{id}` (Remove access)
*   **Role Management**:
    *   `POST /api/admin/roles` (Define new custom roles)
