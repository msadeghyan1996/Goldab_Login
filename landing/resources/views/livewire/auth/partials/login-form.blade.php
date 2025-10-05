<div class="space-y-6">
    <!-- Login Form Header -->
    <div class="text-center space-y-2">
        <h1 class="text-4xl font-bold text-base-content">Sign In</h1>
        <p class="text-base-content/70">Enter your phone number to continue</p>
    </div>

    <!-- Flash Messages -->
    @if (session()->has('success'))
        <div class="alert alert-success">
            <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none"
                 viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span>{{ session('success') }}</span>
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

    <!-- Login Form -->
    <form wire:submit.prevent="login" class="space-y-6">
        <!-- Phone Number Input -->
        <div class="form-control w-full space-y-2">
            <label class="label">
                <span class="label-text font-semibold text-base">Phone Number</span>
            </label>
            <label
                class="input input-bordered input-lg flex items-center gap-2 w-full @error('phoneNumber') input-error @enderror">
                <svg class="w-5 h-5 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                </svg>
                <input
                    type="tel"
                    placeholder="Example: 09357507574"
                    class="grow w-full"
                    wire:model="phoneNumber"
                    required
                />
            </label>
            @error('phoneNumber')
            <label class="label">
                <span class="label-text-alt text-error font-medium">{{ $message }}</span>
            </label>
            @enderror
        </div>

        <!-- Login Button -->
        <button type="submit" class="btn btn-primary w-full btn-lg gap-2" @if($isLoading) disabled @endif>
            @if($isLoading)
                <span class="loading loading-spinner loading-md"></span>
                Signing In...
            @else
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
                </svg>
                Sign In
            @endif
        </button>
    </form>
</div>
