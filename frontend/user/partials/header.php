<?php
$currentPage = basename($_SERVER['PHP_SELF'] ?? 'index.php');
?>
  <div class="topline">
    <div class="shell topline-inner">
      <div class="topline-note">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 21s7-6.1 7-12a7 7 0 1 0-14 0c0 5.9 7 12 7 12Z" stroke="currentColor" stroke-width="1.7"/><circle cx="12" cy="9" r="2.3" stroke="currentColor" stroke-width="1.7"/></svg>
        <span>House-35,road-01,Sector-02,block-H Aftabnagar, Badda,Dhaka</span>
      </div>
      <a class="topline-link" href="contact.php"> <span aria-hidden="true">→</span> Contact us: 01300700982 </a>
    </div>
  </div>

  <header class="header">
    <nav class="shell nav" aria-label="Main navigation">
      <a class="brand" href="index.php" aria-label="EstateFlow home">
        <span class="brand-mark" aria-hidden="true">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none"><path d="m3.5 11 8.5-7 8.5 7v9.5h-6v-6h-5v6h-6V11Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/><path d="M8 9.2V7h2" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
        </span>
        <span class="brand-name">Estate<span>Flow</span></span>
      </a>
      <ul class="nav-links" id="nav-links">
        <li><a href="index.php" <?= $currentPage === 'index.php' ? 'aria-current="page"' : '' ?>>Home</a></li>
        <li><a href="properties.php" <?= $currentPage === 'properties.php' ? 'aria-current="page"' : '' ?>>Properties</a></li>
        <li><a href="projects.php" <?= $currentPage === 'projects.php' ? 'aria-current="page"' : '' ?>>Projects</a></li>
        <li><a href="about.php" <?= $currentPage === 'about.php' ? 'aria-current="page"' : '' ?>>About</a></li>
        <li><a href="contact.php" <?= $currentPage === 'contact.php' ? 'aria-current="page"' : '' ?>>Contact</a></li>
      </ul>
      <div class="nav-actions">
        <a class="sign-in" href="../../backend/admin/index.php">Sign in</a>
        <a class="button" href="properties.php">Explore homes <span aria-hidden="true">↗</span></a>
        <button class="menu-toggle" type="button" aria-label="Open navigation menu" aria-expanded="false" aria-controls="nav-links">
          <svg width="19" height="19" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
        </button>
      </div>
    </nav>
  </header> 