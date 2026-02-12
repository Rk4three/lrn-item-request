USE item_request_db;
GO -- Note: This assumes dsp_accounts and dsp_employees tables already exist from the authentication system
    -- 1. Requests Table
    IF NOT EXISTS (
        SELECT *
        FROM sysobjects
        WHERE name = 'Requests'
            AND xtype = 'U'
    ) BEGIN CREATE TABLE Requests (
        request_id INT IDENTITY(1, 1) PRIMARY KEY,
        date_filed DATETIME DEFAULT GETDATE(),
        date_needed DATETIME,
        time_needed VARCHAR(20),
        requestor_name VARCHAR(100),
        employee_id VARCHAR(50),
        department VARCHAR(100),
        assigned_area VARCHAR(50),
        status VARCHAR(50) DEFAULT 'Pending',
        created_at DATETIME DEFAULT GETDATE(),
        updated_at DATETIME DEFAULT GETDATE()
    );
PRINT 'Requests table created successfully';
END
ELSE BEGIN PRINT 'Requests table already exists';
END
GO -- 2. RequestItems Table
    IF NOT EXISTS (
        SELECT *
        FROM sysobjects
        WHERE name = 'RequestItems'
            AND xtype = 'U'
    ) BEGIN CREATE TABLE RequestItems (
        item_id INT IDENTITY(1, 1) PRIMARY KEY,
        request_id INT,
        category VARCHAR(50),
        item_code VARCHAR(50),
        item_name VARCHAR(100),
        sub_group VARCHAR(50),
        uom VARCHAR(20),
        size VARCHAR(20),
        quantity INT,
        approver VARCHAR(100),
        FOREIGN KEY (request_id) REFERENCES Requests(request_id) ON DELETE CASCADE
    );
PRINT 'RequestItems table created successfully';
END
ELSE BEGIN PRINT 'RequestItems table already exists';
END
GO -- 3. ItemMaster Table (Expanded with comprehensive items)
    IF NOT EXISTS (
        SELECT *
        FROM sysobjects
        WHERE name = 'ItemMaster'
            AND xtype = 'U'
    ) BEGIN CREATE TABLE ItemMaster (
        id INT IDENTITY(1, 1) PRIMARY KEY,
        category VARCHAR(50),
        item_code VARCHAR(50),
        item_name VARCHAR(100),
        sub_group VARCHAR(50),
        default_uom VARCHAR(20),
        approver VARCHAR(100),
        price DECIMAL(10, 2) DEFAULT 0,
        restricted_roles VARCHAR(255)
    );
PRINT 'ItemMaster table created, inserting sample data...';
-- Full Item Master List from sample.csv
INSERT INTO ItemMaster (
        category,
        item_code,
        item_name,
        sub_group,
        default_uom,
        approver,
        price,
        restricted_roles
    )
