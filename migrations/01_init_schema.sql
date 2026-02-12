-- migrations/01_init_schema.sql
-- 0. Schema Setup
CREATE SCHEMA IF NOT EXISTS item_request;
SET search_path TO item_request;
-- 1. Users Table (Mock for Auth)
CREATE TABLE IF NOT EXISTS users (
    id SERIAL PRIMARY KEY,
    username VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    empcode VARCHAR(50) NOT NULL,
    fullname VARCHAR(100) NOT NULL,
    department VARCHAR(100),
    position_title VARCHAR(100),
    job_level VARCHAR(50),
    role_profile VARCHAR(100),
    employee_id VARCHAR(50),
    role VARCHAR(50) DEFAULT 'Employee'
);
-- Insert Mock Users (Only if table is empty to avoid duplicates on re-run)
INSERT INTO users (
        username,
        password,
        empcode,
        fullname,
        department,
        position_title,
        job_level,
        role_profile,
        employee_id,
        role
    )
SELECT 'admin',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    '001',
    'Admin User',
    'IT',
    'System Administrator',
    'Manager',
    'Manager',
    '001',
    'sub_admin'
WHERE NOT EXISTS (
        SELECT 1
        FROM users
        WHERE username = 'admin'
    )
UNION ALL
SELECT 'laundry_mgr',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    '002',
    'Laundry Manager',
    'Facilities - Laundry',
    'Laundry Manager',
    'Manager',
    'Manager',
    '002',
    'Employee'
WHERE NOT EXISTS (
        SELECT 1
        FROM users
        WHERE username = 'laundry_mgr'
    )
UNION ALL
SELECT 'johndoe',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    '003',
    'John Doe',
    'Production 1',
    'Operator',
    'Rank and File',
    'Associate',
    '003',
    'Employee'
WHERE NOT EXISTS (
        SELECT 1
        FROM users
        WHERE username = 'johndoe'
    )
UNION ALL
SELECT 'super_approver',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    '4',
    'Super Approver',
    'Admin',
    'Plant Manager',
    'Director',
    'Director',
    '4',
    'admin'
WHERE NOT EXISTS (
        SELECT 1
        FROM users
        WHERE username = 'super_approver'
    );
-- Update password to 'password123' for easy testing
UPDATE users
SET password = 'password123';
-- 2. Requests Table
CREATE TABLE IF NOT EXISTS Requests (
    request_id SERIAL PRIMARY KEY,
    date_filed TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    date_needed TIMESTAMP,
    time_needed VARCHAR(20),
    requestor_name VARCHAR(100),
    employee_id VARCHAR(50),
    requestor_photo_id VARCHAR(50),
    department VARCHAR(100),
    assigned_area VARCHAR(50),
    status VARCHAR(50) DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    rejection_reason TEXT,
    action_by VARCHAR(255),
    action_date TIMESTAMP,
    auth_deduct INT DEFAULT 0,
    is_company_issued INT DEFAULT 0
);
-- 3. RequestItems Table
CREATE TABLE IF NOT EXISTS RequestItems (
    item_id SERIAL PRIMARY KEY,
    request_id INT REFERENCES Requests(request_id) ON DELETE CASCADE,
    category VARCHAR(50),
    item_code VARCHAR(50),
    item_name VARCHAR(100),
    sub_group VARCHAR(50),
    uom VARCHAR(20),
    size VARCHAR(20),
    quantity INT,
    approver VARCHAR(100),
    items_needed TEXT
);
-- 4. ItemMaster Table
CREATE TABLE IF NOT EXISTS ItemMaster (
    id SERIAL PRIMARY KEY,
    category VARCHAR(50),
    item_code VARCHAR(50),
    item_name VARCHAR(100),
    sub_group VARCHAR(50),
    default_uom VARCHAR(20),
    approver VARCHAR(100),
    price DECIMAL(10, 2) DEFAULT 0,
    restricted_roles VARCHAR(255)
);
-- Insert Sample Items (Subset) if empty
INSERT INTO ItemMaster (
        category,
        item_code,
        item_name,
        sub_group,
        default_uom,
        approver,
        price
    )
