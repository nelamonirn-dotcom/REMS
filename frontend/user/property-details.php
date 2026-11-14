<?php
session_start();
if (!isset($_SESSION['email'])) {
  header("Location: ../../backend/admin/index.php");
  exit;
}
if ($_SESSION['role'] != "customer") {
  header("Location: ../../backend/admin/unauthorize.php");
  exit;
}

// dbconfig.php koi ache seta auto khuje nibe
$dbCandidates = [
  __DIR__ . '/dbconfig.php',
  __DIR__ . '/../dbconfig.php',
  __DIR__ . '/../../dbconfig.php',
  __DIR__ . '/../../backend/dbconfig.php',
  __DIR__ . '/../../backend/admin/dbconfig.php',
];
foreach ($dbCandidates as $file) {
  if (file_exists($file)) { include_once $file; break; }
}
if (!isset($conn)) {
  die('dbconfig.php khuje pawa jayni. $dbCandidates er path gulo check koro.');
}

// Listing page (jekhane shob property dekhay)
$listingPage = 'customers_dashboard.php#properties';

$id = (int)($_GET['id'] ?? 0);
$p  = null;

if ($id > 0) {
  $stmt = $conn->prepare("SELECT property_id, title, property_type, address, area_sqft,
                                 price, monthly_rent, bedrooms, bathrooms, status, description
                          FROM properties WHERE property_id = ? LIMIT 1");
  $stmt->bind_param('i', $id);
  $stmt->execute();
  $p = $stmt->get_result()->fetch_assoc();
}

if (!$p) {
  http_response_code(404);
}

// Helpers
function money($n) { return '৳' . number_format((float)$n); }

if ($p) {
  $typeLabel   = ucfirst($p['property_type']);
  $statusLabel = ucfirst($p['status']);
  $hasPrice    = $p['price'] !== null;
  $hasRent     = $p['monthly_rent'] !== null;

  // Listing tag
  if ($p['status'] === 'sold')        $tag = 'Sold';
  elseif ($p['status'] === 'rented')  $tag = 'Rented';
  elseif ($hasPrice && $hasRent)      $tag = 'For sale / rent';
  elseif ($hasRent)                   $tag = 'For rent';
  else                                $tag = 'For sale';

  // Short location (address er shesh 2 ta part)
  $parts    = array_map('trim', explode(',', $p['address']));
  $shortLoc = implode(', ', array_slice($parts, -2));

  $contactLink = 'contact.php?property=' . urlencode($p['title']);
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" content="#102b2b">
  <meta name="description" content="Explore the details of a thoughtfully selected EstateFlow home.">
  <title><?= $p ? htmlspecialchars($p['title']) . ' | EstateFlow' : 'Property not found | EstateFlow' ?></title>
  <link rel="stylesheet" href="partials/style.css">
  <link rel="stylesheet" href="partials/property-details.css">
</head>
<body class="property-details-page">
  <?php include __DIR__ . '/partials/customar_header.php'; ?>

  <main>
<?php if (!$p): ?>
    <section class="shell" style="padding:80px 0; text-align:center;">
      <h1>Property pawa jayni</h1>
      <p>Ei property ta hoyto remove kora hoyeche ba link ta thik nei.</p>
      <a class="button" href="<?= $listingPage ?>">Browse all properties <span aria-hidden="true">→</span></a>
    </section>
<?php else: ?>
    <div class="shell details-breadcrumb">
      <a href="<?= $listingPage ?>">Properties</a>
      <span aria-hidden="true">/</span>
      <span><?= htmlspecialchars($shortLoc) ?></span>
    </div>

    <section class="shell detail-gallery" aria-label="Property photo gallery">
      <div class="gallery-main">
        <img src="https://images.unsplash.com/photo-1600607687920-4e2a09cf159d?auto=format&amp;fit=crop&amp;w=1400&amp;q=85" alt="<?= htmlspecialchars($p['title']) ?>">
        <span class="listing-tag"><?= htmlspecialchars($tag) ?></span>
        <span class="gallery-count">1 / 4 photos</span>
      </div>
      <div class="gallery-side">
        <img src="https://images.unsplash.com/photo-1600566753086-00f18fb6b3ea?auto=format&amp;fit=crop&amp;w=800&amp;q=82" alt="Exterior view">
        <img src="https://images.unsplash.com/photo-1600210492486-724fe5c67fb0?auto=format&amp;fit=crop&amp;w=800&amp;q=82" alt="Living area">
        <button class="gallery-more" type="button" aria-label="View all 4 property photos">
          <img src="https://images.unsplash.com/photo-1600607687939-ce8a6c25118c?auto=format&amp;fit=crop&amp;w=800&amp;q=82" alt="">
          <span>View all photos <strong>＋</strong></span>
        </button>
      </div>
    </section>

    <section class="shell property-detail-layout">
      <div class="property-main-content">
        <div class="property-heading">
          <div>
            <p class="property-location">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M19 10c0 5-7 11-7 11S5 15 5 10a7 7 0 1 1 14 0Z" stroke="currentColor" stroke-width="1.7"/><circle cx="12" cy="10" r="2" stroke="currentColor" stroke-width="1.7"/></svg>
              <?= htmlspecialchars($shortLoc) ?>
            </p>
            <h1><?= htmlspecialchars($p['title']) ?></h1>
            <p class="property-address"><?= htmlspecialchars($p['address']) ?></p>
          </div>
          <button class="save-property" type="button" aria-label="Save this property">
            <svg width="19" height="19" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M20.8 8.8c0 5.2-8.8 10-8.8 10s-8.8-4.8-8.8-10A4.8 4.8 0 0 1 12 6.1a4.8 4.8 0 0 1 8.8 2.7Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/></svg>
            Save
          </button>
        </div>

        <div class="property-highlights" aria-label="Property highlights">
          <div><span class="highlight-icon" aria-hidden="true">⌂</span><strong><?= htmlspecialchars($typeLabel) ?></strong><small>Property type</small></div>
          <?php if ($p['area_sqft'] !== null): ?>
            <div><span class="highlight-icon" aria-hidden="true">▱</span><strong><?= number_format($p['area_sqft']) ?> sqft</strong><small>Total area</small></div>
          <?php endif; ?>
          <?php if ($p['bedrooms'] !== null): ?>
            <div><span class="highlight-icon" aria-hidden="true">▤</span><strong><?= (int)$p['bedrooms'] ?> bedroom<?= (int)$p['bedrooms'] === 1 ? '' : 's' ?></strong><small>Comfortable rooms</small></div>
          <?php endif; ?>
          <?php if ($p['bathrooms'] !== null): ?>
            <div><span class="highlight-icon" aria-hidden="true">♨</span><strong><?= (int)$p['bathrooms'] ?> bathroom<?= (int)$p['bathrooms'] === 1 ? '' : 's' ?></strong><small>Full bathrooms</small></div>
          <?php endif; ?>
        </div>

        <section class="detail-section" aria-labelledby="overview-title">
          <p class="section-kicker">THE HOME</p>
          <h2 id="overview-title">A little more about this place</h2>
          <?php if (!empty(trim((string)$p['description']))): ?>
            <p class="detail-description"><?= nl2br(htmlspecialchars($p['description'])) ?></p>
          <?php else: ?>
            <p class="detail-description">Contact our team to learn more about this property.</p>
          <?php endif; ?>
        </section>

        <section class="detail-section location-section" aria-labelledby="location-title">
          <p class="section-kicker">THE NEIGHBORHOOD</p>
          <h2 id="location-title">A well-connected address</h2>
          <p class="detail-description"><?= htmlspecialchars($p['address']) ?></p>
          <div class="location-visual" role="img" aria-label="Decorative neighborhood map for <?= htmlspecialchars($shortLoc) ?>">
            <span class="map-road road-one"></span>
            <span class="map-road road-two"></span>
            <span class="map-road road-three"></span>
            <span class="map-park"></span>
            <span class="map-pin" aria-hidden="true">⌂</span>
            <span class="map-label"><?= htmlspecialchars($parts[count($parts) > 1 ? count($parts) - 2 : 0]) ?></span>
          </div>
        </section>
      </div>

      <aside class="property-sidebar" aria-label="Property price and contact">
        <section class="price-card">
          <?php if ($hasPrice): ?>
            <span class="price-card-label">ASKING PRICE</span>
            <div class="property-detail-price"><?= money($p['price']) ?></div>
            <p class="price-caption">One-time purchase price</p>
          <?php endif; ?>
          <?php if ($hasRent): ?>
            <span class="price-card-label"><?= $hasPrice ? 'MONTHLY RENT' : 'RENT' ?></span>
            <div class="property-detail-price"><?= money($p['monthly_rent']) ?><small>/month</small></div>
            <p class="price-caption">Monthly rent</p>
          <?php endif; ?>
          <?php if (!$hasPrice && !$hasRent): ?>
            <span class="price-card-label">PRICE</span>
            <div class="property-detail-price">On request</div>
          <?php endif; ?>
          <div class="price-divider"></div>
          <div class="sidebar-fact"><span>Property type</span><strong><?= htmlspecialchars($typeLabel) ?></strong></div>
          <div class="sidebar-fact"><span>Availability</span><strong class="availability"><i aria-hidden="true"></i> <?= htmlspecialchars($statusLabel) ?></strong></div>
          <?php
            $msgs = [
              'success'     => ['Booking request pathano hoyeche. Amader team shigroi jogajog korbe.', '#1b6b3a'],
              'exists'      => ['Ei property te apnar ekta pending booking already ache.', '#9a6a00'],
              'baddate'     => ['Ekta bhobishyoter tarikh o somoy din.', '#b3261e'],
              'unavailable' => ['Ei property ekhon available nei.', '#b3261e'],
              'error'       => ['Booking kora gelo na. Abar chesta korun.', '#b3261e'],
            ];
            $bk = $_GET['booking'] ?? '';
          ?>
          <div id="booking"></div>
          <?php if (isset($msgs[$bk])): ?>
            <p style="margin:12px 0; font-weight:600; color:<?= $msgs[$bk][1] ?>;"><?= $msgs[$bk][0] ?></p>
          <?php endif; ?>
          <?php if ($p['status'] === 'available'): ?>
            <form method="post" action="book_property.php" style="display:grid; gap:10px;">
              <input type="hidden" name="property_id" value="<?= (int)$p['property_id'] ?>">
              <label for="booking_date" style="font-size:14px;">Preferred viewing date &amp; time</label>
              <input id="booking_date" name="booking_date" type="datetime-local" required
                     min="<?= date('Y-m-d\TH:i') ?>"
                     style="padding:10px 12px; border:1px solid #ccc; border-radius:8px;">
              <button class="button viewing-button" type="submit">Request a viewing <span aria-hidden="true">→</span></button>
            </form>
          <?php endif; ?>
          <p class="contact-note">Our local team can help arrange a convenient visit.</p>
        </section>

        <section class="advisor-card">
          <div class="advisor-avatar" aria-hidden="true">EF</div>
          <div>
            <p class="advisor-label">HERE TO HELP</p>
            <h2>Talk to our property team</h2>
            <p>Get answers about this home and the next steps.</p>
          </div>
          <a class="advisor-link" href="<?= htmlspecialchars($contactLink) ?>">Contact our team <span aria-hidden="true">↗</span></a>
        </section>
      </aside>
    </section>
<?php endif; ?>

    <section class="more-homes">
      <div class="shell more-homes-inner">
        <div>
          <p class="section-kicker">KEEP EXPLORING</p>
          <h2>Still looking for the right fit?</h2>
          <p>There are more thoughtful homes waiting to be discovered.</p>
        </div>
        <a class="button" href="<?= $listingPage ?>">Browse all properties <span aria-hidden="true">→</span></a>
      </div>
    </section>
  </main>

  <?php include __DIR__ . '/partials/footer.php'; ?>
</body>
</html>