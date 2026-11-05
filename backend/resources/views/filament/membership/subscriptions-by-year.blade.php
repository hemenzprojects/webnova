{{-- App\Plugins\Membership\Filament\Widgets\SubscriptionsByYear --}}
<x-filament-widgets::widget>
    <x-filament::section heading="Subscriptions by membership year" description="Members who paid for each year, and the fees received for it (all payment methods).">
        @if ($years->isEmpty())
            <p class="text-sm text-gray-500">No payments yet.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-gray-500 border-b border-gray-200 dark:border-white/10">
                            <th class="py-2 pr-4 font-medium">Year</th>
                            <th class="py-2 pr-4 font-medium text-right">Members paid</th>
                            <th class="py-2 pr-4 font-medium text-right">Joining fees</th>
                            <th class="py-2 pr-4 font-medium text-right">Renewals</th>
                            <th class="py-2 font-medium text-right">Received</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($years as $row)
                            <tr class="border-b border-gray-100 dark:border-white/5 last:border-0">
                                <td class="py-2 pr-4 font-medium">{{ $row->year }}</td>
                                <td class="py-2 pr-4 text-right">{{ number_format($row->members) }}</td>
                                <td class="py-2 pr-4 text-right">{{ number_format($row->joining) }}</td>
                                <td class="py-2 pr-4 text-right">{{ number_format($row->renewals) }}</td>
                                <td class="py-2 text-right whitespace-nowrap">{{ $currency }} {{ number_format((float) $row->total, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
