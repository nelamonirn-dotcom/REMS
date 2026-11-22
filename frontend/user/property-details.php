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

$userId = (int)($_SESSION['user_id'] ?? 0);
$customerId = 0;
if ($userId > 0) {
  $cStmt = $conn->prepare("SELECT customer_id FROM customers WHERE user_id = ? LIMIT 1");
  $cStmt->bind_param('i', $userId);
  $cStmt->execute();
  $cRow = $cStmt->get_result()->fetch_assoc();
  $customerId = (int)($cRow['customer_id'] ?? 0);

  if ($customerId === 0) {
    $insC = $conn->prepare("INSERT INTO customers (user_id, customer_type) VALUES (?, 'buyer')");
    $insC->bind_param('i', $userId);
    $insC->execute();
    $customerId = (int)$conn->insert_id;
  }
}

// Handle Mark Visited directly from property details page
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'mark_visited') {
  $visitId = (int)($_POST['visit_id'] ?? 0);
  if ($visitId > 0 && $customerId > 0) {
    $upd = $conn->prepare("UPDATE visits SET status = 'completed' WHERE visit_id = ? AND customer_id = ? AND status = 'scheduled'");
    $upd->bind_param('ii', $visitId, $customerId);
    $upd->execute();
    header("Location: property-details.php?id=$id&booking=visited#booking");
    exit;
  }
}

// Handle Proceed to Buy / Proceed to Rent directly
if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($_POST['action'] ?? '', ['proceed_to_buy', 'proceed_to_rent'], true)) {
  $intent = $_POST['action'] === 'proceed_to_buy' ? 'buy' : 'rent';
  $agreementType = $intent === 'buy' ? 'sale' : 'rent';
  $bookingId = (int)($_POST['booking_id'] ?? 0);

  if ($id > 0 && $customerId > 0) {
    $propQ = $conn->prepare("SELECT price, monthly_rent FROM properties WHERE property_id = ? LIMIT 1");
    $propQ->bind_param('i', $id);
    $propQ->execute();
    $pData = $propQ->get_result()->fetch_assoc();
    $propAmount = $intent === 'buy' ? (float)($pData['price'] ?? 0) : (float)($pData['monthly_rent'] ?? 0);

    if ($propAmount > 0) {
      $conn->begin_transaction();
      $agQ = $conn->prepare("SELECT agreement_id FROM agreements WHERE property_id = ? AND customer_id = ? AND status = 'active' LIMIT 1");
      $agQ->bind_param('ii', $id, $customerId);
      $agQ->execute();
      $agRow = $agQ->get_result()->fetch_assoc();

      if ($agRow) {
        $agreementId = (int)$agRow['agreement_id'];
      } else {
        $startDate = date('Y-m-d');
        $insAg = $conn->prepare("INSERT INTO agreements (booking_id, property_id, customer_id, agreement_type, start_date, amount, status) VALUES (?, ?, ?, ?, ?, ?, 'active')");
        $insAg->bind_param('iiissd', $bookingId, $id, $customerId, $agreementType, $startDate, $propAmount);
        $insAg->execute();
        $agreementId = (int)$conn->insert_id;
      }

      $payQ = $conn->prepare("SELECT payment_id FROM payments WHERE agreement_id = ? AND status = 'pending' LIMIT 1");
      $payQ->bind_param('i', $agreementId);
      $payQ->execute();
      $payRow = $payQ->get_result()->fetch_assoc();

      if (!$payRow) {
        $dueDate = date('Y-m-d', strtotime('+7 days'));
        $insPay = $conn->prepare("INSERT INTO payments (agreement_id, amount, due_date, payment_type, status) VALUES (?, ?, ?, ?, 'pending')");
        $insPay->bind_param('idss', $agreementId, $propAmount, $dueDate, $agreementType);
        $insPay->execute();
      }

      $conn->commit();
      header('Location: payments.php?result=invoice_ready');
      exit;
    }
  }
}

