# TMS SKU API Documentation

## Overview

The **TMS SKU API** is a comprehensive RESTful API for managing Stock Keeping Units (SKUs) in the Tire Management System. It enables external systems to create, update, retrieve, and synchronize SKU data seamlessly.

### Key Features

- ✅ Create and manage SKUs
- ✅ Update existing SKU information
- ✅ Bulk SKU creation
- ✅ Stock level tracking and alerts
- ✅ Real-time stock status monitoring
- ✅ Token-based authentication (Bearer token)
- ✅ Comprehensive error handling
- ✅ Pagination and filtering
- ✅ Tire-specific attributes support

---

## Authentication

All API requests require authentication using a **Bearer Token**.

### Headers Required

```http
Content-Type: application/json
Authorization: Bearer YOUR_ACCESS_TOKEN
```

### How to Get a Token

1. Login via `/api/login` with valid credentials
2. Use the returned token for all subsequent requests
3. Token should be included in the `Authorization` header

**Example:**
```bash
curl -X POST https://your-api-url.com/api/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "user@example.com",
    "password": "your-password"
  }'
```

**Response:**
```json
{
  "token": "1|abcdefghijklmnopqrstuvwxyz123456",
  "user": {...}
}
```

---

## Base URL

```
Production: https://your-production-url.com/api
Development: http://localhost/api
```

---

## API Endpoints

### 1. **List SKUs** (with filtering & pagination)

**Endpoint:** `GET /api/sku/list`

**Description:** Retrieve a paginated list of all SKUs with optional filtering.

**Query Parameters:**

| Parameter | Type | Description | Example |
|-----------|------|-------------|---------|
| `status` | string | Filter by status | `active`, `inactive`, `discontinued` |
| `category` | string | Filter by category | `Tires` |
| `brand` | string | Filter by brand (partial match) | `Michelin` |
| `low_stock` | boolean | Show only low stock items | `true`, `false` |
| `needs_reorder` | boolean | Show items needing reorder | `true`, `false` |
| `search` | string | Search by SKU code or name | `TIRE-MIC` |
| `per_page` | integer | Items per page (default: 20) | `50` |

**Example Request:**
```bash
curl -X GET "https://your-api-url.com/api/sku/list?status=active&per_page=20" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json"
```

**Success Response (200):**
```json
{
  "current_page": 1,
  "data": [
    {
      "id": 1,
      "sku_code": "TIRE-MIC-205-55-16",
      "sku_name": "Michelin Primacy 4 205/55 R16",
      "brand": "Michelin",
      "model": "Primacy 4",
      "size": "205/55 R16",
      "category": "Tires",
      "unit_price": "150.00",
      "cost_price": "100.00",
      "current_stock": 50,
      "status": "active",
      "created_at": "2026-01-07T10:00:00.000000Z"
    }
  ],
  "total": 100,
  "per_page": 20,
  "last_page": 5
}
```

---

### 2. **Get SKU by Code**

**Endpoint:** `GET /api/sku/{sku_code}`

**Description:** Retrieve detailed information for a specific SKU.

**Example Request:**
```bash
curl -X GET "https://your-api-url.com/api/sku/TIRE-MIC-205-55-16" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json"
```

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "sku_code": "TIRE-MIC-205-55-16",
    "sku_name": "Michelin Primacy 4 205/55 R16",
    "brand": "Michelin",
    "model": "Primacy 4",
    "size": "205/55 R16",
    "tire_type": "summer",
    "load_index": 91,
    "speed_rating": "V",
    "unit_price": "150.00",
    "cost_price": "100.00",
    "current_stock": 50,
    "min_stock_level": 10,
    "reorder_point": 20,
    "status": "active",
    "is_low_stock": false,
    "needs_reorder": false,
    "stock_status": "in_stock",
    "profit_margin": 50.00
  }
}
```

**Error Response (404):**
```json
{
  "error": "SKU not found",
  "message": "SKU with code 'INVALID-CODE' does not exist"
}
```

---

### 3. **Create New SKU**

**Endpoint:** `POST /api/sku/create`

**Description:** Register a new SKU in the system.

**Required Fields:**
- `sku_code` (unique)
- `sku_name`
- `unit_price`

**Request Body:**
```json
{
  "sku_code": "TIRE-MIC-205-55-16",
  "sku_name": "Michelin Primacy 4 205/55 R16",
  "category": "Tires",
  "unit_of_measure": "piece",
  "unit_price": 150.00,
  "cost_price": 100.00,
  "status": "active",
  "description": "Premium summer tire for sedans",
  "brand": "Michelin",
  "model": "Primacy 4",
  "size": "205/55 R16",
  "tire_type": "summer",
  "load_index": 91,
  "speed_rating": "V",
  "current_stock": 50,
  "min_stock_level": 10,
  "max_stock_level": 200,
  "reorder_point": 20,
  "metadata": {
    "supplier": "Michelin Direct",
    "warranty_months": 24
  }
}
```

**Example Request:**
```bash
curl -X POST "https://your-api-url.com/api/sku/create" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "sku_code": "TIRE-MIC-205-55-16",
    "sku_name": "Michelin Primacy 4 205/55 R16",
    "unit_price": 150.00,
    "brand": "Michelin",
    "current_stock": 50
  }'
