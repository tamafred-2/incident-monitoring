<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Subdivision Monitoring Platform') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        :root {
            --page-bg: #eef2f6;
            --surface: rgba(255, 255, 255, 0.78);
            --surface-strong: rgba(255, 255, 255, 0.92);
            --border: #dbe3ec;
            --text: #111827;
            --muted: #5d6f8b;
            --heading: #0d1726;
            --primary: #0ea5e9;
            --primary-soft: #e0f2fe;
            --success: #059669;
            --shadow: 0 24px 50px rgba(15, 23, 42, 0.08);
        }

        * {
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: 'Outfit', sans-serif;
            color: var(--text);
            background:
                radial-gradient(circle at top left, rgba(127, 179, 255, 0.16), transparent 26%),
                linear-gradient(180deg, #f5f7fb 0%, var(--page-bg) 100%);
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        .shell {
            width: min(1180px, calc(100% - 32px));
            margin: 0 auto;
        }

        .topbar {
            padding: 12px 0;
            color: #f8fbff;
            background: linear-gradient(90deg, #0a1321 0%, #0d1726 54%, #172235 100%);
        }

        .topbar-inner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            font-size: 0.95rem;
        }

        .topbar-copy {
            display: flex;
            gap: 18px;
            flex-wrap: wrap;
            opacity: 0.95;
        }

        .hero-wrap {
            padding: 24px 0 56px;
        }

        .nav-card,
        .hero-card,
        .feature-card,
        .overview-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 30px;
            box-shadow: var(--shadow);
            backdrop-filter: blur(18px);
        }

        .nav-card {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            padding: 18px 22px;
            margin-bottom: 22px;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 16px;
            min-width: 0;
        }

        .brand-mark {
            width: 60px;
            height: 60px;
            border-radius: 999px;
            overflow: hidden;
            flex: 0 0 auto;
            border: 1px solid rgba(255, 255, 255, 0.65);
            background: linear-gradient(145deg, #ffffff, #e9eef8);
            box-shadow: 0 12px 24px rgba(18, 31, 61, 0.12);
        }

        .brand-mark img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .brand-kicker,
        .eyebrow,
        .card-eyebrow,
        .mini-label {
            letter-spacing: 0.18em;
            text-transform: uppercase;
            font-size: 0.76rem;
            font-weight: 700;
        }

        .brand-kicker {
            color: #546987;
        }

        .brand-title {
            margin: 4px 0 0;
            font-size: 1.6rem;
            font-weight: 700;
            color: var(--heading);
            line-height: 1.1;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .nav-link,
        .ghost-btn,
        .secondary-btn {
            padding: 12px 18px;
            border-radius: 999px;
            border: 1px solid rgba(14, 165, 233, 0.12);
            color: var(--heading);
            background: rgba(255, 255, 255, 0.56);
            font-weight: 600;
            transition: transform 0.18s ease, box-shadow 0.18s ease, background 0.18s ease;
        }

        .primary-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 14px 22px;
            border-radius: 999px;
            border: 1px solid #0284c7;
            color: #fff;
            background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%);
            box-shadow: none;
            font-weight: 700;
            transition: transform 0.18s ease, box-shadow 0.18s ease;
        }

        .primary-btn:hover,
        .ghost-btn:hover,
        .secondary-btn:hover,
        .nav-link:hover {
            transform: translateY(-1px);
        }

        .hero-card {
            display: grid;
            grid-template-columns: minmax(0, 1.08fr) minmax(320px, 0.92fr);
            gap: 28px;
            padding: 34px;
        }

        .hero-copy {
            padding: 12px 4px;
        }

        .eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 10px 16px;
            color: #384a6a;
            background: rgba(255, 255, 255, 0.74);
            border: 1px solid rgba(14, 165, 233, 0.14);
            border-radius: 999px;
        }

        .hero-title {
            margin: 22px 0 16px;
            font-size: clamp(2.7rem, 5vw, 4.9rem);
            line-height: 0.98;
            letter-spacing: -0.04em;
            color: var(--heading);
        }

        .hero-title .accent {
            display: inline-block;
            color: #0369a1;
        }

        .hero-description {
            max-width: 640px;
            margin: 0 0 24px;
            font-size: 1.1rem;
            line-height: 1.75;
            color: var(--muted);
        }

        .hero-actions {
            display: flex;
            gap: 14px;
            flex-wrap: wrap;
            margin-bottom: 28px;
        }

        .hero-metrics {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 14px;
        }

        .metric {
            padding: 18px 18px 16px;
            background: rgba(255, 255, 255, 0.70);
            border: 1px solid rgba(14, 165, 233, 0.10);
            border-radius: 22px;
        }

        .metric-value {
            display: block;
            margin-bottom: 6px;
            font-size: 1.7rem;
            font-weight: 800;
            color: var(--heading);
        }

        .metric-note {
            margin: 0;
            color: var(--muted);
            line-height: 1.45;
        }

        .hero-preview {
            position: relative;
            display: flex;
            flex-direction: column;
            gap: 16px;
            padding: 26px;
            border-radius: 28px;
            background:
                radial-gradient(circle at top right, rgba(127, 179, 255, 0.22), transparent 35%),
                linear-gradient(180deg, rgba(255, 255, 255, 0.88), rgba(247, 249, 255, 0.94));
            border: 1px solid rgba(14, 165, 233, 0.12);
            overflow: hidden;
            min-height: 100%;
        }

        .hero-preview::before {
            content: "";
            position: absolute;
            right: -60px;
            top: -40px;
            width: 180px;
            height: 180px;
            border-radius: 50%;
            background: rgba(127, 179, 255, 0.18);
            filter: blur(4px);
        }

        .preview-top {
            position: relative;
            z-index: 1;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .preview-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 14px;
            border-radius: 999px;
            background: rgba(14, 165, 233, 0.10);
            color: #075985;
            font-weight: 700;
        }

        .status-pill {
            padding: 8px 12px;
            border-radius: 999px;
            color: #065f46;
            background: rgba(5, 150, 105, 0.12);
            font-weight: 700;
            font-size: 0.9rem;
        }

        .preview-panel {
            position: relative;
            z-index: 1;
            padding: 22px;
            border-radius: 24px;
            background: var(--surface-strong);
            border: 1px solid rgba(255, 255, 255, 0.72);
            box-shadow: 0 18px 30px rgba(18, 31, 61, 0.08);
        }

        .preview-panel h3,
        .overview-heading {
            margin: 6px 0 10px;
            font-size: 1.45rem;
            color: var(--heading);
        }

        .preview-panel p,
        .overview-copy,
        .feature-card p {
            margin: 0;
            line-height: 1.65;
            color: var(--muted);
        }

        .progress {
            height: 10px;
            margin: 18px 0 12px;
            border-radius: 999px;
            background: #e7ecf5;
            overflow: hidden;
        }

        .progress > span {
            display: block;
            width: 86%;
            height: 100%;
            border-radius: inherit;
            background: linear-gradient(90deg, #0ea5e9, #38bdf8);
        }

        .preview-grid {
            position: relative;
            z-index: 1;
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
        }

        .preview-mini {
            padding: 16px 16px 14px;
            border-radius: 20px;
            background: rgba(255, 255, 255, 0.84);
            border: 1px solid rgba(14, 165, 233, 0.08);
        }

        .mini-value {
            display: block;
            margin-top: 8px;
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--heading);
        }

        .modules {
            padding-bottom: 56px;
        }

        .overview-card {
            padding: 30px;
            margin-bottom: 22px;
        }

        .overview-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 20px;
        }

        .overview-copy {
            max-width: 720px;
        }

        .overview-stat {
            min-width: 190px;
            padding: 18px 20px;
            border-radius: 24px;
            background: rgba(255, 255, 255, 0.84);
            border: 1px solid rgba(14, 165, 233, 0.10);
            text-align: right;
        }

        .overview-stat strong {
            display: block;
            margin-bottom: 4px;
            font-size: 2rem;
            color: var(--heading);
        }

        .feature-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 18px;
        }

        .feature-card {
            padding: 24px;
        }

        .card-eyebrow {
            color: #546987;
        }

        .feature-card h3 {
            margin: 10px 0 10px;
            font-size: 1.75rem;
            color: var(--heading);
        }

        .feature-card .accent-line {
            width: 56px;
            height: 4px;
            margin: 16px 0;
            border-radius: 999px;
            background: linear-gradient(90deg, #0ea5e9, #38bdf8);
        }

        .workflow-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
            margin-top: 22px;
        }

        .workflow-card {
            padding: 20px;
            border-radius: 22px;
            background: rgba(255, 255, 255, 0.82);
            border: 1px solid rgba(14, 165, 233, 0.12);
        }

        .workflow-card h3 {
            margin: 0;
            font-size: 1.12rem;
            color: var(--heading);
        }

        .workflow-list {
            margin: 12px 0 0;
            padding-left: 22px;
            color: var(--muted);
        }

        .workflow-list li {
            margin-bottom: 8px;
            line-height: 1.5;
        }

        .label-cloud {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 22px;
        }

        .flow-label {
            display: inline-flex;
            align-items: center;
            padding: 9px 14px;
            border-radius: 999px;
            background: #f0f7ff;
            border: 1px solid rgba(14, 165, 233, 0.18);
            color: #0b4a6b;
            font-size: 0.9rem;
            font-weight: 600;
            line-height: 1.2;
        }

        .table-wrap {
            margin-top: 22px;
            overflow-x: auto;
            border-radius: 22px;
            border: 1px solid rgba(14, 165, 233, 0.16);
            background: rgba(255, 255, 255, 0.88);
        }

        .permissions-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 860px;
        }

        .permissions-table th,
        .permissions-table td {
            padding: 14px 16px;
            border-bottom: 1px solid #dce6f2;
            text-align: left;
            vertical-align: top;
        }

        .permissions-table th {
            background: #e8f3ff;
            color: #0f2940;
            font-size: 0.78rem;
            letter-spacing: 0.12em;
            text-transform: uppercase;
        }

        .permissions-table td {
            color: #2f4460;
            line-height: 1.55;
        }

        .permissions-table tr:last-child td {
            border-bottom: 0;
        }

        .page-footer {
            padding: 18px 0 34px;
            text-align: center;
            color: #5f718d;
        }

        @media (max-width: 980px) {
            .hero-card,
            .feature-grid,
            .workflow-grid {
                grid-template-columns: 1fr;
            }

            .overview-head,
            .nav-card,
            .topbar-inner {
                flex-direction: column;
                align-items: flex-start;
            }

            .overview-stat {
                text-align: left;
                min-width: 0;
                width: 100%;
            }
        }

        @media (max-width: 720px) {
            .hero-wrap {
                padding-top: 16px;
            }

            .hero-card,
            .overview-card,
            .feature-card {
                padding: 22px;
                border-radius: 24px;
            }

            .hero-metrics,
            .preview-grid {
                grid-template-columns: 1fr;
            }

            .nav-links {
                width: 100%;
                justify-content: flex-start;
            }

            .brand-title {
                font-size: 1.3rem;
            }

            .hero-title {
                font-size: clamp(2.3rem, 10vw, 3.6rem);
            }
        }
    </style>
