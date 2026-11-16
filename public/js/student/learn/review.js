document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.sticky-quiz-btn a').forEach(function (takeQuizBtn) {
        takeQuizBtn.addEventListener('click', function () {
            const questionType = takeQuizBtn.dataset.questionType === 'identification'
                ? 'identification quiz'
                : 'multiple-choice quiz';
            const icon = takeQuizBtn.querySelector('i');
            const text = takeQuizBtn.querySelector('span');

            if (icon) {
                icon.className = 'spinner-border spinner-border-sm';
            }

            if (text) {
                text.textContent = 'Preparing your quiz...';
            }

            document.querySelectorAll('.sticky-quiz-btn a').forEach(link => {
                link.style.pointerEvents = 'none';
            });
            document.getElementById('quizGeneratingModalLabel').textContent = `Preparing your ${questionType}`;
            bootstrap.Modal.getOrCreateInstance(document.getElementById('quizGeneratingModal')).show();
        });
    });
});
