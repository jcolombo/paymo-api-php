<?php
namespace Jcolombo\PaymoApiPhp\Tests\ResourceTests;

use Jcolombo\PaymoApiPhp\Tests\ResourceTest;
use Jcolombo\PaymoApiPhp\Entity\Resource\File;
use Jcolombo\PaymoApiPhp\Entity\AbstractResource;

/**
 * File Resource Test
 *
 * NOTE: File collections require a parent filter (task_id, project_id, discussion_id, or comment_id).
 * This is an SDK-enforced validation, not an API limitation.
 * See FileCollection::validateFetch() for the validation logic.
 */
class FileResourceTest extends ResourceTest
{
    public function getResourceClass(): string
    {
        return File::class;
    }

    public function getResourceName(): string
    {
        return 'File';
    }

    public function getResourceCategory(): string
    {
        // Mark as read_only since it requires parent filter
        return 'read_only';
    }

    /**
     * File requires a parent filter for list operations.
     * Returns the filter key and method to get the value.
     */
    public function getRequiredParentFilter(): ?array
    {
        return ['project_id', 'ensureProject'];
    }

    protected function createTestResource(): ?AbstractResource
    {
        // File creation requires file upload which is complex
        // Skip CRUD tests, only run property discovery and listing
        return null;
    }
}
