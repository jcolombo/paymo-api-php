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
