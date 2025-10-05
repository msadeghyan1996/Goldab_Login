<!-- Password Form Component -->
<div class="space-y-6">
    <!-- Header -->
    <div class="text-center">
        <h1 class="text-3xl font-bold text-base-content mb-2">Enter Password</h1>
        <p class="text-base-content/70">Please enter your password to continue</p>
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

    <!-- Password Form -->
    <form wire:submit.prevent="loginWithPassword" class="space-y-6">

        <!-- Password Field -->
        <div class="form-control space-y-2">
            <label class="label">
                <span class="label-text font-medium">Password</span>
            </label>
            <label
                class="input input-bordered input-lg flex items-center gap-2 w-full @error('passwordToLogin') input-error @enderror">
                <svg class="w-5 h-5 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                </svg>
                <input
                    type="password"
                    placeholder="Enter your password"
                    class="grow"
                    wire:model="passwordToLogin"
                    required
                />
            </label>
            @error('passwordToLogin')
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
                wire:target="verifyPassword"
            >
                @if($isLoading)
                    <span class="loading loading-spinner loading-sm"></span>
                    Verifying...
                @else
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Verify Password
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
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                Back
            </button>
        </div>

    </form>
</div>
