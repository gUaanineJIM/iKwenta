@if ($entries->isEmpty())
    <div class="owner-products-empty">
        <span class="owner-products-empty__icon" aria-hidden="true">
            <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"
                stroke-linecap="round" stroke-linejoin="round">
                <path d="M3 12L7 12L9 6L13 18L15 10L17 12L21 12" />
                <circle cx="12" cy="12" r="9" />
            </svg>
        </span>

        <strong>{{ $q ? 'No matches for "'.$q.'"' : 'No activity yet' }}</strong>

        <p>{{ $q ? 'Try a different keyword — actions, owners, and records are searched.' : 'Actions recorded in the owner portal will appear here.' }}</p>
    </div>
@else
    <p class="owner-products-count">
        {{ $logs->total() }} {{ $logs->total() === 1 ? 'entry' : 'entries' }}
    </p>

    <div class="owner-table-wrap">
        <table class="owner-rank-table owner-activity-table">
            <thead>
                <tr>
                    <th scope="col">Time</th>
                    <th scope="col">Owner</th>
                    <th scope="col">Action</th>
                    <th scope="col">Record</th>
                    <th scope="col">Changes</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($entries as $entry)
                    <tr class="owner-activity-row">
                        <td data-label="Time">
                            <span class="owner-activity-time">{{ $entry['time'] }}</span>
                        </td>

                        <td data-label="Owner">
                            <strong class="owner-activity-actor">{{ $entry['actor'] }}</strong>
                        </td>

                        <td data-label="Action">
                            <span class="activity-type activity-type--{{ $entry['type_key'] }}">{{ $entry['type_label'] }}</span>
                            <span class="owner-activity-action">{{ $entry['action_label'] }}</span>
                        </td>

                        <td data-label="Record">
                            <span class="owner-activity-record">{{ $entry['record'] }}</span>
                        </td>

                        <td data-label="Changes" class="owner-activity-changes-cell">
                            @if (count($entry['changes']) > 0)
                                <details class="activity-changes-details">
                                    <summary>
                                        <span class="activity-changes-summary">
                                            {{ count($entry['changes']) }} {{ count($entry['changes']) === 1 ? 'field' : 'fields' }}
                                        </span>
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <path d="M6 9L12 15L18 9" />
                                        </svg>
                                    </summary>

                                    <dl class="activity-changes-list">
                                        @foreach ($entry['changes'] as $change)
                                            <div class="activity-change">
                                                <dt>{{ $change['field'] }}</dt>
                                                <dd>
                                                    <span class="activity-change__old">{{ $change['old'] ?? '—' }}</span>
                                                    <span class="activity-change__arrow" aria-hidden="true">→</span>
                                                    <span class="activity-change__new">{{ $change['new'] ?? '—' }}</span>
                                                </dd>
                                            </div>
                                        @endforeach
                                    </dl>
                                </details>
                            @else
                                <span class="activity-changes-empty">—</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{ $logs->onEachSide(1)->links() }}
@endif