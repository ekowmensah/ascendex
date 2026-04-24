<x-layouts.app :title="'Register'">
    <div class="mx-auto max-w-md rounded-2xl border border-slate-800 bg-slate-900 p-6">
        <h1 class="mb-4 text-xl font-semibold">Create Account</h1>
        <form method="POST" action="{{ route('register.perform') }}" class="space-y-4">
            @csrf
            <div>
                <label class="mb-1 block text-sm text-slate-300">Name</label>
                <input name="name" type="text" value="{{ old('name') }}" required class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2">
            </div>
            <div>
                <label class="mb-1 block text-sm text-slate-300">Email</label>
                <input name="email" type="email" value="{{ old('email') }}" required class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2">
            </div>
            <div>
                <label class="mb-1 block text-sm text-slate-300">Password</label>
                <input name="password" type="password" required class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2">
            </div>
            <div>
                <label class="mb-1 block text-sm text-slate-300">Confirm Password</label>
                <input name="password_confirmation" type="password" required class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2">
            </div>
            <button class="w-full rounded-lg bg-cyan-600 px-4 py-2 font-medium hover:bg-cyan-500">Create Account</button>
            <div class="flex flex-wrap items-center justify-between gap-2 text-sm">
                <a href="{{ route('landing') }}" class="rounded-lg border border-slate-700 px-3 py-1.5 text-slate-300 hover:border-cyan-500/60 hover:text-cyan-200">Homepage</a>
                <a href="{{ route('login') }}" class="text-cyan-300 hover:text-cyan-200">Already have account?</a>
            </div>
        </form>
    </div>
</x-layouts.app>
