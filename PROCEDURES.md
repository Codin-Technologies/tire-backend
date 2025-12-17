# System Operating Procedures (SOP)

This document outlines the standard operating procedures for the Tire Management System.
All API interactions can be performed via the Swagger UI at `/api/documentation`.

## 1. Initial Setup (Administrator)
**Goal:** Initialize the system with master data (Users, Warehouses, Initial Stock).

**Step 1.1: Login as Administrator**
*   **Endpoint:** `POST /api/login`
*   **Body:** `{"email": "frankkiruma05@gmail.com", "password": "<your_password>"}`
*   **Action:** Copy the `token` from the response.
*   **Auth:** Click **Authorize** button in Swagger and paste `Bearer <token>`.

**Step 1.2: Create Locations (Warehouses)**
*   **Endpoint:** `POST /api/stock/warehouses` (If API exists, otherwise use Seeder data)
*   *Note: Warehouses are currently created via Seeders or direct DB access. Ensure "Central Warehouse" exists.*

**Step 1.3: Create Staff Accounts**
*   **Endpoint:** `POST /api/admin/users`
*   **Body Example:**
    ```json
    {
      "name": "John Technician",
      "email": "tech1@tiresystem.com",
      "role": "Technician" // or "Fleet Manager"
    }
    ```
*   **Result:** System emails the new user their credentials.

**Step 1.4: Add Tire Stock**
*   **Endpoint:** `POST /api/stock/tires`
*   **Body Example:**
    ```json
    {
      "brand": "Michelin",
      "model": "X Multi Z",
      "size": "315/80R22.5",
      "cost": 450.00,
      "warehouse_id": 1,
      "serial_number": "SN-2025-001"
    }
    ```

---

## 2. Daily Operations (Technician)
**Goal:** Track tire usage and mounting on vehicles.

**Step 2.1: Login**
*   Technician logs in using credentials received via email.

**Step 2.2: Mount Tire to Vehicle**
*   **Endpoint:** `POST /api/operations/mount`
*   **Body Example:**
    ```json
    {
      "tire_id": 1,
      "vehicle_id": 5,
      "position": "FL", // Front Left
      "odometer": 50000
    }
    ```

**Step 2.3: Dismount/Rotate**
*   Use `POST /api/operations/dismount` or `rotate` as needed.

---

## 3. Inspections (Technician & Manager)
**Goal:** Regular safety checks and compliance.

**Step 3.1: Create Inspection (Technician)**
*   **Endpoint:** `POST /api/inspections`
*   **Body Example:**
    ```json
    {
      "vehicle_id": 5,
      "odometer": 50500,
      "type": "routine",
      "items": [
        {
          "position": "FL",
          "pressure_psi": 105,
          "tread_depth_mm": 12,
          "condition": "good"
        }
      ]
    }
    ```
*   *Note: Low pressure (< 90psi) will automatically trigger an Alert.*

**Step 3.2: Approve Inspection (Manager)**
*   **Endpoint:** `POST /api/inspections/{id}/approve`
*   **Auth:** Requires 'Fleet Manager' or 'Administrator' role.

---

## 4. Reporting & Analytics (Manager)
**Goal:** Analyze costs and inventory health.

**Step 4.1: View Low Stock**
*   **Endpoint:** `GET /api/reports/low-stock`

**Step 4.2: Tire Performance (Cost Per KM)**
*   **Endpoint:** `GET /api/reports/tire-performance`
*   **Result:** returns list of tires/brands sorted by CPK efficiency.

---

## 5. Security & Maintenance
*   **Change Password:** Users should call `POST /api/change-password` regularly.
*   **Audit Logs:** Admin can view `AuditLog` table (via database) to trace all user actions.
