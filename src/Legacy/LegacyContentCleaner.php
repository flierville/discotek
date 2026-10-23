<?php

declare(strict_types=1);

namespace App\Legacy;

/**
 * Cleans texts coming from the legacy database.
 */
final class LegacyContentCleaner
{
    /** Placeholders replaced by forms in the legacy formatage_html(): the forms are now added by the page kind. */
    private const FORM_PLACEHOLDER = '/(?:<br\s*\/?>\s*)*\{FORM_[A-Z_]+\}/';

    public function cleanPageContent(string $html): string
    {
        $html = $this->fixDoubleEncoding($html);
        $html = (string) preg_replace(self::FORM_PLACEHOLDER, '', $html);
        // Mixed content would be blocked on the HTTPS site.
        $html = (string) preg_replace('#http://(www\.)?youtube\.com/#i', 'https://www.youtube.com/', $html);

        return trim($html);
    }

    /**
     * Some rows were saved twice encoded ("FredÃ©rique" instead of "Frédérique").
     */
    public function fixDoubleEncoding(string $text): string
    {
        if (!preg_match('/Ã[\x{0080}-\x{00BF}]|Â[\x{00A0}-\x{00BF}]/u', $text)) {
            return $text;
        }

        $decoded = mb_convert_encoding($text, 'ISO-8859-1', 'UTF-8');

        return mb_check_encoding($decoded, 'UTF-8') ? $decoded : $text;
    }

    public function cleanTitle(string $title): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', $this->fixDoubleEncoding(html_entity_decode($title, ENT_QUOTES | ENT_HTML5, 'UTF-8'))));
    }

    /**
     * Code of a party = directory of its photos in the legacy "contenu_medias" column
     * ("/medias/images/photos_soirees/lila20/20120716_165526.jpg:0:0:::;" => "lila20").
     */
    public function partyCodeFromMedia(string $media): ?string
    {
        return preg_match('#/photos_soirees/([^/]+)/#', $media, $matches) ? $matches[1] : null;
    }
}
