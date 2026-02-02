<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Tests\Unit;

use Esegments\ModularArchitecture\GitHub\GitHubClient;
use Esegments\ModularArchitecture\Tests\TestCase;

class GitHubClientTest extends TestCase
{
    public function test_parses_owner_repo_format(): void
    {
        $result = GitHubClient::parseRepoIdentifier('esegments/blog-module');

        $this->assertNotNull($result);
        $this->assertEquals('esegments', $result['owner']);
        $this->assertEquals('blog-module', $result['repo']);
    }

    public function test_parses_github_https_url(): void
    {
        $result = GitHubClient::parseRepoIdentifier('https://github.com/esegments/blog-module');

        $this->assertNotNull($result);
        $this->assertEquals('esegments', $result['owner']);
        $this->assertEquals('blog-module', $result['repo']);
    }

    public function test_parses_github_https_url_with_git_suffix(): void
    {
        $result = GitHubClient::parseRepoIdentifier('https://github.com/esegments/blog-module.git');

        $this->assertNotNull($result);
        $this->assertEquals('esegments', $result['owner']);
        $this->assertEquals('blog-module', $result['repo']);
    }

    public function test_parses_github_ssh_url(): void
    {
        $result = GitHubClient::parseRepoIdentifier('git@github.com:esegments/blog-module.git');

        $this->assertNotNull($result);
        $this->assertEquals('esegments', $result['owner']);
        $this->assertEquals('blog-module', $result['repo']);
    }

    public function test_returns_null_for_invalid_identifier(): void
    {
        $this->assertNull(GitHubClient::parseRepoIdentifier('invalid'));
        $this->assertNull(GitHubClient::parseRepoIdentifier(''));
        $this->assertNull(GitHubClient::parseRepoIdentifier('no-slash'));
    }

    public function test_generates_archive_url(): void
    {
        $client = new GitHubClient();

        $url = $client->getArchiveUrl('esegments', 'blog-module', 'main');

        $this->assertEquals(
            'https://github.com/esegments/blog-module/archive/refs/heads/main.zip',
            $url
        );
    }

    public function test_generates_release_archive_url(): void
    {
        $client = new GitHubClient();

        $url = $client->getReleaseArchiveUrl('esegments', 'blog-module', 'v1.0.0');

        $this->assertEquals(
            'https://github.com/esegments/blog-module/archive/refs/tags/v1.0.0.zip',
            $url
        );
    }
}
