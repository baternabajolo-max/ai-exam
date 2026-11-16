document.addEventListener('DOMContentLoaded', function() {
    const deptSelect = document.getElementById('department_id');
    const courseSelect = document.getElementById('course_id');

    document.querySelectorAll('[data-toggle-password]').forEach(function(toggleButton) {
        toggleButton.addEventListener('click', function() {
            const passwordInput = document.getElementById(toggleButton.dataset.togglePassword);
            const icon = toggleButton.querySelector('i');

            if (!passwordInput || !icon) {
                return;
            }

            const isPasswordHidden = passwordInput.type === 'password';
            passwordInput.type = isPasswordHidden ? 'text' : 'password';
            icon.classList.toggle('fa-eye', isPasswordHidden);
            icon.classList.toggle('fa-eye-slash', !isPasswordHidden);
            toggleButton.setAttribute('aria-label', isPasswordHidden ? 'Hide password' : 'Show password');
            toggleButton.setAttribute('title', isPasswordHidden ? 'Hide password' : 'Show password');
        });
    });
    
    // Check if old values exist via data attributes or inline scripts if needed
    const oldDepartmentId = deptSelect ? deptSelect.getAttribute('data-old-dept') : null;
    const oldCourseId = deptSelect ? deptSelect.getAttribute('data-old-course') : null;

    function loadCourses(departmentId, selectedCourseId = null) {
        if (!departmentId) {
            courseSelect.innerHTML = '<option value="">Select Department First</option>';
            courseSelect.disabled = true;
            return;
        }

        courseSelect.innerHTML = '<option value="">Loading courses...</option>';
        courseSelect.disabled = true;

        fetch(`/api/departments/${departmentId}/courses`)
            .then(res => res.json())
            .then(data => {
                courseSelect.innerHTML = '<option value="">Select Course</option>';
                data.forEach(course => {
                    let option = document.createElement('option');
                    option.value = course.id;
                    option.textContent = course.name;
                    if (selectedCourseId && String(course.id) === String(selectedCourseId)) {
                        option.selected = true;
                    }
                    courseSelect.appendChild(option);
                });
                courseSelect.disabled = false;
            })
            .catch(err => {
                console.error('Course load error:', err);
                courseSelect.innerHTML = '<option value="">Error loading courses</option>';
            });
    }

    if (deptSelect) {
        deptSelect.addEventListener('change', function() {
            loadCourses(this.value);
        });

        if (deptSelect.value) {
            loadCourses(deptSelect.value, oldCourseId);
        }
    }
});