SELECT 'cleaning chemical',
    'ITEM-001',
    'Generic Cleaner',
    'Facilities',
    'pc',
    'Facilities',
    0
WHERE NOT EXISTS (
        SELECT 1
        FROM ItemMaster
        WHERE item_code = 'ITEM-001'
    )
UNION ALL
SELECT 'cleaning chemical',
    'ITEM-002',
    'Generic Liquid',
    'Facilities',
    'gallon',
    'Facilities',
    0
WHERE NOT EXISTS (
        SELECT 1
        FROM ItemMaster
        WHERE item_code = 'ITEM-002'
    )
UNION ALL
SELECT 'uniform & PPEs',
    'ITEM-003',
    'Generic Boots',
    'Facilities',
    'pair',
    'Facilities',
    0
WHERE NOT EXISTS (
        SELECT 1
        FROM ItemMaster
        WHERE item_code = 'ITEM-003'
    )
UNION ALL
SELECT 'uniform & PPEs',
    'ITEM-004',
    'Generic Uniform',
    'Facilities',
    'pc',
    'Facilities',
    0
WHERE NOT EXISTS (
        SELECT 1
        FROM ItemMaster
        WHERE item_code = 'ITEM-004'
    );
-- 5. Approvers Table
CREATE TABLE IF NOT EXISTS Approvers (
    id SERIAL PRIMARY KEY,
    department VARCHAR(100) NOT NULL,
    approver_name_1 VARCHAR(100) NOT NULL,
    approver_name_2 VARCHAR(100)
);
INSERT INTO Approvers (department, approver_name_1, approver_name_2)
SELECT 'Admin',
    'Super Approver',
    'Admin Approver 2'
WHERE NOT EXISTS (
        SELECT 1
        FROM Approvers
        WHERE department = 'Admin'
    )
UNION ALL
SELECT 'IT',
    'Admin User',
    'IT Approver 2'
WHERE NOT EXISTS (
        SELECT 1
        FROM Approvers
        WHERE department = 'IT'
    )
UNION ALL
SELECT 'Production 1',
    'Prod Approver 1',
    'Prod Approver 2'
WHERE NOT EXISTS (
        SELECT 1
        FROM Approvers
        WHERE department = 'Production 1'
    )
UNION ALL
SELECT 'Facilities',
    'Laundry Manager',
    NULL
WHERE NOT EXISTS (
        SELECT 1
        FROM Approvers
        WHERE department = 'Facilities'
    )
UNION ALL
SELECT 'Facilities - Laundry',
    'Laundry Manager',
    NULL
WHERE NOT EXISTS (
        SELECT 1
        FROM Approvers
        WHERE department = 'Facilities - Laundry'
    )
UNION ALL
SELECT 'Facilities - Housekeeping',
    'Laundry Manager',
    NULL
WHERE NOT EXISTS (
        SELECT 1
        FROM Approvers
        WHERE department = 'Facilities - Housekeeping'
    );
-- 6. Admins Table (ItemRequest_Admins)
CREATE TABLE IF NOT EXISTS ItemRequest_Admins (
    id SERIAL PRIMARY KEY,
    biometrics_id VARCHAR(50),
    role VARCHAR(50),
    added_by VARCHAR(100) DEFAULT 'System',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
INSERT INTO ItemRequest_Admins (biometrics_id, role, added_by)
SELECT '4',
    'super_admin',
    'System Init'
WHERE NOT EXISTS (
        SELECT 1
        FROM ItemRequest_Admins
        WHERE biometrics_id = '4'
    )
UNION ALL
SELECT '001',
    'admin',
    'System Init'
WHERE NOT EXISTS (
        SELECT 1
        FROM ItemRequest_Admins
        WHERE biometrics_id = '001'
    );
-- 7. UniformAllowances Table (New)
CREATE TABLE IF NOT EXISTS UniformAllowances (
    id SERIAL PRIMARY KEY,
    department VARCHAR(100) NOT NULL,
    level VARCHAR(50) NOT NULL,
    uniform_name VARCHAR(200) NOT NULL,
    max_quantity INT NOT NULL,
    issuance_type VARCHAR(20) NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(department, level, uniform_name)
);