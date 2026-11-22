<?php
include_once __DIR__ . '/../../../backend/owner/property_crud/dbconfig.php';

$conn->set_charset('utf8mb4');
$propertyQuery = $conn->prepare(
    "SELECT property_id, title, property_type, address, area_sqft, price,
            monthly_rent, flat_details, imagas
     FROM properties
     WHERE status = ?
     ORDER BY created_at DESC"
);
$availableStatus = 'available';
$propertyQuery->bind_param('s', $availableStatus);
$propertyQuery->execute();
$properties = $propertyQuery->get_result()->fetch_all(MYSQLI_ASSOC);

$listingCards = [];
$buyListingCount = 0;
foreach ($properties as $property) {
    $price = (float)($property['price'] ?? 0);
    $monthlyRent = (float)($property['monthly_rent'] ?? 0);

    if ($price > 0) {
        $listingCards[] = ['property' => $property, 'mode' => 'buy', 'price' => $price];
        $buyListingCount++;
    }
    if ($monthlyRent > 0) {
        $listingCards[] = ['property' => $property, 'mode' => 'rent', 'price' => $monthlyRent];
    }
    if ($price <= 0 && $monthlyRent <= 0) {
        $listingCards[] = ['property' => $property, 'mode' => 'buy', 'price' => 0];
        $buyListingCount++;
    }
}

// Find visited and scheduled properties for logged in customer
$visitedPropertyIds = [];
$scheduledPropertyIds = [];
if (!empty($_SESSION['email'])) {
    $sessionUserId = (int)($_SESSION['user_id'] ?? 0);
    if ($sessionUserId > 0) {
        $cQ = $conn->prepare("SELECT customer_id FROM customers WHERE user_id = ? LIMIT 1");
        $cQ->bind_param('i', $sessionUserId);
        $cQ->execute();
        $cRes = $cQ->get_result()->fetch_assoc();
        $sessionCustId = (int)($cRes['customer_id'] ?? 0);
        if ($sessionCustId > 0) {
            $vQ = $conn->prepare("SELECT property_id, status FROM visits WHERE customer_id = ?");
            $vQ->bind_param('i', $sessionCustId);
            $vQ->execute();
            $vRes = $vQ->get_result();
            while ($vRow = $vRes->fetch_assoc()) {
                if ($vRow['status'] === 'completed') {
                    $visitedPropertyIds[(int)$vRow['property_id']] = true;
                } elseif ($vRow['status'] === 'scheduled') {
                    $scheduledPropertyIds[(int)$vRow['property_id']] = true;
                }
            }
        }
    }
}

$escape = static fn($value): string => htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
?>
<section class="section" id="properties" aria-labelledby="properties-title">
  <div class="shell">
    <div class="section-head">
      <div>
        <p class="section-kicker">Available listings</p>
        <h2 class="section-title" id="properties-title">Homes worth coming home to.</h2>
        <p class="section-description">Explore the latest homes available for sale and rent.</p>
      </div>
      <a class="text-link" href="properties.php">View all properties <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M5 12h14m-6-6 6 6-6 6" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg></a>
    </div>
    <div class="listing-meta" aria-live="polite">
      <span id="results-count">Showing <?= $buyListingCount ?> available properties</span>
      <span>Across available locations</span>
    </div>
    <div class="property-grid" id="property-grid">
      <?php foreach ($listingCards as $listing):
        $property = $listing['property'];
        $title = (string)$property['title'];
        $address = (string)$property['address'];
        $propertyType = (string)$property['property_type'];
        $mode = $listing['mode'];
        $listingPrice = $listing['price'];
        $details = trim((string)$property['flat_details']);
        $image = trim((string)$property['imagas']);
        $imageUrl = $image !== ''
            ? '../../backend/owner/property_crud/' . ltrim($image, '/\\')
            : '';
      ?>
        <article class="property-card"
                 data-mode="<?= $mode ?>"
                 data-type="<?= $escape($propertyType) ?>"
                 data-price="<?= $escape($listingPrice) ?>"
                 data-location="<?= $escape(strtolower($address)) ?>">
          <div class="property-image">
            <?php if ($imageUrl !== ''): ?>
              <img src="<?= $escape($imageUrl) ?>" alt="<?= $escape($title) ?>" loading="lazy">
            <?php endif; ?>
            <span class="badge"><?= $mode === 'rent' ? 'For rent' : 'For sale' ?></span>
            <?php if (!empty($visitedPropertyIds[(int)$property['property_id']])): ?>
              <span class="badge" style="background:#15803d; color:#ffffff; font-weight:700;"><i class="fa-solid fa-circle-check"></i> Visited</span>
            <?php elseif (!empty($scheduledPropertyIds[(int)$property['property_id']])): ?>
              <span class="badge" style="background:#0284c7; color:#ffffff; font-weight:600;"><i class="fa-regular fa-calendar-check"></i> Scheduled</span>
            <?php endif; ?>
            <button class="favorite" type="button" aria-label="Save <?= $escape($title) ?>" aria-pressed="false">
              <svg width="17" height="17" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M20.8 8.8c0 5.2-8.8 10-8.8 10s-8.8-4.8-8.8-10A4.8 4.8 0 0 1 12 6.1a4.8 4.8 0 0 1 8.8 2.7Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/></svg>
            </button>
          </div>
          <div class="property-info">
            <div class="property-place">
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M19 10c0 5-7 11-7 11S5 15 5 10a7 7 0 1 1 14 0Z" stroke="currentColor" stroke-width="1.7"/><circle cx="12" cy="10" r="2" stroke="currentColor" stroke-width="1.7"/></svg>
              <?= $escape($address) ?>
            </div>
            <h3 class="property-name"><?= $escape($title) ?></h3>
            <div class="property-specs">
              <span><?= $escape(ucfirst($propertyType)) ?></span>
              <?php if ($details !== '' && strtolower($details) !== 'nai'): ?>
                <span>
                  <?= $propertyType === 'flat' && is_numeric($details)
                    ? $escape($details) . ' beds'
                    : $escape($details) ?>
                </span>
              <?php endif; ?>
              <?php if ($property['area_sqft'] !== null): ?>
                <span><?= number_format((float)$property['area_sqft']) ?> sqft</span>
              <?php endif; ?>
            </div>
            <div class="property-price-row">
              <div class="property-price">
                <?= $listingPrice > 0 ? '৳' . number_format($listingPrice) : 'Price on request' ?>
                <?php if ($listingPrice > 0): ?>
                  <small>/ <?= $mode === 'rent' ? 'month' : 'sale' ?></small>
                <?php endif; ?>
              </div>
              <a class="details-link" href="property-details.php?id=<?= (int)$property['property_id'] ?>">Details <span aria-hidden="true">↗</span></a>
            </div>
          </div>
        </article>
      <?php endforeach; ?>
      <div class="empty-state" id="empty-state" style="<?= $listingCards ? '' : 'display:block' ?>">
        <strong>No homes found just yet.</strong>
        Try a different area or broaden your search filters.
      </div>
    </div>
  </div>
</section>