</head>
<body>
@php
    $subdivision = \App\Models\Subdivision::query()->orderBy('subdivision_name')->first();
    $brandName = $subdivision?->subdivision_name ?? 'Subdivision Monitoring Platform';
    $brandLogo = $subdivision?->logo_url ?? asset('imgsrc/logo.png');
    $contactLabel = $subdivision?->contact_number
        ? 'Contact: ' . $subdivision->contact_number
        : 'Contact';
@endphp

    <div class="topbar">
        <div class="shell topbar-inner">
            <div class="topbar-copy">
                <span><strong>Monitoring Platform</strong></span>
                <span>Visitor logs, resident records, and incident coordination in one system.</span>
            </div>
            <span>{{ $contactLabel }}</span>
        </div>
    </div>

    <main class="hero-wrap">
        <div class="shell">
            <header class="nav-card">
                <div class="brand">
                    <div class="brand-mark">
                        <img src="{{ $brandLogo }}" alt="{{ $brandName }} logo">
                    </div>
                    <div>
                        <div class="brand-kicker">Subdivision System</div>
                        <p class="brand-title">{{ $brandName }}</p>
                    </div>
                </div>

                <nav class="nav-links">
                    <a href="#modules" class="nav-link">Modules</a>
                    <a href="#overview" class="nav-link">Overview</a>
                    <a href="#process" class="nav-link">Process</a>
                    <a href="{{ route('login') }}" class="primary-btn">Get Started</a>
                </nav>
            </header>

            <section class="hero-card">
                <div class="hero-copy">
                    <div class="eyebrow">Manual And Automated Approval</div>
                    <h1 class="hero-title">
                        Contact Residents Through <span class="accent">Registered Phone Numbers</span> And Track Every Response
                    </h1>
                    <p class="hero-description">
                        The Admin/Guard or system contacts the resident using the phone number registered in the system.
                        Visitor approval, check-in/check-out, and incident handling follow one aligned workflow for manual and automated operations.
                    </p>

                    <div class="hero-actions">
                        <a href="{{ route('login') }}" class="primary-btn">Log In To Continue</a>
                        <a href="#process" class="ghost-btn">View Corrected Process</a>
                    </div>

                    <div class="hero-metrics">
                        <div class="metric">
                            <span class="metric-value">Manual</span>
                            <p class="metric-note">Admin/Guard records details and contacts the resident using the registered phone number in the system.</p>
                        </div>
                        <div class="metric">
                            <span class="metric-value">Automated</span>
                            <p class="metric-note">System check-in triggers resident contact through the registered number and returns the resident response.</p>
                        </div>
                        <div class="metric">
                            <span class="metric-value">Incident</span>
                            <p class="metric-note">Incident status remains Pending until handled, then moves to Resolved with report-ready logs.</p>
                        </div>
                    </div>
                </div>

                <aside class="hero-preview">
                    <div class="preview-top">
                        <span class="preview-badge">Operations Snapshot</span>
                        <span class="status-pill">Ready</span>
                    </div>

                    <div class="preview-panel">
                        <div class="mini-label">Platform Modules</div>
                        <h3>Aligned to your diagram and corrected call process.</h3>
                        <p>Every visitor and incident step now uses the same wording and flow for manual and automated operations.</p>
                        <div class="progress"><span></span></div>
                        <p>Resident contact always points to the registered phone number stored in the system.</p>
                    </div>

                    <div class="preview-grid">
                        <div class="preview-mini">
                            <div class="mini-label">Visitor Approval</div>
                            <span class="mini-value">Phone-Based</span>
                            <p>Contact resident using registered phone number and record the resident response.</p>
                        </div>
                        <div class="preview-mini">
                            <div class="mini-label">Resident Response</div>
                            <span class="mini-value">Approve / Deny</span>
                            <p>Resident response controls whether visitor entry is allowed or denied.</p>
                        </div>
                        <div class="preview-mini">
                            <div class="mini-label">Incident Reporting</div>
                            <span class="mini-value">Tracked</span>
                            <p>Report incident through call or system, then track Pending and Resolved states.</p>
                        </div>
                        <div class="preview-mini">
                            <div class="mini-label">Reports</div>
                            <span class="mini-value">Generated</span>
                            <p>Generate visitor and incident reports from logged responses and status updates.</p>
                        </div>
                    </div>
                </aside>
            </section>
        </div>
    </main>

    <section class="modules" id="modules">
        <div class="shell">
            <div class="overview-card" id="overview">
                <div class="overview-head">
                    <div>
                        <div class="card-eyebrow">Platform Overview</div>
                        <h2 class="overview-heading">System flow aligned to your manual and automated diagram.</h2>
                        <p class="overview-copy">
                            This system now centers the corrected process wording: the Admin/Guard or system contacts the resident using the
                            registered phone number in the system, then records the resident response to allow or deny entry.
                        </p>
                    </div>
                    <div class="overview-stat">
                        <strong>2</strong>
                        <span>Main paths: Manual and Automated</span>
                    </div>
                </div>
            </div>

            <div class="feature-grid">
                <article class="feature-card">
                    <div class="card-eyebrow">Resident Contact</div>
                    <h3>Registered Phone Number</h3>
                    <div class="accent-line"></div>
                    <p>Use the resident phone number saved in the system for visitor confirmation and incident-related calls.</p>
                </article>

                <article class="feature-card">
                    <div class="card-eyebrow">Visitor Control</div>
                    <h3>Approve Or Deny Entry</h3>
                    <div class="accent-line"></div>
                    <p>Resident response is captured and shown to Admin/Guard before visitor check-in and final entry decision.</p>
                </article>

                <article class="feature-card">
                    <div class="card-eyebrow" style="color: #0369a1;">Incident Status</div>
                    <h3>Pending To Resolved</h3>
                    <div class="accent-line"></div>
                    <p>Manage incidents with clear pending and resolved outcomes, then generate visitor and incident reports.</p>
                </article>
            </div>

            <div class="overview-card" id="process">
                <div class="card-eyebrow">Corrected Main Process</div>
                <h2 class="overview-heading">Manual and automated flows now use one consistent approval logic.</h2>
                <p class="overview-copy">The corrected wording is applied system-wide: <strong>Contact resident using registered phone number</strong>.</p>

                <div class="workflow-grid">
                    <article class="workflow-card">
                        <h3>Manual Visitor Approval</h3>
                        <ol class="workflow-list">
                            <li>Visitor arrives.</li>
                            <li>Admin/Guard records visitor details.</li>
                            <li>Admin/Guard gets the resident phone number from the system.</li>
                            <li>Admin/Guard contacts the resident using the registered phone number.</li>
                            <li>Resident response is recorded.</li>
                            <li>Visitor approved? If yes, allow entry. If no, deny entry.</li>
                            <li>Visitor checks out before leaving.</li>
                        </ol>
                    </article>

                    <article class="workflow-card">
                        <h3>System/Automated Visitor Approval</h3>
                        <ol class="workflow-list">
                            <li>Visitor checks in through the system.</li>
                            <li>System identifies the resident being visited.</li>
                            <li>System uses the resident registered phone number.</li>
                            <li>System automatically contacts the resident.</li>
                            <li>Resident responds through call, SMS, app, or system prompt.</li>
                            <li>System shows the response to Admin/Guard.</li>
                            <li>Visitor approved? If yes, allow entry and track check-out.</li>
                        </ol>
                    </article>

                    <article class="workflow-card">
                        <h3>Manual Incident Reporting</h3>
                        <ol class="workflow-list">
                            <li>Resident, Admin/Guard, or Admin/Staff notices an incident.</li>
                            <li>Incident is reported manually.</li>
                            <li>Admin/Guard or Admin/Staff records the incident.</li>
                            <li>Admin/Staff reviews and manages incident status.</li>
                            <li>Status remains Incident pending until handled.</li>
                            <li>Once handled, mark as Incident resolved.</li>
                        </ol>
                    </article>

                    <article class="workflow-card">
                        <h3>System/Automated Incident Reporting</h3>
                        <ol class="workflow-list">
                            <li>Incident is submitted through the system.</li>
                            <li>System records the incident and notifies Admin/Staff.</li>
                            <li>Admin/Staff reviews updates and assigns actions.</li>
                            <li>System tracks Incident pending until resolved.</li>
                            <li>Once fixed, status is changed to Incident resolved.</li>
                            <li>Generate visitor/incident report when needed.</li>
                        </ol>
                    </article>
                </div>

                <div class="label-cloud">
                    <span class="flow-label">Contact resident using registered phone number</span>
                    <span class="flow-label">Resident response</span>
                    <span class="flow-label">Visitor approved?</span>
                    <span class="flow-label">Report incident using registered phone number/system</span>
                    <span class="flow-label">Generate visitor/incident report</span>
                    <span class="flow-label">Manage incident status</span>
                    <span class="flow-label">Incident resolved</span>
                    <span class="flow-label">Incident pending</span>
                </div>
            </div>

            <div class="overview-card">
                <div class="card-eyebrow">Permission Summary</div>
                <h2 class="overview-heading">Manual and automated responsibilities by user type.</h2>

                <div class="table-wrap">
                    <table class="permissions-table">
                        <thead>
                            <tr>
                                <th>User Type</th>
                                <th>Manual Actions</th>
                                <th>System/Automated Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong>Resident</strong></td>
                                <td>Answer call, approve visitor, deny visitor, report incident.</td>
                                <td>Receive automated call/SMS/app notification, approve or deny visitor through system.</td>
                            </tr>
                            <tr>
                                <td><strong>Visitor</strong></td>
                                <td>Give details to Admin/Guard, wait for approval, check in, check out.</td>
                                <td>Use system check-in/check-out and wait for automated approval result.</td>
                            </tr>
                            <tr>
                                <td><strong>Admin/Guard</strong></td>
                                <td>Contact resident using registered phone number, record response, allow/deny visitor, assist incident report.</td>
                                <td>Monitor resident response from system, confirm entry decision, review automated records.</td>
                            </tr>
                            <tr>
                                <td><strong>Admin/Staff</strong></td>
                                <td>Manage incidents, update status, generate reports.</td>
                                <td>View automated logs, receive unresolved notifications, generate scheduled reports.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>

    <footer class="page-footer">
        <div class="shell">
            <strong>{{ $brandName }}</strong><br>
            Visitor approval and incident reporting aligned to registered phone number contact logic.
        </div>
    </footer>
</body>
</html>
