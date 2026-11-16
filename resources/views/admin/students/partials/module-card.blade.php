@php
    $moduleModalId = (isset($modal) && $modal ? 'all-module-' : 'module-').$module->id.'-modal';
    $progress = $moduleProgress[$module->id] ?? ['points' => 0, 'maximum' => 0, 'subtopics' => []];
@endphp
<div class="{{ isset($modal) && $modal ? 'col-md-6' : 'col-md-6 col-lg-3' }}">
    <button type="button" class="module-card bg-white w-100 text-start" data-bs-toggle="modal" data-bs-target="#{{ $moduleModalId }}">
        <div class="d-flex justify-content-between gap-2">
            <span class="module-logo">@if($module->logo_path)<img src="{{ asset('storage/'.$module->logo_path) }}" alt="{{ $module->title }}">@else<i class="bi bi-beaker"></i>@endif</span>
            <span class="module-points">{{ $progress['points'] }}/{{ $progress['maximum'] }}</span>
        </div>
        <div class="fw-bold small mt-2">{{ $module->title }}</div>
        <small class="text-muted">Subtopics: {{ $module->subtopics->count() }}</small>
    </button>
</div>
<div class="modal fade" id="{{ $moduleModalId }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered"><div class="modal-content"><div class="modal-header"><h2 class="modal-title h5"><i class="bi bi-book me-2"></i>{{ $module->title }}</h2><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div><div class="modal-body">
        @forelse($module->subtopics as $subtopic)
            @php
                $subtopicPoints = $progress['subtopics'][$subtopic->id] ?? 0;
            @endphp
            <div class="subtopic-row d-flex align-items-center gap-2 mb-2 p-2"><i class="bi bi-book text-primary"></i><span class="flex-grow-1">{{ $subtopic->title }}</span><strong>{{ $subtopicPoints }}/30</strong></div>
        @empty
            <p class="text-muted mb-0">No subtopics are available in this module.</p>
        @endforelse
    </div></div></div>
</div>
