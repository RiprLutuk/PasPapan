<div>
    @if($hasCheckedIn && $hasCheckedOut)
        <x-attendance-hero-card :attendance="$attendance" />
    @else
        <x-home-actions-card 
            :hasCheckedIn="$hasCheckedIn" 
            :hasCheckedOut="$hasCheckedOut" 
            :attendance="$attendance" 
        />
    @endif
</div>
