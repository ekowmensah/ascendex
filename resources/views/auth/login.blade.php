<x-layouts.app :title="'Login'">
    <div class="mx-auto max-w-md rounded-2xl border border-slate-800 bg-slate-900 p-6">
        <h1 class="mb-4 text-xl font-semibold">Login</h1>
        <form method="POST" action="{{ route('login.perform') }}" class="space-y-4">
            @csrf
            <div>
                <label class="mb-1 block text-sm text-slate-300">Email</label>
                <input name="email" type="email" value="{{ old('email') }}" required class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2">
            </div>
            <div>
                <label class="mb-1 block text-sm text-slate-300">Password</label>
                <input name="password" type="password" required class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2">
            </div>
            <label class="flex items-center gap-2 text-sm text-slate-300">
                <input type="checkbox" name="remember" value="1" class="h-4 w-4 rounded border-slate-600 bg-slate-950">
                Remember me
            </label>
            <button class="w-full rounded-lg bg-cyan-600 px-4 py-2 font-medium hover:bg-cyan-500">Sign In</button>
        </form>
    </div>
</x-layouts.app>
