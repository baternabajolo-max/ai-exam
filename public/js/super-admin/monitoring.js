document.addEventListener('DOMContentLoaded', function () {
    const config = window.monitoringConfig;
    if (!config) {
        return;
    }

    const numberFormat = new Intl.NumberFormat();
    const elements = {
        status: document.getElementById('monitoring-status'),
        collegeChart: document.getElementById('college-monitoring-chart'),
        collegeActions: document.getElementById('college-monitoring-actions'),
        collegeError: document.getElementById('college-monitoring-error'),
        collegeEmpty: document.getElementById('college-monitoring-empty'),
        collegeTab: document.getElementById('college-monitoring-tab'),
        programTab: document.getElementById('program-monitoring-tab'),
        collegePanel: document.getElementById('college-monitoring-panel'),
        programPanel: document.getElementById('program-monitoring-panel'),
        programTitle: document.getElementById('program-monitoring-title'),
        programDescription: document.getElementById('program-monitoring-description'),
        programChart: document.getElementById('program-monitoring-chart'),
        programActions: document.getElementById('program-monitoring-actions'),
        programLoading: document.getElementById('program-monitoring-loading'),
        programError: document.getElementById('program-monitoring-error'),
        programEmpty: document.getElementById('program-monitoring-empty'),
        detailsSection: document.getElementById('program-contribution-section'),
        detailsTitle: document.getElementById('program-contribution-title'),
        detailsSummary: document.getElementById('program-contribution-summary'),
        detailsLoading: document.getElementById('program-details-loading'),
        detailsError: document.getElementById('program-details-error'),
        facultyRows: document.getElementById('faculty-contribution-rows'),
        facultySearch: document.getElementById('faculty-contribution-search'),
        facultySort: document.getElementById('faculty-contribution-sort'),
        facultyCount: document.getElementById('faculty-contribution-count'),
        facultyEmpty: document.getElementById('faculty-contribution-empty'),
        unattributedNote: document.getElementById('faculty-unattributed-note'),
        monthlyScope: document.getElementById('monthly-contributions-scope'),
        monthlyChart: document.getElementById('monthly-contributions-chart'),
        monthlyEmpty: document.getElementById('monthly-contributions-empty'),
        questionTrend: document.getElementById('monitoring-question-trend'),
        collegeCrumb: document.getElementById('monitoring-college-crumb'),
        programSeparator: document.getElementById('monitoring-program-separator'),
        programCrumb: document.getElementById('monitoring-program-crumb'),
        modal: document.getElementById('faculty-contribution-modal'),
        modalTitle: document.getElementById('faculty-contribution-modal-title'),
        modalContext: document.getElementById('faculty-contribution-context'),
        modalStatus: document.getElementById('faculty-contribution-modal-status'),
        modalQuestionCount: document.getElementById('faculty-modal-question-count'),
        modalFolderCount: document.getElementById('faculty-modal-folder-count'),
        modalTotalFolderCount: document.getElementById('faculty-modal-total-folder-count'),
        modalFolders: document.getElementById('faculty-modal-folders'),
        modalEmpty: document.getElementById('faculty-modal-empty'),
        facultyMessageToggle: document.getElementById('faculty-message-toggle'),
        facultyMessageForm: document.getElementById('faculty-message-form'),
        facultyMessageInput: document.getElementById('faculty-message-input'),
        facultyMessageCourseId: document.getElementById('faculty-message-course-id'),
        facultyMessageStatus: document.getElementById('faculty-message-status'),
        facultyMessageSend: document.getElementById('faculty-message-send'),
        facultyMessageCancel: document.getElementById('faculty-message-cancel'),
        facultyViewQuestions: document.getElementById('faculty-view-questions'),
    };
    const state = {
        colleges: config.colleges || [],
        programs: [],
        faculty: [],
        selectedCollege: null,
        selectedProgram: null,
        selectedFacultyId: null,
        monthlyChart: null,
        collegeChart: null,
        programChart: null,
        modalRequestId: 0,
    };
    const requestControllers = new Map();
    const programCache = new Map();
    const programDetailsCache = new Map();
    const facultyContributionCache = new Map();

    function setError(element, message) {
        element.textContent = message || '';
        element.hidden = !message;
    }

    function setBusy(element, isBusy) {
        element.hidden = !isBusy;
    }

    function abortRequest(key) {
        requestControllers.get(key)?.abort();
        requestControllers.delete(key);
    }

    function replaceId(template, id) {
        return template.replace('__ID__', encodeURIComponent(String(id)));
    }

    async function fetchJson(key, url) {
        requestControllers.get(key)?.abort();
        const controller = new AbortController();
        requestControllers.set(key, controller);

        try {
            const response = await fetch(url, {
                credentials: 'same-origin',
                cache: 'no-store',
                headers: { Accept: 'application/json' },
                signal: controller.signal,
            });
            if (!response.ok) {
                throw new Error(response.status === 404
                    ? 'This monitoring information is no longer available.'
                    : 'Monitoring data could not be loaded. Please try again.');
            }

            const result = await response.json();
            return requestControllers.get(key) === controller ? result : null;
        } finally {
            if (requestControllers.get(key) === controller) {
                requestControllers.delete(key);
            }
        }
    }

    function averageLinePlugin(average) {
        return {
            id: 'monitoringAverageLine',
            afterDatasetsDraw: function (chart) {
                if (!average || !chart.chartArea || !chart.scales.x) {
                    return;
                }

                const x = chart.scales.x.getPixelForValue(average);
                const { ctx, chartArea } = chart;
                ctx.save();
                ctx.beginPath();
                ctx.setLineDash([5, 5]);
                ctx.strokeStyle = '#d69e12';
                ctx.lineWidth = 2;
                ctx.moveTo(x, chartArea.top);
                ctx.lineTo(x, chartArea.bottom);
                ctx.stroke();
                ctx.restore();
            },
        };
    }

    function renderBarChart(canvas, records, chartKey, selectedId, onSelect) {
        const currentChart = state[chartKey];
        if (currentChart) {
            currentChart.destroy();
            state[chartKey] = null;
        }

        const wrapper = canvas.parentElement;
        wrapper.style.height = `${Math.max(300, records.length * 42 + 70)}px`;
        if (!window.Chart || records.length === 0) {
            return;
        }

        const values = records.map((record) => Number(record.question_count) || 0);
        const average = values.reduce((total, value) => total + value, 0) / values.length;
        const maximum = Math.max(...values);
        const animation = window.matchMedia('(prefers-reduced-motion: reduce)').matches
            ? false
            : { duration: 420, delay: (context) => context.type === 'data' ? context.dataIndex * 35 : 0 };

        state[chartKey] = new Chart(canvas, {
            type: 'bar',
            plugins: [averageLinePlugin(average)],
            data: {
                labels: records.map((record) => record.name),
                datasets: [{
                    data: values,
                    minBarLength: 4,
                    borderRadius: 7,
                    borderSkipped: false,
                    backgroundColor: records.map((record) => {
                        if (String(record.id) === String(selectedId)) {
                            return '#d69e12';
                        }

                        return Number(record.question_count) === maximum ? '#4a0e17' : '#8f2f3f';
                    }),
                    hoverBackgroundColor: '#6b1825',
                    barPercentage: .72,
                    categoryPercentage: .82,
                }],
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                animation,
                onClick: function (event, _activeElements, chart) {
                    const target = chart.getElementsAtEventForMode(
                        event,
                        'nearest',
                        { intersect: true },
                        true,
                    )[0];
                    if (target) {
                        onSelect(records[target.index]);
                    }
                },
                onHover: function (event, activeElements) {
                    event.native.target.style.cursor = activeElements.length ? 'pointer' : 'default';
                },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: (context) => `${numberFormat.format(context.parsed.x)} questions`,
                            afterBody: () => [`Average: ${numberFormat.format(average)} questions`],
                        },
                    },
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        ticks: { precision: 0 },
                        title: { display: true, text: 'Question Bank questions' },
                        grid: { color: '#edf0f4' },
                    },
                    y: { grid: { display: false } },
                },
            },
        });
    }

    function renderSelectionButtons(container, records, selectedId, onSelect) {
        container.replaceChildren();
        records.forEach((record) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = `monitoring-chart-action${String(record.id) === String(selectedId) ? ' is-selected' : ''}`;
            button.setAttribute('aria-pressed', String(String(record.id) === String(selectedId)));

            const name = document.createElement('span');
            name.textContent = record.name;
            const count = document.createElement('span');
            count.className = 'monitoring-chart-action-count';
            count.textContent = numberFormat.format(Number(record.question_count) || 0);
            button.append(name, count);
            button.addEventListener('click', () => onSelect(record));
            container.append(button);
        });
    }

    function renderColleges() {
        const records = state.colleges;
        elements.collegeEmpty.hidden = records.length > 0;
        renderBarChart(elements.collegeChart, records, 'collegeChart', state.selectedCollege?.id, selectCollege);
        renderSelectionButtons(elements.collegeActions, records, state.selectedCollege?.id, selectCollege);
    }

    function renderPrograms() {
        const records = state.programs;
        elements.programEmpty.hidden = records.length > 0;
        elements.programDescription.textContent = state.selectedCollege
            ? `Select a program in ${state.selectedCollege.name} to see its folders and faculty contributions.`
            : 'Select a program to see its folders and faculty contributions.';
        renderBarChart(elements.programChart, records, 'programChart', state.selectedProgram?.id, selectProgram);
        renderSelectionButtons(elements.programActions, records, state.selectedProgram?.id, selectProgram);
    }

    function updateBreadcrumb() {
        elements.collegeCrumb.hidden = !state.selectedCollege;
        elements.collegeCrumb.textContent = state.selectedCollege?.name || '';
        elements.programSeparator.hidden = !state.selectedProgram;
        elements.programCrumb.hidden = !state.selectedProgram;
        elements.programCrumb.textContent = state.selectedProgram?.name || '';
    }

    function setMonitoringTab(tabName, focusTab = false) {
        if (tabName === 'programs' && !state.selectedCollege) {
            return;
        }

        const isCollegeTab = tabName === 'colleges';
        elements.programTab.disabled = !state.selectedCollege;
        elements.programTab.setAttribute('aria-disabled', String(!state.selectedCollege));
        elements.collegeTab.setAttribute('aria-selected', String(isCollegeTab));
        elements.programTab.setAttribute('aria-selected', String(!isCollegeTab));
        elements.collegeTab.tabIndex = isCollegeTab ? 0 : -1;
        elements.programTab.tabIndex = isCollegeTab ? -1 : 0;
        elements.collegeTab.classList.toggle('is-active', isCollegeTab);
        elements.programTab.classList.toggle('is-active', !isCollegeTab);
        elements.collegePanel.hidden = !isCollegeTab;
        elements.programPanel.hidden = isCollegeTab;

        if (focusTab) {
            (isCollegeTab ? elements.collegeTab : elements.programTab).focus();
        }
    }

    function renderMonthlyContributions(data) {
        const values = data.values || [];
        const hasQuestions = values.some((value) => Number(value) > 0);
        elements.monthlyEmpty.hidden = hasQuestions;
        elements.monthlyChart.hidden = !hasQuestions;

        if (state.monthlyChart) {
            state.monthlyChart.destroy();
            state.monthlyChart = null;
        }
        if (!hasQuestions) {
            elements.questionTrend.textContent = 'No faculty-attributed questions this period';
            return;
        }

        const average = values.reduce((total, value) => total + Number(value || 0), 0) / values.length;
        const delta = Number(data.month_delta) || 0;
        const deltaLabel = delta > 0
            ? `↑ ${numberFormat.format(delta)} vs last month`
            : (delta < 0 ? `↓ ${numberFormat.format(Math.abs(delta))} vs last month` : 'No change vs last month');
        elements.questionTrend.textContent = `${numberFormat.format(data.current_month_count)} this month · ${deltaLabel}`;

        if (!window.Chart) {
            elements.monthlyChart.hidden = true;
            elements.monthlyEmpty.hidden = false;
            elements.monthlyEmpty.textContent = 'Monthly contribution data is available, but the chart could not load.';
            return;
        }

        const animation = window.matchMedia('(prefers-reduced-motion: reduce)').matches
            ? false
            : { duration: 500 };
        state.monthlyChart = new Chart(elements.monthlyChart, {
            type: 'line',
            data: {
                labels: data.labels,
                datasets: [
                    {
                        label: 'Questions added',
                        data: values,
                        borderColor: '#4a0e17',
                        backgroundColor: 'rgba(74, 14, 23, .10)',
                        pointBackgroundColor: '#fff',
                        pointBorderColor: '#4a0e17',
                        pointBorderWidth: 2,
                        pointRadius: 3,
                        pointHoverRadius: 6,
                        fill: true,
                        tension: .28,
                    },
                    {
                        label: '12-month average',
                        data: values.map(() => average),
                        borderColor: '#d69e12',
                        borderDash: [5, 5],
                        borderWidth: 2,
                        pointRadius: 0,
                        fill: false,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation,
                interaction: { intersect: false, mode: 'index' },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: (context) => `${context.dataset.label}: ${numberFormat.format(context.parsed.y)} questions`,
                        },
                    },
                },
                scales: {
                    x: { grid: { display: false } },
                    y: {
                        beginAtZero: true,
                        ticks: { precision: 0 },
                        title: { display: true, text: 'Questions added' },
                        grid: { color: '#edf0f4' },
                    },
                },
            },
        });
    }

    function updateMonthlyContributions(data, scope) {
        renderMonthlyContributions(data.monthly_contributions || data || { labels: [], values: [] });
        const context = scope || 'all colleges';
        elements.monthlyScope.textContent = `Question Bank questions attributed to faculty by original creation date · ${context}.`;
    }

    function sortedFaculty() {
        const search = elements.facultySearch.value.trim().toLocaleLowerCase();
        const direction = elements.facultySort.value === 'least' ? 1 : -1;

        return state.faculty
            .filter((faculty) => faculty.name.toLocaleLowerCase().includes(search))
            .sort((left, right) => {
                const countDifference = (Number(left.question_count) - Number(right.question_count)) * direction;
                return countDifference || left.name.localeCompare(right.name);
            });
    }

    function renderFacultyTable() {
        const faculty = sortedFaculty();
        elements.facultyRows.replaceChildren();
        elements.facultyCount.textContent = `${numberFormat.format(faculty.length)} shown`;
        elements.facultyEmpty.hidden = state.faculty.length > 0;

        if (state.faculty.length > 0 && faculty.length === 0) {
            const row = document.createElement('tr');
            const cell = document.createElement('td');
            cell.colSpan = 4;
            cell.className = 'text-center text-secondary py-4';
            cell.textContent = 'No faculty match this search.';
            row.append(cell);
            elements.facultyRows.append(row);
            return;
        }

        faculty.forEach((member) => {
            const row = document.createElement('tr');
            const nameCell = document.createElement('th');
            nameCell.scope = 'row';
            nameCell.className = 'fw-semibold text-dark';
            nameCell.textContent = member.name;

            const countCell = document.createElement('td');
            countCell.className = 'monitoring-question-count';
            countCell.textContent = numberFormat.format(Number(member.question_count) || 0);

            const foldersCell = document.createElement('td');
            const contributedFolders = Array.isArray(member.folders) ? member.folders : [];
            if (contributedFolders.length === 0) {
                const empty = document.createElement('span');
                empty.className = 'monitoring-no-folder-contributions';
                empty.textContent = 'No contributions yet';
                foldersCell.append(empty);
            } else {
                const folderBadges = document.createElement('div');
                folderBadges.className = 'monitoring-faculty-folders';
                contributedFolders.forEach((folder) => {
                    const badge = document.createElement('span');
                    badge.className = 'monitoring-faculty-folder';
                    badge.textContent = `${folder.name} · ${numberFormat.format(Number(folder.question_count) || 0)}`;
                    folderBadges.append(badge);
                });
                foldersCell.append(folderBadges);
            }

            const actionCell = document.createElement('td');
            actionCell.className = 'text-end';
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'btn btn-sm btn-outline-primary rounded-pill monitoring-view-contribution';
            button.dataset.facultyId = member.id;
            button.textContent = 'View details';
            button.setAttribute('aria-label', `View ${member.name}'s contribution details`);
            actionCell.append(button);
            row.append(nameCell, countCell, foldersCell, actionCell);
            elements.facultyRows.append(row);
        });
    }

    function renderProgramDetails(data) {
        elements.detailsTitle.textContent = data.program.name;
        elements.detailsSummary.textContent = `${data.program.college_name} · ${numberFormat.format(data.program.question_count)} total Question Bank questions`;
        state.faculty = data.faculty || [];
        renderFacultyTable();
        const unattributed = Number(data.unlisted_creator_question_count) || 0;
        elements.unattributedNote.hidden = unattributed === 0;
        elements.unattributedNote.textContent = unattributed > 0
            ? `${numberFormat.format(unattributed)} question${unattributed === 1 ? ' has' : 's have'} no creator listed among this college's Faculty.`
            : '';
        updateMonthlyContributions(data, `${data.program.college_name} · ${data.program.name}`);
    }

    async function selectCollege(college, requestedProgram = null) {
        if (state.selectedCollege?.id === college.id && !requestedProgram && !elements.programLoading.hidden) {
            return;
        }

        state.selectedCollege = college;
        state.selectedProgram = null;
        state.programs = [];
        state.faculty = [];
        abortRequest('program-details');
        updateBreadcrumb();
        renderColleges();
        setMonitoringTab('programs');
        elements.detailsSection.hidden = true;
        elements.programTitle.textContent = `Programs · ${college.name}`;
        if (state.programChart) {
            state.programChart.destroy();
            state.programChart = null;
        }
        elements.programActions.replaceChildren();
        elements.programEmpty.hidden = true;
        setError(elements.programError, '');
        setBusy(elements.programLoading, true);
        elements.status.textContent = `Loading academic programs for ${college.name}.`;
        updateMonthlyContributions(
            programCache.get(String(college.id)) || {
                monthly_contributions: { labels: [], values: [] },
            },
            college.name,
        );

        try {
            let data = programCache.get(String(college.id));
            if (!data) {
                data = await fetchJson(
                    'programs',
                    replaceId(config.programsUrlTemplate, college.id),
                );
                if (!data) {
                    return;
                }
                programCache.set(String(college.id), data);
            }
            if (String(state.selectedCollege?.id) !== String(college.id)) {
                return;
            }

            state.programs = data.programs || [];
            elements.programTitle.textContent = `Programs · ${data.college.name}`;
            renderPrograms();
            updateMonthlyContributions(data, data.college.name);
            elements.status.textContent = `${state.programs.length} academic programs loaded for ${data.college.name}.`;

            if (requestedProgram) {
                const program = state.programs.find((record) => String(record.id) === String(requestedProgram));
                if (program) {
                    await selectProgram(program);
                } else {
                    setError(elements.programError, 'The requested program is not part of this college.');
                }
            }
        } catch (error) {
            if (error.name !== 'AbortError') {
                setError(elements.programError, error.message || 'Programs could not be loaded. Please try again.');
                elements.status.textContent = 'Programs could not be loaded.';
            }
        } finally {
            if (String(state.selectedCollege?.id) === String(college.id)) {
                setBusy(elements.programLoading, false);
            }
        }
    }

    async function selectProgram(program) {
        if (!state.selectedCollege) {
            return;
        }

        state.selectedProgram = program;
        updateBreadcrumb();
        renderPrograms();
        elements.detailsSection.hidden = false;
        elements.detailsTitle.textContent = program.name;
        elements.detailsSummary.textContent = '';
        elements.facultyRows.replaceChildren();
        elements.facultyEmpty.hidden = true;
        elements.unattributedNote.hidden = true;
        setError(elements.detailsError, '');
        setBusy(elements.detailsLoading, true);
        elements.status.textContent = `Loading Question Bank folders and faculty contributions for ${program.name}.`;

        try {
            let data = programDetailsCache.get(String(program.id));
            if (!data) {
                data = await fetchJson(
                    'program-details',
                    replaceId(config.programDetailsUrlTemplate, program.id),
                );
                if (!data) {
                    return;
                }
                programDetailsCache.set(String(program.id), data);
            }
            if (String(state.selectedProgram?.id) !== String(program.id)) {
                return;
            }

            renderProgramDetails(data);
            elements.status.textContent = `Folders and faculty contributions loaded for ${program.name}.`;
        } catch (error) {
            if (error.name !== 'AbortError') {
                setError(elements.detailsError, error.message || 'Program contributions could not be loaded. Please try again.');
                elements.status.textContent = 'Program contributions could not be loaded.';
            }
        } finally {
            if (String(state.selectedProgram?.id) === String(program.id)) {
                setBusy(elements.detailsLoading, false);
            }
        }
    }

    function facultyQuestionsUrl(facultyId) {
        const url = new URL(config.questionBankUrl, window.location.origin);
        url.searchParams.set('department_id', state.selectedCollege.id);
        url.searchParams.set('course_id', state.selectedProgram.id);
        url.searchParams.set('faculty_id', facultyId);
        return url.toString();
    }

    async function showFacultyContribution(facultyId) {
        if (!state.selectedProgram || !window.bootstrap) {
            return;
        }

        const programId = state.selectedProgram.id;
        state.selectedFacultyId = facultyId;
        const cacheKey = `${facultyId}:${programId}`;
        const modal = window.bootstrap.Modal.getOrCreateInstance(elements.modal);
        elements.modalTitle.textContent = 'Faculty member';
        elements.modalContext.textContent = '';
        elements.modalStatus.textContent = 'Loading contribution details…';
        elements.modalStatus.className = 'small text-secondary';
        elements.modalQuestionCount.textContent = '—';
        elements.modalFolderCount.textContent = '—';
        elements.modalTotalFolderCount.textContent = '—';
        elements.modalFolders.replaceChildren();
        elements.modalEmpty.hidden = true;
        elements.facultyMessageForm.reset();
        elements.facultyMessageCourseId.value = programId;
        elements.facultyMessageForm.hidden = true;
        elements.facultyMessageToggle.setAttribute('aria-expanded', 'false');
        elements.facultyMessageStatus.textContent = '';
        elements.facultyViewQuestions.href = facultyQuestionsUrl(facultyId);
        modal.show();

        const requestId = ++state.modalRequestId;
        try {
            let data = facultyContributionCache.get(cacheKey);
            if (!data) {
                const url = `${replaceId(config.facultyContributionsUrlTemplate, facultyId)}?course_id=${encodeURIComponent(programId)}`;
                data = await fetchJson('faculty-contributions', url);
                if (!data) {
                    return;
                }
                facultyContributionCache.set(cacheKey, data);
            }
            if (requestId !== state.modalRequestId) {
                return;
            }

            elements.modalTitle.textContent = data.faculty.name;
            elements.modalContext.textContent = `${data.college} · ${data.program}`;
            elements.modalQuestionCount.textContent = numberFormat.format(data.total_questions);
            elements.modalFolderCount.textContent = numberFormat.format(data.folder_count);
            elements.modalTotalFolderCount.textContent = numberFormat.format(data.total_folder_count);
            elements.modalStatus.textContent = '';
            elements.modalEmpty.hidden = data.folders.length > 0;
            data.folders.forEach((folder) => {
                const item = document.createElement('article');
                item.className = `monitoring-folder-card${folder.has_contribution ? '' : ' is-uncovered'}`;
                const icon = document.createElement('span');
                icon.className = 'monitoring-folder-icon';
                icon.setAttribute('aria-hidden', 'true');
                const iconElement = document.createElement('i');
                iconElement.className = 'bi bi-folder2-open fs-5';
                icon.append(iconElement);

                const heading = document.createElement('div');
                heading.className = 'monitoring-folder-card-heading';
                const content = document.createElement('div');
                content.className = 'min-w-0 flex-grow-1';
                const name = document.createElement('h4');
                name.className = 'monitoring-folder-name mb-0';
                name.textContent = folder.name;
                const count = document.createElement('p');
                count.className = 'monitoring-folder-count mb-0';
                count.textContent = `${numberFormat.format(Number(folder.question_count) || 0)} question${Number(folder.question_count) === 1 ? '' : 's'}`;
                content.append(name, count);
                heading.append(icon, content);
                item.append(heading);

                if (!folder.has_contribution) {
                    const uncovered = document.createElement('span');
                    uncovered.className = 'monitoring-folder-uncovered';
                    uncovered.textContent = 'No faculty contributions yet';
                    item.append(uncovered);
                }
                elements.modalFolders.append(item);
            });
        } catch (error) {
            if (error.name !== 'AbortError' && requestId === state.modalRequestId) {
                elements.modalStatus.textContent = error.message || 'Contribution details could not be loaded. Please try again.';
                elements.modalStatus.className = 'small text-danger';
            }
        }
    }

    async function sendFacultyMessage(event) {
        event.preventDefault();
        const message = elements.facultyMessageInput.value.trim();
        if (!message) {
            elements.facultyMessageStatus.textContent = 'Enter a message before sending.';
            elements.facultyMessageStatus.className = 'small text-danger';
            elements.facultyMessageInput.focus();
            return;
        }
        if (!state.selectedFacultyId || !state.selectedProgram) {
            elements.facultyMessageStatus.textContent = 'Select a faculty member and program before sending.';
            elements.facultyMessageStatus.className = 'small text-danger';
            return;
        }

        const controller = new AbortController();
        requestControllers.get('faculty-message')?.abort();
        requestControllers.set('faculty-message', controller);
        const formData = new FormData(elements.facultyMessageForm);
        formData.set('course_id', String(state.selectedProgram.id));
        formData.set('message', message);
        elements.facultyMessageSend.disabled = true;
        elements.facultyMessageStatus.textContent = 'Sending message…';
        elements.facultyMessageStatus.className = 'small text-secondary';

        try {
            const response = await fetch(
                replaceId(config.facultyMessageUrlTemplate, state.selectedFacultyId),
                {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': String(formData.get('_token') || ''),
                    },
                    body: formData,
                    signal: controller.signal,
                },
            );
            const result = await response.json();
            if (!response.ok) {
                const validationMessage = result.errors
                    ? Object.values(result.errors).flat()[0]
                    : null;
                throw new Error(validationMessage || result.message || 'The message could not be sent. Please try again.');
            }

            elements.facultyMessageStatus.textContent = result.message;
            elements.facultyMessageStatus.className = 'small text-success';
            elements.modalStatus.textContent = result.message;
            elements.modalStatus.className = 'small text-success';
            elements.facultyMessageInput.value = '';
        } catch (error) {
            if (error.name !== 'AbortError') {
                const messageText = error.message || 'The message could not be sent. Please try again.';
                elements.facultyMessageStatus.textContent = messageText;
                elements.facultyMessageStatus.className = 'small text-danger';
            }
        } finally {
            if (requestControllers.get('faculty-message') === controller) {
                requestControllers.delete('faculty-message');
                elements.facultyMessageSend.disabled = false;
            }
        }
    }

    function setMessageComposerVisible(isVisible) {
        elements.facultyMessageForm.hidden = !isVisible;
        elements.facultyMessageToggle.setAttribute('aria-expanded', String(isVisible));
        if (isVisible) {
            elements.facultyMessageInput.focus();
        }
    }

    function returnToCollegeOverview() {
        abortRequest('programs');
        abortRequest('program-details');
        abortRequest('faculty-contributions');
        state.modalRequestId++;
        state.selectedCollege = null;
        state.selectedProgram = null;
        state.programs = [];
        state.faculty = [];
        updateBreadcrumb();
        renderColleges();
        setMonitoringTab('colleges');
        elements.detailsSection.hidden = true;
        updateMonthlyContributions(
            config.allCollegesMonthlyContributions || config.monthlyContributions || { labels: [], values: [] },
            'all colleges',
        );
        elements.status.textContent = 'Showing all colleges.';
    }

    function returnToPrograms() {
        if (!state.selectedCollege) {
            return;
        }

        abortRequest('program-details');
        abortRequest('faculty-contributions');
        state.modalRequestId++;
        state.selectedProgram = null;
        state.faculty = [];
        updateBreadcrumb();
        renderPrograms();
        setMonitoringTab('programs');
        elements.detailsSection.hidden = true;
        const data = programCache.get(String(state.selectedCollege.id));
        if (data) {
            updateMonthlyContributions(data, state.selectedCollege.name);
        }
        elements.status.textContent = `Showing programs in ${state.selectedCollege.name}.`;
    }

    elements.facultySearch.addEventListener('input', renderFacultyTable);
    elements.facultySort.addEventListener('change', renderFacultyTable);
    elements.facultyRows.addEventListener('click', function (event) {
        const button = event.target.closest('[data-faculty-id]');
        if (button) {
            showFacultyContribution(button.dataset.facultyId);
        }
    });
    document.getElementById('monitoring-home-link').addEventListener('click', returnToCollegeOverview);
    document.getElementById('monitoring-back-to-programs').addEventListener('click', returnToPrograms);
    elements.facultyMessageToggle.addEventListener('click', function () {
        setMessageComposerVisible(elements.facultyMessageForm.hidden);
    });
    elements.facultyMessageCancel.addEventListener('click', function () {
        elements.facultyMessageForm.reset();
        elements.facultyMessageCourseId.value = state.selectedProgram?.id || '';
        elements.facultyMessageStatus.textContent = '';
        elements.facultyMessageStatus.className = 'small text-secondary';
        setMessageComposerVisible(false);
    });
    elements.facultyMessageForm.addEventListener('submit', sendFacultyMessage);
    elements.collegeTab.addEventListener('click', returnToCollegeOverview);
    elements.programTab.addEventListener('click', function () {
        if (state.selectedCollege) {
            setMonitoringTab('programs');
            elements.status.textContent = `Showing programs in ${state.selectedCollege.name}.`;
        }
    });
    const monitoringTabs = [elements.collegeTab, elements.programTab];
    monitoringTabs.forEach((tab) => {
        tab.addEventListener('keydown', function (event) {
            if (event.key !== 'ArrowLeft' && event.key !== 'ArrowRight') {
                return;
            }

            const availableTabs = monitoringTabs.filter((candidate) => !candidate.disabled);
            const currentIndex = availableTabs.indexOf(tab);
            const direction = event.key === 'ArrowRight' ? 1 : -1;
            const nextTab = availableTabs[(currentIndex + direction + availableTabs.length) % availableTabs.length];
            event.preventDefault();
            nextTab.focus();
            nextTab.click();
        });
    });
    elements.modal.addEventListener('hidden.bs.modal', function () {
        abortRequest('faculty-contributions');
        abortRequest('faculty-message');
        state.modalRequestId++;
        state.selectedFacultyId = null;
        elements.facultyMessageSend.disabled = false;
    });

    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach((element) => {
        if (window.bootstrap?.Tooltip) {
            window.bootstrap.Tooltip.getOrCreateInstance(element);
        }
    });

    renderColleges();
    renderMonthlyContributions(config.monthlyContributions || { labels: [], values: [] });

    if (!window.Chart) {
        setError(elements.collegeError, 'Interactive charts could not load. Use the college and program buttons below each chart, or reload the page.');
    }

    if (config.selectedCollegeId) {
        const college = state.colleges.find((record) => String(record.id) === String(config.selectedCollegeId));
        if (college) {
            selectCollege(college, config.selectedProgramId);
        }
    }
});
