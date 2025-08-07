<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Modal extends Component
{
   public $modalId;
   public $formId;
   public $pathForm;

    public function __construct($modalId, $formId, $pathForm)
    {
        $this->modalId = $modalId;
        $this->formId = $formId;
        $this->pathForm = $pathForm;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.modal');
    }
}
