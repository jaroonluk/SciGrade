@php
    /** @var \App\Models\ThesisGrade $report */
    $s0Students = $report->s0Students();
    $role = $role ?? 'instructor';
@endphp
@if ($s0Students->isNotEmpty())
    <div class="mt-3 mb-3 rounded-lg border border-amber-200 bg-amber-50/70 px-3 py-2.5">
        <p class="text-xs font-bold tracking-wide text-[#854d0e] mb-2">พิมพ์บันทึกข้อความ S=0</p>
        <div class="space-y-1.5">
            @foreach ($s0Students as $student)
                @php
                    $letterUrl = $role === 'dept'
                        ? route('dept-admin.thesis-grades.s0-letter', [$report, $student])
                        : route('thesis-grades.s0-letter.student', [$report, $student]);
                    $docxUrl = $role === 'dept'
                        ? route('dept-admin.thesis-grades.s0.docx', [$report, $student])
                        : route('thesis-grades.s0.docx.student', [$report, $student]);
                @endphp
                <div class="flex flex-wrap items-center gap-2 text-sm">
                    <a href="{{ $letterUrl }}" target="_blank" rel="noopener"
                       class="inline-flex items-center justify-center w-8 h-8 rounded-lg border border-amber-300 bg-white text-[#854d0e] hover:bg-amber-100"
                       title="พิมพ์บันทึกข้อความ" aria-label="พิมพ์บันทึกข้อความ {{ $student->student_code }}">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="w-4 h-4" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 9V4h12v5M6 18H5a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-1M6 14h12v6H6v-6Z"/>
                        </svg>
                    </a>
                    <a href="{{ $docxUrl }}"
                       class="inline-flex items-center justify-center w-8 h-8 rounded-lg border border-amber-300 bg-white text-[#854d0e] hover:bg-amber-100"
                       title="ดาวน์โหลด Word (.docx)" aria-label="ดาวน์โหลด Word {{ $student->student_code }}">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="w-4 h-4" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v10m0 0 3.5-3.5M12 14 8.5 10.5M6 18h12"/>
                        </svg>
                    </a>
                    <span class="text-[#5C2E1F]">
                        <span class="font-semibold">{{ $student->student_code }}</span>
                        {{ $student->displayName() }}
                    </span>
                    <span class="text-xs text-[#7A4A3A]/80">S=0</span>
                </div>
            @endforeach
        </div>
    </div>
@endif
