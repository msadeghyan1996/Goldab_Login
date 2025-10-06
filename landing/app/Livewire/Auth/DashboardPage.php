<?php

namespace App\Livewire\Auth;

use App\Models\User\User;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Dashboard page')]
class DashboardPage extends Component
{
    public function mount(): void
    {
        if (!auth()->check()) {
            $this->redirectRoute('auth');
            return;
        }
        $this->user = auth()->user();
    }

    #[Computed]
    public function authenticatedUser(): User
    {
        return auth()->user();
    }

    public function logout(): void
    {
        auth()->logout();
        $this->redirectRoute('auth');
    }

    public function render()
    {
        return view('livewire.auth.dashboard-page');
    }
}
