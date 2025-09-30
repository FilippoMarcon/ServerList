<?php
require_once 'config.php';

// Build absolute base URL
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
           (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
$scheme = $isHttps ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$base = $scheme . '://' . $host;

header('Content-Type: application/xml; charset=UTF-8');
echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
  <url>
    <loc><?= htmlspecialchars($base . '/') ?></loc>
    <priority>1.0</priority>
  </url>
  <url>
    <loc><?= htmlspecialchars($base . '/annunci') ?></loc>
    <priority>0.8</priority>
  </url>
  <url>
    <loc><?= htmlspecialchars($base . '/forum') ?></loc>
    <priority>0.7</priority>
  </url>
  <?php
  // Forum categories
  try {
      $cats = $pdo->query("SELECT id, slug FROM sl_forum_categories ORDER BY name ASC")->fetchAll();
      foreach ($cats as $c) {
          $loc = $base . '/forum/category/' . (int)$c['id'] . '-' . urlencode($c['slug'] ?? 'categoria');
          echo "  <url>\n    <loc>" . htmlspecialchars($loc) . "</loc>\n    <priority>0.6</priority>\n  </url>\n";
      }
  } catch (Exception $e) {}

  // Recent threads (limit to 200 to keep sitemap light)
  try {
      $threads = $pdo->query("SELECT id, slug, COALESCE(updated_at, created_at) AS lastmod FROM sl_forum_threads ORDER BY COALESCE(updated_at, created_at) DESC LIMIT 200")->fetchAll();
      foreach ($threads as $t) {
          $loc = $base . '/forum/' . (int)$t['id'] . '-' . urlencode($t['slug'] ?? 'thread');
          $lastmod = date('c', strtotime($t['lastmod'] ?? 'now'));
          echo "  <url>\n    <loc>" . htmlspecialchars($loc) . "</loc>\n    <lastmod>" . htmlspecialchars($lastmod) . "</lastmod>\n    <priority>0.5</priority>\n  </url>\n";
      }
  } catch (Exception $e) {}
  ?>
</urlset>