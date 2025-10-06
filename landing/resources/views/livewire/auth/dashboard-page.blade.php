<div class="flex justify-center items-center h-screen">
    <div class="card w-full md:w-1/3 border border-primary p-12">
        <div class="card-title">Just some title</div>
        <div class="card-body">
        <span>
            <strong>Your data:</strong>
            <ul>
                <li>Name: {{ $this->user->name }}</li>
                <li>National ID: {{ $this->user->national_id }}</li>
                <li>Phone number: {{ $this->user->phone_number }}</li>
            </ul>
        </span>
            <button class="btn btn-primary" wire:click.prevent="logout">Logout</button>
        </div>
    </div>
</div>
