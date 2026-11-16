<script>
    document.addEventListener('DOMContentLoaded', function () {
        const root = document.getElementById('student-board-readiness');
        if (!root) {
            return;
        }

        const sliders = Array.from(root.querySelectorAll('[data-readiness-slider]'));
        const gauge = root.querySelector('#readiness-gauge-progress');
        const gaugeValue = root.querySelector('#readiness-gauge-value');
        const gaugeDescription = root.querySelector('#readiness-gauge-description');
        const status = root.querySelector('#readiness-status');
        const summary = root.querySelector('#readiness-summary');
        const actualReadinessScore = root.dataset.score === '' ? null : Number(root.dataset.score);
        const readinessQuestionCount = Number(root.dataset.questionCount);
        const assessedQuestionCounts = sliders
            .filter(function (slider) {
                return slider.dataset.assessed === 'true';
            })
            .map(function (slider) {
                return Number(slider.dataset.questionCount);
            });
        const simulatedModuleQuestionWeight = assessedQuestionCounts.length
            ? assessedQuestionCounts.reduce(function (total, count) {
                return total + count;
            }, 0) / assessedQuestionCounts.length
            : 1;
        const gaugeLength = gauge.getTotalLength();
        gauge.style.strokeDasharray = `${gaugeLength} ${gaugeLength}`;

        function updateReadiness() {
            const hasSimulation = sliders.some(function (slider) {
                return slider.dataset.simulated === 'true';
            });
            let score = actualReadinessScore;

            if (hasSimulation) {
                const projected = sliders.reduce(function (total, slider) {
                    const isAssessed = slider.dataset.assessed === 'true';
                    const weight = isAssessed
                        ? Number(slider.dataset.questionCount)
                        : simulatedModuleQuestionWeight;
                    const baseline = isAssessed ? Number(slider.dataset.actualScore) : 50;
                    const moduleScore = slider.dataset.simulated === 'true' ? Number(slider.value) : baseline;

                    return {
                        weightedScore: total.weightedScore + moduleScore * weight,
                        weight: total.weight + weight,
                    };
                }, { weightedScore: 0, weight: 0 });
                score = projected.weight
                    ? Math.max(0, Math.min(100, Math.round(projected.weightedScore / projected.weight)))
                    : null;
            }

            if (score === null) {
                gauge.style.strokeDashoffset = String(gaugeLength);
                gauge.classList.remove('is-passing', 'is-high');
                gaugeValue.classList.remove('readiness-gauge-value');
                gaugeValue.classList.remove('is-passing', 'is-high');
                gaugeValue.classList.add('readiness-gauge-empty');
                gaugeValue.textContent = 'No score';
                gaugeDescription.textContent = 'No general-test score recorded yet.';
                status.classList.remove('is-passing', 'is-high');
                status.textContent = 'AWAITING DATA';
                summary.textContent = 'No general-test score recorded yet. Adjust a module slider to preview a simulated result.';
            } else {
                const isHigh = score >= 90;
                const isPassing = score >= 75 && ! isHigh;

                gauge.style.strokeDashoffset = String(gaugeLength * (1 - score / 100));
                gauge.classList.toggle('is-passing', isPassing);
                gauge.classList.toggle('is-high', isHigh);
                gaugeValue.classList.remove('readiness-gauge-empty');
                gaugeValue.classList.add('readiness-gauge-value');
                gaugeValue.classList.toggle('is-passing', isPassing);
                gaugeValue.classList.toggle('is-high', isHigh);
                gaugeValue.textContent = `${score}%`;
                gaugeDescription.textContent = `${hasSimulation ? 'Simulated weighted module readiness' : 'Latest general-test score'} is ${score} percent.`;
                status.classList.toggle('is-passing', isPassing);
                status.classList.toggle('is-high', isHigh);
                status.textContent = `${hasSimulation ? 'SIMULATED · ' : ''}${isHigh ? 'HIGH MASTERY' : (isPassing ? 'ON TRACK' : 'NEEDS FOCUS')}`;
                summary.textContent = hasSimulation
                    ? `Projected across ${sliders.length} modules; unassessed modules are estimated at 50%.`
                    : `Latest general-test result: ${score}% across ${readinessQuestionCount} ${readinessQuestionCount === 1 ? 'question' : 'questions'}.`;
            }

            sliders.forEach(function (slider, index) {
                const moduleSlider = slider.closest('.readiness-slider-item');
                const isTracked = slider.dataset.assessed === 'true' || slider.dataset.simulated === 'true';
                const moduleValue = Number(slider.value);
                const moduleHigh = moduleValue >= 90;
                const modulePassing = moduleValue >= 75 && ! moduleHigh;
                const sliderValue = moduleSlider.querySelector('[data-slider-value]');
                const barRow = root.querySelector(`[data-module-index="${index}"]`);
                const moduleScore = barRow.querySelector('[data-module-score]');
                const moduleBar = barRow.querySelector('[data-module-bar]');
                const barTrack = moduleBar.parentElement;

                if (! isTracked) {
                    slider.setAttribute('aria-valuetext', `No recorded score; simulation starts at ${moduleValue} percent`);
                    sliderValue.textContent = 'No data';
                    sliderValue.classList.remove('readiness-slider-value', 'is-passing', 'is-high');
                    sliderValue.classList.add('readiness-caption', 'small');
                    moduleScore.textContent = 'No data';
                    moduleScore.classList.remove('readiness-subject-score', 'is-passing', 'is-high');
                    moduleScore.classList.add('readiness-caption', 'small');
                    moduleBar.style.width = '0%';
                    moduleBar.classList.remove('is-passing', 'is-high');
                    slider.classList.remove('is-passing', 'is-high');
                    barTrack.setAttribute('aria-label', `${moduleSlider.querySelector('.readiness-subject-name').textContent}: no recorded score`);

                    return;
                }

                slider.setAttribute('aria-valuetext', `${moduleValue} percent`);
                sliderValue.textContent = `${moduleValue}%`;
                sliderValue.classList.remove('readiness-caption', 'small');
                sliderValue.classList.add('readiness-slider-value');
                sliderValue.classList.toggle('is-passing', modulePassing);
                sliderValue.classList.toggle('is-high', moduleHigh);
                moduleScore.textContent = `${moduleValue}%`;
                moduleScore.classList.remove('readiness-caption', 'small');
                moduleScore.classList.add('readiness-subject-score');
                moduleScore.classList.toggle('is-passing', modulePassing);
                moduleScore.classList.toggle('is-high', moduleHigh);
                moduleBar.style.width = `${moduleValue}%`;
                moduleBar.classList.toggle('is-passing', modulePassing);
                moduleBar.classList.toggle('is-high', moduleHigh);
                slider.classList.toggle('is-passing', modulePassing);
                slider.classList.toggle('is-high', moduleHigh);
                barTrack.setAttribute('aria-label', `${moduleSlider.querySelector('.readiness-subject-name').textContent}: ${moduleValue} percent`);
            });
        }

        sliders.forEach(function (slider) {
            slider.addEventListener('input', function () {
                if (slider.dataset.assessed !== 'true' || slider.value !== slider.dataset.actualScore) {
                    slider.dataset.simulated = 'true';
                } else {
                    delete slider.dataset.simulated;
                }
                updateReadiness();
            });
        });

        root.querySelector('#readiness-reset').addEventListener('click', function () {
            sliders.forEach(function (slider) {
                slider.value = slider.dataset.assessed === 'true' ? slider.dataset.actualScore : '50';
                delete slider.dataset.simulated;
            });
            updateReadiness();
        });

        updateReadiness();
    });
</script>
