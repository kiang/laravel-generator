<?php

namespace InfyOm\Generator\Generators\Scaffold;

use Illuminate\Support\Str;
use InfyOm\Generator\Common\CommandData;

class RoutesGenerator
{
    /** @var CommandData */
    private $commandData;

    /** @var string */
    private $path;

    /** @var string */
    private $routeContents;

    /** @var string */
    private $routesTemplate;
    
    private $breadcrumbsBackendContent;
    private $breadcrumbsTemplate;

    public function __construct(CommandData $commandData)
    {
        $this->commandData = $commandData;
        $this->path = $commandData->config->pathRoutes;
        $this->routeContents = file_get_contents($this->path);
        if (!empty($this->commandData->config->prefixes['route'])) {
            $this->routesTemplate = get_template('scaffold.routes.prefix_routes', 'laravel-generator');
        } else {
            $this->routesTemplate = get_template('scaffold.routes.routes', 'laravel-generator');
        }
        $this->routesTemplate = fill_template($this->commandData->dynamicVars, $this->routesTemplate);
        
        $this->breadcrumbsBackendContent = file_get_contents(base_path('routes/breadcrumbs/backend/backend.php'));
        
        $this->breadcrumbsTemplate = get_template('scaffold.routes.breadcrumbs', 'laravel-generator');
        $this->breadcrumbsTemplate = fill_template($this->commandData->dynamicVars, $this->breadcrumbsTemplate);
    }

    public function generate()
    {
        $this->routeContents .= "\n\n".$this->routesTemplate;
        $existingRouteContents = file_get_contents($this->path);
        if (Str::contains($existingRouteContents, "Route::resource('".$this->commandData->config->mSnakePlural."',")) {
            $this->commandData->commandObj->info('Route '.$this->commandData->config->mPlural.' is already exists, Skipping Adjustment.');

            return;
        }

        file_put_contents($this->path, $this->routeContents);
        
        file_put_contents(base_path('routes/breadcrumbs/backend/' . $this->commandData->config->mSnakePlural . '.php'), $this->breadcrumbsTemplate);
        $this->breadcrumbsBackendContent .= "\nrequire __DIR__.'/{$this->commandData->config->mSnakePlural}.php';";
        file_put_contents(base_path('routes/breadcrumbs/backend/backend.php'), $this->breadcrumbsBackendContent);
        $this->commandData->commandComment("\n".$this->commandData->config->mCamelPlural.' routes added.');
    }

    public function rollback()
    {
        if (Str::contains($this->routeContents, $this->routesTemplate)) {
            $this->routeContents = str_replace($this->routesTemplate, '', $this->routeContents);
            file_put_contents($this->path, $this->routeContents);
            $this->commandData->commandComment('scaffold routes deleted');
        }
    }
}
