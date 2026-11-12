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
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" content="#102b2b">
  <meta name="description" content="Find a home that feels like yours with EstateFlow. Explore thoughtfully selected properties for sale and rent.">
  <title>EstateFlow | Find your place</title>
  <link rel="stylesheet" href="partials/style.css">
</head>
<body>
  
    <!--start header-->
  <?php include __DIR__ . '/partials/customar_header.php'; ?>
  <!--end header-->
  <main>
    <section class="hero" aria-labelledby="hero-title">
      <div class="shell">
        <div class="hero-content">
          <p class="eyebrow">A better way to find home</p>
          <h1 id="hero-title">A place for your <em>next chapter.</em></h1>
          <p class="hero-copy">Discover thoughtfully selected homes, honest guidance, and a smoother path to the place that feels like yours.</p>
          <div class="hero-proof">
            <span class="proof-stars" aria-hidden="true">✦</span>
            <span>Thoughtful homes, local guidance, <strong>clear next steps</strong></span>
          </div>
        </div>
      </div>
    </section>

    <div class="shell search-wrap">
      <form class="search-panel" id="property-search" role="search">
        <div class="search-tabs" role="tablist" aria-label="Listing type">
          <button class="search-tab" type="button" role="tab" aria-selected="true" data-mode="buy">Buy</button>
          <button class="search-tab" type="button" role="tab" aria-selected="false" data-mode="rent">Rent</button>
        </div>
        <div class="search-fields">
          <div class="field">
            <label for="location">Location</label>
            <div class="input-wrap">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M19 10c0 5-7 11-7 11S5 15 5 10a7 7 0 1 1 14 0Z" stroke="currentColor" stroke-width="1.6"/><circle cx="12" cy="10" r="2.2" stroke="currentColor" stroke-width="1.6"/></svg>
              <input id="location" name="location" type="search" placeholder="Area or neighborhood">
            </div>
          </div>
          <div class="field">
            <label for="property-type">Property type</label>
            <div class="input-wrap">
              <select id="property-type" name="type">
                <option value="">All property types</option>
                <option value="apartment">Apartment</option>
                <option value="house">House</option>
              </select>
            </div>
          </div>
          <div class="field">
            <label for="budget">Maximum budget</label>
            <div class="input-wrap">
              <select id="budget" name="budget">
                <option value="">Any budget</option>
                <option value="10000000">Up to ৳1 crore</option>
                <option value="20000000">Up to ৳2 crore</option>
                <option value="30000000">Up to ৳3 crore</option>
              </select>
            </div>
          </div>
          <button class="button search-submit" type="submit">
            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="10.8" cy="10.8" r="6.8" stroke="currentColor" stroke-width="1.8"/><path d="m16 16 4.2 4.2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
            Find a home
          </button>
        </div>
      </form>
    </div>

     <!--card-->
  <?php  include __DIR__ . '/partials/card.php'; ?>
  <!--card-->

    <section class="trust-band" aria-label="EstateFlow at a glance">
      <div class="shell trust-inner">
        <div class="trust-copy"><strong>Good decisions start with good information.</strong>Explore homes, arrange a visit, and take your next step with confidence.</div>
        <div class="trust-stat"><strong>Find</strong><span>a home that fits your life</span></div>
        <div class="trust-stat"><strong>Visit</strong><span>with helpful local guidance</span></div>
        <div class="trust-stat"><strong>Settle</strong><span>in with a clearer next step</span></div>
      </div>
    </section>

    <section class="how-section" id="how-it-works" aria-labelledby="how-title">
      <div class="shell">
        <div class="how-intro">
          <p class="section-kicker">Simple from the start</p>
          <h2 class="section-title" id="how-title">Your next home, in three steps.</h2>
          <p class="section-description">A clearer, more considered way to move from looking to feeling at home.</p>
        </div>
        <div class="steps">
          <article class="step">
            <span class="step-number">01</span>
            <h3>Find your fit</h3>
            <p>Explore homes by neighborhood, price, and the details that matter most to you.</p>
          </article>
          <article class="step">
            <span class="step-number">02</span>
            <h3>Take a closer look</h3>
            <p>Save the places you love and arrange a visit with a local property specialist.</p>
          </article>
          <article class="step">
            <span class="step-number">03</span>
            <h3>Make yourself at home</h3>
            <p>Get thoughtful guidance through the next steps, right up to the keys in your hand.</p>
          </article>
        </div>
      </div>
    </section>

    <section class="cta" id="contact">
      <div class="shell cta-inner">
        <div>
          <p class="section-kicker">A good move starts with a conversation</p>
          <h2>Tell us what home looks like for you.</h2>
        </div>
        <a class="button" href="mailto:hello@estateflow.example">Talk to our team <span aria-hidden="true">↗</span></a>
      </div>
    </section>
  </main>


  <!--card-->
  <?php  include __DIR__ . '/partials/footer.php'; ?>
  <!--card-->
 <script src="partials/script.js"></script>
</body>
</html>