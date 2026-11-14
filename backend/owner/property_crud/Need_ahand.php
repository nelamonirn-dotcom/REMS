<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Get help managing your EstateFlow owner workspace.">
    <title>Need a Hand? | EstateFlow</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/owner/need-a-hand.css">
</head>
<body class="need-a-hand-page">
    <main class="container py-4 py-lg-5">
        <nav class="page-nav d-flex align-items-center justify-content-between mb-5" aria-label="Page navigation">
            <a class="brand-mark text-decoration-none" href="../owner_dashboard.php">
                <span class="brand-icon" aria-hidden="true">E</span>
                <span>EstateFlow</span>
            </a>
            <a class="btn btn-outline-secondary" href="../owner_dashboard.php">Back to dashboard</a>
        </nav>

        <header class="support-hero mb-4">
            <span class="hero-icon" aria-hidden="true">?</span>
            <p class="eyebrow mb-2">OWNER SUPPORT</p>
            <h1 class="display-6 fw-bold mb-2">Need a hand?</h1>
            <p class="mb-0">Find help with your properties, bookings, and owner account.</p>
        </header>

        <div class="alert preview-notice d-flex gap-2 align-items-start mb-4" role="note">
            <span aria-hidden="true">ⓘ</span>
            <span>This is a static support page preview. Contact information below is a placeholder.</span>
        </div>

        <section class="row g-3 mb-5" aria-label="Support options">
            <div class="col-12 col-md-6">
                <article class="support-card h-100">
                    <span class="support-icon email-icon" aria-hidden="true">✉</span>
                    <div>
                        <h2 class="h5 mb-2">Email the property team</h2>
                        <p class="text-secondary mb-3">Send a message about your owner workspace or property listing.</p>
                        <a class="support-link" href="mailto:hello@estateflow.example">hello@estateflow.example</a>
                    </div>
                </article>
            </div>
            <div class="col-12 col-md-6">
                <article class="support-card h-100">
                    <span class="support-icon guide-icon" aria-hidden="true">☷</span>
                    <div>
                        <h2 class="h5 mb-2">Owner workspace guide</h2>
                        <p class="text-secondary mb-3">Go directly to common owner tasks and review your workspace.</p>
                        <a class="support-link" href="../owner_dashboard.php">Return to dashboard <span aria-hidden="true">→</span></a>
                    </div>
                </article>
            </div>
        </section>

        <section class="mb-5" aria-labelledby="topics-title">
            <div class="section-heading mb-3">
                <p class="eyebrow mb-2">QUICK LINKS</p>
                <h2 id="topics-title" class="h3 mb-0">What can we help with?</h2>
            </div>
            <div class="row g-3">
                <div class="col-12 col-sm-6 col-lg-3">
                    <a class="topic-card" href="myproperty.php">
                        <span class="topic-icon topic-blue" aria-hidden="true">⌂</span>
                        <strong>My properties</strong>
                        <small>Review your listings</small>
                        <span class="topic-arrow" aria-hidden="true">→</span>
                    </a>
                </div>
                <div class="col-12 col-sm-6 col-lg-3">
                    <a class="topic-card" href="Addproperty.php">
                        <span class="topic-icon topic-green" aria-hidden="true">＋</span>
                        <strong>Add a property</strong>
                        <small>Create a listing</small>
                        <span class="topic-arrow" aria-hidden="true">→</span>
                    </a>
                </div>
                <div class="col-12 col-sm-6 col-lg-3">
                    <a class="topic-card" href="bookings.php">
                        <span class="topic-icon topic-purple" aria-hidden="true">▦</span>
                        <strong>Bookings</strong>
                        <small>Review booking requests</small>
                        <span class="topic-arrow" aria-hidden="true">→</span>
                    </a>
                </div>
                <div class="col-12 col-sm-6 col-lg-3">
                    <a class="topic-card" href="profile.php">
                        <span class="topic-icon topic-amber" aria-hidden="true">◎</span>
                        <strong>My profile</strong>
                        <small>View account details</small>
                        <span class="topic-arrow" aria-hidden="true">→</span>
                    </a>
                </div>
            </div>
        </section>

        <section class="faq-panel" aria-labelledby="faq-title">
            <div class="section-heading mb-3">
                <p class="eyebrow mb-2">HELP CENTER</p>
                <h2 id="faq-title" class="h3 mb-0">Frequently asked questions</h2>
            </div>

            <div class="faq-item">
                <h3>How do I add a property?</h3>
                <p>Open “Add a property” from the quick links and fill in the property details.</p>
            </div>
            <div class="faq-item">
                <h3>Where can I see booking requests?</h3>
                <p>Open the Bookings page to review the current booking request list.</p>
            </div>
            <div class="faq-item">
                <h3>How do I add photos to a listing?</h3>
                <p>Use the Property Images page to choose a property and select its photos.</p>
            </div>
        </section>

        <footer class="page-footer mt-5 pt-3">
            <span>EstateFlow · Owner workspace</span>
            <a href="../owner_dashboard.php">Return to dashboard</a>
        </footer>
    </main>
</body>
</html>
