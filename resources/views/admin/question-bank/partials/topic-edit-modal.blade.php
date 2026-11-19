<div class="modal fade" id="editTopicModal{{ $topic->id }}" tabindex="-1" aria-labelledby="edit-topic-heading-{{ $topic->id }}" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered question-bank-modal-dialog">
        <div class="modal-content question-bank-modal border-0 shadow-lg rounded-4">
            <form action="{{ route('admin.question-bank.topics.update', $topic) }}" method="POST">
                @csrf
                @method('PUT')
                <input type="hidden" name="form_context" value="topic-edit-{{ $topic->id }}">
                <div class="modal-header px-4 pt-4">
                    <div>
                        <span class="faculty-question-bank__eyebrow text-primary">Edit topic folder</span>
                        <h2 class="modal-title h4 fw-bold mt-1 mb-0" id="edit-topic-heading-{{ $topic->id }}">{{ $topic->name }}</h2>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body px-4 pb-4">
                    <label for="topic-name-{{ $topic->id }}" class="form-label fw-semibold">Topic Name</label>
                    <input
                        id="topic-name-{{ $topic->id }}"
                        name="name"
                        value="{{ old('form_context') === 'topic-edit-'.$topic->id ? old('name') : $topic->name }}"
                        class="form-control @if (old('form_context') === 'topic-edit-'.$topic->id) @error('name') is-invalid @enderror @endif"
                        maxlength="255"
                        required
                    >
                    @if (old('form_context') === 'topic-edit-'.$topic->id)
                        @error('name')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    @endif
                </div>
                <div class="modal-footer justify-content-between px-4 py-3">
                    <button class="btn btn-light rounded-pill px-4" type="button" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn btn-primary rounded-pill px-4 faculty-question-bank__add-button" type="submit">Save Topic</button>
                </div>
            </form>
        </div>
    </div>
</div>