// Find customer's latest visit for this property
$userVisit = null;
if ($customerId > 0 && $id > 0) {
  $vStmt = $conn->prepare("SELECT v.visit_id, v.booking_id, v.visit_time, v.status
                           FROM visits v
                           WHERE v.property_id = ? AND v.customer_id = ?
                           ORDER BY v.visit_id DESC LIMIT 1");
  $vStmt->bind_param('ii', $id, $customerId);
  $vStmt->execute();
  $userVisit = $vStmt->get_result()->fetch_assoc();
}

if ($id > 0) {
  $stmt = $conn->prepare("SELECT property_id, title, property_type, address, area_sqft,
                                 price, monthly_rent, flat_details, status,imagas, description
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

$imageUrl = static function (?string $path): ?string {
  $path = trim(str_replace('\\', '/', (string)$path));
  if ($path === '') {
    return null;
  }
  if (!str_starts_with($path, 'uploads/') && !str_starts_with($path, '../uploads/')) {
    return null;
  }

  $ownerDirectory = realpath(__DIR__ . '/../../backend/owner');
  $filePath = realpath(__DIR__ . '/../../backend/owner/property_crud/' . $path);
  if (
    $ownerDirectory === false
    || $filePath === false
    || !is_file($filePath)
    || !str_starts_with(strtolower($filePath), strtolower($ownerDirectory . DIRECTORY_SEPARATOR))
  ) {
    return null;
  }

  return '../../backend/owner/property_crud/' . implode('/', array_map('rawurlencode', explode('/', $path)));
};

$galleryImages = [];
if ($p) {
  $imageStmt = $conn->prepare(
    'SELECT image_path FROM property_images
     WHERE property_id = ?
     ORDER BY is_primary DESC, created_at ASC, image_id ASC'
  );
  $imageStmt->bind_param('i', $p['property_id']);
  $imageStmt->execute();
  foreach ($imageStmt->get_result()->fetch_all(MYSQLI_ASSOC) as $image) {
    $src = $imageUrl($image['image_path'] ?? null);
    if ($src !== null) {
      $galleryImages[$src] = $src;
    }
  }

  $legacyImage = $imageUrl($p['imagas'] ?? null);
  if ($legacyImage !== null) {
    $galleryImages[$legacyImage] = $legacyImage;
  }
  $galleryImages = array_values($galleryImages);

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
  <meta name="theme-color" content="#e8f5e9">
  <meta name="description" content="Explore the details of a thoughtfully selected EstateFlow home.">
  <title><?= $p ? htmlspecialchars($p['title']) . ' | EstateFlow' : 'Property not found | EstateFlow' ?></title>
  <link rel="stylesheet" href="partials/style.css">
  <link rel="stylesheet" href="partials/property-details.css">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet" referrerpolicy="no-referrer">
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

    <section class="shell detail-gallery<?= count($galleryImages) <= 1 ? ' detail-gallery-single' : '' ?>" aria-label="Property photo gallery">
      <div class="gallery-main">
        <?php if ($galleryImages): ?>
          <img src="<?= htmlspecialchars($galleryImages[0], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($p['title'], ENT_QUOTES, 'UTF-8') ?>">
          <span class="gallery-count">1 / <?= count($galleryImages) ?> photos</span>
        <?php else: ?>
          <span class="gallery-empty">No photos uploaded yet.</span>
        <?php endif; ?>
        <span class="listing-tag"><?= htmlspecialchars($tag) ?></span>
      </div>
      <?php if (count($galleryImages) > 1): ?>
        <div class="gallery-side">
          <?php foreach (array_slice($galleryImages, 1) as $index => $src): ?>
            <figure>
              <img src="<?= htmlspecialchars($src, ENT_QUOTES, 'UTF-8') ?>"
                   alt="<?= htmlspecialchars($p['title'], ENT_QUOTES, 'UTF-8') ?> photo <?= $index + 2 ?>"
                   loading="lazy">
            </figure>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
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
          <?php if ($p['flat_details'] !== null): ?>
            <div><span class="highlight-icon" aria-hidden="true">▤</span><strong><?= (int)$p['flat_details'] ?> bedroom<?= (int)$p['flat_details'] === 1 ? '' : 's' ?></strong><small>Comfortable rooms</small></div>
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
          <?php if ($userVisit && $userVisit['status'] === 'completed'): ?>
            <div class="sidebar-fact">
              <span>Your Status</span>
              <strong style="color:#15803d;"><i class="fa-solid fa-circle-check"></i> Visited</strong>
            </div>
          <?php elseif ($userVisit && $userVisit['status'] === 'scheduled'): ?>
            <div class="sidebar-fact">
              <span>Your Status</span>
              <strong style="color:#0284c7;"><i class="fa-regular fa-calendar-check"></i> Visit Scheduled</strong>
            </div>
          <?php endif; ?>

          <?php
            $msgs = [
              'scheduled'         => ['Visit scheduled successfully! You can mark it as visited once you complete your tour.', '#15803d'],
              'visited'           => ['Property marked as visited! You can now proceed to buy, rent, or pay.', '#15803d'],
              'success'           => ['Visit scheduled successfully! Check My Bookings for details.', '#15803d'],
              'already_scheduled' => ['You already have an upcoming visit scheduled for this property.', '#0284c7'],
              'already_visited'   => ['You have already visited this property. See the options below.', '#15803d'],
              'exists'            => ['You already have a pending booking or visit for this property.', '#9a6a00'],
              'baddate'           => ['Please select a valid future date and time for the visit.', '#b3261e'],
              'unavailable'       => ['This property is currently not available.', '#b3261e'],
              'error'             => ['Unable to complete request. Please try again.', '#b3261e'],
            ];
            $bk = $_GET['booking'] ?? '';
          ?>
          <div id="booking"></div>
          <?php if (isset($msgs[$bk])): ?>
            <p style="margin:12px 0; font-weight:600; color:<?= $msgs[$bk][1] ?>;"><?= $msgs[$bk][0] ?></p>
          <?php endif; ?>

          <?php if ($userVisit && $userVisit['status'] === 'completed'): ?>
            <!-- Already Visited Card with Next Step Options -->
            <div style="background:#f0fdf4; border:1px solid #86efac; border-radius:12px; padding:18px; margin:16px 0;">
              <div style="display:flex; align-items:center; gap:8px; color:#15803d; font-size:16px; font-weight:700;">
                <i class="fa-solid fa-circle-check fa-lg"></i> You Visited This Property
              </div>
              <p style="margin:6px 0 14px; font-size:13px; color:#475569;">
                Visit completed on <strong><?= date('M d, Y · h:i A', strtotime($userVisit['visit_time'])) ?></strong>.
              </p>

              <div style="display:grid; gap:10px;">
                <?php if ($p['status'] === 'available'): ?>
                  <?php if ($hasPrice): ?>
                    <form method="post">
                      <input type="hidden" name="action" value="proceed_to_buy">
                      <input type="hidden" name="booking_id" value="<?= (int)$userVisit['booking_id'] ?>">
                      <button class="button" type="submit" style="width:100%; background:#0f766e; color:#fff; font-weight:700; border:none; padding:10px 14px; border-radius:8px; cursor:pointer;">
                        <i class="fa-solid fa-handshake"></i> Proceed to Buy · <?= money($p['price']) ?>
                      </button>
                    </form>
                  <?php endif; ?>
                  <?php if ($hasRent): ?>
                    <form method="post">
                      <input type="hidden" name="action" value="proceed_to_rent">
                      <input type="hidden" name="booking_id" value="<?= (int)$userVisit['booking_id'] ?>">
                      <button class="button" type="submit" style="width:100%; background:#0284c7; color:#fff; font-weight:700; border:none; padding:10px 14px; border-radius:8px; cursor:pointer;">
                        <i class="fa-solid fa-key"></i> Proceed to Rent · <?= money($p['monthly_rent']) ?>/mo
                      </button>
                    </form>
                  <?php endif; ?>
                <?php else: ?>
                  <p style="margin:0; font-size:13px; font-weight:600; color:#15803d;">
                    <i class="fa-solid fa-circle-check"></i> Status: <?= htmlspecialchars(ucfirst($p['status'])) ?>
                  </p>
                <?php endif; ?>

                <a href="payments.php" style="display:block; text-align:center; background:#ffffff; border:1px solid #cbd5e1; color:#1e293b; padding:9px 12px; border-radius:8px; text-decoration:none; font-weight:700; font-size:13px;">
                  <i class="fa-solid fa-credit-card"></i> View Invoices &amp; Pay via SSLCommerz
                </a>
              </div>

              <details style="margin-top:14px; font-size:13px;">
                <summary style="cursor:pointer; color:#0f766e; font-weight:600;">Want to book another visit?</summary>
                <form method="post" action="book_property.php" style="display:grid; gap:8px; margin-top:8px;">
                  <input type="hidden" name="property_id" value="<?= (int)$p['property_id'] ?>">
                  <label for="revisit_date" style="font-size:12px;">New viewing date &amp; time</label>
                  <input id="revisit_date" name="booking_date" type="datetime-local" required
                         min="<?= date('Y-m-d\TH:i') ?>"
                         style="padding:8px 10px; border:1px solid #ccc; border-radius:6px; font-size:13px;">
                  <button class="button" type="submit" style="padding:8px; font-size:13px;">Schedule New Visit</button>
                </form>
              </details>
            </div>

          <?php elseif ($userVisit && $userVisit['status'] === 'scheduled'): ?>
            <!-- Visit Scheduled Box with "Mark as Visited" Action -->
            <div style="background:#eff6ff; border:1px solid #93c5fd; border-radius:12px; padding:18px; margin:16px 0;">
              <div style="display:flex; align-items:center; gap:8px; color:#1d4ed8; font-size:16px; font-weight:700;">
                <i class="fa-regular fa-calendar-check fa-lg"></i> Visit Scheduled
              </div>
              <p style="margin:6px 0 12px; font-size:13px; color:#334155;">
                Scheduled for <strong><?= date('M d, Y · h:i A', strtotime($userVisit['visit_time'])) ?></strong>.
              </p>

              <form method="post" onsubmit="return confirm('Confirm that you have completed this property visit?');">
                <input type="hidden" name="action" value="mark_visited">
                <input type="hidden" name="visit_id" value="<?= (int)$userVisit['visit_id'] ?>">
                <button class="button" type="submit" style="width:100%; background:#16a34a; color:#fff; font-weight:700; border:none; padding:10px 14px; border-radius:8px; cursor:pointer;">
                  <i class="fa-solid fa-check"></i> Mark as Visited
                </button>
              </form>

              <div style="margin-top:10px; text-align:center;">
                <a href="my_bookings.php" style="font-size:12px; color:#64748b; text-decoration:underline;">
                  Reschedule or manage in My Bookings
                </a>
              </div>
            </div>

          <?php elseif ($p['status'] === 'available'): ?>
            <form method="post" action="book_property.php" style="display:grid; gap:10px; margin-top:12px;">
              <input type="hidden" name="property_id" value="<?= (int)$p['property_id'] ?>">
              <label for="booking_date" style="font-size:14px; font-weight:600;">Preferred viewing date &amp; time</label>
              <input id="booking_date" name="booking_date" type="datetime-local" required
                     min="<?= date('Y-m-d\TH:i') ?>"
                     style="padding:10px 12px; border:1px solid #ccc; border-radius:8px;">
              <button class="button viewing-button" type="submit">Schedule a Visit <span aria-hidden="true">→</span></button>
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