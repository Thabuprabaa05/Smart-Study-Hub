<?php
// ==============================================================
// Smart Study Hub - Study Tips & Academic Productivity Guide
// ==============================================================

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$pageTitle = 'Study Tips & Academic Productivity';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="padding-top: 3.5rem; padding-bottom: 4rem;">
    <!-- Header -->
    <div style="text-align: center; max-width: 720px; margin: 0 auto 3.5rem;">
        <span class="badge badge-accent" style="margin-bottom: 0.75rem;"><i class="fas fa-brain"></i> Evidence-Based Learning</span>
        <h1 style="font-size: 2.3rem; margin-bottom: 0.5rem;">Science-Backed Study Strategies</h1>
        <p class="text-muted" style="font-size: 1.05rem;">
            Optimize your study sessions, retain complex course concepts longer, and maximize your GPA with proven college learning frameworks.
        </p>
    </div>

    <!-- Interactive Pomodoro Timer Widget -->
    <div class="card" style="max-width: 650px; margin: 0 auto 3.5rem; text-align: center; background: linear-gradient(135deg, #4f46e5, #4338ca); color: white; box-shadow: var(--shadow-xl); border: none;">
        <span class="badge" style="background: rgba(255, 255, 255, 0.2); color: white; margin: 0 auto 1rem;">
            <i class="fas fa-stopwatch"></i> Interactive Focus Tool
        </span>
        <h2 style="color: white; font-size: 1.6rem; margin-bottom: 0.25rem;">Pomodoro Focus Timer</h2>
        <p style="color: #e0e7ff; font-size: 0.9rem; margin-bottom: 1.5rem;">
            Study distraction-free for 25 minutes, followed by a rejuvenating 5-minute break.
        </p>

        <!-- Timer Display -->
        <div id="pomodoro-timer" style="font-family: 'JetBrains Mono', monospace; font-size: 4.5rem; font-weight: 700; letter-spacing: 2px; margin-bottom: 1.5rem; text-shadow: 0 4px 10px rgba(0,0,0,0.2);">
            25:00
        </div>

        <div style="display: flex; justify-content: center; gap: 0.75rem; margin-bottom: 1.25rem;">
            <button type="button" id="start-timer-btn" class="btn btn-secondary" style="background: white; color: var(--primary); font-weight: 700; border: none; padding: 0.75rem 1.75rem;">
                <i class="fas fa-play"></i> Start Session
            </button>
            <button type="button" id="pause-timer-btn" class="btn btn-outline-primary" style="color: white; border-color: rgba(255,255,255,0.4);" disabled>
                <i class="fas fa-pause"></i> Pause
            </button>
            <button type="button" id="reset-timer-btn" class="btn btn-outline-primary" style="color: white; border-color: rgba(255,255,255,0.4);">
                <i class="fas fa-redo"></i> Reset
            </button>
        </div>

        <div id="pomodoro-mode-badge" style="font-size: 0.85rem; color: #c7d2fe;">
            Status: <strong id="pomodoro-status">Ready for Study Sprint</strong>
        </div>
    </div>

    <!-- Strategy Cards Grid -->
    <div class="grid-3">
        <!-- 1. Active Recall -->
        <div class="card">
            <div class="logo-icon" style="background: #e0e7ff; color: #4338ca; margin-bottom: 1rem;">
                <i class="fas fa-bolt"></i>
            </div>
            <h3 style="font-size: 1.25rem; margin-bottom: 0.5rem;">1. Active Recall</h3>
            <p class="text-muted" style="font-size: 0.9rem; line-height: 1.6; margin-bottom: 1rem;">
                Instead of passively re-reading slides or textbooks, close the book and quiz yourself or solve a whiteboard problem from memory. Forcing neural retrieval strengthens synaptogenesis and long-term memory.
            </p>
            <div style="font-size: 0.8rem; background: var(--bg-main); padding: 0.75rem; border-radius: var(--radius-sm); border-left: 3px solid var(--primary);">
                <strong>Actionable Tip:</strong> After reading a chapter, write down the 5 biggest key points on a blank sheet without peeking.
            </div>
        </div>

        <!-- 2. Spaced Repetition -->
        <div class="card">
            <div class="logo-icon" style="background: #ecfdf5; color: #059669; margin-bottom: 1rem;">
                <i class="fas fa-calendar-check"></i>
            </div>
            <h3 style="font-size: 1.25rem; margin-bottom: 0.5rem;">2. Spaced Repetition</h3>
            <p class="text-muted" style="font-size: 0.9rem; line-height: 1.6; margin-bottom: 1rem;">
                The Ebbinghaus Forgetting Curve shows we forget up to 70% of new information within 48 hours unless reviewed. Review your notes at calculated intervals: Day 1, Day 3, Day 7, Day 14, and Day 30.
            </p>
            <div style="font-size: 0.8rem; background: var(--bg-main); padding: 0.75rem; border-radius: var(--radius-sm); border-left: 3px solid var(--accent);">
                <strong>Actionable Tip:</strong> Schedule quick 10-minute reviews of yesterday's lecture notes before starting new subjects.
            </div>
        </div>

        <!-- 3. The Feynman Technique -->
        <div class="card">
            <div class="logo-icon" style="background: #fef3c7; color: #d97706; margin-bottom: 1rem;">
                <i class="fas fa-chalkboard-teacher"></i>
            </div>
            <h3 style="font-size: 1.25rem; margin-bottom: 0.5rem;">3. The Feynman Technique</h3>
            <p class="text-muted" style="font-size: 0.9rem; line-height: 1.6; margin-bottom: 1rem;">
                Named after Nobel physicist Richard Feynman: if you cannot explain an idea in simple plain words to a 10-year-old, you don't truly understand it. Identify jargon crutches and explain concepts aloud to peers.
            </p>
            <div style="font-size: 0.8rem; background: var(--bg-main); padding: 0.75rem; border-radius: var(--radius-sm); border-left: 3px solid var(--warning);">
                <strong>Actionable Tip:</strong> Use your study squad discussions to teach a complex theorem or algorithm to another student.
            </div>
        </div>

        <!-- 4. Cornell Note-Taking -->
        <div class="card">
            <div class="logo-icon" style="background: #fee2e2; color: #dc2626; margin-bottom: 1rem;">
                <i class="fas fa-edit"></i>
            </div>
            <h3 style="font-size: 1.25rem; margin-bottom: 0.5rem;">4. Cornell Note-Taking System</h3>
            <p class="text-muted" style="font-size: 0.9rem; line-height: 1.6; margin-bottom: 1rem;">
                Divide your notebook page into 3 sections: a narrow left column for cues/keywords, a large right section for lecture notes, and a bottom summary block. This formats your notes for instant active self-quizzing.
            </p>
            <div style="font-size: 0.8rem; background: var(--bg-main); padding: 0.75rem; border-radius: var(--radius-sm); border-left: 3px solid var(--danger);">
                <strong>Actionable Tip:</strong> Check out the Cornell-formatted PDF uploads in our database for clean exam revision structures.
            </div>
        </div>

        <!-- 5. Group Study Optimization -->
        <div class="card">
            <div class="logo-icon" style="background: #e0f2fe; color: #0284c7; margin-bottom: 1rem;">
                <i class="fas fa-users-cog"></i>
            </div>
            <h3 style="font-size: 1.25rem; margin-bottom: 0.5rem;">5. High-Impact Study Squads</h3>
            <p class="text-muted" style="font-size: 0.9rem; line-height: 1.6; margin-bottom: 1rem;">
                Study groups are most effective when kept under 6–10 students with an explicit agenda. Assign subtopics beforehand: each member prepares one tough question to dissect collaboratively.
            </p>
            <div style="font-size: 0.8rem; background: var(--bg-main); padding: 0.75rem; border-radius: var(--radius-sm); border-left: 3px solid var(--secondary);">
                <strong>Actionable Tip:</strong> Use our portal announcements feature to set the agenda 24 hours prior to meetings.
            </div>
        </div>

        <!-- 6. Beating Distractions -->
        <div class="card">
            <div class="logo-icon" style="background: #ede9fe; color: #6d28d9; margin-bottom: 1rem;">
                <i class="fas fa-mobile-alt"></i>
            </div>
            <h3 style="font-size: 1.25rem; margin-bottom: 0.5rem;">6. Digital Friction</h3>
            <p class="text-muted" style="font-size: 0.9rem; line-height: 1.6; margin-bottom: 1rem;">
                Context switching between phone notifications and lecture readings causes heavy cognitive drain. Place smartphones in another room or turn on "Do Not Disturb" during designated focus blocks.
            </p>
            <div style="font-size: 0.8rem; background: var(--bg-main); padding: 0.75rem; border-radius: var(--radius-sm); border-left: 3px solid #6d28d9;">
                <strong>Actionable Tip:</strong> Reward yourself with 5 minutes of leisure browsing only after completing 2 full Pomodoro blocks.
            </div>
        </div>
    </div>
