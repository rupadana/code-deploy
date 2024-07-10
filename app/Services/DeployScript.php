<?php

namespace App\Services;

use App\Models\Server;
use App\Models\Site;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Spatie\Ssh\Ssh;
use Symfony\Component\Process\Process;

class DeployScript
{
    const PHP_VERSIONS = [
        '7.4',
        '8.1',
        '8.2',
        '8.3',
    ];

    const NODE_VERSIONS = [
        'latest',
        '18',
        '17',
    ];

    protected array $script = [];

    protected bool $laravel = true;

    protected ?string $domain = null;

    protected ?string $siteUser = null;

    protected ?string $repositoryUrl = null;

    protected bool $initiate = false;

    protected ?Server $server = null;

    protected ?string $databasePassword = '';

    protected ?Site $site = null;

    private function __construct()
    {
        $this->databasePassword = uniqid();
    }

    public static function make(?Server $server = null): self
    {
        $instance = new static();

        return $instance->server($server);
    }

    public function templates()
    {
        return $this->script('clpctl vhost-templates:list');
    }

    public function getTemplates(): Collection
    {
        return Cache::remember('deployment-templates-'.$this->getServer()->id, 86400 * 30, function () {
            $process = $this->templates()
                ->execute();

            $lines = explode("\n", $process->getOutput());

            // Remove empty lines
            $lines = array_filter(array_map('trim', $lines));

            // Initialize the array to store the data
            $data_array = [];

            // Process each line and split by pipe character to create the array
            foreach ($lines as $line) {
                $data_array[] = array_map('trim', explode('|', $line));
            }

            // Remove header and footer
            $data_array = array_slice($data_array, 3, -1);

            return collect($data_array)->map(function (array $data) {
                return [
                    'name' => $data[1],
                    'root_directory' => $data[2],
                    'type' => $data[3],
                ];
            });
        });
    }

    public function getSsh(): Ssh
    {
        if (! $this->getServer()) {
            throw new Exception('Server login is empty');
        }

        $ssh_private_key_path = storage_path('private/'.$this->getServer()->ssh_key_name);

        return Ssh::create($this->getServer()->user, $this->getServer()->host)
            ->disablePasswordAuthentication()
            ->disableStrictHostKeyChecking()
            ->enableQuietMode()
            ->usePrivateKey($ssh_private_key_path);
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
     * @return self
     */
    public function laravel(bool $condition = true): static
    {
        $this->laravel = $condition = true;

        return $this;
    }

    public function initiate(string $template = 'Laravel 10', string $projectType = 'php', $version = '8.2'): static
    {
        $domain = $this->getDomain();
        $siteUser = $this->getSiteUser();
        $repositoryUrl = $this->getRepositoryUrl();

        if (! $domain) {
            throw new Exception("Invalid domain : $domain");
        }

        if (! $repositoryUrl) {
            throw new Exception("Invalid repository url : $repositoryUrl");
        }

        $databasePassword = $this->getDatabasePassword();
        $databaseName = $this->getDatabaseName();

        if ($projectType === 'php') {
            $this->script("clpctl site:add:php --domainName=$domain --phpVersion=$version --vhostTemplate='$template' --siteUser=$siteUser --siteUserPassword='$databasePassword'");
        }

        $this->script([
            "clpctl db:add --domainName=$domain --databaseName=$databaseName --databaseUserName=$siteUser --databaseUserPassword='$databasePassword'",
            "rm -rf /home/$siteUser/htdocs/$domain",
            "su $siteUser",
            'cd ~/htdocs',
            "git clone $repositoryUrl $domain",
            "cd $domain",
            'cp .env.example .env',
        ]);

        if ($projectType === 'php') {
            $this->script([
                'composer install',
                'php artisan key:generate',
            ]);
        }

        $this->script([
            'exit',
            "clpctl lets-encrypt:install:certificate --domainName=$domain",
        ]);

        return $this;
    }

    public function getSiteDirectory()
    {
        $siteUser = $this->getSiteUser();
        $domain = $this->getDomain();

        return "/home/$siteUser/htdocs/$domain";
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
     * @return self
     */
    public function script(array|string $script): static
    {
        if (gettype($script) === 'array') {
            $this->script = [
                ...$this->script,
                ...$script,
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
        if ($this->site) {
            return $this->site->domain;
        }

        return $this->domain;
    }

    /**
     * Set the value of domain
     *
     * @return self
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
        if (! $this->siteUser) {
            return str($this->getDomain())->replace('.', '-')->toString();
        }

        return $this->siteUser;
    }

    /**
     * Set the value of siteUser
     *
     * @return self
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
     * @return self
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
        if ($this->site && $this->server === null) {
            return $this->site->server;
        }

        return $this->server;
    }

    /**
     * Set the value of server
     *
     * @return self
     */
    public function server($server)
    {
        $this->server = $server;

        return $this;
    }

    public function toSiteDirectory(): static
    {
        if ($this->site && $this->site->directory) {
            $directory = $this->site->directory;
        } else {
            $directory = '~/htdocs/'.$this->getDomain();
        }

        return $this->script('cd '.$directory);
    }

    public function actAsSiteUser(?string $siteUser = null): static
    {
        return $this->script('su '.$this->getSiteUser())
            ->script('cd');
    }

    public function checkoutTo(string $commit): static
    {
        return $this->gitFetch()->script('git checkout '.$commit);
    }

    public function gitFetch(): static
    {
        return $this->script('git fetch');
    }

    public function downloadEnv(?string $destination = null): Process
    {

        return $this->getSsh()
            ->download($this->getSiteDirectory().'/.env', $destination ?? storage_path('private'.'/.env.'.$this->getDomain()));
    }

    public function uploadEnv(?string $source = null): Process
    {
        return $this->getSsh()
            ->upload($source ?? storage_path('private'.'/.env.'.$this->getDomain()), $this->getSiteDirectory().'/.env');
    }

    /**
     * Get the value of databasePassword
     */
    public function getDatabasePassword(): string
    {
        return $this->databasePassword;
    }

    public function gitStash(): static
    {
        return $this->script('git stash');
    }

    public function gitStashClear(): static
    {
        return $this->script('git stash clear');
    }

    public function gitPull(): static
    {
        return $this->script('git pull');
    }

    /**
     * Set the value of databasePassword
     *
     * @return self
     */
    public function databasePassword($databasePassword): static
    {
        $this->databasePassword = $databasePassword;

        return $this;
    }

    public function getSite(): ?Site
    {
        return $this->site;
    }

    public function site(?Site $site): DeployScript
    {
        $this->site = $site;

        return $this
            ->siteUser($site->site_user);
    }
}
