<?php
/**
 * Paymo API PHP SDK - Client Resource Test
 *
 * Comprehensive tests for the Client resource.
 *
 * @package    Jcolombo\PaymoApiPhp\Tests\ResourceTests
 * @author     Joel Colombo <jc-dev@360psg.com>
 * @license    MIT
 */

namespace Jcolombo\PaymoApiPhp\Tests\ResourceTests;

use Jcolombo\PaymoApiPhp\Tests\ResourceTest;
use Jcolombo\PaymoApiPhp\Entity\Resource\Client;
use Jcolombo\PaymoApiPhp\Entity\AbstractResource;
use Throwable;

class ClientResourceTest extends ResourceTest
{
    /**
     * Path to test image for upload testing
     */
    private const TEST_IMAGE_PATH = __DIR__ . '/../Fixtures/test-image.png';

    public function getResourceClass(): string
    {
        return Client::class;
    }

    public function getResourceName(): string
    {
        return 'Client';
    }

    public function getResourceCategory(): string
    {
        return 'safe_crud';
    }

    protected function createTestResource(): ?AbstractResource
    {
        $data = $this->factory->clientData();

        $client = new Client();
        $client->name = $data['name'];
        $client->create();

        return $client;
    }

    /**
     * Override property discovery to also test with image upload
     *
     * Some Client properties (image_thumb_large, image_thumb_medium, image_thumb_small)
     * only appear in the API response when the client has an image.
     */
    protected function runPropertyDiscovery(): void
    {
        // First, run the standard property discovery
        parent::runPropertyDiscovery();

        // Now test with an image to verify image_thumb_* properties
        $this->runImagePropertyDiscovery();
    }

    /**
     * Test property discovery with an uploaded image
     */
    protected function runImagePropertyDiscovery(): void
    {
        $testName = $this->getResourceName() . "::imagePropertyDiscovery";

        if ($this->dryRun) {
            $this->output->dryRun("Would run image property discovery for " . $this->getResourceName());
            return;
        }

        // Check if test image exists
        if (!file_exists(self::TEST_IMAGE_PATH)) {
            $this->logDetail("");
            $this->logDetail("=== IMAGE PROPERTY DISCOVERY ===");
            $this->logDetail("SKIP: Test image not found at: " . self::TEST_IMAGE_PATH);
            return;
        }

        $this->logDetail("");
        $this->logDetail("=== IMAGE PROPERTY DISCOVERY ===");
        $this->logDetail("Purpose: Verify image_thumb_* properties appear when client has an image");

        $startTime = microtime(true);

        try {
            // Create a test client
            $client = $this->createTestResource();
            if (!$client) {
                $this->logDetail("SKIP: Could not create test client for image upload");
                return;
            }

            $this->logDetail("Created test client #{$client->id} for image property test");
            $this->cleanupManager->track('Client', $client->id, Client::class);

            // Upload an image
            $this->logApiCall(
                'POST',
                "Uploading image to Client #{$client->id}",
                null,
                $this->getApiPath() . "/{$client->id}",
                ['multipart' => 'image file']
            );

            try {
                $client->image(self::TEST_IMAGE_PATH);
                $this->logDetail("  Result: SUCCESS - Image uploaded");
            } catch (Throwable $e) {
                $this->logDetail("  Result: FAILED - " . $e->getMessage());
                $this->logDetail("  Note: Image upload may not be available or the test image is invalid");
                return;
            }

            // Now fetch the client again to see the image properties
            $this->logApiCall(
                'GET',
                "Fetching Client #{$client->id} after image upload",
                null,
                $this->getApiPath() . "/{$client->id}"
            );

            $refreshedClient = Client::new()->fetch($client->id);

            if (!$refreshedClient) {
                $this->logDetail("  Result: FAILED - Could not fetch client after image upload");
                return;
            }

            // Check for image_thumb_* properties
            $imageProps = ['image', 'image_thumb_large', 'image_thumb_medium', 'image_thumb_small'];
            $apiProps = $this->extractResourceProperties($refreshedClient);

            $this->logDetail("");
            $this->logDetail("IMAGE PROPERTY ANALYSIS (after upload):");
            foreach ($imageProps as $prop) {
                $exists = array_key_exists($prop, $apiProps);
                $value = $exists ? $this->getValuePreview($apiProps[$prop]) : 'NOT PRESENT';
                $status = $exists ? '[FOUND]' : '[MISSING]';
                $this->logDetail("  {$status} {$prop}: {$value}");
            }

            // Summary
            $foundCount = 0;
            foreach ($imageProps as $prop) {
                if (array_key_exists($prop, $apiProps) && $apiProps[$prop] !== null) {
                    $foundCount++;
                }
            }

            $this->logDetail("");
            $this->logDetail("SUMMARY:");
            $this->logDetail("  Image properties found: {$foundCount}/" . count($imageProps));

            if ($foundCount < count($imageProps)) {
                $this->logDetail("  *** CONCERN: Some image properties missing even with uploaded image");
            } else {
                $this->logDetail("  All image properties present after image upload");
            }

            $duration = microtime(true) - $startTime;
            $this->logDetail("");
            $this->logDetail("Image property discovery completed in " . sprintf('%.3f', $duration) . "s");

        } catch (Throwable $e) {
            $this->logDetail("EXCEPTION during image property discovery: " . $e->getMessage());
            $this->logDetail("File: " . $e->getFile() . ":" . $e->getLine());
        }
    }
}
