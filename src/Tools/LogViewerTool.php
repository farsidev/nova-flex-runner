<?php

namespace Farsi\NovaFlexRunner\Tools;

use Laravel\Nova\Menu\MenuSection;
use Laravel\Nova\Nova;
use Laravel\Nova\Tool;

class LogViewerTool extends Tool
{
    public function boot()
    {
        Nova::script('nova-flex-runner-logs', __DIR__.'/../../dist/js/log-viewer.js');
        Nova::style('nova-flex-runner-logs', __DIR__.'/../../dist/css/log-viewer.css');
    }

    public function menu()
    {
        return MenuSection::make('Command Logs')
            ->path('/nova-flex-runner/logs')
            ->icon('document-text');
    }

    public function authorize($request)
    {
        return $request->user()->can('viewNovaFlexRunnerLogs', $request->user());
    }
}