```

**Success Response (201):**
```json
{
  "success": true,
  "message": "SKU created successfully",
  "data": {
    "id": 1,
    "sku_code": "TIRE-MIC-205-55-16",
    "sku_name": "Michelin Primacy 4 205/55 R16",
    "unit_price": "150.00",
    "status": "active",
    "created_at": "2026-01-07T10:00:00.000000Z"
  }
}
```

**Error Response (422 - Validation Error):**
```json
{
  "error": "Validation failed",
  "message": "Please check your input data",
  "errors": {
    "sku_code": ["The sku code field is required."],
    "unit_price": ["The unit price must be at least 0."]
  }
}
```

**Error Response (409 - Duplicate):**
```json
{
  "error": "Duplicate SKU code",
  "message": "SKU code already exists in the system"
}
```

---

### 4. **Update SKU**

**Endpoint:** `PUT /api/sku/{sku_code}`

**Description:** Update an existing SKU's information.

**Request Body (all fields optional):**
```json
{
  "sku_name": "Updated Michelin Primacy 4",
  "unit_price": 160.00,
  "current_stock": 75,
  "status": "active",
  "min_stock_level": 15
}
```

**Example Request:**
```bash
curl -X PUT "https://your-api-url.com/api/sku/TIRE-MIC-205-55-16" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "unit_price": 160.00,
    "current_stock": 75
  }'
```

**Success Response (200):**
```json
{
  "success": true,
  "message": "SKU updated successfully",
  "data": {
    "sku_code": "TIRE-MIC-205-55-16",
    "sku_name": "Michelin Primacy 4 205/55 R16",
    "unit_price": "160.00",
    "current_stock": 75,
    "updated_at": "2026-01-07T11:00:00.000000Z"
  }
}
```

---

### 5. **Delete SKU**

**Endpoint:** `DELETE /api/sku/{sku_code}`

**Description:** Soft delete an SKU (can be restored later).

**Example Request:**
```bash
curl -X DELETE "https://your-api-url.com/api/sku/TIRE-MIC-205-55-16" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json"
```

**Success Response (200):**
```json
{
  "success": true,
  "message": "SKU deleted successfully"
}
```

---

### 6. **Bulk Create SKUs**

**Endpoint:** `POST /api/sku/bulk-create`

**Description:** Create multiple SKUs in a single request.

**Request Body:**
```json
{
  "skus": [
    {
      "sku_code": "TIRE-MIC-195-65-15",
      "sku_name": "Michelin Energy Saver 195/65 R15",
      "unit_price": 120.00
    },
    {
      "sku_code": "TIRE-BRI-205-55-16",
      "sku_name": "Bridgestone Turanza 205/55 R16",
      "unit_price": 140.00
    }
  ]
}
```

**Success Response (201):**
```json
{
  "success": true,
  "message": "Bulk creation completed",
  "results": {
    "total": 2,
    "success": 2,
    "failed": 0,
    "errors": []
  }
}
```

**Partial Success Response:**
```json
{
  "success": true,
  "message": "Bulk creation completed",
  "results": {
    "total": 3,
    "success": 2,
    "failed": 1,
    "errors": [
      {
        "row": 2,
        "sku_code": "TIRE-MIC-195-65-15",
        "error": "SKU code already exists"
      }
    ]
  }
}
```

---

### 7. **Get Stock Alerts**

**Endpoint:** `GET /api/sku/stock-alerts`

**Description:** Retrieve all SKUs with low stock or needing reorder.

**Example Request:**
```bash
curl -X GET "https://your-api-url.com/api/sku/stock-alerts" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json"
```

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "low_stock": [
      {
        "sku_code": "TIRE-MIC-205-55-16",
        "sku_name": "Michelin Primacy 4 205/55 R16",
        "current_stock": 8,
        "min_stock_level": 10,
        "alert_type": "low_stock"
      }
    ],
    "needs_reorder": [
      {
        "sku_code": "TIRE-BRI-195-65-15",
        "sku_name": "Bridgestone Turanza 195/65 R15",
        "current_stock": 15,
        "reorder_point": 20,
        "alert_type": "needs_reorder"
      }
    ],
    "total_alerts": 2
  }
}
```

