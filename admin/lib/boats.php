<?php
/**
 * Parse the public index.html into a structured list of boats / sections and
 * their Dropbox links. index.html is the single source of truth, so the admin
 * dropdown never drifts from the live site.
 *
 * Covers:
 *   - boats, docks and landmarks  (<div class="subsection" id="…">)
 *   - leaf content blocks          (<section class="content-section" id="block-…">
 *                                   such as ECO, Catering, Loga, AI, …)
 * Container blocks (block-flotila / -pristaviste / -pamatky / -video) are
 * skipped because their inner subsections are parsed individually.
 */

declare(strict_types=1);

/**
 * @return array<int,array{
 *   id:string, boat:string, anchor:string,
 *   folders:array<int,array{title:string,anchor:string,links:array<int,array{label:string,url:string}>}>
 * }>
 */
function pb_parse_boats(string $htmlPath = PB_INDEX_HTML): array
{
    if (!is_file($htmlPath)) {
        return [];
    }
    $html = file_get_contents($htmlPath);
    if ($html === false || $html === '') {
        return [];
    }

    $dom = new DOMDocument();
    libxml_use_internal_errors(true);
    $dom->loadHTML('<?xml encoding="utf-8"?>' . $html, LIBXML_NOERROR | LIBXML_NOWARNING);
    libxml_clear_errors();
    $xp = new DOMXPath($dom);

    $entries = [];

    // 1) Individual subsections: boats, docks, landmarks.
    $subs = $xp->query('//div[contains(concat(" ", normalize-space(@class), " "), " subsection ") and @id]');
    foreach ($subs as $section) {
        /** @var DOMElement $section */
        $id = $section->getAttribute('id');
        if ($id === 'flotila-ostatni' || strpos($id, 'block-video') === 0) {
            continue;
        }
        $titleNode = $xp->query('.//h3[contains(@class,"subsection-title")]', $section)->item(0);
        $name = $titleNode ? trim($titleNode->textContent) : $id;
        $folders = pb_parse_cards($xp, $section);
        if ($folders) {
            $entries[] = ['id' => $id, 'boat' => $name, 'anchor' => $id, 'folders' => $folders];
        }
    }

    // 2) Leaf content blocks (ECO, Catering, Loga, AI, Stock footage, …).
    $blocks = $xp->query('//section[contains(concat(" ", normalize-space(@class), " "), " content-section ") and starts-with(@id, "block-")]');
    foreach ($blocks as $section) {
        /** @var DOMElement $section */
        // Skip container blocks that hold nested subsections.
        if ($xp->query('.//div[contains(concat(" ", normalize-space(@class), " "), " subsection ")]', $section)->length > 0) {
            continue;
        }
        $id = $section->getAttribute('id');
        $titleNode = $xp->query('.//span[contains(@class,"section-title-text")]', $section)->item(0);
        $name = $titleNode ? trim($titleNode->textContent) : $id;
        $folders = pb_parse_cards($xp, $section);
        if ($folders) {
            $entries[] = ['id' => $id, 'boat' => $name, 'anchor' => $id, 'folders' => $folders];
        }
    }

    // 3) News items — each headline becomes its own selectable folder.
    $newsFolders = [];
    $newsItems = $xp->query('//div[contains(concat(" ", normalize-space(@class), " "), " news-item ")]');
    $seen = [];
    foreach ($newsItems as $item) {
        /** @var DOMElement $item */
        $titleNode = $xp->query('.//h4[contains(@class,"news-title")]', $item)->item(0);
        $linkNode  = $xp->query('.//a[contains(@class,"news-link") and starts-with(@href,"http")]', $item)->item(0);
        if (!$titleNode || !$linkNode) continue;
        $title = trim($titleNode->textContent);
        $url   = trim($linkNode->getAttribute('href'));
        if ($url === '' || isset($seen[$url])) continue; // dedupe (preview vs full list)
        $seen[$url] = true;
        $newsFolders[] = ['title' => $title, 'anchor' => 'news', 'links' => [['label' => 'Otevřít v Dropboxu', 'url' => $url]]];
    }
    if ($newsFolders) {
        $entries[] = ['id' => 'news', 'boat' => 'Novinky', 'anchor' => 'news', 'folders' => $newsFolders];
    }

    // 4) Archive items — each event becomes its own folder (with all its links).
    // Scoped to #block-archive so video-items (same class) don't sneak in.
    $eventFolders = [];
    $events = $xp->query('//section[@id="block-archive"]//div[contains(concat(" ", normalize-space(@class), " "), " event-item ")]');
    foreach ($events as $item) {
        /** @var DOMElement $item */
        $titleNode = $xp->query('.//h4[contains(@class,"event-title")]', $item)->item(0);
        $title = $titleNode ? trim($titleNode->textContent) : '';
        $links = [];
        $linkNodes = $xp->query('.//a[contains(@class,"link-text") and starts-with(@href,"http")]', $item);
        foreach ($linkNodes as $a) {
            /** @var DOMElement $a */
            $url = trim($a->getAttribute('href'));
            if ($url === '') continue;
            $label = trim($a->textContent);
            $links[] = ['label' => $label !== '' ? $label : 'Odkaz', 'url' => $url];
        }
        if ($title !== '' && $links) {
            $eventFolders[] = ['title' => $title, 'anchor' => 'archive', 'links' => $links];
        }
    }
    if ($eventFolders) {
        $entries[] = ['id' => 'block-archive', 'boat' => 'Archiv', 'anchor' => 'block-archive', 'folders' => $eventFolders];
    }

    usort($entries, static fn($a, $b) => strcoll($a['boat'], $b['boat']));
    return $entries;
}

/**
 * Extract folders (cards + their Dropbox links) from a section node.
 *
 * @return array<int,array{title:string,anchor:string,links:array<int,array{label:string,url:string}>}>
 */
function pb_parse_cards(DOMXPath $xp, DOMElement $section): array
{
    $folders = [];
    $cards = $xp->query(
        './/div[contains(concat(" ", normalize-space(@class), " "), " media-card ")'
        . ' or contains(concat(" ", normalize-space(@class), " "), " stock-footage-card ")]',
        $section
    );

    foreach ($cards as $card) {
        /** @var DOMElement $card */
        $cardTitleNode = $xp->query('.//h4[contains(@class,"card-title")]', $card)->item(0);
        $cardTitle = $cardTitleNode ? trim($cardTitleNode->textContent) : '';

        $links = [];
        $linkNodes = $xp->query('.//a[contains(@class,"link-text") and starts-with(@href,"http")]', $card);
        foreach ($linkNodes as $a) {
            /** @var DOMElement $a */
            $url = trim($a->getAttribute('href'));
            if ($url === '') {
                continue;
            }
            $label = trim($a->textContent);
            $links[] = ['label' => $label !== '' ? $label : 'Odkaz', 'url' => $url];
        }
        if (!$links) {
            continue;
        }
        $folders[] = [
            'title'  => $cardTitle !== '' ? $cardTitle : 'Fotky',
            'anchor' => $card->getAttribute('id'),
            'links'  => $links,
        ];
    }

    return $folders;
}
