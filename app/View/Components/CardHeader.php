<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class CardHeader extends Component
{
    public $title;
    public $action;
    public $count;

    public function __construct($title, $action = null, $count = null)
    {
        $this->title = $title;
        $this->action = $action;
        $this->count = $count;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.card-header');
    }
}
