<?php

declare(strict_types=1);

namespace App\Service;

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Color\Color;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class QrCodeService
{
    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator
    ) {
    }

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

    public function generateInterestQrCode(string $eventSlug, int $postId): string
    {
        return $this->generateQrCodeDataUri('post_interest', [
            'slug' => $eventSlug,
            'id' => $postId
        ]);
    }
}