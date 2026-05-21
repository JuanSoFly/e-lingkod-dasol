<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class AppLayout extends Component
{
    /**
     * Get the view / contents that represents the component.
     */
    public function render(): View
    {
        if (request()->ajax() || request()->query('modal')) {
            return view('layouts.modal');
        }
        return view('layouts.app');
    }
}
