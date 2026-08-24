/**
 * BHEL-HPVP Vizag Quiz Application
 * Dynamic Exam Engine & Interactive Client Manager
 */

document.addEventListener('DOMContentLoaded', function() {
    if (typeof quizData === 'undefined') return;

    let currentQuestionIndex = 0;
    let currentLang = 'en'; // 'en', 'hi', 'te'
    const totalQuestions = quizData.questions.length;
    let remainingSeconds = quizData.remaining_seconds;
    let timerInterval = null;

    // Local state tracking for responses & review flags
    // userResponses: { questionId: selectedOption (1..4 or null) }
    // reviewFlags: { questionId: boolean }
    const userResponses = quizData.initial_responses || {};
    const reviewFlags = quizData.initial_reviews || {};

    const qTextEl = document.getElementById('q-text');
    const optionsContainerEl = document.getElementById('options-container');
    const currentQNumBadge = document.getElementById('current-q-badge');
    const paletteContainer = document.getElementById('palette-grid');
    const timerDisplayEl = document.getElementById('timer-display');
    const formAttemptId = document.getElementById('attempt-id-input');

    // Initialize UI
    initPalette();
    renderQuestion(currentQuestionIndex);
    startTimer();

    // Language Toggle Buttons
    document.querySelectorAll('.lang-btn[data-lang]').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.lang-btn[data-lang]').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            currentLang = this.getAttribute('data-lang');
            renderQuestion(currentQuestionIndex);
        });
    });

    // Navigation Buttons
    document.getElementById('btn-prev').addEventListener('click', function() {
        if (currentQuestionIndex > 0) {
            currentQuestionIndex--;
            renderQuestion(currentQuestionIndex);
        }
    });

    document.getElementById('btn-next').addEventListener('click', function() {
        if (currentQuestionIndex < totalQuestions - 1) {
            currentQuestionIndex++;
            renderQuestion(currentQuestionIndex);
        }
    });

    document.getElementById('btn-mark-review').addEventListener('click', function() {
        const qId = quizData.questions[currentQuestionIndex].question_id;
        reviewFlags[qId] = !reviewFlags[qId];
        saveResponse(qId, userResponses[qId] || null, reviewFlags[qId]);
        updatePalette();
    });

    document.getElementById('btn-clear-choice').addEventListener('click', function() {
        const qId = quizData.questions[currentQuestionIndex].question_id;
        userResponses[qId] = null;
        saveResponse(qId, null, reviewFlags[qId] || false);
        renderQuestion(currentQuestionIndex);
        updatePalette();
    });

    document.getElementById('btn-submit-quiz').addEventListener('click', function() {
        confirmAndSubmit(false);
    });

    /**
     * Render Single Question & Choices
     */
    function renderQuestion(index) {
        currentQuestionIndex = index;
        const q = quizData.questions[index];
        const qId = q.question_id;

        // Question text by language
        const qText = q['question_' + currentLang] || q['question_en'];
        qTextEl.textContent = `${index + 1}. ${qText}`;
        currentQNumBadge.textContent = `Question ${index + 1} of ${totalQuestions}`;

        // Options
        optionsContainerEl.innerHTML = '';
        const selectedOpt = userResponses[qId] || null;

        for (let i = 1; i <= 4; i++) {
            const optText = q[`option_${i}_${currentLang}`] || q[`option_${i}_en`];
            const optLetter = String.fromCharCode(64 + i); // A, B, C, D

            const optDiv = document.createElement('div');
            optDiv.className = `option-item ${selectedOpt === i ? 'selected' : ''}`;
            optDiv.dataset.option = i;

            optDiv.innerHTML = `
                <div class="option-letter">${optLetter}</div>
                <div style="flex: 1; font-size: 15px;">${escapeHtml(optText)}</div>
                <input type="radio" name="opt_choice" value="${i}" class="option-radio" ${selectedOpt === i ? 'checked' : ''}>
            `;

            optDiv.addEventListener('click', function() {
                selectOption(qId, i);
            });

            optionsContainerEl.appendChild(optDiv);
        }

        // Prev/Next Button visibility
        document.getElementById('btn-prev').style.visibility = index === 0 ? 'hidden' : 'visible';
        
        if (index === totalQuestions - 1) {
            document.getElementById('btn-next').style.display = 'none';
        } else {
            document.getElementById('btn-next').style.display = 'inline-flex';
        }

        updatePalette();
    }

    /**
     * Handle Option Selection
     */
    function selectOption(qId, optNum) {
        userResponses[qId] = optNum;
        saveResponse(qId, optNum, reviewFlags[qId] || false);
        renderQuestion(currentQuestionIndex);
        updatePalette();
    }

    /**
     * Build Sidebar Question Palette
     */
    function initPalette() {
        paletteContainer.innerHTML = '';
        quizData.questions.forEach((q, idx) => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'palette-btn';
            btn.textContent = idx + 1;
            btn.dataset.index = idx;

            btn.addEventListener('click', function() {
                renderQuestion(idx);
            });

            paletteContainer.appendChild(btn);
        });
        updatePalette();
    }

    /**
     * Refresh Question Palette Visual States
     */
    function updatePalette() {
        const btns = paletteContainer.querySelectorAll('.palette-btn');
        let answeredCount = 0;
        let reviewCount = 0;
        let pendingCount = 0;

        quizData.questions.forEach((q, idx) => {
            const qId = q.question_id;
            const btn = btns[idx];
            const hasAns = userResponses[qId] && userResponses[qId] !== null;
            const isRev = reviewFlags[qId] === true || reviewFlags[qId] === 1;

            btn.className = 'palette-btn';

            if (idx === currentQuestionIndex) {
                btn.classList.add('current');
            }

            if (isRev) {
                btn.classList.add('review');
                reviewCount++;
            } else if (hasAns) {
                btn.classList.add('attempted');
                answeredCount++;
            } else {
                pendingCount++;
            }
        });

        document.getElementById('count-answered').textContent = answeredCount;
        document.getElementById('count-pending').textContent = pendingCount;
        document.getElementById('count-review').textContent = reviewCount;
    }

    /**
     * Autosave response to server via fetch AJAX
     */
    function saveResponse(questionId, selectedOption, isMarkedReview) {
        const formData = new FormData();
        formData.append('action', 'save_response');
        formData.append('attempt_id', quizData.attempt_id);
        formData.append('question_id', questionId);
        formData.append('selected_option', selectedOption !== null ? selectedOption : '');
        formData.append('is_marked_review', isMarkedReview ? 1 : 0);

        fetch('quiz_submit.php', {
            method: 'POST',
            body: formData
        }).catch(err => console.error('Autosave error:', err));
    }

    /**
     * Timer Countdown Engine
     */
    function startTimer() {
        updateTimerDisplay(remainingSeconds);

        timerInterval = setInterval(function() {
            remainingSeconds--;
            updateTimerDisplay(remainingSeconds);

            if (remainingSeconds <= 0) {
                clearInterval(timerInterval);
                alert('Time has expired! Submitting your quiz answers automatically...');
                confirmAndSubmit(true);
            }
        }, 1000);
    }

    function updateTimerDisplay(totalSecs) {
        if (totalSecs < 0) totalSecs = 0;
        const mins = Math.floor(totalSecs / 60);
        const secs = totalSecs % 60;
        timerDisplayEl.textContent = `${String(mins).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;

        if (totalSecs < 300) { // < 5 mins
            timerDisplayEl.style.color = '#EF4444';
        }
    }

    /**
     * Submit Confirmation & Final Submission
     */
    function confirmAndSubmit(isAutoSubmit) {
        if (!isAutoSubmit) {
            let answered = 0, pending = 0, review = 0;
            quizData.questions.forEach(q => {
                const qId = q.question_id;
                if (reviewFlags[qId]) review++;
                else if (userResponses[qId]) answered++;
                else pending++;
            });

            const confirmMsg = `Are you sure you want to submit your quiz?\n\n` +
                `• Attempted: ${answered}\n` +
                `• Unanswered / Pending: ${pending}\n` +
                `• Marked for Review: ${review}\n\n` +
                `Click OK to finalize submission.`;

            if (!confirm(confirmMsg)) return;
        }

        clearInterval(timerInterval);

        // Submit form
        const form = document.getElementById('quiz-submit-form');
        form.submit();
    }

    function escapeHtml(str) {
        return String(str || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }
});
