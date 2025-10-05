<div class="space-y-6">
    <!-- OTP Form Header -->
    <div class="text-center space-y-2">
        <h1 class="text-4xl font-bold text-base-content">Enter OTP Code</h1>
        <p class="text-base-content/70">Please enter the 6-digit code sent to your phone</p>
        @if($phoneNumber)
            <div class="badge badge-primary badge-lg mt-2">{{ $phoneNumber }}</div>
        @endif
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

    <!-- OTP Form -->
    <form wire:submit.prevent="verifyOtp" class="space-y-6">
        <!-- OTP Input Fields -->
        <div class="form-control w-full space-y-3" x-data="{
        length: 6,
        init() {
            this.$nextTick(() => this.$refs.d0.focus());
        },
        focus(index) {
            if (this.$refs['d'+index]) {
                this.$refs['d'+index].focus();
                this.$refs['d'+index].select();
            }
        },
        onInput(e,index) {
            const val = e.target.value.replace(/\\D/g, '').slice(-1);
            e.target.value = val;
            $wire.set('digits.'+index, val);

            if (val && index < this.length - 1) {
                this.focus(index+1);
            }
        },
        onBackspace(e,index) {
            if (!e.target.value && index>0) {
                this.focus(index-1);
            }
        },
        onPaste(e) {
            const text = (e.clipboardData || window.clipboardData).getData('text').replace(/\\D/g, '');
            for (let i=0;i<Math.min(this.length,text.length);i++) {
                this.$refs['d'+i].value=text[i];
                $wire.set('digits.'+i,text[i]);
            }
            this.focus(Math.min(text.length,this.length-1));
        }
    }">
            <label class="label">
                <span class="label-text font-semibold text-base">Verification Code</span>
            </label>
            <div class="flex justify-center gap-3">
                @for ($i = 0; $i < 6; $i++)
                    <input
                        type="text"
                        inputmode="numeric"
                        autocomplete="one-time-code"
                        maxlength="1"
                        class="otp-input"
                        x-ref="d{{ $i }}"
                        x-on:input="onInput($event, {{ $i }})"
                        x-on:keydown.backspace.prevent="onBackspace($event, {{ $i }})"
                        x-on:paste.prevent="onPaste($event)"
                        wire:model.live="digits.{{ $i }}"
                    />
                @endfor
            </div>
            @error('digits')
            <label class="label">
                <span class="label-text-alt text-error font-medium">{{ $message }}</span>
            </label>
            @enderror
        </div>

        <!-- Timer and Resend -->
        <div class="card bg-base-200" x-data="{
            countdown: @this.get('timerCountDown'),
            timerInterval: null,

            init() {
                this.startTimer();
            },
            startTimer() {
                this.timerInterval = setInterval(() => {
                    this.countdown--;

                    if (this.countdown <= 0) {
                        clearInterval(this.timerInterval);
                    }
                }, 1000);
            },

            resetTimer() {
                this.countdown = @this.get('timerCountDown');
                clearInterval(this.timerInterval);
                this.startTimer();
            }
        }">
            <div class="card-body text-center py-4">
                <p class="text-sm">
                    Didn't receive the code?
                    <span x-show="countdown > 0" class="font-bold text-primary"
                          x-text="`Resend in ${countdown}s`"></span>
                </p>
                <button type="button"
                        class="btn btn-sm btn-ghost btn-primary"
                        :class="{ 'btn-disabled': countdown > 0 }"
                        wire:click="resendOtp"
                        @click="resetTimer()"
                        :disabled="countdown > 0">
                    Resend Code
                </button>
            </div>
        </div>

        <!-- Verify Button -->
        <button type="submit" class="btn btn-primary w-full btn-lg gap-2" @if($isLoading) disabled @endif>
            @if($isLoading)
                <span class="loading loading-spinner loading-md"></span>
                Verifying...
            @else
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                Verify Code
            @endif
        </button>
    </form>

    <!-- Back to Login -->
    <div class="divider">OR</div>
    <div class="text-center">
        <button type="button" wire:click="goBack" class="link link-primary font-medium">
            ← Back to Login
        </button>
    </div>
</div>
<script>
    // Timer Alpine.js Component
    function timerComponent() {
        return {
            countdown: 60,
            timerInterval: null,

            init() {
                this.startTimer();
            },

            startTimer() {
                this.timerInterval = setInterval(() => {
                    this.countdown--;

                    if (this.countdown <= 0) {
                        clearInterval(this.timerInterval);
                        @this.
                        set('canResend', true);
                    }
                }, 1000);
            },

            resetTimer() {
                this.countdown = 60;
                clearInterval(this.timerInterval);
                this.startTimer();
                @this.
                set('canResend', false);
            }
        }
    }
</script>