VALUES (
        'cleaning chemical',
        'CM.AS.03.0059',
        'Air Freshener Canister',
        'Facilities - Housekeeping',
        'pc',
        'Facilities - Housekeeping',
        0,
        NULL
    ),
    (
        'cleaning chemical',
        'CM.AS.03.0060',
        'Air Freshener Dispenser',
        'Facilities - Housekeeping',
        'pc',
        'Facilities - Housekeeping',
        0,
        NULL
    ),
    (
        'cleaning chemical',
        'CM.AS.03.0083',
        'All Star Light',
        'Facilities - Housekeeping',
        'gallon',
        'Facilities - Housekeeping',
        0,
        NULL
    ),
    (
        'cleaning chemical',
        'CM.AS.03.0003',
        'Biocare Liquid Laundry Soap  25L',
        'Facilities - Housekeeping',
        'gallon',
        'Facilities - Housekeeping',
        0,
        NULL
    ),
    (
        'cleaning chemical',
        'CM.EN.02.0252',
        'Degreaser Optikleen OCHeavy DutyOven Cleaner & Degreaser',
        'Facilities - Housekeeping',
        'pc',
        'Facilities - Housekeeping',
        0,
        NULL
    ),
    (
        'cleaning chemical',
        'CM.AS.03.0009',
        'Dirt and Stain Remover, Wipeout',
        'Facilities - Housekeeping',
        'pc',
        'Facilities - Housekeeping',
        0,
        NULL
    ),
    (
        'cleaning chemical',
        'CM.AS.03.0016',
        'Disinfectant Spray, Aerosol Can',
        'Facilities - Housekeeping',
        'pc',
        'Facilities - Housekeeping',
        0,
        NULL
    ),
    (
        'cleaning chemical',
        'CM.AS.03.0006',
        'Diswashing Liquid',
        'Facilities - Housekeeping',
        'gallon',
        'Facilities - Housekeeping',
        0,
        NULL
    ),
    (
        'cleaning chemical',
        'CM.AS.03.0007',
        'Divoquat Carbouy',
        'Facilities - Housekeeping',
        'gallon',
        'Facilities - Housekeeping',
        0,
        NULL
    ),
    (
        'cleaning chemical',
        'CM.AS.03.0008',
        'Divosan Hypoclorite Carbouy',
        'Facilities - Housekeeping',
        'gallon',
        'Facilities - Housekeeping',
        0,
        NULL
    ),
    (
        'cleaning chemical',
        'CM.AS.03.0012',
        'Glade Spray  Citrus Scent 320 Ml.',
        'Facilities - Housekeeping',
        'pc',
        'Facilities - Housekeeping',
        0,
        NULL
    ),
    (
        'cleaning chemical',
        'CM.AS.03.0065',
        'Glass Cleaner',
        'Facilities - Housekeeping',
        'pc',
        'Facilities - Housekeeping',
        0,
        NULL
    ),
    (
        'cleaning chemical',
        'CM.AS.03.0014',
        'Hand Bac -Handcare  4Gal/Cs',
        'Facilities - Housekeeping',
        'gallon',
        'Facilities - Housekeeping',
        0,
        NULL
    ),
    (
        'cleaning chemical',
        'CM.AS.03.0044',
        'Hand Sanitizer',
        'Facilities - Housekeeping',
        'gallon',
        'Facilities - Housekeeping',
        0,
        NULL
    ),
    (
        'cleaning chemical',
        'CM.AS.03.0088',
        'Isoprophyl Alcohol 70%  250ML',
        'Facilities - Housekeeping',
        'gallon',
        'Facilities - Housekeeping',
        0,
        NULL
    ),
    (
        'cleaning chemical',
        'CM.AS.03.0090',
        'Joy Dishwashing Liquid Antibac (250Ml)',
        'Facilities - Housekeeping',
        'pack',
        'Facilities - Housekeeping',
        0,
        NULL
    ),
    (
        'cleaning chemical',
        'CM.AS.03.0068',
        'Laundry Detergent',
        'Facilities - Housekeeping',
        'gallon',
        'Facilities - Housekeeping',
        0,
        NULL
    ),
    (
        'cleaning chemical',
        'CM.EN.02.0372',
        'Liquid Sosa',
        'Facilities - Housekeeping',
        'pc',
        'Facilities - Housekeeping',
        0,
        NULL
    ),
    (
        'cleaning chemical',
        'CM.AS.03.0017',
        'Lysol Spray  Citrus Scent 391Gm',
        'Facilities - Housekeeping',
        'pc',
        'Facilities - Housekeeping',
        0,
        NULL
    ),
    (
        'cleaning chemical',
        'CM.AS.03.0042',
        'Scented oil - for oil burner',
        'Facilities - Housekeeping',
        'pc',
        'Facilities - Housekeeping',
        0,
        NULL
    ),
    (
        'cleaning chemical',
        'CM.AS.03.0056',
        'Spotless',
        'Facilities - Housekeeping',
        'pc',
        'Facilities - Housekeeping',
        0,
        NULL
    ),
    (
        'cleaning chemical',
        'CM.AS.03.0038',
        'Super Chemxme IV Plus',
        'Facilities - Housekeeping',
        'gallon',
        'Facilities - Housekeeping',
        0,
        'Supervisor'
    ),
    (
        'cleaning chemical',
        'CM.AS.03.0066',
        'Varnish',
        'Facilities - Housekeeping',
        'pc',
        'Facilities - Housekeeping',
        0,
        NULL
    ),
    (
        'cleaning chemical',
        'CM.AS.03.0058',
        'Wood Furniture Polish, Pledge',
        'Facilities - Housekeeping',
        'pc',
        'Facilities - Housekeeping',
        0,
        NULL
    ),
    (
        'cleaning chemical',
        'CM.AS.03.0037',
        'Zonrox Original  4Lit/Gal',
        'Facilities - Housekeeping',
        'gallon',
        'Facilities - Housekeeping',
        0,
        NULL
    ),
    (
        'cleaning material',
        'CM.AS.03.0051',
        'Automatic Hand Roll Tissue Dispenser',
        'Facilities - Housekeeping',
        'pc',
        'Facilities - Housekeeping',
        0,
        NULL
    ),
    (
        'cleaning material',
        'CM.AS.03.0004',
        'Broom Sticks  Long Handle',
        'Facilities - Housekeeping',
        'pc',
        'Facilities - Housekeeping',
        0,
        NULL
    ),
    (
        'cleaning material',
        'CM.AS.03.0048',
        'Carpet',
        'Facilities - Housekeeping',
        'pc',
        'Facilities - Housekeeping',
        0,
        NULL
    ),
    (
        'cleaning material',
        'CM.AS.03.0057',
        'Dust pan',
        'Facilities - Housekeeping',
        'pc',
        'Facilities - Housekeeping',
        0,
        NULL
    ),
    (
        'cleaning material',
        'CM.AS.03.0095',
        'Feather Duster',
        'Facilities - Housekeeping',
        'pc',
        'Facilities - Housekeeping',
        0,
        NULL
    ),
    (
        'cleaning material',
        'CM.PR.03.0084',
        'Floor Brush',
        'Facilities - Housekeeping',
        'pc',
        'Facilities - Housekeeping',
        0,
        NULL
    ),
    (
        'cleaning material',
        'CM.PR.03.0080',
        'Floor Squeegee with Handle Type: Rubber',
        'Facilities - Housekeeping',
        'pc',
        'Facilities - Housekeeping',
        0,
        NULL
    ),
    (
        'cleaning material',
        'CM.AS.03.0049',
        'Grey scrubbing pad Scoth Brite brand',
        'Facilities - Housekeeping',
        'pc',
        'Facilities - Housekeeping',
        0,
        NULL
    ),
    (
        'cleaning material',
        'CM.AS.03.0099',
        'Hand Brush',
        'Facilities - Housekeeping',
        'pc',
        'Facilities - Housekeeping',
        0,
        NULL
    ),
    (
        'cleaning material',
        'CM.AS.04.0006',
        'Hand Dryer, Heavy-duty',
        'Facilities - Housekeeping',
        'pc',
        'Facilities - Housekeeping',
        0,
        NULL
    ),
    (
        'cleaning material',
        'CM.PR.03.0057',
        'Interfolded Paper Towel  175Sheets/30Pck/Box',
        'Facilities - Housekeeping',
        'pc',
        'Facilities - Housekeeping',
        0,
        NULL
    ),
    (
        'cleaning material',
        'CM.AS.03.0092',
        'Interleave Tissue',
        'Facilities - Housekeeping',
        'pc',
        'Facilities - Housekeeping',
        0,
        NULL
    ),
    (
        'cleaning material',
        'CM.PR.03.0058',
        'Jumbo Roll Tissue 250Meter/Box/16Rolls',
        'Facilities - Housekeeping',
        'pc',
        'Facilities - Housekeeping',
        0,
        NULL
    ),
    (
        'cleaning material',
        'CM.PR.03.0060',
        'Lint Remover Refill',
        'Facilities - Housekeeping',
        'pc',
        'Facilities - Housekeeping',
        0,
        NULL
    ),
    (
        'cleaning material',
        'CM.PR.03.0085',
        'Lint Remover Roller Handle',
        'Facilities - Housekeeping',
        'pc',
        'Facilities - Housekeeping',
        0,
        NULL
    ),
    (
        'cleaning material',
        'CM.AS.03.0001',
        'Magic Mop 12"',
        'Facilities - Housekeeping',
        'pc',
        'Facilities - Housekeeping',
        0,
        NULL
    ),
    (
        'cleaning material',
        'CM.AS.03.0039',
        'Manual alcohol dispenser',
        'Facilities - Housekeeping',
        'pc',
        'Facilities - Housekeeping',
        0,
        NULL
    ),
    (
        'cleaning material',
        'CM.AS.03.0087',
        'Manual Hand Soap Dispenser   Model : Lx-V7101',
        'Facilities - Housekeeping',
        'pc',
        'Facilities - Housekeeping',
        0,
        NULL
    ),
    (
        'cleaning material',
        'CM.AS.03.0018',
        'Mop Handle Blue  F1002',
        'Facilities - Housekeeping',
        'pc',
        'Facilities - Housekeeping',
        0,
        NULL
    ),
    (
        'cleaning material',
        'CM.AS.03.0061',
        'Mop head - refill only',
        'Facilities - Housekeeping',
        'pc',
        'Facilities - Housekeeping',
        0,
        NULL
    ),
    (
        'cleaning material',
        'CM.AS.03.0019',
        'Mop Head With Handle',
        'Facilities - Housekeeping',
        'pc',
        'Facilities - Housekeeping',
        0,
        NULL
    ),
    (
        'cleaning material',
        'CM.AS.03.0020',
        'Mop Squeezer',
        'Facilities - Housekeeping',
        'pc',
        'Facilities - Housekeeping',
        0,
        NULL
    ),
    (
        'cleaning material',
        'CM.PR.03.0086',
        'Nail Brush, 115x58x35mm, Nylon Bristles, White',
        'Facilities - Housekeeping',
        'pc',
        'Facilities - Housekeeping',
        0,
        NULL
    ),
    (
        'cleaning material',
        'CM.AS.03.0055',
        'Plastic Apron - Admin',
        'Facilities - Housekeeping',
        'pc',
        'Facilities - Housekeeping',
        0,
        NULL
    ),
    (
        'cleaning material',
        'CM.AS.03.0021',
        'Plastic Broom',
        'Facilities - Housekeeping',
        'pc',
        'Facilities - Housekeeping',
        0,
        NULL
    ),
    (
        'cleaning material',
        'CM.AS.03.0072',
        'Plastic Container  (for Chemical & Cleaning materials )',
        'Facilities - Housekeeping',
        'pc',
        'Facilities - Housekeeping',
        0,
        NULL
    ),
    (
        'cleaning material',
        'CM.AS.03.0040',
        'Push Brush - Refill Only',
        'Facilities - Housekeeping',
        'pc',
        'Facilities - Housekeeping',
        0,
        NULL
    ),
    (
        'cleaning material',
        'CM.AS.03.0041',
        'Push Brush with Handle',
        'Facilities - Housekeeping',
        'pc',
        'Facilities - Housekeeping',
        0,
        NULL
    ),
    (
        'cleaning material',
        'CM.AS.03.0043',
        'Push Brush With Handle',
        'Facilities - Housekeeping',
        'pc',
        'Facilities - Housekeeping',
        0,
        NULL
    ),
    (
        'cleaning material',
        'CM.AS.03.0023',
        'Rubber Gloves - Long',
        'Facilities - Housekeeping',
        'pair',
        'Facilities - Housekeeping',
        0,
        NULL
    ),
    (
        'cleaning material',
        'CM.AS.03.0091',
        'Rubber Mat',
        'Facilities - Housekeeping',
        'pc',
        'Facilities - Housekeeping',
        0,
        NULL
    ),
    (
        'cleaning material',
        'CM.AS.03.0025',
        'Scrubbing Pad  Heavy Duty Gray 2Pcs/Pk',
        'Facilities - Housekeeping',
        'pc',
        'Facilities - Housekeeping',
        0,
        NULL
    ),
    (
        'cleaning material',
        'CM.AS.03.0070',
        'Shoe Brush',
        'Facilities - Housekeeping',
        'pc',
        'Facilities - Housekeeping',
        0,
        NULL
    ),
    (
        'cleaning material',
        'CM.AS.03.0094',
        'Soap Case / Dish Pad Holder, Drainable',
        'Facilities - Housekeeping',
        'pc',
        'Facilities - Housekeeping',
        0,
        NULL
    ),
    (
        'cleaning material',
        'CM.AS.03.0026',
        'Sponge Yellow & Green Scotchbrite brand',
        'Facilities - Housekeeping',
        'pc',
        'Facilities - Housekeeping',
        0,
        NULL
    ),
    (
        'cleaning material',
        'CM.AS.03.0027',
        'Spray Bottle - Clear',
        'Facilities - Housekeeping',
        'pc',
        'Facilities - Housekeeping',
        0,
        NULL
    ),
    (
        'cleaning material',
        'CM.AS.03.0029',
        'Squeegee  (Case/Rubber) 24-Inches - Head Only',
        'Facilities - Housekeeping',
        'pc',
        'Facilities - Housekeeping',
        0,
        NULL
    ),
    (
        'cleaning material',
        'CM.AS.03.0028',
        'Squeegee  Case/Rubber',
        'Facilities - Housekeeping',
        'pc',
        'Facilities - Housekeeping',
        0,
        NULL
    ),
    (
        'cleaning material',
        'CM.VS.01.0018',
        'Tissue (Facial Tissue)Kleenex Brand Color White Unscented',
        'Facilities - Housekeeping',
        'pc',
        'Facilities - Housekeeping',
        0,
        NULL
    ),
    (
        'cleaning material',
        'CM.AS.03.0045',
        'Tissue for Motion Sensor Dispenser',
        'Facilities - Housekeeping',
        'pc',
        'Facilities - Housekeeping',
        0,
        NULL
    ),
    (
        'cleaning material',
        'CM.AS.03.0098',
        'Toilet Brush',
        'Facilities - Housekeeping',
        'pc',
        'Facilities - Housekeeping',
        0,
        NULL
    ),
    (
        'cleaning material',
        'CM.AS.03.0032',
        'Trash Bag  (Medium)-Black',
        'Facilities - Housekeeping',
        'pc',
        'Facilities - Housekeeping',
        0,
        NULL
    ),
    (
        'cleaning material',
        'CM.AS.03.0054',
        'Trash Bag - XXL   Black',
        'Facilities - Housekeeping',
        'pc',
        'Facilities - Housekeeping',
        0,
        NULL
    ),
    (
        'cleaning material',
        'CM.AS.03.0034',
        'Trash Bag Xxl Clear',
        'Facilities - Housekeeping',
        'pc',
        'Facilities - Housekeeping',
        0,
        NULL
    ),
    (
        'cleaning material',
        'CM.AS.03.0033',
        'Trash Bag Xxl Green',
        'Facilities - Housekeeping',
        'pc',
        'Facilities - Housekeeping',
        0,
        NULL
    ),
    (
        'cleaning material',
        'CM.AS.03.0035',
        'Trash Bag Xxl Yellow',
        'Facilities - Housekeeping',
        'pc',
        'Facilities - Housekeeping',
        0,
        NULL
    ),
    (
        'cleaning material',
        'CM.AS.03.0036',
        'Trash Bags - Small  Black',
        'Facilities - Housekeeping',
        'pc',
        'Facilities - Housekeeping',
        0,
        NULL
    ),
    (
        'cleaning material',
        'CM.AS.03.0067',
        'Trash Bin Small',
        'Facilities - Housekeeping',
        'pc',
        'Facilities - Housekeeping',
        0,
        NULL
    ),
    (
        'cleaning material',
        'CM.AS.03.0024',
        'Trash bin, Stainless, Non-Prod Area',
        'Facilities - Housekeeping',
        'pc',
        'Facilities - Housekeeping',
        0,
        NULL
    ),
    (
        'cleaning material',
        'CM.AS.03.0071',
        'Wall Mop',
        'Facilities - Housekeeping',
        'pc',
        'Facilities - Housekeeping',
        0,
        NULL
    ),
    (
        'cleaning material',
        'CM.AS.03.0084',
        'Window Wiper',
        'Facilities - Housekeeping',
        'pc',
        'Facilities - Housekeeping',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.CU.07.0001',
        'Boots Black, rubber',
        'Facilities - Housekeeping',
        'pair',
        'Facilities - Housekeeping',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.PR.03.0055',
        'Cotton Gloves',
        'Facilities - Housekeeping',
        'pc',
        'Facilities - Housekeeping',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.CU.07.0023',
        'Silicon Gloves',
        'Facilities - Housekeeping',
        'pc',
        'Facilities - Housekeeping',
        0,
        NULL
    ),
    (
        'cleaning material',
        'CM.PR.03.0065',
        'Towel Color Blue',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'cleaning material',
        'CM.PR.03.0067',
        'Towel Color Green',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'cleaning material',
        'CM.PR.03.0068',
        'Towel Color Pink',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'cleaning material',
        'CM.PR.03.0069',
        'Towel Color Yellow',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'cleaning material',
        'CM.AS.03.0030',
        'Wash Basin ''S/S',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.CU.08.0005',
        'Apron, White, Washable',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.PR.03.0054',
        'Arm Protector',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.CU.06.0001S',
        'Black Jacket (Company) for IS, Mgr and Sup -7XL',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        'Manager,Supervisor,Internal Security'
    ),
    (
        'uniform & PPEs',
        'CM.CU.06.0001M',
        'Black Jacket (LRN) for IS, Mgr and Sup -M',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        'Manager,Supervisor,Internal Security'
    ),
    (
        'uniform & PPEs',
        'CM.CU.06.0001L',
        'Black Jacket (LRN) for IS, Mgr and Sup -L',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        'Manager,Supervisor,Internal Security'
    ),
    (
        'uniform & PPEs',
        'CM.CU.06.0001XL',
        'Black Jacket (LRN) for IS, Mgr and Sup -XL',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        'Manager,Supervisor,Internal Security'
    ),
    (
        'uniform & PPEs',
        'CM.CU.06.0001XXL',
        'Black Jacket (LRN) for IS, Mgr and Sup -XXL',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        'Manager,Supervisor,Internal Security'
    ),
    (
        'uniform & PPEs',
        'CM.CU.06.00013XL',
        'Black Jacket (LRN) for IS, Mgr and Sup -3XL',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        'Manager,Supervisor,Internal Security'
    ),
    (
        'uniform & PPEs',
        'CM.CU.06.00014XL',
        'Black Jacket (LRN) for IS, Mgr and Sup -4XL',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        'Manager,Supervisor,Internal Security'
    ),
    (
        'uniform & PPEs',
        'CM.CU.06.00015XL',
        'Black Jacket (LRN) for IS, Mgr and Sup -5XL',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        'Manager,Supervisor,Internal Security'
    ),
    (
        'uniform & PPEs',
        'CM.CU.06.00016XL',
        'Black Jacket (LRN) for IS, Mgr and Sup -6XL',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        'Manager,Supervisor,Internal Security'
    ),
    (
        'uniform & PPEs',
        'CM.CU.06.00017XL',
        'Black Jacket (LRN) for IS, Mgr and Sup -7XL',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        'Manager,Supervisor,Internal Security'
    ),
    (
        'uniform & PPEs',
        'CM.VS.01.0026',
        'Blue Gloves, XL',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.CU.08.0002',
        'Bonnet Headcap  (Black)',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.PR.03.0056',
        'CPE Shoe Cover',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.CU.08.0001',
        'Face Mask, 3 Ply, White',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.CU.08.0012',
        'Face Mask, Medical grade, Non-woven, 3ply, Disposable, Blue',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.CU.08.0007',
        'Facemask Washable Cotton',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        50,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.PR.03.0083',
        'Freezer gloves',
        'Facilities - Laundry',
        'pair',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.PR.03.0061',
        'Mob Cap White 18"',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.CU.08.0011',
        'MOB CAP, Reusable (Large) Cloth Material, White Color, L',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        75,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.CU.08.0010',
        'MOB CAP, Reusable (Medium) Cloth Material, White Color, M',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        75,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.CU.08.0004',
        'Production Bonnet  Headcap (White)',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.CU.07.0010',
        'Safety Shoes  - 41',
        'Facilities - Laundry',
        'pair',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.CU.07.0011',
        'Safety Shoes  - 42',
        'Facilities - Laundry',
        'pair',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.CU.07.0012',
        'Safety Shoes  - 43',
        'Facilities - Laundry',
        'pair',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.CU.07.0020',
        'Safety Shoes  - 45',
        'Facilities - Laundry',
        'pair',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.CU.07.0022',
        'Safety Shoes  - 46',
        'Facilities - Laundry',
        'pair',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.CU.07.0014',
        'Safety Shoes - 36',
        'Facilities - Laundry',
        'pair',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.CU.07.0015',
        'Safety Shoes - 37',
        'Facilities - Laundry',
        'pair',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.CU.07.0016',
        'Safety Shoes - 38',
        'Facilities - Laundry',
        'pair',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.CU.07.0017',
        'Safety Shoes - 39',
        'Facilities - Laundry',
        'pair',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.CU.07.0018',
        'Safety Shoes - 40',
        'Facilities - Laundry',
        'pair',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.CU.07.0019',
        'Safety Shoes - 44',
        'Facilities - Laundry',
        'pair',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.PR.03.0075',
        'Soft White Gloves with Rubber tip Soft White Gloves with Rubber tip',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.CU.02.0005S',
        'Uniform, Black Chef Polo for Production Manager S',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        'Manager'
    ),
    (
        'uniform & PPEs',
        'CM.CU.02.0005M',
        'Uniform, Black Chef Polo for Production Manager M',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        'Manager'
    ),
    (
        'uniform & PPEs',
        'CM.CU.02.0005L',
        'Uniform, Black Chef Polo for Production Manager L',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        'Manager'
    ),
    (
        'uniform & PPEs',
        'CM.CU.02.0005XL',
        'Uniform, Black Chef Polo for Production Manager XL',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        'Manager'
    ),
    (
        'uniform & PPEs',
        'CM.CU.02.0005XXL',
        'Uniform, Black Chef Polo for Production Manager XXL',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        'Manager'
    ),
    (
        'uniform & PPEs',
        'CM.CU.02.00053XL',
        'Uniform, Black Chef Polo for Production Manager 3XL',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        'Manager'
    ),
    (
        'uniform & PPEs',
        'CM.CU.02.00054XL',
        'Uniform, Black Chef Polo for Production Manager 4XL',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        'Manager'
    ),
    (
        'uniform & PPEs',
        'CM.CU.02.00055XL',
        'Uniform, Black Chef Polo for Production Manager 5XL',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        'Manager'
    ),
    (
        'uniform & PPEs',
        'CM.CU.01.0051S',
        'Uniform, Black Coat,Female S',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.CU.01.0051M',
        'Uniform, Black Coat,Female M',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.CU.01.0051L',
        'Uniform, Black Coat,Female L',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.CU.01.0051XL',
        'Uniform, Black Coat,Female XL',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.CU.01.0051XXL',
        'Uniform, Black Coat,Female XXL',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.CU.01.00513XL',
        'Uniform, Black Coat,Female 3XL',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.CU.01.00514XL',
        'Uniform, Black Coat,Female 4XL',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.CU.01.00515XL',
        'Uniform, Black Coat,Female 5XL',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.CU.01.0053',
        'Uniform, Black Pants  Male - 5XL',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.CU.01.0036',
        'Uniform, Black Pants  Male -3XL',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.CU.01.0037',
        'Uniform, Black Pants  Male -4XL',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.CU.01.0033',
        'Uniform, Black Pants  Male -L',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.CU.01.0032',
        'Uniform, Black Pants  Male -M',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.CU.01.0031',
        'Uniform, Black Pants  Male -S',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.CU.01.0034',
        'Uniform, Black Pants  Male -XL',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.CU.01.0035',
        'Uniform, Black Pants  Male -XXL',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.CU.01.0054',
        'Uniform, Black Pants Female - 5XL',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.CU.01.0043',
        'Uniform, Black Pants Female -3XL',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.CU.01.0044',
        'Uniform, Black Pants Female -4XL',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.CU.01.0040',
        'Uniform, Black Pants Female -L',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.CU.01.0039',
        'Uniform, Black Pants Female -M',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.CU.01.0038',
        'Uniform, Black Pants Female -S',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.CU.01.0041',
        'Uniform, Black Pants Female -XL',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.CU.01.0042',
        'Uniform, Black Pants Female -XXL',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.CU.03.0022',
        'Uniform, Blue Lab Gown -L',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.CU.03.0021',
        'Uniform, Blue Lab Gown -M',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.CU.03.0027',
        'Uniform, Blue Lab Gown -XL',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.CU.03.0028',
        'Uniform, Blue Lab Gown -XXL',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.CU.02.0013',
        'Uniform, Checkered Pants -3XL',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.CU.02.0033',
        'Uniform, Checkered Pants -5XL',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.CU.02.0010',
        'Uniform, Checkered Pants -L',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.CU.02.0009',
        'Uniform, Checkered Pants -M',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.CU.02.0008',
        'Uniform, Checkered Pants -S',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.CU.02.0011',
        'Uniform, Checkered Pants -XL',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.CU.02.0012',
        'Uniform, Checkered Pants -XXL',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.CU.04.0004',
        'Uniform, Eng. Pants XL',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.CU.04.0002',
        'Uniform, Eng. Pants -L',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.CU.04.0003',
        'Uniform, Eng. Pants -M',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.CU.04.0001',
        'Uniform, Eng. Pants -S',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.CU.03.0006',
        'Uniform, Green Lab Gown -M',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.CU.03.0005',
        'Uniform, Green Lab Gown -S',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.CU.03.0003',
        'Uniform, Green Lab Gown -XL',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.CU.03.0004',
        'Uniform, Green Lab Gown -XXL',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.CU.06.0006',
        'Uniform, Internal Security - 2XL',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.CU.06.0007',
        'Uniform, Internal Security - 3XL',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.CU.06.0008',
        'Uniform, Internal Security - 4XL',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.CU.06.0004',
        'Uniform, Internal Security  -L',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.CU.06.0003',
        'Uniform, Internal Security  -M',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.CU.06.0002',
        'Uniform, Internal Security -S',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.CU.06.0005',
        'Uniform, Internal Security -XL',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.CU.03.0033',
        'Uniform, Maroon Lab Gown - L',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.CU.03.0011',
        'Uniform, Maroon Lab Gown -M',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.CU.03.0012',
        'Uniform, Maroon Lab Gown -XL',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.CU.01.0050L',
        'Uniform, Maternity White Long Sleeve Blouse L',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.CU.01.0050XL',
        'Uniform, Maternity White Long Sleeve Blouse XL',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.CU.01.0050XXL',
        'Uniform, Maternity White Long Sleeve Blouse XXL',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.CU.02.0043',
        'Uniform, Polo Chef Jacket Black -4XL',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.CU.02.0040',
        'Uniform, Polo Chef Jacket Black -5XL',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.CU.02.0041',
        'Uniform, Polo Chef Jacket Black -6XL',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.CU.02.0022',
        'Uniform, Polo Chef Jacket Black -L',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.CU.02.0021',
        'Uniform, Polo Chef Jacket Black -M',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.CU.02.0020',
        'Uniform, Polo Chef Jacket Black -S',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.CU.02.0023',
        'Uniform, Polo Chef Jacket Black -XL',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.CU.02.0018',
        'Uniform, Polo Chef Jacket Red -2XL',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.CU.02.0019',
        'Uniform, Polo Chef Jacket Red -3XL',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.CU.02.0027',
        'Uniform, Polo Chef Jacket Red -4XL',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.CU.02.0042',
        'Uniform, Polo Chef Jacket Red -5XL',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.CU.02.0039',
        'Uniform, Polo Chef Jacket Red -6XL',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.CU.02.0016',
        'Uniform, Polo Chef Jacket Red -L',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.CU.02.0015',
        'Uniform, Polo Chef Jacket Red -M',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.CU.02.0014',
        'Uniform, Polo Chef Jacket Red -S',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.CU.02.0017',
        'Uniform, Polo Chef Jacket Red -XL',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.CU.02.0026',
        'Uniform, Production Black T-Shirt -3XL',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.CU.02.0044',
        'Uniform, Production Black T-Shirt -4XL',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.CU.02.0054',
        'Uniform, Production Black T-Shirt -5XL',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.CU.02.0045',
        'Uniform, Production Black T-Shirt -6XL',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.CU.02.0001',
        'Uniform, Production Black T-Shirt -L',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.CU.02.0002',
        'Uniform, Production Black T-Shirt -M',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.CU.02.0003',
        'Uniform, Production Black T-Shirt -S',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.CU.02.0004',
        'Uniform, Production Black T-Shirt -XL',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.CU.02.0007',
        'Uniform, Production Black T-Shirt -XXL',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.CU.02.0047S',
        'Uniform, Production Black T-Shirt, Dri-fit long sleeve S',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.CU.02.0047M',
        'Uniform, Production Black T-Shirt, Dri-fit long sleeve M',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.CU.02.0047L',
        'Uniform, Production Black T-Shirt, Dri-fit long sleeve L',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.CU.02.0047XL',
        'Uniform, Production Black T-Shirt, Dri-fit long sleeve XL',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.CU.02.0047XXL',
        'Uniform, Production Black T-Shirt, Dri-fit long sleeve XXL',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.CU.02.00473XL',
        'Uniform, Production Black T-Shirt, Dri-fit long sleeve 3XL',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.CU.02.00474XL',
        'Uniform, Production Black T-Shirt, Dri-fit long sleeve 4XL',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.CU.02.00475XL',
        'Uniform, Production Black T-Shirt, Dri-fit long sleeve 5XL',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.CU.02.00476XL',
        'Uniform, Production Black T-Shirt, Dri-fit long sleeve 6XL',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.CU.02.0046S',
        'Uniform, Production Black T-Shirt, Dri-fit short sleeve S',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.CU.02.0046M',
        'Uniform, Production Black T-Shirt, Dri-fit short sleeve M',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.CU.02.0046L',
        'Uniform, Production Black T-Shirt, Dri-fit short sleeve L',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.CU.02.0046XL',
        'Uniform, Production Black T-Shirt, Dri-fit short sleeve XL',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.CU.02.0046XXL',
        'Uniform, Production Black T-Shirt, Dri-fit short sleeve XXL',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.CU.02.00463XL',
        'Uniform, Production Black T-Shirt, Dri-fit short sleeve 3XL',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.CU.02.00464XL',
        'Uniform, Production Black T-Shirt, Dri-fit short sleeve 4XL',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.CU.02.00465XL',
        'Uniform, Production Black T-Shirt, Dri-fit short sleeve 5XL',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.CU.02.00466XL',
        'Uniform, Production Black T-Shirt, Dri-fit short sleeve 6XL',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.CU.02.0064',
        'Uniform, Production, Longsleeve with hood, Extra Large',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.CU.02.0063',
        'Uniform, Production, Longsleeve with hood, Large',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.CU.02.0062',
        'Uniform, Production, Longsleeve with hood, Medium',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.CU.02.0061',
        'Uniform, Production, Longsleeve with hood, Small',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.CU.07.0005',
        'Uniform, Thermal Suit Jacket -L',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.CU.07.0006',
        'Uniform, Thermal Suit Jacket -M',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.CU.07.0024',
        'Uniform, Thermal Suit Jacket -XL',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.CU.07.0007',
        'Uniform, Thermal Suit Pants -L',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.CU.07.0008',
        'Uniform, Thermal Suit Pants -M',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.CU.07.0021',
        'Uniform, Thermal Suit Pants -XL',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.CU.03.0030',
        'Uniform, White Lab Gown for Visitors - 3XL',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.CU.03.0031',
        'Uniform, White Lab Gown for Visitors - 4XL',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.CU.03.0032',
        'Uniform, White Lab Gown for Visitors - 5XL',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.CU.03.0020',
        'Uniform, White Lab Gown for Visitors - XXL',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.CU.03.0014',
        'Uniform, White Lab Gown -S',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.CU.03.0015',
        'Uniform, White Lab Gown -M',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.CU.03.0016',
        'Uniform, White Lab Gown -L',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.CU.03.0017',
        'Uniform, White Lab Gown -XL',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.CU.01.0046',
        'Uniform, White Longsleeves ( Female) -10XL',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.CU.01.0007',
        'Uniform, White Longsleeves ( Female) -3XL',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.CU.01.0008',
        'Uniform, White Longsleeves ( Female) -4XL',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.CU.01.0048',
        'Uniform, White Longsleeves ( Female) -5XL',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.CU.01.0047',
        'Uniform, White Longsleeves ( Female) -6XL',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.CU.01.0004',
        'Uniform, White Longsleeves ( Female) -L',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.CU.01.0003',
        'Uniform, White Longsleeves ( Female) -M',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.CU.01.0002',
        'Uniform, White Longsleeves ( Female) -S',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.CU.01.0005',
        'Uniform, White Longsleeves ( Female) -XL',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.CU.01.0006',
        'Uniform, White Longsleeves ( Female) -XXL',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.CU.01.0013',
        'Uniform, White Longsleeves ( Male) -2XL',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.CU.01.0014',
        'Uniform, White Longsleeves ( Male) -3XL',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.CU.01.0011',
        'Uniform, White Longsleeves ( Male) -L',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.CU.01.0010',
        'Uniform, White Longsleeves ( Male) -M',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.CU.01.0009',
        'Uniform, White Longsleeves ( Male) -S',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.CU.01.0012',
        'Uniform, White Longsleeves ( Male) -XL',
        'Facilities - Laundry',
        'pc',
        'Facilities - Laundry',
        0,
        NULL
    ),
    (
        'Clinic Item',
        'CM.SH.01.0262',
        '0.9% Sodium Chloride Dextrose PNSS solution for IV infusion 1000ml',
        'Facilities - Safety',
        'pc',
        'Facilities - Safety',
        0,
        NULL
    ),
    (
        'Clinic Item',
        'CM.SH.01.0070',
        'Acetaminophen (paracetamol)  250mg-propyphenazone 150mg-caffeine 50mg',
        'Facilities - Safety',
        'pc',
        'Facilities - Safety',
        0,
        NULL
    ),
    (
        'Clinic Item',
        'CM.SH.01.0001',
        'Alcohol 70% Solution
    Brand: Green Cross',
        'Facilities - Safety',
        'pc',
        'Facilities - Safety',
        0,
        NULL
    ),
    (
        'Clinic Item',
        'CM.SH.01.0005',
        'Alcohol Swab',
        'Facilities - Safety',
        'pc',
        'Facilities - Safety',
        0,
        NULL
    ),
    (
        'Clinic Item',
        'CM.SH.01.0071',
        'Aluminum Hydroxide/Magnesium Hydroxide 200mg/100mg Tablet',
        'Facilities - Safety',
        'pc',
        'Facilities - Safety',
        0,
        NULL
    ),
    (
        'Clinic Item',
        'CM.SH.01.0261',
        'Bacillus Clausii, 5ml tube',
        'Facilities - Safety',
        'pc',
        'Facilities - Safety',
        0,
        NULL
    ),
    (
        'Clinic Item',
        'CM.SH.01.0104',
        'Bactidol 200ml',
        'Facilities - Safety',
        'pc',
        'Facilities - Safety',
        0,
        NULL
    ),
    (
        'Clinic Item',
        'CM.SH.01.0076',
        'Bactroban 5G Ointment',
        'Facilities - Safety',
        'pc',
        'Facilities - Safety',
        0,
        NULL
    ),
    (
        'Clinic Item',
        'CM.SH.01.0007',
        'Band Aid Colored',
        'Facilities - Safety',
        'pc',
        'Facilities - Safety',
        0,
        NULL
    ),
    (
        'Clinic Item',
        'CM.SH.01.0107',
        'Benzocaine Boric Acid 15G Ointment',
        'Facilities - Safety',
        'pc',
        'Facilities - Safety',
        0,
        NULL
    ),
    (
        'Clinic Item',
        'CM.SH.01.0006',
        'Betadine 120ml',
        'Facilities - Safety',
        'pc',
        'Facilities - Safety',
        0,
        NULL
    ),
    (
        'Clinic Item',
        'CM.SH.01.0074',
        'Betahistine Dihydrochloride 8mg',
        'Facilities - Safety',
        'pc',
        'Facilities - Safety',
        0,
        NULL
    ),
    (
        'Clinic Item',
        'CM.SH.01.0064',
        'Buscopan 10mg Tablet',
        'Facilities - Safety',
        'pc',
        'Facilities - Safety',
        0,
        NULL
    ),
    (
        'Clinic Item',
        'CM.SH.01.0063',
        'Carbocistine 500mg Capsule',
        'Facilities - Safety',
        'pc',
        'Facilities - Safety',
        0,
        NULL
    ),
    (
        'Clinic Item',
        'CM.SH.01.0002',
        'Clean Gloves  Surgical Gloves (Medical Gloves)',
        'Facilities - Safety',
        'pc',
        'Facilities - Safety',
        0,
        NULL
    ),
    (
        'Clinic Item',
        'CM.SH.01.0077',
        'Clonidine  75Mg - Tablet',
        'Facilities - Safety',
        'pc',
        'Facilities - Safety',
        0,
        NULL
    ),
    (
        'Clinic Item',
        'CM.SH.01.0003',
        'Cotton Balls  150''S/Pk',
        'Facilities - Safety',
        'pc',
        'Facilities - Safety',
        0,
        NULL
    ),
    (
        'Clinic Item',
        'CM.SH.01.0004',
        'Cotton Tip Applicator  100pcs',
        'Facilities - Safety',
        'pc',
        'Facilities - Safety',
        0,
        NULL
    ),
    (
        'Clinic Item',
        'CM.SH.01.0073',
        'Dextromethorphan HBr /Phenylephrine HCL /Paracetamol 15mg/ Phenylephribe HCL 10mg/ Paracetamol 325mg',
        'Facilities - Safety',
        'pc',
        'Facilities - Safety',
        0,
        NULL
    ),
    (
        'Clinic Item',
        'CM.SH.01.0264',
        'Dextromethorphan Hbr, Phenylpropanolamine HCl Paracetamol,  325mg capsule',
        'Facilities - Safety',
        'pc',
        'Facilities - Safety',
        0,
        NULL
    ),
    (
        'Clinic Item',
        'CM.SH.01.0265',
        'Diphenhydramine Hcl, 1ml ampule',
        'Facilities - Safety',
        'pc',
        'Facilities - Safety',
        0,
        NULL
    ),
    (
        'Clinic Item',
        'CM.SH.01.0079',
        'Domperidone  10mg Tablet',
        'Facilities - Safety',
        'pc',
        'Facilities - Safety',
        0,
        NULL
    ),
    (
        'Clinic Item',
        'CM.SH.01.0015',
        'Elastic Bandage 3Inch',
        'Facilities - Safety',
        'pc',
        'Facilities - Safety',
        0,
        NULL
    ),
    (
        'Clinic Item',
        'CM.SH.01.0016',
        'Elastic Bandage Size 4"',
        'Facilities - Safety',
        'pc',
        'Facilities - Safety',
        0,
        NULL
    ),
    (
        'Clinic Item',
        'CM.SH.01.0008',
        'Gauze Sterile
    Brand: Indoplast',
        'Facilities - Safety',
        'pc',
        'Facilities - Safety',
        0,
        NULL
    ),
    (
        'Clinic Item',
        'CM.SH.01.0059',
        'Glucometer Strips Accu-Check Active25',
        'Facilities - Safety',
        'pc',
        'Facilities - Safety',
        0,
        NULL
    ),
    (
        'Clinic Item',
        'CM.SH.01.0268',
        'Glucometer strips/test strips, color of box: Yellow green',
        'Facilities - Safety',
        'pc',
        'Facilities - Safety',
        0,
        NULL
    ),
    (
        'Clinic Item',
        'CM.SH.01.0111',
        'Hydrite',
        'Facilities - Safety',
        'pc',
        'Facilities - Safety',
        0,
        NULL
    ),
    (
        'Clinic Item',
        'CM.SH.01.0080',
        'Hydrocortisone 15G Ointment',
        'Facilities - Safety',
        'pc',
        'Facilities - Safety',
        0,
        NULL
    ),
    (
        'Clinic Item',
        'CM.SH.01.0010',
        'Hydrogen Peroxide Antiseptic Solution',
        'Facilities - Safety',
        'pc',
        'Facilities - Safety',
        0,
        NULL
    ),
    (
        'Clinic Item',
        'CM.SH.01.0072',
        'Hyoscine Butylbromide  10mg Tablet',
        'Facilities - Safety',
        'pc',
        'Facilities - Safety',
        0,
        NULL
    ),
    (
        'Clinic Item',
        'CM.SH.01.0068',
        'Ibuprofen 400mg Tablet',
        'Facilities - Safety',
        'pc',
        'Facilities - Safety',
        0,
        NULL
    ),
    (
        'Clinic Item',
        'CM.SH.01.0108',
        'Leukoplast 2.5CMX 1M',
        'Facilities - Safety',
        'pc',
        'Facilities - Safety',
        0,
        NULL
    ),
    (
        'Clinic Item',
        'CM.SH.01.0069',
        'Loperamide  2mg Capsule',
        'Facilities - Safety',
        'pc',
        'Facilities - Safety',
        0,
        NULL
    ),
    (
        'Clinic Item',
        'CM.SH.01.0081',
        'Loratadine  10mg Tablet',
        'Facilities - Safety',
        'pc',
        'Facilities - Safety',
        0,
        NULL
    ),
    (
        'Clinic Item',
        'CM.SH.01.0082',
        'Maalox',
        'Facilities - Safety',
        'pc',
        'Facilities - Safety',
        0,
        NULL
    ),
    (
        'Clinic Item',
        'CM.SH.01.0085',
        'Maxitrol Eye Drops 5ml',
        'Facilities - Safety',
        'pc',
        'Facilities - Safety',
        0,
        NULL
    ),
    (
        'Clinic Item',
        'CM.SH.01.0075',
        'Medicine (details on memo)',
        'Facilities - Safety',
        'pc',
        'Facilities - Safety',
        0,
        NULL
    ),
    (
        'Clinic Item',
        'CM.SH.01.0067',
        'Mefenamic Acid 500mg Capsule',
        'Facilities - Safety',
        'pc',
        'Facilities - Safety',
        0,
        NULL
    ),
    (
        'Clinic Item',
        'CM.SH.01.0087',
        'Metoclopramide Tablet',
        'Facilities - Safety',
        'pc',
        'Facilities - Safety',
        0,
        NULL
    ),
    (
        'Clinic Item',
        'CM.SH.01.0088',
        'Micropore 1 Inch Brand: 3M',
        'Facilities - Safety',
        'pc',
        'Facilities - Safety',
        0,
        NULL
    ),
    (
        'Clinic Item',
        'CM.SH.01.0089',
        'Neobloc Tablet',
        'Facilities - Safety',
        'pc',
        'Facilities - Safety',
        0,
        NULL
    ),
    (
        'Clinic Item',
        'CM.SH.01.0062',
        'Neozep  Non-Drowse Tablet',
        'Facilities - Safety',
        'pc',
        'Facilities - Safety',
        0,
        NULL
    ),
    (
        'Clinic Item',
        'CM.SH.01.0057',
        'Omega Pain Killer Big',
        'Facilities - Safety',
        'pc',
        'Facilities - Safety',
        0,
        NULL
    ),
    (
        'Clinic Item',
        'CM.SH.01.0260',
        'Omeprazole, 20mg capsule',
        'Facilities - Safety',
        'pc',
        'Facilities - Safety',
        0,
        NULL
    ),
    (
        'Clinic Item',
        'CM.SH.01.0110',
        'Paracetamol 500mg (1x100tablets)',
        'Facilities - Safety',
        'pc',
        'Facilities - Safety',
        0,
        NULL
    ),
    (
        'Clinic Item',
        'CM.SH.01.0055',
        'Petroleum Jelly 100% pure 50G',
        'Facilities - Safety',
        'pc',
        'Facilities - Safety',
        0,
        NULL
    ),
    (
        'Clinic Item',
        'CM.SH.01.0061',
        'Phenylephrine /Paracetamol 10 mg/ 500mg Tablet',
        'Facilities - Safety',
        'pc',
        'Facilities - Safety',
        0,
        NULL
    ),
    (
        'Clinic Item',
        'CM.SH.01.0045',
        'Pregnancy test kit Easy to Use - HCG Test Kit',
        'Facilities - Safety',
        'pc',
        'Facilities - Safety',
        0,
        NULL
    ),
    (
        'Clinic Item',
        'CM.SH.01.0058',
        'Salonpas 10''S/Box',
        'Facilities - Safety',
        'pc',
        'Facilities - Safety',
        0,
        NULL
    ),
    (
        'Clinic Item',
        'CM.SH.01.0065',
        'Saridon 250 mg/ 150mg/ 50mg Tablet',
        'Facilities - Safety',
        'pc',
        'Facilities - Safety',
        0,
        NULL
    ),
    (
        'Clinic Item',
        'CM.SH.01.0105',
        'Silver Sulfadiazine 10mg Cream',
        'Facilities - Safety',
        'pc',
        'Facilities - Safety',
        0,
        NULL
    ),
    (
        'Clinic Item',
        'CM.SH.01.0084',
        'Sinecod Forte',
        'Facilities - Safety',
        'pc',
        'Facilities - Safety',
        0,
        NULL
    ),
    (
        'Clinic Item',
        'CM.SH.01.0263',
        'Sodium Alginate, Sodium Bicarbonate, Calcium Carbonate, 10ml sachet',
        'Facilities - Safety',
        'pc',
        'Facilities - Safety',
        0,
        NULL
    ),
    (
        'Clinic Item',
        'CM.SH.01.0048',
        'Sterile Tongue Depressor',
        'Facilities - Safety',
        'pc',
        'Facilities - Safety',
        0,
        NULL
    ),
    (
        'Clinic Item',
        'CM.SH.01.0103',
        'Steri-Strip',
        'Facilities - Safety',
        'pc',
        'Facilities - Safety',
        0,
        NULL
    ),
    (
        'Clinic Item',
        'CM.SH.01.0112',
        'Strepsils Loz (8x36pack per box)',
        'Facilities - Safety',
        'pc',
        'Facilities - Safety',
        0,
        NULL
    ),
    (
        'Clinic Item',
        'CM.SH.01.0053',
        'Syringe 1 CC',
        'Facilities - Safety',
        'pc',
        'Facilities - Safety',
        0,
        NULL
    ),
    (
        'Clinic Item',
        'CM.SH.01.0054',
        'Syringe 3 CC',
        'Facilities - Safety',
        'pc',
        'Facilities - Safety',
        0,
        NULL
    ),
    (
        'Clinic Item',
        'CM.SH.01.0052',
        'Syringe 5 CC',
        'Facilities - Safety',
        'pc',
        'Facilities - Safety',
        0,
        NULL
    ),
    (
        'Clinic Item',
        'CM.SH.01.0106',
        'Ventolin Nebule',
        'Facilities - Safety',
        'pc',
        'Facilities - Safety',
        0,
        NULL
    ),
    (
        'Clinic Item',
        'CM.SH.01.0113',
        'Visine',
        'Facilities - Safety',
        'pc',
        'Facilities - Safety',
        0,
        NULL
    ),
    (
        'Clinic Item',
        'CM.SH.01.0056',
        'White Flower 20ml',
        'Facilities - Safety',
        'pc',
        'Facilities - Safety',
        0,
        NULL
    ),
    (
        'Clinic Item',
        'CM.SH.01.0269',
        'Zinc oxide + calamine, 3.5g topical ointment',
        'Facilities - Safety',
        'pc',
        'Facilities - Safety',
        0,
        NULL
    ),
    (
        'Safety Item',
        'CM.SH.01.0254',
        'Anti-slip Tape',
        'Facilities - Safety',
        'pc',
        'Facilities - Safety',
        0,
        NULL
    ),
    (
        'Safety Item',
        'CM.SH.03.0005',
        'Caution / Hazard / Directional Tape Roll',
        'Facilities - Safety',
        'pc',
        'Facilities - Safety',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.PR.03.0074',
        'Back Support',
        'Facilities - Safety',
        'pc',
        'Facilities - Safety',
        0,
        'Supervisor'
    ),
    (
        'uniform & PPEs',
        'CM.CU.08.0013',
        'Face Mask, KN95, Disposable, No Valve, White',
        'Facilities - Safety',
        'pc',
        'Facilities - Safety',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.SH.01.0250',
        'Reflective Adjustable Safety Security High Visibility Vest Gear Stripes Jacket Garterized',
        'Facilities - Safety',
        'pc',
        'Facilities - Safety',
        0,
        NULL
    ),
    (
        'uniform & PPEs',
        'CM.CU.07.0004',
        'Safety Goggles, Clear',
        'Facilities - Safety',
        'pc',
        'Facilities - Safety',
        0,
        NULL
    );