</div>

<script>
// Interactive Pomodoro Clock Script
let timerDuration = 25 * 60; // 25 minutes
let remainingTime = timerDuration;
let timerInterval = null;
let isStudyMode = true;

const timerDisplay = document.getElementById('pomodoro-timer');
const startBtn = document.getElementById('start-timer-btn');
const pauseBtn = document.getElementById('pause-timer-btn');
const resetBtn = document.getElementById('reset-timer-btn');
const statusText = document.getElementById('pomodoro-status');

function updateDisplay() {
    const mins = Math.floor(remainingTime / 60);
    const secs = remainingTime % 60;
    timerDisplay.textContent = `${String(mins).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
}

startBtn.addEventListener('click', () => {
    if (timerInterval) return;
    startBtn.disabled = true;
    pauseBtn.disabled = false;
    statusText.textContent = isStudyMode ? '🔥 Focus Sprint Active!' : '☕ Short Break Active!';

    timerInterval = setInterval(() => {
        if (remainingTime > 0) {
            remainingTime--;
            updateDisplay();
        } else {
            clearInterval(timerInterval);
            timerInterval = null;
            startBtn.disabled = false;
            pauseBtn.disabled = true;

            if (isStudyMode) {
                isStudyMode = false;
                remainingTime = 5 * 60;
                statusText.textContent = '🎉 Study block completed! Take a 5-minute break.';
                showToast('Great job! 25 minutes focus completed. Take a 5 min break!', 'success');
            } else {
                isStudyMode = true;
                remainingTime = 25 * 60;
                statusText.textContent = 'Break finished! Ready for next sprint.';
                showToast('Break finished. Ready to study?', 'info');
            }
            updateDisplay();
        }
    }, 1000);
});

pauseBtn.addEventListener('click', () => {
    if (timerInterval) {
        clearInterval(timerInterval);
        timerInterval = null;
        startBtn.disabled = false;
        pauseBtn.disabled = true;
        statusText.textContent = 'Paused.';
    }
});

resetBtn.addEventListener('click', () => {
    if (timerInterval) {
        clearInterval(timerInterval);
        timerInterval = null;
    }
    isStudyMode = true;
    remainingTime = 25 * 60;
    startBtn.disabled = false;
    pauseBtn.disabled = true;
    statusText.textContent = 'Ready for Study Sprint';
    updateDisplay();
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
