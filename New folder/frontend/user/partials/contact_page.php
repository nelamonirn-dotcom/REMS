<?php
$contactContextInput = $_GET['property'] ?? $_GET['project'] ?? '';
$contactOptions = [
  'Gulshan Garden Residence' => 'Gulshan Garden Residence',
  'Bashundhara Courtyard Home' => 'Bashundhara Courtyard Home',
  'Dhanmondi Light-Filled Apartment' => 'Dhanmondi Light-Filled Apartment',
  'Uttara Greenview Apartment' => 'Uttara Greenview Apartment',
  'courtyard-residences' => 'Courtyard Residences',
  'northlight-living' => 'Northlight Living',
  'garden-lane-homes' => 'Garden Lane Homes',
];
$contactContext = is_string($contactContextInput) ? ($contactOptions[$contactContextInput] ?? '') : '';
$contactContext = htmlspecialchars($contactContext, ENT_QUOTES, 'UTF-8');
?>
<section class="page-section contact-section" id="contact-form">
  <div class="shell contact-layout">
    <div class="contact-details">
      <p class="section-kicker">Get in touch</p>
      <h2 class="section-title">A helpful conversation can make all the difference.</h2>
      <p class="section-description">Share what you have in mind and our team will be ready to guide you through your options.</p>
      <div class="contact-detail">
        <span class="contact-icon" aria-hidden="true">01</span>
        <div><strong>Call our team</strong><a href="tel:+8801300700982">01300 700 982</a></div>
      </div>
      <div class="contact-detail">
        <span class="contact-icon" aria-hidden="true">02</span>
        <div><strong>Email us</strong><a href="mailto:hello@estateflow.example">hello@estateflow.example</a></div>
      </div>
      <div class="contact-detail">
        <span class="contact-icon" aria-hidden="true">03</span>
        <div><strong>Visit</strong><span>Aftabnagar, Badda, Dhaka</span></div>
      </div>
    </div>

    <form class="contact-form" id="contact-form-element">
      <h2>Send us a message</h2>
      <p>Fields marked with * are required.</p>
      <?php if ($contactContext !== ''): ?>
        <div class="contact-context">Enquiry about: <strong><?= $contactContext ?></strong></div>
        <input type="hidden" name="interest" value="<?= $contactContext ?>">
      <?php endif; ?>
      <div class="contact-fields">
        <label class="contact-field">Your name *
          <input type="text" name="name" autocomplete="name" required maxlength="100" placeholder="Name">
        </label>
        <label class="contact-field">Email address *
          <input type="email" name="email" autocomplete="email" required maxlength="160" placeholder="you@example.com">
        </label>
        <label class="contact-field">Phone number
          <input type="tel" name="phone" autocomplete="tel" maxlength="30" placeholder="Your phone number">
        </label>
        <label class="contact-field">I’m interested in
          <select name="reason">
            <option value="">Choose a topic</option>
            <option value="buy">Buying a property</option>
            <option value="rent">Renting a property</option>
            <option value="sell">Selling or listing a property</option>
            <option value="project">A residential project</option>
            <option value="management">Property management</option>
          </select>
        </label>
        <label class="contact-field contact-field-wide">How can we help? *
          <textarea name="message" rows="5" required maxlength="2000" placeholder="Tell us a little about what you need"></textarea>
        </label>
      </div>
      <button class="button contact-submit" type="submit">Review message <span aria-hidden="true">→</span></button>
      <p class="contact-demo-note">Demo form only: your message is not sent or stored.</p>
      <p class="contact-form-status" id="contact-form-status" role="status" aria-live="polite"></p>
    </form>
  </div>
</section>
