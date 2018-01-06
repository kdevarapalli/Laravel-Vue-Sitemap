<?php print '<?xml version="1.0" encoding="UTF-8"?>'; ?>
<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1" xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/siteindex.xsd">
    @for ($counter = 1; $counter <= $sCounter-1; $counter++)
    <sitemap>
        <loc><?php print "<domain-name>"; ?>/sitemap-{{ $counter }}.xml</loc>
        <lastmod><?php print date('c',time()); ?></lastmod>
    </sitemap>
    @endfor
</sitemapindex>