PRINT 'ItemMaster data inserted successfully';
END
ELSE BEGIN PRINT 'ItemMaster table already exists';
END
GO -- Add columns for approval/rejection history
ALTER TABLE Requests
ADD action_by VARCHAR(255);
ALTER TABLE Requests
ADD action_date DATETIME;
GO PRINT '';
PRINT '==================================================';
PRINT 'Database setup completed successfully!';
PRINT '==================================================';
PRINT 'Tables created:';
PRINT '  - Requests (for requisition headers)';
PRINT '  - RequestItems (for line items)';
PRINT '  - ItemMaster (with sample items)';
PRINT '';
PRINT 'The logistics system is now integrated with your';
PRINT 'existing authentication system (dsp_accounts, dsp_employees)';
PRINT '';
PRINT 'Login using your existing credentials from auth/login.php';
PRINT '==================================================';
GO -- create_approvers_table.sql
    -- Run this script to create the Approvers table
    CREATE TABLE Approvers (
        id INT IDENTITY(1, 1) PRIMARY KEY,
        department NVARCHAR(100) NOT NULL,
        approver_name_1 NVARCHAR(100) NOT NULL,
        approver_name_2 NVARCHAR(100) NULL
    );
-- Insert all department-approver mappings
INSERT INTO Approvers (department, approver_name_1, approver_name_2)
VALUES ('Admin', 'Arnel Elizalde', 'Carol Tiamzon'),
    (
        'Creatives',
        'Samantha Cortez',
        'Rainier Serrano'
    ),
    ('CRM', 'Michael Samson', NULL),
    ('Engg', 'Rolly Tungol', 'Messina Galang'),
    ('Facilities - Clinic', 'Annalyn Martinez', NULL),
    (
        'Facilities - Housekeeping',
        'Joanna Solomon',
        'Annalyn Martinez'
    ),
    (
        'Facilities - Laundry',
        'Myla Angeles',
        'Jiraldim Manlapaz'
    ),
    ('Facilities - Safety', 'Annalyn Martinez', NULL),
    ('FGW', 'Val Hasen Cortez', NULL),
    (
        'Finance',
        'Edith Gonzales',
        'Maria America Ofalla'
    ),
    ('HR', 'Angeli Laxamana', 'Justin Manalo'),
    ('IS', 'Rudyard Ammog-Ao', NULL),
    ('IT', 'Rudnan Chavez', 'Hyacinth Faye Mendez'),
    ('Labels', 'Michael Favila', 'Ana Victoria David'),
    ('Logistics', 'Mylin Cabusao', 'Sonny Samson'),
    ('MWH', 'Val Hasen Cortez', NULL),
    ('PMC', 'Joan Angad', 'Alnette Quinez'),
    (
        'Prodn - ISO',
        'Maria Cecelia Medrano',
        'Regina Calonzo'
    ),
    (
        'Prodn - Office',
        'Maria Cecelia Medrano',
        'Regina Calonzo'
    ),
    (
        'Prodn - Phase1',
        'Maria Cecelia Medrano',
        'Regina Calonzo'
    ),
    (
        'Prodn - Phase2',
        'Maria Cecelia Medrano',
        'Regina Calonzo'
    ),
    (
        'Prodn - Phase3',
        'Maria Cecelia Medrano',
        'Regina Calonzo'
    ),
    (
        'Prodn - Phase4',
        'Maria Cecelia Medrano',
        'Regina Calonzo'
    ),
    ('Purchasing', 'Pearl Abrique', 'Jenny Anicete'),
    ('QA', 'Sharon Dela Paz', 'Ana Victoria David'),
    ('QC', 'Sharon Dela Paz', 'Ana Victoria David'),
    ('R&I', 'Gen Ong', 'Raquel Rodriguez'),
    ('Sales', 'Chelsea Favila', 'Margaret Santos');