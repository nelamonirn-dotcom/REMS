<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" content="#102b2b">
  <meta name="description" content="Browse homes for sale and rent across Dhaka with EstateFlow.">
  <title>Properties | EstateFlow</title>
  <link rel="stylesheet" href="partials/style.css">
</head>
<body>
  <?php include __DIR__ . '/partials/header.php'; ?>
  <main>
    <section class="page-hero properties-hero">
      <div class="shell page-hero-content">
        <p class="eyebrow">Find your place in Dhaka</p>
        <h1>Spaces that fit <em>your life.</em></h1>
        <p class="hero-copy">Explore thoughtfully selected homes for sale and rent, with the details you need to make a confident choice.</p>
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
            <div class="input-wrap"><input id="location" name="location" type="search" placeholder="Area or neighborhood"></div>
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
          <button class="button search-submit" type="submit">Find a home</button>
        </div>
      </form>
    </div>

    <?php include __DIR__ . '/partials/card.php'; ?>

    <section class="cta">
      <div class="shell cta-inner">
        <div>
          <p class="section-kicker">Need a little guidance?</p>
          <h2>Let’s find the right place together.</h2>
        </div>
        <a class="button" href="contact.php">Talk to our team <span aria-hidden="true">↗</span></a>
      </div>
    </section>
  </main>
  <?php include __DIR__ . '/partials/footer.php'; ?>
  <script src="partials/script.js" defer></script>
</body>
</html>
