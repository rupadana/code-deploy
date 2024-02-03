<?php

namespace App\Services;

use App\Models\Server;
use Exception;
use Spatie\Ssh\Ssh;
use Symfony\Component\Process\Process;

class DeployScript
{
    protected array $script = [];

    protected bool $laravel = true;

    protected ?string $domain = null;

    protected ?string $siteUser = null;

    protected ?string $repositoryUrl = null;

    protected bool $initiate = false;

    protected ?Server $server = null;

    protected ?string $databasePassword = '';


    private function __construct()
    {
        $this->databasePassword = uniqid();
    }

    public static function make(): self
    {
        return new static;
    }

    public function getSsh(): Ssh
    {
        if (!$this->server) throw new Exception("Server login is empty");

        $this->script([
            'echo "Script Executed"'
        ]);

        $ssh_private_key_path = storage_path('private/' . $this->server->ssh_key_name);

        $ssh = Ssh::create($this->server->user, $this->server->host)
            ->disablePasswordAuthentication()
            ->enableQuietMode()
            ->usePrivateKey($ssh_private_key_path);

        return $ssh;
    }

    public function execute(): Process
    {
        return $this->getSsh()
            ->execute($this->getScript());
    }

    /**
     * Get the value of laravel
     */
    public function isLaravel()
    {
        return $this->laravel;
    }

    /**
     * Set the value of laravel
     *
     * @return  self
     */
    public function laravel(bool $condition = true): static
    {
        $this->laravel = $condition = true;

        return $this;
    }

    public function initiate(): static
    {
        $domain = $this->getDomain();
        $siteUser = $this->getSiteUser();
        $repositoryUrl = $this->getRepositoryUrl();

        if (!$domain) throw new Exception("Invalid domain : $domain");

        if (!$repositoryUrl) throw new Exception("Invalid repository url : $repositoryUrl");

        if (!$siteUser) {
            $siteUser = str($domain)->replace('.', '-')->toString();
        }

        $databasePassword = $this->getDatabasePassword();
        $databaseName = $this->getDatabaseName();

        $this->script([
            "clpctl site:add:php --domainName=$domain --phpVersion=8.2 --vhostTemplate='Laravel 10' --siteUser=$siteUser --siteUserPassword='!secretPassword!'",
            "clpctl db:add --domainName=$domain --databaseName=$databaseName --databaseUserName=$siteUser --databaseUserPassword='$databasePassword'",
            "rm -rf /home/$siteUser/htdocs/$domain",
            "su $siteUser",
            "cd ~/htdocs",
            "git clone $repositoryUrl $domain",
            "cd $domain",
            "cp .env.example .env",
            "composer install",
            "php artisan key:generate",
            "exit",
            "clpctl lets-encrypt:install:certificate --domainName=$domain",
        ]);

        return $this;
    }

    public function getDatabaseName(): string
    {
        return str($this->getDomain())->explode('.')->slice(0, -1)->implode('-');
    }
    /**
     * Get the value of script
     */
    public function getScript(): array
    {
        return $this->script;
    }

    /**
     * Set the value of script
     *
     * @return  self
     */
    public function script(array|string $script): static
    {
        if (gettype($script) === 'array') {
            $this->script = [
                ...$this->script,
                ...$script
            ];
        } else {
            $this->script[] = $script;
        }

        return $this;
    }

    /**
     * Get the value of domain
     */
    public function getDomain(): ?string
    {
        return $this->domain;
    }

    /**
     * Set the value of domain
     *
     * @return  self
     */
    public function domain($domain): static
    {
        $this->domain = $domain;

        return $this;
    }

    /**
     * Get the value of siteUser
     */
    public function getSiteUser(): ?string
    {
        if (!$this->siteUser) {
            return str($this->domain)->replace('.', '-')->toString();
        }

        return $this->siteUser;
    }

    /**
     * Set the value of siteUser
     *
     * @return  self
     */
    public function siteUser($siteUser): static
    {
        $this->siteUser = $siteUser;

        return $this;
    }

    /**
     * Get the value of repositoryUrl
     */
    public function getRepositoryUrl(): ?string
    {
        return $this->repositoryUrl;
    }

    /**
     * Set the value of repositoryUrl
     *
     * @return  self
     */
    public function repositoryUrl($repositoryUrl): static
    {
        $this->repositoryUrl = $repositoryUrl;

        return $this;
    }

    /**
     * Get the value of initiate
     */
    public function isInitiate(): bool
    {
        return $this->initiate;
    }

    public function deleteSite()
    {
        $domain = $this->getDomain();

        return $this->script("clpctl site:delete --domainName=$domain --force");
    }

    /**
     * Get the value of server
     */
    public function getServer(): ?Server
    {
        return $this->server;
    }

    /**
     * Set the value of server
     *
     * @return  self
     */
    public function server($server)
    {
        $this->server = $server;

        return $this;
    }

    public function toSiteDirectory(): static
    {
        return $this->script("cd ~/htdocs/" . $this->getDomain());
    }

    public function actAsSiteUser(): static
    {
        return $this->script("su " . $this->getSiteUser());
    }

    public function checkoutTo(string $commit): static
    {
        return $this->script('git checkout ', $commit);
    }

    public function downloadEnv(?string $destination = null): Process
    {
        return $this->getSsh()
            ->download('/home/' . $this->getSiteUser() . '/htdocs/' . $this->getDomain() . '/.env', $destination ?? storage_path('private' . '/.env.' . $this->getDomain()));
    }

    public function uploadEnv(?string $source = null): Process
    {
        return $this->getSsh()
            ->upload($source ?? storage_path('private' . '/.env.' . $this->getDomain()), '/home/' . $this->getSiteUser() . '/htdocs/' . $this->getDomain() . '/.env');
    }

    /**
     * Get the value of databasePassword
     */
    public function getDatabasePassword(): string
    {
        return $this->databasePassword;
    }

    public function gitPull(): static
    {
        return $this->script('git pull');
    }

    /**
     * Set the value of databasePassword
     *
     * @return  self
     */
    public function databasePassword($databasePassword): static
    {
        $this->databasePassword = $databasePassword;

        return $this;
    }
}
