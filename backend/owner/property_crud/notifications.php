<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="View recent updates about your properties.">
    <title>Notifications | EstateFlow</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/owner/notifications.css">
</head>
<body class="owner-notifications-page">
    <main class="container py-4 py-lg-5">
        <nav class="page-nav d-flex align-items-center justify-content-between mb-5" aria-label="Page navigation">
            <a class="brand-mark text-decoration-none" href="../owner_dashboard.php">
                <span class="brand-icon" aria-hidden="true">E</span>
                <span>EstateFlow</span>
            </a>
            <a class="btn btn-outline-secondary" href="../owner_dashboard.php">Back to dashboard</a>
        </nav>

        <header class="d-flex flex-column flex-md-row align-items-md-end justify-content-between gap-3 mb-4">
            <div>
                <p class="eyebrow mb-2">OWNER WORKSPACE</p>
                <h1 class="display-6 fw-bold mb-2">Notifications</h1>
                <p class="text-secondary mb-0">Stay up to date with activity across your property portfolio.</p>
            </div>
            <a class="btn btn-outline-primary" href="bookings.php">View bookings</a>
        </header>

        <div class="alert preview-notice d-flex gap-2 align-items-start mb-4" role="note">
            <span aria-hidden="true">ⓘ</span>
            <span>These notifications are sample content for display only.</span>
        </div>

        <section class="row g-3 mb-4" aria-label="Notification summary">
            <div class="col-12 col-sm-6">
                <article class="summary-card h-100">
                    <span class="summary-label">New booking requests</span>
                    <strong class="summary-blue">02</strong>
                </article>
            </div>
            <div class="col-12 col-sm-6">
                <article class="summary-card h-100">
                    <span class="summary-label">Payment updates</span>
                    <strong class="summary-green">01</strong>
                </article>
            </div>
        </section>

        <section class="notification-panel" aria-labelledby="recent-title">
            <div class="notification-panel-heading d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div>
                    <h2 id="recent-title" class="h4 mb-1">Recent activity</h2>
                    <p class="text-secondary mb-0">The latest updates from your properties.</p>
                </div>
                <span class="sample-count">4 sample updates</span>
            </div>

            <div class="notification-list">
                <article class="notification-item is-unread">
                    <span class="notification-icon icon-booking" aria-hidden="true">▦</span>
                    <div class="notification-copy">
                        <div class="notification-title-row">
                            <h3>New booking request</h3>
                            <span class="unread-dot" aria-label="Unread"></span>
                        </div>
                        <p>Nadia Karim requested a booking for <strong>Lakeview Heights</strong>.</p>
                        <span class="notification-time">Today · 9:15 AM</span>
                    </div>
                    <span class="notification-category">Booking</span>
                </article>

                <article class="notification-item is-unread">
                    <span class="notification-icon icon-payment" aria-hidden="true">৳</span>
                    <div class="notification-copy">
                        <div class="notification-title-row">
                            <h3>Payment received</h3>
                            <span class="unread-dot" aria-label="Unread"></span>
                        </div>
                        <p>November rent payment received for <strong>Cedar Court, Unit 3C</strong>.</p>
                        <span class="notification-time">Yesterday · 4:40 PM</span>
                    </div>
                    <span class="notification-category">Payment</span>
                </article>

                <article class="notification-item">
                    <span class="notification-icon icon-maintenance" aria-hidden="true">⌁</span>
                    <div class="notification-copy">
                        <div class="notification-title-row">
                            <h3>Maintenance request updated</h3>
                        </div>
                        <p>The kitchen plumbing request for <strong>Cedar Court</strong> is now in progress.</p>
                        <span class="notification-time">Nov 10 · 11:20 AM</span>
                    </div>
                    <span class="notification-category">Maintenance</span>
                </article>

                <article class="notification-item">
                    <span class="notification-icon icon-booking" aria-hidden="true">▦</span>
                    <div class="notification-copy">
                        <div class="notification-title-row">
                            <h3>Booking request approved</h3>
                        </div>
                        <p>Maliha Chowdhury’s request for <strong>Cedar Court, Unit 3C</strong> was approved.</p>
                        <span class="notification-time">Nov 08 · 2:05 PM</span>
                    </div>
                    <span class="notification-category">Booking</span>
                </article>
            </div>
        </section>

        <footer class="page-footer mt-5 pt-3">
            <span>EstateFlow · Owner workspace</span>
            <a href="../owner_dashboard.php">Return to dashboard</a>
        </footer>
    </main>
</body>
</html>
