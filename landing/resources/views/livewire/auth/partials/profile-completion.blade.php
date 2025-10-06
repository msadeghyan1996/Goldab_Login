<!-- Profile Completion Form Component -->
<div class="space-y-6">
    <!-- Header -->
    <div class="text-center">
        <h1 class="text-3xl font-bold text-base-content mb-2">Complete Your Profile</h1>
        <p class="text-base-content/70">Please fill in your information to complete your profile</p>
    </div>

    <!-- Flash Messages -->
    @if (session()->has('message'))
        <div class="alert alert-success">
            <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none"
                 viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span>{{ session('message') }}</span>
        </div>
    @endif

    @if (session()->has('error'))
        <div class="alert alert-error">
            <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none"
                 viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <!-- Profile Completion Form -->
    <form wire:submit.prevent="completeProfile" class="space-y-6">

        <!-- National ID Field -->
        <div class="form-control">
            <label class="label">
                <span class="label-text font-medium">National ID</span>
            </label>
            <label
                class="input input-bordered input-lg flex items-center gap-2 w-full @error('national_id') input-error @enderror">
                <svg class="w-5 h-5 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"></path>
                </svg>
                <input
                    type="text"
                    placeholder="Enter your national ID"
                    class="grow"
                    wire:model="national_id"
                    required
                />
            </label>
            @error('national_id')
            <label class="label">
                <span class="label-text-alt text-error">{{ $message }}</span>
            </label>
            @enderror
        </div>

        <!-- Name Field -->
        <div class="form-control">
            <label class="label">
                <span class="label-text font-medium">Full Name</span>
            </label>
            <label
                class="input input-bordered input-lg flex items-center gap-2 w-full @error('name') input-error @enderror">
                <svg class="w-5 h-5 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                </svg>
                <input
                    type="text"
                    placeholder="Enter your full name"
                    class="grow"
                    wire:model="name"
                    required
                />
            </label>
            @error('name')
            <label class="label">
                <span class="label-text-alt text-error">{{ $message }}</span>
            </label>
            @enderror
        </div>

        <!-- Password Field -->
        <div class="form-control">
            <label class="label">
                <span class="label-text font-medium">Password</span>
            </label>
            <label
                class="input input-bordered input-lg flex items-center gap-2 w-full @error('password') input-error @enderror">
                <svg class="w-5 h-5 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                </svg>
                <input
                    type="password"
                    placeholder="Enter your password"
                    class="grow"
                    wire:model="password"
                    required
                />
            </label>
            @error('password')
            <label class="label">
                <span class="label-text-alt text-error">{{ $message }}</span>
            </label>
            @enderror
        </div>

        <!-- Password Confirmation Field -->
        <div class="form-control">
            <label class="label">
                <span class="label-text font-medium">Confirm Password</span>
            </label>
            <label
                class="input input-bordered input-lg flex items-center gap-2 w-full @error('password_confirmation') input-error @enderror">
                <svg class="w-5 h-5 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <input
                    type="password"
                    placeholder="Confirm your password"
                    class="grow"
                    wire:model="password_confirmation"
                    required
                />
            </label>
            @error('password_confirmation')
            <label class="label">
                <span class="label-text-alt text-error">{{ $message }}</span>
            </label>
            @enderror
        </div>

        <!-- Submit Button -->
        <div class="form-control">
            <button
                type="submit"
                class="btn btn-primary btn-lg w-full"
                wire:loading.attr="disabled"
                wire:target="completeProfile"
            >
                @if($isLoading)
                    <span class="loading loading-spinner loading-sm"></span>
                    Completing...
                @else
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    Complete Profile
                @endif
            </button>
        </div>

        <!-- Back Button -->
        <div class="form-control">
            <button
                type="button"
                class="btn btn-ghost btn-lg w-full"
                wire:click="goBack"
            >
                Logout
            </button>
        </div>

    </form>
</div>
