@extends('layouts.app')

@section('title', 'เข้าสู่ระบบ')

@section('content')
<div class="min-h-screen flex items-center justify-center px-4 py-12 bg-gradient-to-br from-[#FAF0E6] via-[#F5E6D8] to-[#E8C4B8]">
    <div class="absolute inset-0 overflow-hidden pointer-events-none">
        <div class="absolute -top-24 -right-24 w-72 h-72 rounded-full bg-[#C4725C]/20 blur-3xl"></div>
        <div class="absolute -bottom-32 -left-16 w-96 h-96 rounded-full bg-[#B85C4A]/15 blur-3xl"></div>
    </div>

    <div class="relative w-full max-w-md">
        <div class="bg-[#FFFBF7]/90 backdrop-blur-sm rounded-2xl shadow-xl shadow-[#8B4513]/10 border border-[#E8C4B8]/60 overflow-hidden">
            <div class="h-2 bg-gradient-to-r from-[#A0522D] via-[#B85C4A] to-[#C4725C]"></div>

            <div class="px-8 pt-8 pb-6 text-center">
                <div class="mx-auto mb-5 flex h-16 w-16 items-center justify-center rounded-2xl bg-gradient-to-br from-[#B85C4A] to-[#8B4513] shadow-lg shadow-[#8B4513]/25">
                    <svg class="h-8 w-8 text-[#FAF0E6]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342" />
                    </svg>
                </div>

                <h1 class="text-xl font-bold text-[#5C2E1F] leading-snug">
                    ระบบรายงานผลการสอบ
                </h1>
                <p class="mt-1 text-sm font-medium text-[#8B4513]">
                    คณะวิทยาศาสตร์
                </p>
                <p class="text-sm text-[#A0522D]/80">
                    มหาวิทยาลัยขอนแก่น
                </p>
            </div>

            <div class="px-8 pb-8">
                <p class="mb-6 text-center text-sm text-[#7A4A3A]/80">
                    เข้าสู่ระบบด้วยบัญชี Google ของมหาวิทยาลัย
                </p>

                <a
                    href="{{ route('auth.google') }}"
                    class="group flex w-full items-center justify-center gap-3 rounded-xl border border-[#E8C4B8] bg-white px-5 py-3.5 text-sm font-medium text-[#5C2E1F] shadow-sm transition-all duration-200 hover:border-[#C4725C] hover:bg-[#FFFBF7] hover:shadow-md hover:shadow-[#8B4513]/10 focus:outline-none focus:ring-2 focus:ring-[#B85C4A]/40 focus:ring-offset-2"
                >
                    <svg class="h-5 w-5" viewBox="0 0 24 24" aria-hidden="true">
                        <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                        <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                        <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                        <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                    </svg>
                    <span>เข้าสู่ระบบด้วย Google</span>
                </a>

                @if ($errors->any())
                    <div class="mt-4 rounded-lg bg-[#FFF0ED] border border-[#E8A99A] px-4 py-3 text-sm text-[#8B3A2F]">
                        {{ $errors->first() }}
                    </div>
                @endif
            </div>

            <div class="border-t border-[#E8C4B8]/50 bg-[#FAF0E6]/50 px-8 py-4 text-center">
                <p class="text-xs text-[#A0522D]/60">
                    SciGrade &copy; {{ date('Y') }} คณะวิทยาศาสตร์ มหาวิทยาลัยขอนแก่น
                </p>
            </div>
        </div>
    </div>
</div>
@endsection
