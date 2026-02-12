-- docker/init.sql
-- 1. Users Table (Mock for Auth)
CREATE TABLE users (
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
-- Insert Mock Users
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
VALUES -- Admin User
    (
        'admin',
        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
        '001',
        'Admin User',
        'IT',
        'System Administrator',
        'Manager',
        'Manager',
        '001',
        'sub_admin'
    ),
    -- Laundry Manager (for uniform approval testing)
    (
        'laundry_mgr',
        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
        '002',
        'Laundry Manager',
        'Facilities - Laundry',
        'Laundry Manager',
        'Manager',
        'Manager',
        '002',
        'Employee'
    ),
    -- Regular User
    (
        'johndoe',
        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
        '003',
        'John Doe',
        'Production 1',
        'Operator',
        'Rank and File',
        'Associate',
        '003',
        'Employee'
    ),
    -- Super Approver (Mock ID 4)
    (
        'super_approver',
        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
        '4',
        'Super Approver',
        'Admin',
        'Plant Manager',
        'Director',
        'Director',
        '4',
        'admin'
    );
-- Note: 'password' hash is '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' (which is 'password') - wait, standard bcrypt for 'password' is usually different but let's assume valid hash or use plain text temporarily if code supports it (code supports legacy md5/sha1/plain too, but verify calls password_verify first). I'll generate a valid bcrypt hash for 'password123'.
-- Hash for 'password123': $2y$10$r/w/o/w/o/w/o/w/o/w/oeK.eK.eK.eK.eK.eK.eK.eK.eK.eK.eK (just kidding, I'll use a known hash or let the user reset).
-- Actually the code checkLegacyPassword includes plain text check: if ($inputPassword === $storedHash). So I can just store 'password123' and it will work!
UPDATE users
SET password = 'password123';
-- 2. Requests Table
CREATE TABLE Requests (
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
CREATE TABLE RequestItems (
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
CREATE TABLE ItemMaster (
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
-- Insert Sample Items (Subset)
INSERT INTO ItemMaster (
        category,
        item_code,
        item_name,
        sub_group,
        default_uom,
        approver,
        price
    )
VALUES (
        'cleaning chemical',
        'ITEM-001',
        'Generic Cleaner',
        'Facilities',
        'pc',
        'Facilities',
        0
    ),
    (
        'cleaning chemical',
        'ITEM-002',
        'Generic Liquid',
        'Facilities',
        'gallon',
        'Facilities',
        0
    ),
    (
        'uniform & PPEs',
        'ITEM-003',
        'Generic Boots',
        'Facilities',
        'pair',
        'Facilities',
        0
    ),
    (
        'uniform & PPEs',
        'ITEM-004',
        'Generic Uniform',
        'Facilities',
        'pc',
        'Facilities',
        0
    );
-- 5. Approvers Table
CREATE TABLE Approvers (
    id SERIAL PRIMARY KEY,
    department VARCHAR(100) NOT NULL,
    approver_name_1 VARCHAR(100) NOT NULL,
    approver_name_2 VARCHAR(100)
);
INSERT INTO Approvers (department, approver_name_1, approver_name_2)
VALUES ('Admin', 'Super Approver', 'Admin Approver 2'),
    ('IT', 'Admin User', 'IT Approver 2'),
    (
        'Production 1',
        'Prod Approver 1',
        'Prod Approver 2'
    ),
    ('Facilities', 'Laundry Manager', NULL),
    ('Facilities - Laundry', 'Laundry Manager', NULL),
    (
        'Facilities - Housekeeping',
        'Laundry Manager',
        NULL
    );
-- 6. Admins Table (ItemRequest_Admins)
CREATE TABLE ItemRequest_Admins (
    id SERIAL PRIMARY KEY,
    biometrics_id VARCHAR(50),
    role VARCHAR(50),
    added_by VARCHAR(100) DEFAULT 'System',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
INSERT INTO ItemRequest_Admins (biometrics_id, role, added_by)
VALUES ('4', 'super_admin', 'System Init'),
    ('001', 'admin', 'System Init');