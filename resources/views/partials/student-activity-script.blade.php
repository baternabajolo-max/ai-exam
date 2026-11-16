<script>
    document.addEventListener('DOMContentLoaded', function () {
        const root = document.getElementById('student-activity-analytics');
        if (!root) {
            return;
        }

        const analyticsUrl = root.dataset.analyticsUrl;
        const breadcrumbs = document.getElementById('activity-breadcrumbs');
        const view = document.getElementById('activity-view');
        const title = document.getElementById('activity-view-title');
        const total = document.getElementById('activity-total');
        const error = document.getElementById('activity-error');
        const quizModalElement = document.getElementById('activity-topic-quizzes-modal');
        const quizModalTitle = document.getElementById('activity-topic-quizzes-title');
        const quizList = document.getElementById('activity-topic-quizzes-list');
        document.body.append(quizModalElement);
        const quizModal = new bootstrap.Modal(quizModalElement);
        const weekdays = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
        const selection = { month: null, date: null, module: null };
        let quizHistoryRequest = null;

        function formatDuration(seconds) {
            const hours = Math.floor(seconds / 3600);
            const minutes = Math.floor((seconds % 3600) / 60);
            return hours > 0 ? `${hours}h ${minutes}m` : `${minutes}m`;
        }

        function makeElement(tagName, className, text) {
            const element = document.createElement(tagName);
            if (className) {
                element.className = className;
            }
            if (text !== undefined) {
                element.textContent = text;
            }

            return element;
        }

        async function showTopicQuizzes(topic) {
            if (quizHistoryRequest) {
                quizHistoryRequest.abort();
            }

            quizHistoryRequest = new AbortController();
            quizModalTitle.textContent = `${topic.title} · Recent quizzes`;
            quizList.replaceChildren(makeElement('p', 'activity-muted small mb-0', 'Loading quiz history…'));
            quizModal.show();

            try {
                const url = root.dataset.topicQuizzesUrl.replace('__SUBTOPIC__', encodeURIComponent(topic.id));
                const response = await fetch(url, {
                    headers: { Accept: 'application/json' },
                    credentials: 'same-origin',
                    signal: quizHistoryRequest.signal,
                });
                const data = await response.json();
                if (!response.ok) {
                    throw new Error(data.message || 'Unable to load this topic’s quiz history.');
                }

                quizList.replaceChildren();
                if (data.quizzes.length === 0) {
                    quizList.append(makeElement('p', 'activity-muted small mb-0', 'No quizzes have been taken for this topic yet.'));
                    return;
                }

                data.quizzes.forEach(function (quiz) {
                    const item = makeElement('article', 'activity-quiz-history-item');
                    const details = makeElement('div');
                    const date = new Date(quiz.created_at).toLocaleDateString(undefined, {
                        year: 'numeric',
                        month: 'short',
                        day: 'numeric',
                    });
                    details.append(
                        makeElement('h3', 'h6 fw-bold mb-1', quiz.title),
                        makeElement('p', 'activity-muted small mb-0', `${date} · ${quiz.score}/${quiz.total_questions} correct`),
                    );
                    const score = makeElement('span', 'activity-status', `${quiz.percentage}%`);
                    if (quiz.url) {
                        const link = makeElement('a', 'btn btn-sm btn-outline-primary rounded-pill', 'View');
                        link.href = quiz.url;
                        link.setAttribute('aria-label', `View ${quiz.title} result from ${date}`);
                        item.append(details, score, link);
                    } else {
                        item.append(details, score);
                    }
                    quizList.append(item);
                });
            } catch (loadError) {
                if (loadError.name !== 'AbortError') {
                    quizList.replaceChildren(makeElement('p', 'text-danger small mb-0', loadError.message || 'Quiz history could not be loaded. Please try again.'));
                }
            }
        }

        function addBreadcrumb(label, isCurrent, onClick) {
            if (breadcrumbs.childElementCount > 0) {
                breadcrumbs.append(makeElement('span', 'activity-muted', '›'));
            }

            const crumb = makeElement('button', 'activity-crumb', label);
            crumb.type = 'button';
            crumb.setAttribute('aria-current', isCurrent ? 'page' : 'false');
            crumb.disabled = isCurrent;
            crumb.addEventListener('click', onClick);
            breadcrumbs.append(crumb);
        }

        function renderBreadcrumbs() {
            breadcrumbs.replaceChildren();
            addBreadcrumb('All Months', !selection.month, function () {
                selection.month = null;
                selection.date = null;
                selection.module = null;
                loadActivity(true);
            });

            if (selection.month) {
                const monthLabel = new Date(`${selection.month}-01T00:00:00Z`).toLocaleDateString(undefined, {
                    month: 'long',
                    year: 'numeric',
                    timeZone: 'UTC',
                });
                addBreadcrumb(monthLabel, !selection.date, function () {
                    selection.date = null;
                    selection.module = null;
                    loadActivity(true);
                });
            }

            if (selection.date) {
                addBreadcrumb(new Date(`${selection.date}T00:00:00Z`).toLocaleDateString(undefined, {
                    month: 'long',
                    day: 'numeric',
                    timeZone: 'UTC',
                }), !selection.module, function () {
                    selection.module = null;
                    loadActivity(true);
                });
            }

            if (selection.module) {
                addBreadcrumb(selection.module.title, true, function () {});
            }
        }

        function renderMonths(data) {
            title.textContent = 'Monthly active study time';
            const chart = makeElement('div', 'activity-chart-scroll');
            const monthBars = makeElement('div', 'activity-month-chart');
            const maxSeconds = Math.max(0, ...data.months.map(function (month) {
                return Number(month.active_seconds) || 0;
            }));
            const totalSeconds = data.months.reduce(function (sum, month) {
                return sum + (Number(month.active_seconds) || 0);
            }, 0);
            total.textContent = `Total: ${formatDuration(totalSeconds)}`;

            const useHours = maxSeconds >= 3600;
            const axisUnit = useHours ? 3600 : 60;
            const maxValue = maxSeconds / axisUnit;
            const targetStep = Math.max(maxValue / 4, 0.1);
            const stepMagnitude = Math.pow(10, Math.floor(Math.log10(targetStep)));
            const normalizedStep = targetStep / stepMagnitude;
            const stepMultiplier = normalizedStep <= 1 ? 1 : (normalizedStep <= 2 ? 2 : (normalizedStep <= 5 ? 5 : 10));
            const step = stepMagnitude * stepMultiplier;
            const intervalCount = maxValue > 0 ? Math.ceil(maxValue / step) : 1;
            const axisMax = intervalCount * step;
            const plotHeight = 236;
            const axis = makeElement('div', 'activity-month-axis');
            const axisTitle = makeElement('span', 'activity-month-axis-title', useHours ? 'Hours' : 'Minutes');
            const yTicks = makeElement('div', 'activity-month-y-ticks');
            for (let index = 0; index <= intervalCount; index += 1) {
                const value = axisMax - (step * index);
                const tick = makeElement(
                    'span',
                    'activity-month-y-tick',
                    `${Number(value.toFixed(2))}${useHours ? 'h' : 'm'}`,
                );
                const position = intervalCount === 0 ? 100 : (index / intervalCount) * 100;
                tick.style.top = `${position}%`;
                tick.style.transform = index === 0 ? 'translateY(0)' : (index === intervalCount ? 'translateY(-100%)' : 'translateY(-50%)');
                yTicks.append(tick);
            }
            axis.append(axisTitle, yTicks);

            const plot = makeElement('div', 'activity-month-plot');
            const plotWidth = Math.max(560, data.months.length * 92);
            const monthColumnWidth = plotWidth / Math.max(1, data.months.length);
            plot.style.width = `${plotWidth}px`;
            const gridlines = makeElement('div', 'activity-month-gridlines');
            const columns = makeElement('div', 'activity-month-columns');
            for (let index = 0; index <= intervalCount; index += 1) {
                const line = makeElement('span', `activity-month-gridline${index === intervalCount ? ' is-baseline' : ''}`);
                line.style.top = `${(index / intervalCount) * 100}%`;
                gridlines.append(line);
            }

            data.months.forEach(function (month) {
                const button = makeElement('button', 'activity-month-button');
                button.type = 'button';
                button.style.flexBasis = `${monthColumnWidth}px`;
                const activeSeconds = Number(month.active_seconds) || 0;
                button.classList.toggle('is-highest', maxSeconds > 0 && activeSeconds === maxSeconds);
                button.setAttribute('aria-label', `${month.label}: ${formatDuration(activeSeconds)} active study time. Open calendar.`);
                button.title = `${month.label}: ${formatDuration(activeSeconds)} active study time`;
                const duration = makeElement('span', 'activity-month-seconds', formatDuration(activeSeconds));
                const fill = makeElement('span', 'activity-month-column');
                const barHeight = axisMax > 0 ? (activeSeconds / axisUnit / axisMax) * plotHeight : 0;
                fill.style.height = `${activeSeconds > 0 ? Math.max(4, barHeight) : 3}px`;
                duration.style.bottom = `${Math.max(0, barHeight) + 7}px`;
                const monthLabel = makeElement('span', 'activity-month-label', month.label);
                button.append(fill, duration, monthLabel);
                button.addEventListener('click', function () {
                    selection.month = month.month;
                    selection.date = null;
                    selection.module = null;
                    loadActivity(true);
                });
                columns.append(button);
            });

            plot.append(gridlines, columns);
            monthBars.append(axis, plot);
            chart.append(monthBars);
            chart.append(makeElement('p', 'activity-muted small mt-2 mb-0', 'Month (horizontal axis) · Active study time (vertical axis). Bar height is scaled to monthly hours or minutes. Select a month to open daily activity.'));
            view.replaceChildren(chart);
        }

        function renderDays(data) {
            title.textContent = `${data.label} activity`;
            const totalSeconds = data.days.reduce(function (sum, day) {
                return sum + day.active_seconds;
            }, 0);
            total.textContent = `Month total: ${formatDuration(totalSeconds)}`;
            const calendar = makeElement('div', 'activity-calendar');
            const legend = makeElement('div', 'activity-heatmap-legend');
            [
                ['is-level-0', 'No activity'],
                ['is-level-1', 'Low · under 2h'],
                ['is-level-2', 'Moderate · 2h to under 4.5h'],
                ['is-level-3', 'High · 4.5h or more'],
            ].forEach(function ([level, label]) {
                const key = makeElement('span', 'activity-heatmap-key');
                key.append(
                    makeElement('span', `activity-heatmap-swatch ${level}`),
                    makeElement('span', '', label),
                );
                legend.append(key);
            });

            weekdays.forEach(function (weekday) {
                calendar.append(makeElement('span', 'activity-weekday', weekday));
            });
            for (let index = 0; index < data.first_weekday; index += 1) {
                const emptyDay = makeElement('span', 'activity-day-empty');
                emptyDay.setAttribute('aria-hidden', 'true');
                calendar.append(emptyDay);
            }

            data.days.forEach(function (day) {
                const button = makeElement('button', 'activity-day', String(day.day));
                const hours = day.active_seconds / 3600;
                if (day.active_seconds >= 16200) {
                    button.classList.add('is-level-3');
                } else if (day.active_seconds >= 7200) {
                    button.classList.add('is-level-2');
                } else if (day.active_seconds > 0) {
                    button.classList.add('is-level-1');
                } else {
                    button.classList.add('is-level-0');
                }
                button.type = 'button';
                button.setAttribute('aria-label', `${day.date}: ${hours.toFixed(1)} hours of active study. Open modules.`);
                button.addEventListener('click', function () {
                    selection.date = day.date;
                    selection.module = null;
                    loadActivity(true);
                });
                calendar.append(button);
            });

            view.replaceChildren(legend, calendar);
        }

        function appendActivityBarList(items, createRow) {
            const list = makeElement('div', 'activity-bar-list activity-column-chart');
            const rows = items.map(function (item, index) {
                const row = createRow(item);
                row.hidden = index >= 10;
                list.append(row);

                return row;
            });

            if (rows.length > 10) {
                const hiddenCount = rows.length - 10;
                const showMore = makeElement('button', 'activity-show-more', `Show ${hiddenCount} more`);
                showMore.type = 'button';
                showMore.setAttribute('aria-expanded', 'false');
                showMore.addEventListener('click', function () {
                    const isExpanded = showMore.getAttribute('aria-expanded') === 'true';
                    rows.slice(10).forEach(function (row) {
                        row.hidden = isExpanded;
                    });
                    showMore.setAttribute('aria-expanded', String(!isExpanded));
                    showMore.textContent = isExpanded ? `Show ${hiddenCount} more` : 'Show less';
                });
                list.append(showMore);
            }

            view.replaceChildren(list);
        }

        function createActivityColumn(button, titleText, activeSeconds, maxSeconds) {
            const duration = formatDuration(activeSeconds);
            const value = makeElement('span', 'activity-column-value', duration);
            const plot = makeElement('span', 'activity-column-plot');
            const fill = makeElement('span', 'activity-column-fill');
            const label = makeElement('span', 'activity-column-title', titleText);
            const height = maxSeconds > 0 ? (activeSeconds / maxSeconds) * 100 : 0;

            fill.style.height = `${activeSeconds > 0 ? Math.max(3, height) : 0}%`;
            fill.classList.toggle('is-highest', activeSeconds > 0 && activeSeconds === maxSeconds);
            plot.setAttribute('role', 'img');
            plot.setAttribute('aria-label', `${titleText}: ${duration} active study time`);
            plot.append(fill);
            button.title = `${titleText}: ${duration} active study time`;
            button.append(value, plot, label);
        }

        function renderModules(data) {
            title.textContent = `Module activity for ${new Date(`${data.date}T00:00:00Z`).toLocaleDateString(undefined, {
                month: 'long',
                day: 'numeric',
                year: 'numeric',
                timeZone: 'UTC',
            })}`;
            const totalSeconds = data.modules.reduce(function (sum, module) {
                return sum + module.active_seconds;
            }, 0);
            total.textContent = `Day total: ${formatDuration(totalSeconds)}`;

            if (data.modules.length === 0) {
                view.replaceChildren(makeElement('p', 'activity-muted small mb-0', 'No learning modules are available for this student.'));
                return;
            }

            const modules = [...data.modules].sort(function (first, second) {
                return second.active_seconds - first.active_seconds || first.title.localeCompare(second.title);
            });
            const maxSeconds = Math.max(0, ...modules.map(function (module) {
                return module.active_seconds;
            }));
            appendActivityBarList(modules, function (module) {
                const button = makeElement('button', 'activity-bar-row activity-column-bar');
                button.type = 'button';
                button.setAttribute('aria-label', `${module.title}: ${formatDuration(module.active_seconds)}. Open topics.`);
                button.setAttribute('aria-expanded', 'false');
                createActivityColumn(button, module.title, module.active_seconds, maxSeconds);
                button.addEventListener('click', function () {
                    selection.module = module;
                    loadActivity(true);
                });

                return button;
            });
        }

        function renderTopics(data) {
            title.textContent = `${data.module.title} topic activity`;
            const totalSeconds = data.topics.reduce(function (sum, topic) {
                return sum + topic.active_seconds;
            }, 0);
            total.textContent = `Module total: ${formatDuration(totalSeconds)}`;

            if (data.topics.length === 0) {
                view.replaceChildren(makeElement('p', 'activity-muted small mb-0', 'This module has no topics yet.'));
                return;
            }

            const topics = [...data.topics].sort(function (first, second) {
                return second.active_seconds - first.active_seconds || first.title.localeCompare(second.title);
            });
            const maxSeconds = Math.max(0, ...topics.map(function (topic) {
                return topic.active_seconds;
            }));
            appendActivityBarList(topics, function (topic) {
                const row = makeElement('button', 'activity-bar-row activity-column-bar');
                row.type = 'button';
                row.setAttribute('aria-label', `${topic.title}: ${formatDuration(topic.active_seconds)} active reading. Show recent quizzes.`);
                row.setAttribute('aria-haspopup', 'dialog');
                createActivityColumn(row, topic.title, topic.active_seconds, maxSeconds);
                const result = makeElement('span', 'activity-column-meta');
                const statusClass = topic.quiz_status === 'Passed'
                    ? 'activity-status is-passed'
                    : (topic.quiz_status === 'Needs Review' ? 'activity-status is-review' : 'activity-status');
                result.append(makeElement('span', statusClass, `Quiz: ${topic.quiz_status}`));
                if (topic.quiz_score !== null) {
                    result.append(makeElement('span', 'activity-topic-meta', `${topic.quiz_score}%`));
                }
                if (topic.quiz_auto_paused) {
                    result.append(makeElement('span', 'activity-topic-meta', 'Reading timer paused for quiz'));
                }
                row.append(result);
                row.addEventListener('click', function () {
                    showTopicQuizzes(topic);
                });

                return row;
            });
        }

        async function loadActivity(shouldScroll = false) {
            renderBreadcrumbs();
            error.hidden = true;
            const params = new URLSearchParams();
            if (selection.month) {
                params.set('month', selection.month);
            }
            if (selection.date) {
                params.set('date', selection.date);
            }
            if (selection.module) {
                params.set('module_id', selection.module.id);
            }

            try {
                const response = await fetch(`${analyticsUrl}?${params.toString()}`, {
                    headers: { Accept: 'application/json' },
                    credentials: 'same-origin',
                });
                const data = await response.json();
                if (!response.ok) {
                    throw new Error(data.message || 'Unable to load study activity.');
                }

                if (data.level === 'months') {
                    renderMonths(data);
                } else if (data.level === 'days') {
                    renderDays(data);
                } else if (data.level === 'modules') {
                    renderModules(data);
                } else if (data.level === 'topics') {
                    renderTopics(data);
                }

                if (shouldScroll) {
                    requestAnimationFrame(function () {
                        root.scrollIntoView({
                            behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth',
                            block: 'start',
                        });
                    });
                }
            } catch (loadError) {
                error.textContent = loadError.message || 'Unable to load study activity. Please try again.';
                error.hidden = false;
                view.replaceChildren(makeElement('p', 'activity-muted small mb-0', 'Activity data could not be loaded.'));
            }
        }

        loadActivity();
    });
</script>
