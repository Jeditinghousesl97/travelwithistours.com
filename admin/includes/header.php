<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<?php
$admin_site_title = 'Travel with IS Tours';
$admin_site_logo = '../assets/logo/logo.png';
if (isset($pdo)) {
    try {
        $stmt_settings = $pdo->query("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('site_title', 'site_logo')");
        while ($row = $stmt_settings->fetch()) {
            if ($row['setting_key'] === 'site_title' && $row['setting_value']) {
                $admin_site_title = $row['setting_value'];
            }
            if ($row['setting_key'] === 'site_logo' && $row['setting_value']) {
                $admin_site_logo = '../' . $row['setting_value'];
            }
        }
    }
    catch (Exception $e) {
    }
}
?>
    <title>Admin Panel - <?php echo htmlspecialchars($admin_site_title); ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Archivo:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #00bcd4;
            --primary-dark: #0097a7;
            --text-color: #333;
            --bg-color: #f4f6f8;
            --sidebar-width: 250px;
        }

        body {
            font-family: 'Archivo', sans-serif;
            background-color: var(--bg-color);
            margin: 0;
            display: flex;
            height: 100vh;
            color: var(--text-color);
        }
        
        /* Sidebar Styling */
        .sidebar {
            width: var(--sidebar-width);
            background-color: #fff;
            border-right: 1px solid #e0e0e0;
            display: flex;
            flex-direction: column;
            flex-shrink: 0;
            transition: transform 0.3s ease;
            position: fixed;
            height: 100%;
            z-index: 1000;
        }
        
        .sidebar-header {
            padding: 20px;
            border-bottom: 1px solid #e0e0e0;
            text-align: center;
        }
        
        .sidebar-menu {
            padding: 20px 0;
            flex-grow: 1;
            overflow-y: auto;
        }
        
        .sidebar-menu ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .sidebar-menu li a {
            display: block;
            padding: 12px 25px;
            color: #555;
            text-decoration: none;
            font-size: 14px;
            transition: 0.2s;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .sidebar-menu li a:hover, .sidebar-menu li a.active {
            background-color: #f0f8ff;
            color: var(--primary-color);
            border-left: 3px solid var(--primary-color);
            padding-left: 22px; /* Adjust for border */
        }
        
        .sidebar-footer {
            padding: 20px;
            border-top: 1px solid #e0e0e0;
        }
        
        /* Main Content Styling */
        .main-content {
            flex-grow: 1;
            padding: 30px;
            overflow-y: auto;
            margin-left: var(--sidebar-width);
            transition: margin-left 0.3s ease;
            width: calc(100% - var(--sidebar-width));
        }
        
        /* Header / Top Bar */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .header h2 {
            margin: 0;
            font-size: 24px;
        }
        
        /* Buttons */
        .btn {
            padding: 10px 20px;
            background: var(--primary-color);
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
            display: inline-block;
            transition: background 0.2s;
        }
        
        .btn:hover {
            background: var(--primary-dark);
        }
        
        .btn.btn-secondary { background: #6c757d; }
        .btn.btn-secondary:hover { background: #5a6268; }
        
        .btn.btn-success { background: #28a745; }
        .btn.btn-success:hover { background: #218838; }

        .btn.btn-danger { background: #dc3545; }
        .btn.btn-danger:hover { background: #c82333; }
        
        /* Cards & Tables */
        .card {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            padding: 20px;
            margin-bottom: 20px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            text-align: left;
            padding: 15px; /* More padding */
            border-bottom: 1px solid #eee;
        }
        
        th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: #666;
            text-transform: uppercase;
            font-size: 12px;
            letter-spacing: 0.5px;
        }
        
        /* Forms */
        .form-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            border: 1px solid #e9ecef;
            margin-bottom: 25px;
        }
        
        .form-section h3 {
            margin-top: 0;
            margin-bottom: 20px;
            font-size: 18px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 10px;
            color: #444;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #555;
            font-size: 14px;
        }
        
        .form-control {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            box-sizing: border-box;
            font-family: inherit;
            font-size: 14px;
            transition: border-color 0.2s;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            outline: none;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        
        /* Legacy input selector compatibility until all files updated */
        input[type="text"]:not(.form-control), 
        input[type="email"]:not(.form-control), 
        input[type="password"]:not(.form-control), 
        input[type="number"]:not(.form-control), 
        textarea:not(.form-control), 
        select:not(.form-control) {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            box-sizing: border-box;
            margin-bottom: 15px;
            font-family: inherit;
        }
        
        /* Alerts */
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .alert-success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-danger { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

        /* Mobile Toggle Button */
        .mobile-toggle {
            display: none;
            font-size: 24px;
            background: none;
            border: none;
            cursor: pointer;
            color: #333;
            margin-right: 15px;
        }
        
        /* Overlay for mobile sidebar */
        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 900;
        }

        /* Responsive Breakpoints */
        @media (max-width: 900px) {
            /* Table adjustments */
            table {
                display: block;
                overflow-x: auto;
                white-space: nowrap;
            }
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .sidebar.open {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
                width: 100%;
                padding: 15px;
            }
            
            .mobile-toggle {
                display: inline-block;
            }
            
            .sidebar-overlay.active {
                display: block;
            }
            
            .header {
                justify-content: flex-start;
            }
            
            .header h2 {
                font-size: 20px;
            }
        }
    </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<!-- Overlay for mobile -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

<div class="main-content">
    <!-- Mobile Header Bar -->
    <div class="header-tools" style="display: flex; align-items: center; margin-bottom: 15px;">
        <button class="mobile-toggle" onclick="toggleSidebar()">
            <i class="fas fa-bars"></i>
        </button>
    </div>

    <script>
        function toggleSidebar() {
            document.querySelector('.sidebar').classList.toggle('open');
            document.getElementById('sidebarOverlay').classList.toggle('active');
        }
    </script>
