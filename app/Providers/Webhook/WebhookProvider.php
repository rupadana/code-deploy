<?php

namespace App\Providers\Webhook;

use App\Jobs\Concerns\SetSiteSha;
use App\Jobs\DeploymentJob;
use App\Models\Site;
use App\Services\DeployScript;
use Illuminate\Http\Request;

abstract class WebhookProvider
{
    protected string $name;

    public function __construct(protected ?Site $site, protected Request $request) {}

    public static function make(?Site $site, Request $request)
    {
        $provider = new static($site, $request);

        return $provider;
    }

    public function isQuickDeployEnabled()
    {
        return $this->site->quick_deploy;
    }

    public function deploy(string $commit)
    {
        if ($this->isQuickDeployEnabled()) {
            $server = $this->site->server;

            $process = DeployScript::make()
                ->server($server)
                ->site($this->site)
                ->siteUser($this->site->site_user)
                ->actAsSiteUser();

            if ($this->site->{'project-type'} == 'nodejs') {
                $process->script('source .nvm/nvm.sh');
            }
            $process
                ->toSiteDirectory()
                ->gitStash()
                ->gitStashClear()
                ->gitFetch()
                ->checkoutTo($commit)
                ->script(explode('\n', substr(substr(json_encode($this->site->script), 1), 0, -1)));

            // TODO : is it right to use job here? because we can't notify to github when its failed

            DeploymentJob::dispatch($process, auth()->user(), finish: SetSiteSha::make(['sha' => $commit]));
        }
    }

    abstract public function handle(): void;

    /**
     * Get the value of name
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set the value of name
     *
     * @return self
     */
    public function name($name)
    {
        $this->name = $name;

        return $this;
    }
}
