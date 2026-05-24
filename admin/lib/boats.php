<?php
/**
 * Parse the public index.html into a structured list of boats / folders and
 * their Dropbox links. index.html is the single source of truth, so the admin
 * dropdown never drifts from the live site.
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

    $boats = [];
    // Each boat is a <div id="flotila-*" class="subsection">.
    $sections = $xp->query('//div[contains(concat(" ", normalize-space(@class), " "), " subsection ") and starts-with(@id, "flotila-")]');
    if (!$sections) {
        return [];
    }

    foreach ($sections as $section) {
        /** @var DOMElement $section */
        $id = $section->getAttribute('id');
        if ($id === 'flotila-ostatni') {
            continue; // "Other" bucket — not a single boat
        }

        $titleNode = $xp->query('.//h3[contains(@class,"subsection-title")]', $section)->item(0);
        $boatName  = $titleNode ? trim($titleNode->textContent) : $id;

        $folders = [];
        $cards = $xp->query('.//div[contains(concat(" ", normalize-space(@class), " "), " media-card ")]', $section);
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
                continue; // folder with no usable link — skip
            }

            $folders[] = [
                'title'  => $cardTitle !== '' ? $cardTitle : 'Fotky',
                'anchor' => $card->getAttribute('id'),
                'links'  => $links,
            ];
        }

        if (!$folders) {
            continue;
        }

        $boats[] = [
            'id'      => $id,
            'boat'    => $boatName,
            'anchor'  => $id,
            'folders' => $folders,
        ];
    }

    usort($boats, static fn($a, $b) => strcoll($a['boat'], $b['boat']));
    return $boats;
}
