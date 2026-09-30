<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="@yield('meta_robots', 'noindex, nofollow')">
    <meta name="description" content="@yield('meta_description', 'Hệ thống Quản lý Kế hoạch Giảng dạy và Sổ tay Giáo án điện tử - Khoa Công nghệ Thông tin.')">
    <meta name="author" content="Khoa Công nghệ Thông tin">

    <!-- Open Graph Protocol (SEO & Social Sharing) -->
    <meta property="og:title" content="@yield('title', 'Hệ thống Quản lý Kế hoạch Giảng dạy & Sổ tay Giáo án')">
    <meta property="og:description" content="@yield('meta_description', 'Hệ thống Quản lý Kế hoạch Giảng dạy và Sổ tay Giáo án điện tử.')">
    <meta property="og:type" content="website">
    <meta property="og:locale" content="vi_VN">
    <link rel="canonical" href="{{ url()->current() }}">

    <title>@yield('title', 'Hệ thống Quản lý Kế hoạch Giảng dạy & Sổ tay Giáo án')</title>

    <!-- Google Fonts: Lora (Academic Serif for Headings) & Be Vietnam Pro (Clean Sans for Body & Data) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:ital,wght@0,400;0,500;0,600;0,700;1,400&family=Lora:ital,wght@0,500;0,600;0,700;1,500&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 CSS & FontAwesome 6 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Academic Theme Stylesheet -->
    <style>
        :root {
            /* Academic Deep Navy Palette */
            --edu-navy-950: #0a192f;
            --edu-navy-900: #0f274a;
            --edu-navy-800: #163765;
            --edu-primary: #1b4d89;
            --edu-primary-hover: #123866;
            --edu-primary-soft: #f0f5fc;
            --edu-primary-border: #bfdbfe;

            /* Accent Colors */
            --edu-burgundy: #8b1e2f;
            --edu-gold: #b45309;
            --edu-gold-soft: #fef3c7;
            --edu-teal: #0f766e;
            --edu-teal-soft: #ccfbf1;

            /* Neutral Backgrounds & Borders */
            --edu-bg: #f8fafc;
            --edu-surface: #ffffff;
            --edu-border: #cbd5e1;
            --edu-border-light: #e2e8f0;

            /* Typography Colors (High Contrast WCAG AAA) */
            --edu-text-main: #0f172a;
            --edu-text-muted: #334155;
            --edu-text-subtle: #64748b;

            /* Fonts */
            --font-heading: 'Lora', Georgia, 'Times New Roman', serif;
            --font-body: 'Be Vietnam Pro', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }

        body {
            background-color: var(--edu-bg);
            font-family: var(--font-body);
            color: var(--edu-text-main);
            font-size: 0.925rem;
            line-height: 1.55;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Academic Typography Heading Styles */
        h1, h2, h3, h4, .academic-heading {
            font-family: var(--font-heading);
            font-weight: 700;
            color: var(--edu-navy-950);
            letter-spacing: -0.015em;
        }

        /* Top Institutional Header */
        .academic-topbar {
            background: #ffffff;
            border-bottom: 2px solid var(--edu-primary);
            box-shadow: 0 2px 8px rgba(15, 39, 74, 0.05);
        }

        .academic-logo-badge {
            width: 42px;
            height: 42px;
            border-radius: 8px;
            background: linear-gradient(135deg, var(--edu-navy-900), var(--edu-primary));
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            font-size: 1.25rem;
            box-shadow: 0 2px 6px rgba(27, 77, 137, 0.25);
        }

        .institution-label {
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--edu-burgundy);
            line-height: 1.1;
        }

        .portal-title {
            font-family: var(--font-heading);
            font-size: 1.15rem;
            font-weight: 700;
            color: var(--edu-navy-900);
            margin: 0;
            line-height: 1.2;
        }

        /* Academic Cards & Panels */
        .card-academic {
            background: var(--edu-surface);
            border-radius: 10px;
            border: 1px solid var(--edu-border);
            box-shadow: 0 1px 3px rgba(15, 39, 74, 0.04);
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .card-academic:hover {
            border-color: #94a3b8;
            box-shadow: 0 4px 12px rgba(15, 39, 74, 0.06);
        }

        .card-academic-header {
            padding: 0.9rem 1.25rem;
            background-color: #f8fafc;
            border-bottom: 1px solid var(--edu-border-light);
            border-top-left-radius: 9px;
            border-top-right-radius: 9px;
        }

        /* Institutional Buttons */
        .btn-academic-primary {
            background-color: var(--edu-primary);
            border-color: var(--edu-primary);
            color: #ffffff;
            font-weight: 600;
            border-radius: 6px;
        }
        .btn-academic-primary:hover {
            background-color: var(--edu-primary-hover);
            border-color: var(--edu-primary-hover);
            color: #ffffff;
        }

        .btn-academic-outline {
            border-color: var(--edu-primary);
            color: var(--edu-primary);
            font-weight: 600;
            background: #ffffff;
            border-radius: 6px;
        }
        .btn-academic-outline:hover {
            background-color: var(--edu-primary-soft);
            color: var(--edu-primary-hover);
            border-color: var(--edu-primary);
        }

        /* Academic Institutional Table */
        .table-academic {
            border-collapse: separate;
            border-spacing: 0;
            width: 100%;
        }
        .table-academic thead th {
            background-color: #f1f5f9;
            color: var(--edu-navy-900);
            font-family: var(--font-heading);
            font-weight: 700;
            font-size: 0.82rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            padding: 0.75rem 0.85rem;
            border-bottom: 2px solid var(--edu-border);
        }
        .table-academic tbody td {
            padding: 0.75rem 0.85rem;
            border-bottom: 1px solid var(--edu-border-light);
            color: var(--edu-text-main);
            vertical-align: middle;
        }
        .table-academic tbody tr:hover td {
            background-color: #f8fafc;
        }

        /* Footer */
        .academic-footer {
            background: #ffffff;
            border-top: 1px solid var(--edu-border);
            color: var(--edu-text-muted);
            font-size: 0.825rem;
        }

        /* Accessibility Focus Ring */
        a:focus-visible, button:focus-visible, input:focus-visible, select:focus-visible {
            outline: 2px solid var(--edu-primary);
            outline-offset: 2px;
        }

        /* Print Media Styles */
        @media print {
            .academic-topbar, .btn-group, .no-print {
                display: none !important;
            }
            body {
                background: #ffffff;
                color: #000000;
            }
            .card-academic {
                border: 1px solid #000000;
                box-shadow: none;
            }
        }
    </style>

    @stack('styles')
</head>
<body>

    <!-- Semantic Header & Navigation -->
    <header class="academic-topbar sticky-top">
        <nav class="navbar navbar-expand-lg py-2" aria-label="Điều hướng chính">
            <div class="container-fluid px-lg-5">
                <!-- University Crest / Identity Logo -->
                <a class="navbar-brand d-flex align-items-center gap-3 text-decoration-none" href="{{ route('schedules.index') }}" title="Về trang chủ Kế hoạch Giảng dạy">
                    <div class="academic-logo-badge" aria-hidden="true">
                        <i class="fa-solid fa-building-columns"></i>
                    </div>
                    <div>
                        <div class="institution-label">KHOA CÔNG NGHỆ THÔNG TIN</div>
                        <div class="portal-title">SỔ TAY GIẢNG DẠY ĐIỆN TỬ</div>
                    </div>
                </a>

                <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar" aria-controls="mainNavbar" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <!-- Main Navigation Links -->
                <div class="collapse navbar-collapse ms-lg-4" id="mainNavbar">
                    <ul class="navbar-nav me-auto mb-2 mb-lg-0 gap-1">
                        <li class="nav-item">
                            <a class="nav-link px-3 py-1 rounded fw-semibold {{ request()->routeIs('schedules.*') ? 'active bg-primary-subtle text-primary border border-primary-subtle' : 'text-dark' }}" 
                               href="{{ route('schedules.index') }}">
                                <i class="fa-solid fa-calendar-days me-1 text-secondary"></i> Lịch Giảng Dạy
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link px-3 py-1 rounded fw-semibold {{ request()->routeIs('subjects.*') ? 'active bg-primary-subtle text-primary border border-primary-subtle' : 'text-dark' }}" 
                               href="{{ route('subjects.index') }}">
                                <i class="fa-solid fa-book-bookmark me-1 text-secondary"></i> Quản Lý Môn Học
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link px-3 py-1 rounded fw-semibold {{ request()->routeIs('teachers.*') ? 'active bg-primary-subtle text-primary border border-primary-subtle' : 'text-dark' }}" 
                               href="{{ route('teachers.index') }}">
                                <i class="fa-solid fa-chalkboard-user me-1 text-secondary"></i> Quản Lý Giáo Viên
                            </a>
                        </li>
                    </ul>
                </div>

                <!-- Right Menu: Teacher Switcher & Action Tools -->
                <div class="d-flex align-items-center gap-2 ms-auto">
                    @yield('header_actions')
                </div>
            </div>
        </nav>
    </header>

    <!-- Main Content Area with Semantic Landmark -->
    <main id="main-content" class="flex-grow-1">
        @yield('content')
    </main>

    <!-- Semantic Institutional Footer -->
    <footer class="academic-footer py-3 mt-4">
        <div class="container-fluid px-lg-5">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div class="d-flex align-items-center gap-2">
                    <i class="fa-solid fa-graduation-cap text-secondary" aria-hidden="true"></i>
                    <span><strong>Hệ thống Quản lý Kế hoạch Đào tạo</strong> — Khoa Công nghệ Thông tin</span>
                </div>
                <div class="text-secondary small">
                    <span>Chuẩn Sư phạm &middot; Bảo mật Học vụ &middot; Năm học 2026 - 2027</span>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap 5 Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    @stack('scripts')
</body>
</html>