---

## HTTP Status Codes

| Code | Meaning | Description |
|------|---------|-------------|
| 200 | OK | Request successful |
| 201 | Created | Resource created successfully |
| 204 | No Content | Resource deleted successfully |
| 400 | Bad Request | Invalid request format |
| 401 | Unauthorized | Missing or invalid token |
| 404 | Not Found | Resource not found |
| 409 | Conflict | Duplicate resource (e.g., SKU code) |
| 422 | Unprocessable Entity | Validation failed |
| 500 | Internal Server Error | Server-side error |

---

## Error Handling Best Practices

### 1. **Always Check Status Codes**
```javascript
if (response.status === 200) {
  // Success
} else if (response.status === 422) {
  // Validation error - show field errors
} else if (response.status === 401) {
  // Unauthorized - redirect to login
}
```

### 2. **Handle Validation Errors**
```javascript
if (response.status === 422) {
  const errors = response.data.errors;
  Object.keys(errors).forEach(field => {
    console.log(`${field}: ${errors[field][0]}`);
  });
}
```

### 3. **Retry Logic**
Only retry on network errors or 5xx server errors, not on 4xx client errors.

### 4. **Logging**
Always log failed requests with request/response details for debugging.

---

## Integration Example (JavaScript/Node.js)

```javascript
const axios = require('axios');

const API_BASE_URL = 'https://your-api-url.com/api';
const AUTH_TOKEN = 'YOUR_TOKEN_HERE';

// Create SKU
async function createSKU(skuData) {
  try {
    const response = await axios.post(`${API_BASE_URL}/sku/create`, skuData, {
      headers: {
        'Authorization': `Bearer ${AUTH_TOKEN}`,
        'Content-Type': 'application/json'
      }
    });
    
    console.log('SKU created:', response.data);
    return response.data;
    
  } catch (error) {
    if (error.response) {
      // Server responded with error
      console.error('Error:', error.response.data);
      
      if (error.response.status === 422) {
        // Validation errors
        console.error('Validation errors:', error.response.data.errors);
      } else if (error.response.status === 409) {
        // Duplicate SKU
        console.error('SKU already exists');
      }
    } else {
      // Network error
      console.error('Network error:', error.message);
    }
    throw error;
  }
}

// Update SKU
async function updateSKU(skuCode, updateData) {
  try {
    const response = await axios.put(
      `${API_BASE_URL}/sku/${skuCode}`,
      updateData,
      {
        headers: {
          'Authorization': `Bearer ${AUTH_TOKEN}`,
          'Content-Type': 'application/json'
        }
      }
    );
    
    console.log('SKU updated:', response.data);
    return response.data;
    
  } catch (error) {
    console.error('Update failed:', error.response?.data || error.message);
    throw error;
  }
}

// Get SKU
async function getSKU(skuCode) {
  try {
    const response = await axios.get(`${API_BASE_URL}/sku/${skuCode}`, {
      headers: {
        'Authorization': `Bearer ${AUTH_TOKEN}`
      }
    });
    
    return response.data.data;
    
  } catch (error) {
    if (error.response?.status === 404) {
      console.error('SKU not found');
      return null;
    }
    throw error;
  }
}

// Usage
(async () => {
  // Create new SKU
  await createSKU({
    sku_code: 'TIRE-MIC-205-55-16',
    sku_name: 'Michelin Primacy 4 205/55 R16',
    unit_price: 150.00,
    current_stock: 50
  });
  
  // Update SKU
  await updateSKU('TIRE-MIC-205-55-16', {
    current_stock: 75,
    unit_price: 160.00
  });
  
  // Get SKU details
  const sku = await getSKU('TIRE-MIC-205-55-16');
  console.log('SKU details:', sku);
})();
```

---

## Security Best Practices

1. **Never expose tokens on frontend** - Always call API from backend
2. **Validate data before sending** - Don't rely solely on API validation
3. **Use HTTPS in production** - Encrypt all communications
4. **Store tokens securely** - Use environment variables
5. **Implement rate limiting** - Prevent API abuse
6. **Log all requests** - Maintain audit trail

---

## Support & Contact

For API issues or questions:
- Email: support@tms-system.com
- Documentation: https://docs.tms-system.com
- API Status: https://status.tms-system.com

---

**Last Updated:** January 7, 2026  
**API Version:** 1.0
