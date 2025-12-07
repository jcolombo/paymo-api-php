<?php
namespace Jcolombo\PaymoApiPhp\Tests\ResourceTests;

use Jcolombo\PaymoApiPhp\Tests\ResourceTest;
use Jcolombo\PaymoApiPhp\Entity\Resource\Comment;
use Jcolombo\PaymoApiPhp\Entity\Resource\Discussion;
use Jcolombo\PaymoApiPhp\Entity\Resource\Project;
use Jcolombo\PaymoApiPhp\Entity\Resource\Client;
use Jcolombo\PaymoApiPhp\Entity\AbstractResource;

class CommentResourceTest extends ResourceTest
{
    private ?int $discussionId = null;

    public function getResourceClass(): string
    {
        return Comment::class;
    }

    public function getResourceName(): string
    {
        return 'Comment';
    }

    public function getResourceCategory(): string
    {
        return 'safe_crud';
    }

    /**
     * Comments require thread_id filter for API listing.
     * We use discussion_id in the ensure method but thread_id for filtering.
     */
    public function getRequiredParentFilter(): ?array
    {
        return ['thread_id', 'ensureThread'];
    }

    /**
     * Auto-discover a thread_id by finding any task that has comments.
     * This is READ-ONLY safe - only fetches existing data.
     */
    protected function ensureThread(): ?int
    {
        // In read-only mode, try to find an existing thread by finding a task with comments
        if ($this->readOnlyMode) {
            $this->logDetail("Read-only mode: searching for existing thread...");

            // Try to find a task and check for comments via thread include
            try {
                $tasks = \Jcolombo\PaymoApiPhp\Entity\Resource\Task::list()->limit(5)->fetch(['thread']);
                foreach ($tasks as $task) {
                    $thread = $task->included['thread'] ?? null;
                    if ($thread && isset($thread->id)) {
                        $this->logDetail("  Found thread #{$thread->id} from Task #{$task->id}");
                        return $thread->id;
                    }
                }

                // Try discussions
                $discussions = Discussion::list()->limit(5)->fetch(['thread']);
                foreach ($discussions as $discussion) {
                    $thread = $discussion->included['thread'] ?? null;
                    if ($thread && isset($thread->id)) {
                        $this->logDetail("  Found thread #{$thread->id} from Discussion #{$discussion->id}");
                        return $thread->id;
                    }
                }

                $this->logDetail("  No existing threads found");
                return null;

            } catch (\Throwable $e) {
                $this->logDetail("  Thread discovery failed: " . $e->getMessage());
                return null;
            }
        }

        // Non-read-only mode: create a discussion to get a thread
        return $this->createDiscussionThread();
    }

    /**
     * Create a discussion (which creates a thread) - only for non-read-only mode
     */
    protected function createDiscussionThread(): ?int
    {
        $discussionId = $this->ensureDiscussion();
        if (!$discussionId) {
            return null;
        }

        // Fetch the discussion with its thread
        try {
            $discussion = Discussion::new()->fetch($discussionId, ['thread']);
            $thread = $discussion->included['thread'] ?? null;
            if ($thread && isset($thread->id)) {
                return $thread->id;
            }
        } catch (\Throwable $e) {
            $this->logDetail("  Could not get thread from discussion: " . $e->getMessage());
        }

        return null;
    }

    protected function createTestResource(): ?AbstractResource
    {
        if (!$this->discussionId) {
            $this->discussionId = $this->ensureDiscussion();
        }
        if (!$this->discussionId) {
            return null;
        }

        $comment = new Comment();
        $comment->content = $this->factory->uniqueName('Comment body');
        $comment->discussion_id = $this->discussionId;
        $comment->create();

        return $comment;
    }

    protected function ensureDiscussion(): ?int
    {
        $clientId = $this->config->getAnchor('client_id');
        if (!$clientId) {
            $client = new Client();
            $client->name = $this->factory->uniqueName('Client');
            $client->create();
            $this->cleanupManager->track('Client', $client->id, Client::class);
            $clientId = $client->id;
        }

        $project = new Project();
        $project->name = $this->factory->uniqueName('Project');
        $project->client_id = $clientId;
        $project->create();
        $this->cleanupManager->track('Project', $project->id, Project::class);

        $discussion = new Discussion();
        $discussion->name = $this->factory->uniqueName('Discussion');
        $discussion->project_id = $project->id;
        $discussion->create();
        $this->cleanupManager->track('Discussion', $discussion->id, Discussion::class);

        return $discussion->id;
    }
}
