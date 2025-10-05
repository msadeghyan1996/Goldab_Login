<!-- Main Container -->
<div class="min-h-screen flex items-center justify-center p-4 bg-base-200">
    <!-- Auth Card Container -->
    <div class="card w-full max-w-6xl shadow-2xl bg-base-100 overflow-hidden fade-in-up">
        <div class="card-body p-0">
            <div class="grid lg:grid-cols-2 min-h-[600px]">

                <!-- Left Side - Image Section -->
                <div class="relative bg-gradient-to-br from-primary to-secondary overflow-hidden">
                    <div class="absolute inset-0 image-overlay"></div>
                    <!-- Decorative shapes -->
                    <div class="absolute top-10 right-10 w-32 h-32 bg-white bg-opacity-10 rounded-full"></div>
                    <div class="absolute bottom-10 left-10 w-24 h-24 bg-white bg-opacity-10 rounded-full"></div>
                    <div class="absolute top-1/2 left-1/4 w-16 h-16 bg-white bg-opacity-10 rounded-full"></div>

                    <!-- Dynamic content based on current step -->
                    @if($currentStep === 'login')
                        <!-- Login Image Content -->
                        <div class="flex items-center justify-center h-full p-8">
                            <div class="text-center text-secondary-content">
                                <div
                                    class="w-48 h-48 mx-auto mb-6 bg-white bg-opacity-20 rounded-2xl flex items-center justify-center backdrop-blur-sm">
                                    <svg class="w-24 h-24 text-secondary" fill="currentColor" viewBox="0 0 24 24">
                                        <path
                                            d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                                    </svg>
                                </div>
                                <h2 class="text-3xl font-bold mb-4">Welcome Back</h2>
                                <p class="text-lg opacity-90">Sign in to access your account and continue your journey
                                    with us.</p>
                            </div>
                        </div>
                    @elseif($currentStep === 'otp')
                        <!-- OTP Image Content -->
                        <div class="flex items-center justify-center h-full p-8">
                            <div class="text-center text-secondary-content">
                                <div
                                    class="w-48 h-48 mx-auto mb-6 bg-white bg-opacity-20 rounded-2xl flex items-center justify-center backdrop-blur-sm">
                                    <svg class="w-24 h-24 text-secondary" fill="currentColor" viewBox="0 0 24 24">
                                        <path
                                            d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/>
                                        <circle cx="12" cy="15" r="1"/>
                                    </svg>
                                </div>
                                <h2 class="text-3xl font-bold mb-4">Verify Your Identity</h2>
                                <p class="text-lg opacity-90">We've sent a 6-digit verification code to your phone
                                    number. Please enter it below.</p>
                            </div>
                        </div>
                    @elseif($currentStep === 'password')
                        <!-- Password Image Content -->
                        <div class="flex items-center justify-center h-full p-8">
                            <div class="text-center text-secondary-content">
                                <div
                                    class="w-48 h-48 mx-auto mb-6 bg-white bg-opacity-20 rounded-2xl flex items-center justify-center backdrop-blur-sm">
                                    <svg class="w-24 h-24 text-secondary" fill="currentColor" viewBox="0 0 24 24">
                                        <path
                                            d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                    </svg>
                                </div>
                                <h2 class="text-3xl font-bold mb-4">Secure Access</h2>
                                <p class="text-lg opacity-90">Please enter your password to continue with secure
                                    authentication.</p>
                            </div>
                        </div>
                    @elseif($currentStep === 'completion')
                        <!-- Profile Completion Image Content -->
                        <div class="flex items-center justify-center h-full p-8">
                            <div class="text-center text-secondary-content">
                                <div
                                    class="w-48 h-48 mx-auto mb-6 bg-white bg-opacity-20 rounded-2xl flex items-center justify-center backdrop-blur-sm">
                                    <svg class="w-24 h-24 text-secondary" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                    </svg>
                                </div>
                                <h2 class="text-3xl font-bold mb-4">Complete Your Profile</h2>
                                <p class="text-lg opacity-90">Let's set up your account with your personal information
                                    to get started.</p>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Right Side - Form Section -->
                <div class="flex items-center justify-center p-8 lg:p-12">
                    <div class="w-full max-w-md fade-in-up-delay">

                        @if($currentStep === 'login')
                            <div wire:key="login-step">
                                @include('livewire.auth.partials.login-form')
                            </div>
                        @elseif($currentStep === 'completion')
                            <div wire:key="completion-step">
                                @include('livewire.auth.partials.profile-completion')
                            </div>
                        @elseif($currentStep === 'password')
                            <div wire:key="password-step">
                                @include('livewire.auth.partials.password')
                            </div>
                        @else
                            <div wire:key="otp-step">
                                @include('livewire.auth.partials.otp-form')
                            </div>
                        @endif

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
