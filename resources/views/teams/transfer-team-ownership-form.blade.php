<x-action-section>
    <x-slot name="title">
        {{ __('Team Ownership') }}
    </x-slot>

    <x-slot name="description">
        {{ __('Transfer ownership of this team to another member.') }}
    </x-slot>

    <x-slot name="content">
        <div class="max-w-xl text-sm text-gray-600">
            {{ __('If you transfer ownership, you will remain a member of the team but will act as an Administrator. The new owner will gain full control over team settings and billing.') }}
        </div>

        @if($potentialOwners->isEmpty())
            <div class="mt-4 text-sm text-red-600">
                {{ __('There are no other members in this team to transfer ownership to. Please add a member first.') }}
            </div>
        @else
            <div class="mt-4">
                <x-label for="transferToUserId" value="{{ __('New Owner') }}" />

                <div class="flex items-center mt-2">
                    <select id="transferToUserId"
                            wire:model="transferToUserId"
                            class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                        <option value="">{{ __('Select a team member') }}</option>
                        @foreach($potentialOwners as $member)
                            <option value="{{ $member->id }}">{{ $member->name }} ({{ $member->email }})</option>
                        @endforeach
                    </select>
                </div>
                <x-input-error for="transferToUserId" class="mt-2" />
            </div>

            <div class="mt-5">
                <x-danger-button wire:click="confirmTransfer" wire:loading.attr="disabled">
                    {{ __('Transfer Ownership') }}
                </x-danger-button>
            </div>
        @endif

        <!-- Transfer Confirmation Modal -->
        <x-confirmation-modal wire:model.live="confirmingTransfer">
            <x-slot name="title">
                {{ __('Transfer Team Ownership') }}
            </x-slot>

            <x-slot name="content">
                {{ __('Are you sure you want to transfer ownership of this team? You will lose "Owner" status but remain as an Administrator.') }}
            </x-slot>

            <x-slot name="footer">
                <x-secondary-button wire:click="$toggle('confirmingTransfer')" wire:loading.attr="disabled">
                    {{ __('Cancel') }}
                </x-secondary-button>

                <x-danger-button class="ms-3" wire:click="transferOwnership" wire:loading.attr="disabled">
                    {{ __('Transfer Ownership') }}
                </x-danger-button>
            </x-slot>
        </x-confirmation-modal>
    </x-slot>
</x-action-section>
