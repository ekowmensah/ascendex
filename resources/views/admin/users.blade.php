<x-layouts.app :title="'Admin Users'">
    @include('admin.partials.nav', [
        'title' => 'Users',
        'subtitle' => 'Manage account roles and perform controlled balance adjustments per wallet currency.',
        'stats' => $stats,
    ])

    <section class="glass-panel">
        <div class="mb-3 flex items-center justify-between gap-3">
            <h2 class="text-base font-semibold">User Balance Controls</h2>
            <span class="rounded-full border border-slate-700/80 bg-slate-950/80 px-2 py-0.5 text-[11px] text-slate-300">{{ $users->total() }} users</span>
        </div>

        <form method="GET" action="{{ route('admin.users.index') }}" class="mb-3 grid gap-2 md:grid-cols-[1fr,0.45fr,auto,auto]">
            <input type="text" name="q" value="{{ $filters['q'] }}" placeholder="Search name or email" class="input-dark">
            <select name="role" class="input-dark">
                <option value="">All roles</option>
                @foreach($roleOptions as $role)
                    <option value="{{ $role }}" @selected($filters['role'] === $role)>{{ strtoupper($role) }}</option>
                @endforeach
            </select>
            <button class="btn-primary" type="submit">Filter Users</button>
            <a href="{{ route('admin.users.index') }}" class="pill-link inline-flex items-center justify-center">Reset</a>
        </form>

        <div class="overflow-x-auto">
            <table class="table-dark min-w-[64rem]">
                <thead>
                <tr>
                    <th>User</th>
                    <th>Role</th>
                    <th>Wallets</th>
                    <th>Adjust Balance</th>
                </tr>
                </thead>
                <tbody>
                @forelse($users as $user)
                    @php
                        $wallets = $user->wallets->sortBy('currency')->values();
                    @endphp
                    <tr>
                        <td>
                            <p class="font-medium text-slate-100">{{ $user->name }}</p>
                            <p class="text-xs text-slate-400">{{ $user->email }}</p>
                        </td>
                        <td>
                            <span class="rounded-full border px-2 py-0.5 text-[11px] {{ $user->role === 'admin' ? 'border-cyan-400/40 bg-cyan-500/10 text-cyan-200' : 'border-slate-700/80 bg-slate-950/80 text-slate-300' }}">
                                {{ strtoupper($user->role) }}
                            </span>
                        </td>
                        <td>
                            <div class="grid gap-2">
                                @forelse($wallets as $wallet)
                                    <div class="card-muted flex items-center justify-between gap-3 text-sm">
                                        <div>
                                            <p class="font-medium text-slate-100">{{ $wallet->currency }}</p>
                                            <p class="text-[11px] text-slate-400">Locked {{ number_format((float) $wallet->locked_balance, 8) }}</p>
                                        </div>
                                        <span class="font-semibold text-cyan-200">{{ number_format((float) $wallet->balance, 8) }}</span>
                                    </div>
                                @empty
                                    <p class="text-xs text-slate-400">No wallets yet.</p>
                                @endforelse
                            </div>
                        </td>
                        <td>
                            <form method="POST" action="{{ route('admin.users.adjust-balance', $user) }}" class="grid gap-2 md:grid-cols-[0.6fr,0.7fr,1fr,auto]">
                                @csrf
                                <select name="wallet_currency" class="input-dark" required>
                                    <option value="USDT">USDT</option>
                                    <option value="BTC">BTC</option>
                                    <option value="ETH">ETH</option>
                                </select>
                                <input type="number" name="delta" step="0.00000001" placeholder="+50 or -0.1" class="input-dark" required>
                                <input type="text" name="reason" placeholder="Reason for adjustment" class="input-dark" required>
                                <button class="pill-link">Apply</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center text-slate-400">No users found.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $users->links() }}</div>
    </section>
</x-layouts.app>
