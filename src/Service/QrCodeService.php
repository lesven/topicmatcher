<?php

declare(strict_types=1);

namespace App\Service;

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Color\Color;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Service responsible for generating QR codes (Data URIs) for URLs used in the public site.
 */
class QrCodeService
{
    /**
     * QrCodeService constructor.
     *
     * @param UrlGeneratorInterface $urlGenerator URL generator used to build absolute links
     */
    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator
    ) {
    }

    /**
     * Generate a QR code for the given route and return it as a data URI.
     *
     * @param string $route Route name to generate URL for
     * @param array $parameters Route parameters
     * @return string Data URI containing the QR code image
     */
    public function generateQrCodeDataUri(string $route, array $parameters = []): string
    {
        // Generate absolute URL
        $url = $this->urlGenerator->generate($route, $parameters, UrlGeneratorInterface::ABSOLUTE_URL);
        
        try {
            // Create QR Code with correct parameter structure for v6.x
            $qrCode = new QrCode(
                data: $url,
                encoding: new Encoding('UTF-8'),
                errorCorrectionLevel: ErrorCorrectionLevel::Low,
                size: 200,
                margin: 10,
                foregroundColor: new Color(0, 0, 0),
                backgroundColor: new Color(255, 255, 255)
            );
            
            $writer = new PngWriter();
            $result = $writer->write($qrCode);
            
            // Return as data URI for embedding in HTML
            return $result->getDataUri();
        } catch (\Exception $e) {
            // Fallback: return a placeholder data URI if QR generation fails
            return 'data:image/svg+xml;base64,' . base64_encode(
                '<svg xmlns="http://www.w3.org/2000/svg" width="200" height="200"><rect width="200" height="200" fill="#f8f9fa" stroke="#dee2e6"/><text x="100" y="100" text-anchor="middle" dy="0.3em" font-family="Arial" font-size="14" fill="#6c757d">QR-Code nicht verfügbar</text></svg>'
            );
        }
    }

    /**
     * Generate a data URI QR code that links to the interest form for a post.
     *
     * @param string $eventSlug Event slug
     * @param int $postId Post id
     * @return string QR code data URI
     */
    public function generateInterestQrCode(string $eventSlug, int $postId): string
    {
        return $this->generateQrCodeDataUri('post_interest', [
            'slug' => $eventSlug,
            'id' => $postId
        ]);
    }
}