@extends('layouts.app')

@section('title', 'แดชบอร์ด')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-[#FAF0E6] via-[#F5E6D8] to-[#E8C4B8]">
    <header class="border-b border-[#E8C4B8]/60 bg-[#FFFBF7]/80 backdrop-blur-sm">
        <div class="mx-auto flex max-w-5xl items-center justify-between px-4 py-4 sm:px-6">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-[#B85C4A] to-[#8B4513]">
                    <svg class="h-5 w-5 text-[#FAF0E6]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342" />
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-bold text-[#5C2E1F]">SciGrade</p>
                    <p class="text-xs text-[#A0522D]/70">คณะวิทยาศาสตร์ มข.</p>
                </div>
            </div>

            <div class="flex items-center gap-4">
                <div class="hidden sm:flex items-center gap-2">
                    @if (auth()->user()->avatar)
                        <img src="{{ auth()->user()->avatar }}" alt="" class="h-8 w-8 rounded-full ring-2 ring-[#E8C4B8]">
                    @endif
                    <span class="text-sm text-[#5C2E1F]">{{ auth()->user()->name }}</span>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="rounded-lg border border-[#E8C4B8] bg-white px-4 py-2 text-sm font-medium text-[#8B4513] transition hover:border-[#C4725C] hover:bg-[#FAF0E6]">
                        ออกจากระบบ
                    </button>
                </form>
            </div>
        </div>
    </header>

    <main class="mx-auto max-w-5xl px-4 py-10 sm:px-6">
        <div class="rounded-2xl border border-[#E8C4B8]/60 bg-[#FFFBF7]/90 p-8 shadow-lg shadow-[#8B4513]/5">
            <h1 class="text-2xl font-bold text-[#5C2E1F]">
                ยินดีต้อนรับ, {{ auth()->user()->name }}
            </h1>
            <p class="mt-2 text-[#7A4A3A]/80">
                ระบบรายงานผลการสอบ คณะวิทยาศาสตร์ มหาวิทยาลัยขอนแก่น
            </p>

            <div class="mt-8 grid gap-4 sm:grid-cols-3">
                <div class="rounded-xl border border-[#E8C4B8]/50 bg-[#FAF0E6]/60 p-5">
                    <p class="text-xs font-medium uppercase tracking-wide text-[#A0522D]/70">อีเมล</p>
                    <p class="mt-1 text-sm font-medium text-[#5C2E1F]">{{ auth()->user()->email }}</p>
                </div>
                <div class="rounded-xl border border-[#E8C4B8]/50 bg-[#FAF0E6]/60 p-5">
                    <p class="text-xs font-medium uppercase tracking-wide text-[#A0522D]/70">สถานะ</p>
                    <p class="mt-1 text-sm font-medium text-[#5C2E1F]">เข้าสู่ระบบแล้ว</p>
                </div>
                <div class="rounded-xl border border-[#E8C4B8]/50 bg-[#FAF0E6]/60 p-5">
                    <p class="text-xs font-medium uppercase tracking-wide text-[#A0522D]/70">ระบบ</p>
                    <p class="mt-1 text-sm font-medium text-[#5C2E1F]">SciGrade</p>
                </div>
            </div>
        </div>
    </main>
</div>
@endsection
