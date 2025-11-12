<?php

namespace App\View\Components;

use Illuminate\View\Component;

class Alert extends Component
{
    public $type;

    public $message;

    public function __construct()
    {
        if (session()->has('success')) {
            $this->type = 'success';
            $this->message = session('success');
        } elseif (session()->has('error')) {
            $this->type = 'danger';
            $this->message = session('error');
        } elseif (session()->has('warning')) {
            $this->type = 'warning';
            $this->message = session('warning');
        } else {
            $this->type = null;
            $this->message = null;
        }
    }

    public function bsClass()
    {
        $classes = [
            'success' => 'alert-success',
            'danger' => 'alert-danger',
            'warning' => 'alert-warning',
        ];

        return $classes[$this->type] ?? 'alert-info';
    }

    public function render()
    {
        return view('components.alert');
    }
}
