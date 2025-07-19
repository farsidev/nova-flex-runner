<?php

namespace Farsi\NovaFlexRunner\Tools;

use Laravel\Nova\Menu\MenuSection;
use Laravel\Nova\Nova;
use Laravel\Nova\Tool;

class FlexRunnerTool extends Tool
{
    public function boot()
    {
        Nova::script('nova-flex-runner', __DIR__.'/../../dist/js/tool.js');
        Nova::style('nova-flex-runner', __DIR__.'/../../dist/css/tool.css');
    }

    public function menu()
    {
        return MenuSection::make('Flex Runner')
            ->path('/nova-flex-runner')
            ->icon('play');
    }

    public function authorize($request)
    {
        return $request->user()->can('viewNovaFlexRunner', $request->user());
    }
}