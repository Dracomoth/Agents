<?php
/**
 * Agent Workbench Screen View
 * - Multi-agent sequential and iterative execution pipeline
 * - Left side agent selector (private/accessible agents)
 * - Step position assignment (1, 2, 3...)
 * - Sequential prompt piping: Output(Agent N) -> Input(Agent N+1)
 * - Iterative looping (Fixed count or Indeterminate condition)
 */

$pageTitle = 'Agent Workbench - Multi-Agent Orchestrator';
require __DIR__ . '/../layouts/header.php';
require __DIR__ . '/../layouts/sidebar.php';

$providers = $providers ?? [
    'gemini' => ['name' => 'Google Gemini', 'badge_bg' => '#eff6ff', 'badge_color' => '#1d4ed8', 'border_color' => '#bfdbfe'],
    'openai' => ['name' => 'OpenAI', 'badge_bg' => '#ecfdf5', 'badge_color' => '#047857', 'border_color' => '#a7f3d0'],
    'anthropic' => ['name' => 'Anthropic Claude', 'badge_bg' => '#fffbeb', 'badge_color' => '#b45309', 'border_color' => '#fde68a'],
    'groq' => ['name' => 'Groq', 'badge_bg' => '#f5f3ff', 'badge_color' => '#6d28d9', 'border_color' => '#ddd6fe'],
    'local' => ['name' => 'Ollama / Local', 'badge_bg' => '#f1f5f9', 'badge_color' => '#475569', 'border_color' => '#cbd5e1']
];
$documents = $documents ?? [];
?>

<main class="main-content">
    <div class="content-container" style="max-width: 1440px;">
        <!-- Top Toolbar -->
        <div class="page-toolbar">
            <div class="page-title-group">
                <h1 class="page-title">Agent Workbench</h1>
                <p class="page-subtitle">Configure sequential agent execution chains, pipe responses between specialists, and run iterative loops.</p>
            </div>
            <div style="display: flex; gap: 0.75rem; align-items: center;">
                <button type="button" class="btn btn-secondary" onclick="resetPipeline()">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"></path>
                        <path d="M3 3v5h5"></path>
                    </svg>
                    <span>Reset Chain</span>
                </button>
                <button type="button" class="btn btn-primary" id="runPipelineBtn" onclick="runPipeline()">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <polygon points="5 3 19 12 5 21 5 3"></polygon>
                    </svg>
                    <span>Run Pipeline</span>
                </button>
            </div>
        </div>

        <!-- Workbench Grid -->
        <div class="workbench-grid">
            <!-- Left Column: Knowledge Base & Pipeline Sequencer -->
            <div class="workbench-sidebar">
                <!-- Card 1: Knowledge Base Documents Panel (Collapsible) -->
                <div class="workbench-card" id="kbCard">
                    <div class="workbench-card-header collapsible" onclick="toggleCardCollapse('kbCardBody', 'kbCollapseIcon')">
                        <span class="workbench-card-title">
                            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                                <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
                            </svg>
                            <span>Knowledge Base</span>
                        </span>
                        <div style="display: flex; align-items: center; gap: 0.5rem;" onclick="event.stopPropagation()">
                            <span class="badge-model" id="kbDocCountBadge" style="font-size: 0.75rem;">
                                <?= count($documents ?? []) ?> docs
                            </span>
                            <button type="button" class="btn-collapse-toggle" id="kbCollapseIcon" title="Toggle collapse" onclick="toggleCardCollapse('kbCardBody', 'kbCollapseIcon')">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="6 9 12 15 18 9"></polyline>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="workbench-card-body" id="kbCardBody">
                        <!-- Action Buttons & Dropzone -->
                        <div class="kb-actions-bar">
                            <button type="button" class="btn btn-primary btn-sm" style="flex: 1; justify-content: center;" onclick="document.getElementById('kbFileInput').click()">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                    <polyline points="17 8 12 3 7 8"></polyline>
                                    <line x1="12" y1="3" x2="12" y2="15"></line>
                                </svg>
                                <span>Upload File</span>
                            </button>
                            <button type="button" class="btn btn-secondary btn-sm" style="flex: 1; justify-content: center;" onclick="openPasteDocModal()">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="4 7 4 4 20 4 20 7"></polyline>
                                    <line x1="9" y1="20" x2="15" y2="20"></line>
                                    <line x1="12" y1="4" x2="12" y2="20"></line>
                                </svg>
                                <span>+ Paste Text</span>
                            </button>
                        </div>

                        <!-- Hidden File Input -->
                        <input type="file" id="kbFileInput" style="display: none;" multiple onchange="handleFileUpload(event)">

                        <!-- Drag & Drop Zone -->
                        <div class="kb-dropzone" id="kbDropzone" onclick="document.getElementById('kbFileInput').click()">
                            <div class="kb-dropzone-icon">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                    <polyline points="14 2 14 8 20 8"></polyline>
                                    <line x1="12" y1="18" x2="12" y2="12"></line>
                                    <line x1="9" y1="15" x2="15" y2="15"></line>
                                </svg>
                            </div>
                            <div class="kb-dropzone-text">Drop files here or click to browse</div>
                            <div class="kb-dropzone-sub">Supports TXT, MD, CSV, JSON, code files</div>
                        </div>

                        <!-- Active Documents Header -->
                        <div style="font-size: 0.76rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.04em; margin-bottom: 0.6rem; display: flex; justify-content: space-between; align-items: center;">
                            <span>Active Documents</span>
                            <span style="font-size: 0.70rem; font-weight: 500; text-transform: none; color: #10b981;">&bull; Injected to Agents</span>
                        </div>

                        <!-- Documents List -->
                        <div class="kb-doc-list" id="kbDocList" style="max-height: 260px;">
                            <?php if (empty($documents)): ?>
                                <div id="kbEmptyNotice" style="text-align: center; padding: 1.5rem 1rem; color: var(--text-muted); font-size: 0.82rem; border: 1px dashed var(--border-color); border-radius: var(--radius-sm); background: #fafbfc;">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="color: #94a3b8; margin-bottom: 0.35rem;">
                                        <path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"></path>
                                        <polyline points="13 2 13 9 20 9"></polyline>
                                    </svg>
                                    <div style="font-weight: 600; color: var(--text-primary); margin-bottom: 0.2rem;">No documents loaded</div>
                                    <div style="font-size: 0.72rem; color: var(--text-muted);">Uploaded documents serve as knowledge base and are passed into agent prompts.</div>
                                </div>
                            <?php else: ?>
                                <?php foreach ($documents as $doc): ?>
                                    <?php
                                        $ext = strtolower($doc['file_type'] ?? 'txt');
                                        $sizeFormatted = $doc['file_size'] >= 1024 
                                            ? round($doc['file_size'] / 1024, 1) . ' KB' 
                                            : $doc['file_size'] . ' B';
                                        $charsFormatted = number_format($doc['char_count'] ?? strlen($doc['content'])) . ' chars';
                                    ?>
                                    <div class="kb-doc-item" id="kbDocItem-<?= (int)$doc['id'] ?>">
                                        <div class="kb-doc-badge <?= htmlspecialchars($ext) ?>"><?= htmlspecialchars(substr($ext, 0, 4)) ?></div>
                                        <div class="kb-doc-info">
                                            <div class="kb-doc-title" title="<?= htmlspecialchars($doc['filename']) ?>">
                                                <?= htmlspecialchars($doc['filename']) ?>
                                            </div>
                                            <div class="kb-doc-meta">
                                                <span><?= $sizeFormatted ?></span>
                                                <span>&bull;</span>
                                                <span><?= $charsFormatted ?></span>
                                            </div>
                                        </div>
                                        <div class="kb-doc-actions">
                                            <button type="button" class="kb-btn-icon" title="Preview Document" onclick="previewDocument(<?= (int)$doc['id'] ?>)">
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                                    <circle cx="12" cy="12" r="3"></circle>
                                                </svg>
                                            </button>
                                            <button type="button" class="kb-btn-icon danger" title="Remove Document" onclick="deleteDocument(<?= (int)$doc['id'] ?>, '<?= htmlspecialchars(addslashes($doc['filename'])) ?>')">
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <polyline points="3 6 5 6 21 6"></polyline>
                                                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Card 2: Active Execution Pipeline (Collapsible) -->
                <div class="workbench-card" id="pipelineCard">
                    <div class="workbench-card-header collapsible" onclick="toggleCardCollapse('pipelineCardBody', 'pipelineCollapseIcon')">
                        <span class="workbench-card-title">
                            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
                            </svg>
                            <span>Execution Pipeline</span>
                        </span>
                        <div style="display: flex; align-items: center; gap: 0.5rem;" onclick="event.stopPropagation()">
                            <button type="button" class="btn btn-primary btn-sm" onclick="openAddAgentModal()" style="padding: 0.25rem 0.65rem; font-size: 0.8rem; font-weight: 600;">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <line x1="12" y1="5" x2="12" y2="19"></line>
                                    <line x1="5" y1="12" x2="19" y2="12"></line>
                                </svg>
                                <span>Add Agent</span>
                            </button>
                            <span class="badge-model" id="pipelineCountBadge" style="font-size: 0.75rem; background: #eff6ff; color: #1d4ed8; border-color: #bfdbfe;">
                                0 steps
                            </span>
                            <button type="button" class="btn-collapse-toggle" id="pipelineCollapseIcon" title="Toggle collapse" onclick="toggleCardCollapse('pipelineCardBody', 'pipelineCollapseIcon')">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="6 9 12 15 18 9"></polyline>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="workbench-card-body" id="pipelineCardBody">
                        <p style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0; margin-bottom: 0.85rem; line-height: 1.4;">
                            Agents execute in numbered order. Step 1's output feeds as input to Step 2, and so on.
                        </p>

                        <!-- Pipeline List Container -->
                        <div id="pipelineChainList" class="pipeline-chain-list">
                            <!-- Populated via JavaScript -->
                            <div id="emptyPipelineNotice" style="text-align: center; padding: 2rem 1rem; border: 2px dashed #e2e8f0; border-radius: var(--radius-sm); color: var(--text-muted); font-size: 0.84rem;">
                                No agents in the chain yet.<br>
                                <button type="button" class="btn btn-primary btn-sm" style="display: inline-flex; margin-top: 0.65rem; padding: 0.35rem 0.75rem; font-size: 0.82rem;" onclick="openAddAgentModal()">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                        <line x1="12" y1="5" x2="12" y2="19"></line>
                                        <line x1="5" y1="12" x2="19" y2="12"></line>
                                    </svg>
                                    <span>Select Agent</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card 3: Looping & Iterations (Collapsible) -->
                <div class="workbench-card" id="loopCard">
                    <div class="workbench-card-header collapsible" onclick="toggleCardCollapse('loopCardBody', 'loopCollapseIcon')">
                        <span class="workbench-card-title">
                            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21.5 2v6h-6M2.5 22v-6h6M2 11.5a10 10 0 0 1 18.8-4.3M22 12.5a10 10 0 0 1-18.8 4.2"></path>
                            </svg>
                            <span>Looping & Iterations</span>
                        </span>
                        <button type="button" class="btn-collapse-toggle" id="loopCollapseIcon" title="Toggle collapse" onclick="event.stopPropagation(); toggleCardCollapse('loopCardBody', 'loopCollapseIcon')">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="6 9 12 15 18 9"></polyline>
                            </svg>
                        </button>
                    </div>

                    <div class="workbench-card-body" id="loopCardBody">
                        <label class="permission-checkbox-label" style="margin-bottom: 0.85rem;">
                            <input type="checkbox" id="iterativeToggle" onchange="toggleIterativeControls()">
                            <span style="font-weight: 600; color: var(--text-primary); font-size: 0.88rem;">Enable Iterative Looping</span>
                        </label>

                        <div id="iterativeControls" style="display: none; padding-top: 0.5rem; border-top: 1px solid var(--border-color);">
                            <p style="font-size: 0.78rem; color: var(--text-muted); margin-top: 0; margin-bottom: 0.75rem; line-height: 1.4;">
                                When enabled, once the last agent finishes, its output is fed back into Agent #1 to continue refining or collaborating.
                            </p>

                            <!-- Iteration Mode Radios -->
                            <div style="display: flex; flex-direction: column; gap: 0.65rem; margin-bottom: 0.85rem;">
                                <label style="display: flex; align-items: flex-start; gap: 0.5rem; cursor: pointer; font-size: 0.84rem;">
                                    <input type="radio" name="iteration_mode" id="mode_fixed" value="fixed" checked onchange="toggleModeDetails()">
                                    <div>
                                        <div style="font-weight: 600; color: var(--text-primary);">Fixed Number of Iterations</div>
                                        <div style="font-size: 0.75rem; color: var(--text-muted);">Repeats the full chain a specified count.</div>
                                    </div>
                                </label>

                                <label style="display: flex; align-items: flex-start; gap: 0.5rem; cursor: pointer; font-size: 0.84rem;">
                                    <input type="radio" name="iteration_mode" id="mode_indeterminate" value="indeterminate" onchange="toggleModeDetails()">
                                    <div>
                                        <div style="font-weight: 600; color: var(--text-primary);">Indeterminate (Condition-Based)</div>
                                        <div style="font-size: 0.75rem; color: var(--text-muted);">Loop stops when end condition is met in prompt (Safety limit: 5 cycles).</div>
                                    </div>
                                </label>
                            </div>

                            <!-- Fixed Iterations Count Input -->
                            <div id="fixedCountContainer" class="form-group" style="margin-bottom: 0;">
                                <label for="iterationCount" class="form-label" style="font-size: 0.82rem;">Iteration Cycles</label>
                                <div style="display: flex; align-items: center; gap: 0.5rem;">
                                    <input type="number" 
                                           id="iterationCount" 
                                           class="form-input" 
                                           min="1" 
                                           max="10" 
                                           value="2" 
                                           style="width: 70px; padding: 0.4rem 0.6rem; font-weight: 600;">
                                    <span style="font-size: 0.78rem; color: var(--text-muted);">cycles (Max 10)</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Initial Prompt & Execution Stage -->
            <div class="workbench-main">
                <!-- Initial Prompt Card -->
                <div class="workbench-card">
                    <div class="workbench-card-header">
                        <span class="workbench-card-title">
                            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                <polyline points="14 2 14 8 20 8"></polyline>
                                <line x1="16" y1="13" x2="8" y2="13"></line>
                                <line x1="16" y1="17" x2="8" y2="17"></line>
                                <polyline points="10 9 9 9 8 9"></polyline>
                            </svg>
                            <span>Initial Prompt (Input to Agent #1) *</span>
                        </span>
                        <span style="font-size: 0.78rem; color: var(--text-muted);" id="promptCharCount">0 characters</span>
                    </div>

                    <div class="workbench-card-body">
                        <textarea id="initialPrompt" 
                                  class="form-textarea" 
                                  rows="5" 
                                  placeholder="Enter the primary problem or task directive. Agent #1 will process this first.&#10;&#10;Example:&#10;Write an architectural outline for a microservice payment processor with idempotency keys and retry backoff..."
                                  oninput="document.getElementById('promptCharCount').textContent = this.value.length + ' characters';"
                                  style="font-family: inherit; font-size: 0.92rem;"></textarea>

                        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 0.75rem;">
                            <span style="font-size: 0.8rem; color: var(--text-muted);">
                                Outputs cascade sequentially: Output(Agent 1) &rarr; Input(Agent 2) &rarr; ...
                            </span>
                            <button type="button" class="btn btn-primary btn-sm" onclick="runPipeline()">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <polygon points="5 3 19 12 5 21 5 3"></polygon>
                                </svg>
                                <span>Execute Pipeline</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Execution Feed & Live Results Stage -->
                <div class="workbench-card" id="executionStageCard">
                    <div class="workbench-card-header">
                        <span class="workbench-card-title">
                            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"></circle>
                                <polyline points="12 6 12 12 16 14"></polyline>
                            </svg>
                            <span>Execution Feed & Outputs</span>
                        </span>
                        <div id="executionStatusIndicator" style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.8rem; color: var(--text-muted);">
                            <span class="status-dot" style="background: #94a3b8;"></span>
                            <span>Awaiting Run</span>
                        </div>
                    </div>

                    <div class="workbench-card-body" id="executionFeedBody">
                        <!-- Default Idle Visual Blueprint -->
                        <div id="blueprintPlaceholder" style="text-align: center; padding: 4rem 1.5rem; color: var(--text-muted);">
                            <div style="width: 56px; height: 56px; border-radius: 50%; background: #f8fafc; border: 1px solid var(--border-color); display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem; color: var(--primary);">
                                <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <polygon points="12 2 2 7 12 12 22 7 12 2"></polygon>
                                    <polyline points="2 17 12 22 22 17"></polyline>
                                    <polyline points="2 12 12 17 22 12"></polyline>
                                </svg>
                            </div>
                            <h3 style="font-size: 1.05rem; font-weight: 600; color: var(--text-primary); margin-bottom: 0.4rem;">Pipeline Ready for Execution</h3>
                            <p style="font-size: 0.86rem; max-width: 500px; margin: 0 auto; line-height: 1.5;">
                                Add your specialized agents in the left panel, set their order position, enter the initial directive, and press <strong>Run Pipeline</strong>.
                            </p>
                        </div>

                        <!-- Results Container (Populated via JS) -->
                        <div id="resultsStreamContainer" style="display: none;" class="execution-feed-container"></div>
                    </div>
                </div>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Agent Selection Popup Modal -->
<div class="kb-modal-backdrop" id="agentSelectModal" style="display: none;" onclick="if(event.target === this) closeAddAgentModal()">
    <div class="kb-modal-content" style="max-width: 620px;">
        <div class="kb-modal-header">
            <div>
                <h3 style="font-size: 1.05rem; font-weight: 700; color: var(--text-primary); margin: 0; display: flex; align-items: center; gap: 0.5rem;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: var(--primary);">
                        <rect x="3" y="11" width="18" height="10" rx="2"></rect>
                        <circle cx="12" cy="5" r="2"></circle>
                        <path d="M12 7v4"></path>
                    </svg>
                    <span>Select Agent for Pipeline</span>
                </h3>
                <div style="font-size: 0.78rem; color: var(--text-muted); margin-top: 0.2rem;">
                    Choose an agent to append to your sequential execution chain.
                </div>
            </div>
            <button type="button" class="kb-btn-icon" onclick="closeAddAgentModal()" style="font-size: 1.25rem;">&times;</button>
        </div>

        <div style="padding: 0.85rem 1.25rem 0; background: #ffffff;">
            <input type="text" 
                   id="modalAgentSearchInput" 
                   class="form-input" 
                   placeholder="Filter agents by name, role, provider or model..." 
                   onkeyup="filterModalAgents()"
                   style="font-size: 0.86rem; padding: 0.5rem 0.8rem;">
        </div>

        <div class="kb-modal-body" style="padding: 0.85rem 1.25rem; max-height: 420px;">
            <div class="agent-selector-list" id="modalAgentList" style="max-height: none;">
                <?php if (empty($agents)): ?>
                    <div style="text-align: center; padding: 2rem 1rem; color: var(--text-muted); font-size: 0.85rem;">
                        No agents available. <a href="/agents" style="color: var(--primary);">Create an agent</a> to get started.
                    </div>
                <?php else: ?>
                    <?php foreach ($agents as $ag): ?>
                        <?php
                            $pKey = strtolower($ag['provider']);
                            $pInfo = $providers[$pKey] ?? ['name' => ucfirst($pKey), 'badge_bg' => '#f1f5f9', 'badge_color' => '#475569', 'border_color' => '#cbd5e1'];
                            $initials = strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $ag['name']) . 'AG', 0, 2));
                        ?>
                        <div class="agent-selector-item modal-agent-item" 
                             data-agent-id="<?= (int)$ag['id'] ?>"
                             data-search="<?= htmlspecialchars(strtolower($ag['name'] . ' ' . $ag['role'] . ' ' . $ag['model'] . ' ' . $pInfo['name'])) ?>">
                            <div style="display: flex; align-items: center; gap: 0.75rem; min-width: 0; flex: 1;">
                                <div class="avatar-circle" style="width: 34px; height: 34px; font-size: 0.75rem; background: #e0e7ff; color: #4338ca; font-weight: 700; flex-shrink: 0;">
                                    <span><?= htmlspecialchars($initials) ?></span>
                                </div>
                                <div style="min-width: 0; flex: 1;">
                                    <div style="font-weight: 600; font-size: 0.88rem; color: var(--text-primary); display: flex; align-items: center; gap: 0.4rem;">
                                        <span style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?= htmlspecialchars($ag['name']) ?></span>
                                        <?php if (!empty($ag['is_mine'])): ?>
                                            <span class="meta-tag" style="font-size: 0.65rem; background: #f0fdf4; color: #15803d; padding: 1px 4px; flex-shrink: 0;">My Agent</span>
                                        <?php endif; ?>
                                    </div>
                                    <div style="font-size: 0.75rem; color: var(--text-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-top: 1px;">
                                        <?= htmlspecialchars($ag['role']) ?>
                                    </div>
                                    <div style="display: flex; align-items: center; gap: 0.35rem; margin-top: 3px;">
                                        <span class="badge-provider" style="font-size: 0.65rem; padding: 1px 6px; background: <?= $pInfo['badge_bg'] ?>; color: <?= $pInfo['badge_color'] ?>; border-color: <?= $pInfo['border_color'] ?>;">
                                            <?= htmlspecialchars($pInfo['name']) ?>
                                        </span>
                                        <span class="badge-model" style="font-size: 0.68rem; padding: 1px 5px;">
                                            <?= htmlspecialchars($ag['model']) ?>
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <button type="button" 
                                    class="btn btn-primary btn-sm" 
                                    style="padding: 0.35rem 0.75rem; font-size: 0.8rem; font-weight: 600; flex-shrink: 0; min-width: 70px; justify-content: center;"
                                    title="Add to execution pipeline"
                                    onclick="handleAddAgentClick(this, <?= htmlspecialchars(json_encode([
                                        'id' => (int)$ag['id'],
                                        'name' => $ag['name'],
                                        'role' => $ag['role'],
                                        'provider' => $ag['provider'],
                                        'provider_label' => $pInfo['name'],
                                        'badge_bg' => $pInfo['badge_bg'],
                                        'badge_color' => $pInfo['badge_color'],
                                        'border_color' => $pInfo['border_color'],
                                        'model' => $ag['model']
                                    ])) ?>)">
                                + Add
                            </button>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="kb-modal-footer" style="justify-content: space-between; align-items: center;">
            <span style="font-size: 0.8rem; color: var(--text-muted);" id="modalPipelineSummary">
                Current chain: 0 agents
            </span>
            <button type="button" class="btn btn-secondary btn-sm" onclick="closeAddAgentModal()">
                Done
            </button>
        </div>
    </div>
</div>

<!-- Document Preview Modal -->
<div class="kb-modal-backdrop" id="kbPreviewModal" style="display: none;" onclick="if(event.target === this) closePreviewModal()">
    <div class="kb-modal-content">
        <div class="kb-modal-header">
            <div style="min-width: 0;">
                <h3 id="kbPreviewModalTitle" style="font-size: 1rem; font-weight: 700; color: var(--text-primary); margin: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                    Document Preview
                </h3>
                <div id="kbPreviewModalMeta" style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.2rem;"></div>
            </div>
            <button type="button" class="kb-btn-icon" onclick="closePreviewModal()" style="font-size: 1.25rem;">&times;</button>
        </div>
        <div class="kb-modal-body">
            <pre class="kb-preview-pre" id="kbPreviewModalContent"></pre>
        </div>
        <div class="kb-modal-footer">
            <button type="button" class="btn btn-secondary btn-sm" id="kbCopyBtn" onclick="copyPreviewContent()">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                    <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                </svg>
                <span id="kbCopyBtnText">Copy Content</span>
            </button>
            <button type="button" class="btn btn-primary btn-sm" onclick="closePreviewModal()">Close</button>
        </div>
    </div>
</div>

<!-- Paste Text Document Modal -->
<div class="kb-modal-backdrop" id="kbPasteModal" style="display: none;" onclick="if(event.target === this) closePasteDocModal()">
    <div class="kb-modal-content">
        <div class="kb-modal-header">
            <h3 style="font-size: 1rem; font-weight: 700; color: var(--text-primary); margin: 0;">
                Add Knowledge Base Document
            </h3>
            <button type="button" class="kb-btn-icon" onclick="closePasteDocModal()" style="font-size: 1.25rem;">&times;</button>
        </div>
        <div class="kb-modal-body">
            <div class="form-group" style="margin-bottom: 1rem;">
                <label class="form-label" for="pasteDocTitle">Document Name / Title</label>
                <input type="text" id="pasteDocTitle" class="form-input" placeholder="e.g. system_architecture.md or guidelines.txt">
            </div>
            <div class="form-group" style="margin-bottom: 0;">
                <label class="form-label" for="pasteDocContent">Document Content</label>
                <textarea id="pasteDocContent" class="form-input" rows="10" placeholder="Paste or type instructions, context notes, rules, or dataset..." style="font-family: inherit; font-size: 0.88rem;"></textarea>
            </div>
        </div>
        <div class="kb-modal-footer">
            <button type="button" class="btn btn-secondary btn-sm" onclick="closePasteDocModal()">Cancel</button>
            <button type="button" class="btn btn-primary btn-sm" id="savePastedDocBtn" onclick="savePastedDocument()">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                    <polyline points="17 21 17 13 7 13 7 21"></polyline>
                    <polyline points="7 3 7 8 15 8"></polyline>
                </svg>
                <span>Save to Knowledge Base</span>
            </button>
        </div>
    </div>
</div>

<script>
// Active pipeline array storing agents in execution order
let pipeline = [];

// Collapsible card toggle logic
function toggleCardCollapse(bodyId, chevronId) {
    const body = document.getElementById(bodyId);
    const chevron = document.getElementById(chevronId);
    if (!body) return;

    if (body.classList.contains('collapsed') || body.style.display === 'none') {
        body.classList.remove('collapsed');
        body.style.display = 'block';
        if (chevron) chevron.classList.remove('collapsed');
    } else {
        body.classList.add('collapsed');
        body.style.display = 'none';
        if (chevron) chevron.classList.add('collapsed');
    }
}

// Agent Selection Modal logic
function openAddAgentModal() {
    updateModalSummary();
    const search = document.getElementById('modalAgentSearchInput');
    if (search) {
        search.value = '';
        filterModalAgents();
    }
    document.getElementById('agentSelectModal').style.display = 'flex';
    if (search) {
        setTimeout(() => search.focus(), 50);
    }
}

function closeAddAgentModal() {
    document.getElementById('agentSelectModal').style.display = 'none';
}

function filterModalAgents() {
    const q = (document.getElementById('modalAgentSearchInput')?.value || '').toLowerCase().trim();
    const items = document.querySelectorAll('.modal-agent-item');
    items.forEach(el => {
        const text = el.getAttribute('data-search') || '';
        el.style.display = text.includes(q) ? 'flex' : 'none';
    });
}

function handleAddAgentClick(btn, agentData) {
    addAgentToPipeline(agentData);
    const origText = btn.innerHTML;
    btn.innerHTML = '<span>Added ✓</span>';
    btn.style.background = '#ecfdf5';
    btn.style.color = '#047857';
    btn.style.borderColor = '#a7f3d0';
    
    updateModalSummary();
    
    setTimeout(() => {
        btn.innerHTML = origText;
        btn.style.background = '';
        btn.style.color = '';
        btn.style.borderColor = '';
    }, 1000);
}

function updateModalSummary() {
    const summary = document.getElementById('modalPipelineSummary');
    if (summary) {
        summary.textContent = `Current chain: ${pipeline.length} agent${pipeline.length === 1 ? '' : 's'}`;
    }
}

function addAgentToPipeline(agentData) {
    const nextPos = pipeline.length + 1;
    pipeline.push({
        position: nextPos,
        agent: agentData
    });
    renderPipeline();
    updateModalSummary();
}

function removeStep(index) {
    pipeline.splice(index, 1);
    // Renumber positions sequentially
    pipeline.forEach((p, i) => p.position = i + 1);
    renderPipeline();
    updateModalSummary();
}

function moveStep(index, direction) {
    const targetIdx = index + direction;
    if (targetIdx < 0 || targetIdx >= pipeline.length) return;
    
    const temp = pipeline[index];
    pipeline[index] = pipeline[targetIdx];
    pipeline[targetIdx] = temp;
    
    // Renumber positions
    pipeline.forEach((p, i) => p.position = i + 1);
    renderPipeline();
}

function updateStepPosition(index, newPosVal) {
    let newPos = parseInt(newPosVal, 10);
    if (isNaN(newPos) || newPos < 1) newPos = 1;
    
    pipeline[index].position = newPos;
    // Re-sort pipeline array based on assigned positions
    pipeline.sort((a, b) => a.position - b.position);
    // Normalize positions
    pipeline.forEach((p, i) => p.position = i + 1);
    renderPipeline();
}

function resetPipeline() {
    if (pipeline.length > 0 && !confirm('Are you sure you want to clear the active pipeline chain?')) {
        return;
    }
    pipeline = [];
    document.getElementById('initialPrompt').value = '';
    document.getElementById('promptCharCount').textContent = '0 characters';
    renderPipeline();
    
    document.getElementById('blueprintPlaceholder').style.display = 'block';
    document.getElementById('resultsStreamContainer').style.display = 'none';
    document.getElementById('resultsStreamContainer').innerHTML = '';
    updateStatusIndicator('Awaiting Run', '#94a3b8');
}

function toggleIterativeControls() {
    const chk = document.getElementById('iterativeToggle');
    const ctrl = document.getElementById('iterativeControls');
    ctrl.style.display = chk.checked ? 'block' : 'none';
    renderPipeline();
}

function toggleModeDetails() {
    const isFixed = document.getElementById('mode_fixed').checked;
    document.getElementById('fixedCountContainer').style.display = isFixed ? 'block' : 'none';
}

function renderPipeline() {
    const container = document.getElementById('pipelineChainList');
    const countBadge = document.getElementById('pipelineCountBadge');
    countBadge.textContent = `${pipeline.length} step${pipeline.length === 1 ? '' : 's'}`;
    
    if (pipeline.length === 0) {
        container.innerHTML = `
            <div id="emptyPipelineNotice" style="text-align: center; padding: 2rem 1rem; border: 2px dashed #e2e8f0; border-radius: var(--radius-sm); color: var(--text-muted); font-size: 0.84rem;">
                No agents in the chain yet.<br>
                <button type="button" class="btn btn-primary btn-sm" style="display: inline-flex; margin-top: 0.65rem; padding: 0.35rem 0.75rem; font-size: 0.82rem;" onclick="openAddAgentModal()">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    <span>Select Agent</span>
                </button>
            </div>`;
        return;
    }

    const isIterative = document.getElementById('iterativeToggle').checked;
    let html = '';

    pipeline.forEach((step, idx) => {
        const ag = step.agent;
        const isFirst = (idx === 0);
        const isLast = (idx === pipeline.length - 1);

        html += `
            <div class="pipeline-step-item">
                <div class="pipeline-step-num">#${step.position}</div>
                <div style="flex: 1; min-width: 0;">
                    <div style="font-weight: 600; font-size: 0.88rem; color: var(--text-primary); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                        ${escapeHtml(ag.name)}
                    </div>
                    <div style="display: flex; align-items: center; gap: 0.35rem; margin-top: 2px;">
                        <span class="badge-provider" style="font-size: 0.65rem; padding: 1px 6px; background: ${ag.badge_bg}; color: ${ag.badge_color}; border-color: ${ag.border_color};">
                            ${escapeHtml(ag.provider_label || ag.provider)}
                        </span>
                        <span class="badge-model" style="font-size: 0.68rem; padding: 1px 5px;">
                            ${escapeHtml(ag.model)}
                        </span>
                    </div>
                </div>

                <!-- Position Ordering Control -->
                <div style="display: flex; align-items: center; gap: 0.25rem;">
                    <button type="button" 
                            class="btn-action-icon" 
                            style="width: 24px; height: 24px;" 
                            title="Move Up" 
                            ${isFirst ? 'disabled' : ''} 
                            onclick="moveStep(${idx}, -1)">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="18 15 12 9 6 15"></polyline></svg>
                    </button>
                    <input type="number" 
                           class="step-order-input" 
                           value="${step.position}" 
                           min="1" 
                           max="${pipeline.length}" 
                           title="Edit Position"
                           onchange="updateStepPosition(${idx}, this.value)">
                    <button type="button" 
                            class="btn-action-icon" 
                            style="width: 24px; height: 24px;" 
                            title="Move Down" 
                            ${isLast ? 'disabled' : ''} 
                            onclick="moveStep(${idx}, 1)">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"></polyline></svg>
                    </button>
                    <button type="button" 
                            class="btn-danger-icon" 
                            style="width: 24px; height: 24px;" 
                            title="Remove Step" 
                            onclick="removeStep(${idx})">
                        &times;
                    </button>
                </div>
            </div>`;

        // Arrow connector between steps
        if (!isLast) {
            html += `
                <div class="pipeline-arrow-connector">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"></line><polyline points="19 12 12 19 5 12"></polyline></svg>
                </div>`;
        }
    });

    if (isIterative && pipeline.length > 0) {
        html += `
            <div style="margin-top: 0.5rem; padding: 0.45rem 0.65rem; border-radius: 4px; background: #f0fdf4; border: 1px dashed #86efac; color: #166534; font-size: 0.75rem; display: flex; align-items: center; justify-content: center; gap: 0.4rem;">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21.5 2v6h-6M2.5 22v-6h6M2 11.5a10 10 0 0 1 18.8-4.3M22 12.5a10 10 0 0 1-18.8 4.2"></path></svg>
                <span>Loops back from Step #${pipeline.length} &rarr; Step #1</span>
            </div>`;
    }

    container.innerHTML = html;
}

function updateStatusIndicator(text, color) {
    const el = document.getElementById('executionStatusIndicator');
    el.innerHTML = `<span class="status-dot" style="background: ${color};"></span><span>${escapeHtml(text)}</span>`;
}

async function runPipeline() {
    if (pipeline.length === 0) {
        alert('Please add at least one agent to the pipeline before running.');
        return;
    }

    const promptText = document.getElementById('initialPrompt').value.trim();
    if (!promptText) {
        alert('Please provide an initial prompt for Agent #1.');
        document.getElementById('initialPrompt').focus();
        return;
    }

    const isIterative = document.getElementById('iterativeToggle').checked;
    const iterationMode = document.querySelector('input[name="iteration_mode"]:checked')?.value || 'fixed';
    const iterationCount = parseInt(document.getElementById('iterationCount').value, 10) || 1;

    const btn = document.getElementById('runPipelineBtn');
    btn.disabled = true;
    btn.innerHTML = `<span class="status-dot" style="background: #ffffff; animation: pulse 1s infinite;"></span><span>Executing Chain...</span>`;

    updateStatusIndicator('Executing Chain...', '#2563eb');

    document.getElementById('blueprintPlaceholder').style.display = 'none';
    const resultsContainer = document.getElementById('resultsStreamContainer');
    resultsContainer.style.display = 'flex';
    resultsContainer.innerHTML = `
        <div style="padding: 2.5rem; text-align: center; color: var(--text-muted);">
            <div style="width: 38px; height: 38px; border: 3px solid #e2e8f0; border-top-color: var(--primary); border-radius: 50%; animation: spin 0.8s linear infinite; margin: 0 auto 1rem;"></div>
            <div style="font-weight: 600; color: var(--text-primary); font-size: 0.95rem;">Orchestrating Multi-Agent Execution</div>
            <p style="font-size: 0.82rem; margin-top: 0.35rem;">Cascading initial prompt through pipeline steps...</p>
        </div>`;

    try {
        const payload = {
            pipeline: pipeline.map(p => ({ agent_id: p.agent.id, position: p.position })),
            initial_prompt: promptText,
            is_iterative: isIterative,
            iteration_mode: iterationMode,
            max_iterations: iterationCount
        };

        const res = await fetch('/workbench/execute', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });

        const rawText = await res.text();
        let data;
        try {
            data = JSON.parse(rawText);
        } catch (jsonErr) {
            const cleanErr = rawText.replace(/<[^>]*>?/gm, ' ').replace(/\s+/g, ' ').trim();
            resultsContainer.innerHTML = `
                <div class="alert alert-danger" style="white-space: pre-wrap; font-family: inherit;">
                    <strong>Execution Error:</strong> ${escapeHtml(cleanErr || jsonErr.message)}
                </div>`;
            updateStatusIndicator('Execution Failed', '#ef4444');
            return;
        }

        if (!data.success) {
            resultsContainer.innerHTML = `
                <div class="alert alert-danger">
                    <strong>Execution Error:</strong> ${escapeHtml(data.error || 'Pipeline execution failed.')}
                </div>`;
            updateStatusIndicator('Execution Failed', '#ef4444');
            return;
        }

        renderExecutionResults(data);
        updateStatusIndicator('Completed', '#10b981');

    } catch (err) {
        resultsContainer.innerHTML = `
            <div class="alert alert-danger">
                <strong>Execution Error:</strong> ${escapeHtml(err.message)}
            </div>`;
        updateStatusIndicator('Error', '#ef4444');
    } finally {
        btn.disabled = false;
        btn.innerHTML = `
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <polygon points="5 3 19 12 5 21 5 3"></polygon>
            </svg>
            <span>Run Pipeline</span>`;
    }
}

let lastDeliverableText = '';

function renderExecutionResults(data) {
    const container = document.getElementById('resultsStreamContainer');
    lastDeliverableText = data.final_output || '';
    let html = '';

    // Summary banner
    html += `
        <div style="padding: 0.85rem 1.25rem; background: #eff6ff; border: 1px solid #bfdbfe; border-radius: var(--radius-sm); display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.5rem;">
            <div>
                <strong style="color: #1d4ed8; font-size: 0.9rem;">Pipeline Run Completed</strong>
                <div style="font-size: 0.78rem; color: #3b82f6;">${escapeHtml(data.stopped_reason)} &bull; ${data.total_steps} step executions</div>
            </div>
            <button type="button" class="btn btn-secondary btn-sm" onclick="copyFinalOutput()">
                Copy Final Output
            </button>
        </div>`;

    data.iterations.forEach(iter => {
        if (data.is_iterative) {
            html += `
                <div style="display: flex; align-items: center; gap: 0.5rem; margin: 0.5rem 0 0.2rem;">
                    <span class="meta-tag" style="background: #f1f5f9; color: var(--text-primary); font-weight: 700; font-size: 0.75rem;">
                        Iteration Cycle #${iter.iteration}
                    </span>
                    <hr style="flex: 1; border: none; height: 1px; background: var(--border-color); margin: 0;">
                </div>`;
        }

        iter.steps.forEach(step => {
            html += `
                <div class="execution-step-card">
                    <div class="execution-step-header">
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <span class="pipeline-step-num" style="width: 24px; height: 24px; font-size: 0.75rem;">#${step.position}</span>
                            <span style="font-weight: 700; color: var(--text-primary); font-size: 0.9rem;">${escapeHtml(step.agent_name)}</span>
                            <span style="font-size: 0.78rem; color: var(--text-muted);">&bull; ${escapeHtml(step.agent_role)}</span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 0.4rem;">
                            <span class="badge-provider" style="font-size: 0.68rem;">${escapeHtml(step.provider.toUpperCase())}</span>
                            <span class="badge-model" style="font-size: 0.68rem;">${escapeHtml(step.model)}</span>
                            <span style="font-size: 0.72rem; color: var(--text-muted); background: #f8fafc; border: 1px solid var(--border-color); border-radius: 4px; padding: 2px 6px;">
                                ${step.execution_time_ms}ms
                            </span>
                        </div>
                    </div>

                    <!-- Input Sent to Agent (Collapsible) -->
                    <details class="execution-input-preview">
                        <summary style="cursor: pointer; font-weight: 600; outline: none; user-select: none;">
                            Input Prompt Fed to Agent (${step.input.length} chars)
                        </summary>
                        <div style="margin-top: 0.5rem; padding-top: 0.5rem; border-top: 1px solid var(--border-color);" class="markdown-body">
                            ${renderMarkdown(step.input)}
                        </div>
                    </details>

                    <!-- Output Generated by Agent (Markdown Standard) -->
                    <div class="execution-output-body markdown-body">
                        ${renderMarkdown(step.output)}
                    </div>
                </div>`;
        });
    });

    // Final Output Highlight Box
    html += `
        <div class="workbench-card" style="border: 2px solid #2563eb; background: #fafcff; margin-top: 0.5rem;">
            <div class="workbench-card-header" style="background: #eff6ff; border-bottom: 1px solid #bfdbfe;">
                <span class="workbench-card-title" style="color: #1d4ed8;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                    <span>Final Pipeline Deliverable</span>
                </span>
                <button type="button" class="btn btn-primary btn-sm" onclick="copyFinalOutput()">
                    Copy Deliverable
                </button>
            </div>
            <div class="workbench-card-body markdown-body" style="font-size: 0.94rem; line-height: 1.68; color: var(--text-primary);" id="finalOutputContent">
                ${renderMarkdown(data.final_output)}
            </div>
        </div>`;

    container.innerHTML = html;
}

function copyFinalOutput() {
    const text = lastDeliverableText || document.getElementById('finalOutputContent')?.textContent || '';
    if (!text) return;
    navigator.clipboard.writeText(text.trim()).then(() => {
        alert('Final output deliverable copied to clipboard!');
    });
}

/**
 * Robust Client-Side Markdown Parser adhering to standard markdown
 */
function renderMarkdown(md) {
    if (!md) return '';
    
    let text = md.replace(/\r\n/g, '\n').replace(/\r/g, '\n');

    // 1. Isolate code blocks
    const codeBlocks = [];
    text = text.replace(/```([a-zA-Z0-9_-]*)[^\n]*\n([\s\S]*?)```/g, function(match, lang, code) {
        const id = '@@@CODEBLOCK_' + codeBlocks.length + '@@@';
        codeBlocks.push({ lang: (lang || 'text').trim(), code: escapeHtml(code.trim()) });
        return id;
    });

    // 2. Isolate inline code
    const inlineCodes = [];
    text = text.replace(/`([^`\n]+)`/g, function(match, code) {
        const id = '@@@INLINECODE_' + inlineCodes.length + '@@@';
        inlineCodes.push(escapeHtml(code));
        return id;
    });

    // Escape HTML in the rest of the text
    text = escapeHtml(text);

    const lines = text.split('\n');
    const out = [];
    let inUl = false;
    let inOl = false;
    let inBlockquote = false;
    let inTable = false;
    let tableRows = [];

    function closeLists() {
        if (inUl) { out.push('</ul>'); inUl = false; }
        if (inOl) { out.push('</ol>'); inOl = false; }
    }
    function closeBlockquote() {
        if (inBlockquote) { out.push('</blockquote>'); inBlockquote = false; }
    }
    function closeTable() {
        if (inTable) {
            out.push(renderTableHtml(tableRows));
            tableRows = [];
            inTable = false;
        }
    }

    for (let i = 0; i < lines.length; i++) {
        let line = lines[i];
        let trimmed = line.trim();

        // Tables
        if (trimmed.startsWith('|') && trimmed.endsWith('|')) {
            closeLists();
            closeBlockquote();
            inTable = true;
            tableRows.push(trimmed);
            continue;
        } else if (inTable) {
            closeTable();
        }

        // Horizontal Rules (---, ***, ___)
        if (/^(?:---|\*\*\*|___)\s*$/.test(trimmed)) {
            closeLists();
            closeBlockquote();
            out.push('<hr class="markdown-hr">');
            continue;
        }

        // Headers (# to ######)
        const headerMatch = line.match(/^(#{1,6})\s+(.+)$/);
        if (headerMatch) {
            closeLists();
            closeBlockquote();
            const level = headerMatch[1].length;
            const content = parseInlineMarkdown(headerMatch[2]);
            out.push(`<h${level} class="markdown-h${level}">${content}</h${level}>`);
            continue;
        }

        // Blockquotes (> or &gt;)
        const quoteMatch = line.match(/^(?:&gt;|>)\s?(.*)$/);
        if (quoteMatch) {
            closeLists();
            if (!inBlockquote) {
                out.push('<blockquote class="markdown-blockquote">');
                inBlockquote = true;
            }
            out.push('<p>' + parseInlineMarkdown(quoteMatch[1]) + '</p>');
            continue;
        } else if (inBlockquote) {
            closeBlockquote();
        }

        // Unordered lists (*, -, +)
        const ulMatch = line.match(/^(\s*)[*+-]\s+(.+)$/);
        if (ulMatch) {
            if (inOl) { out.push('</ol>'); inOl = false; }
            if (!inUl) {
                out.push('<ul class="markdown-ul">');
                inUl = true;
            }
            out.push(`<li>${parseInlineMarkdown(ulMatch[2])}</li>`);
            continue;
        }

        // Ordered lists (1. 2. etc.)
        const olMatch = line.match(/^(\s*)\d+\.\s+(.+)$/);
        if (olMatch) {
            if (inUl) { out.push('</ul>'); inUl = false; }
            if (!inOl) {
                out.push('<ol class="markdown-ol">');
                inOl = true;
            }
            out.push(`<li>${parseInlineMarkdown(olMatch[2])}</li>`);
            continue;
        }

        // Blank lines
        if (trimmed === '') {
            closeLists();
            closeBlockquote();
            continue;
        }

        closeLists();
        closeBlockquote();

        if (trimmed.startsWith('@@@CODEBLOCK_') && trimmed.endsWith('@@@')) {
            out.push(trimmed);
        } else {
            out.push(`<p class="markdown-p">${parseInlineMarkdown(line)}</p>`);
        }
    }

    closeLists();
    closeBlockquote();
    closeTable();

    let resultHtml = out.join('\n');

    // Restore Code Blocks
    resultHtml = resultHtml.replace(/@@@CODEBLOCK_(\d+)@@@/g, function(match, idx) {
        const item = codeBlocks[parseInt(idx, 10)];
        return `<pre class="markdown-pre"><code class="language-${item.lang}">${item.code}</code></pre>`;
    });

    // Restore Inline Code
    resultHtml = resultHtml.replace(/@@@INLINECODE_(\d+)@@@/g, function(match, idx) {
        const item = inlineCodes[parseInt(idx, 10)];
        return `<code class="markdown-code">${item}</code>`;
    });

    return resultHtml;
}

function parseInlineMarkdown(text) {
    if (!text) return '';
    // Bold + Italic: ***text***
    text = text.replace(/\*\*\*(.*?)\*\*\*/g, '<strong><em>$1</em></strong>');
    text = text.replace(/___(.*?)___/g, '<strong><em>$1</em></strong>');

    // Bold: **text**
    text = text.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
    text = text.replace(/__(.*?)__/g, '<strong>$1</strong>');

    // Italic: *text* (single asterisk)
    text = text.replace(/(^|[^*])\*([^*\n]+)\*(?!\*)/g, '$1<em>$2</em>');
    text = text.replace(/(^|[^_])_([^_\n]+)_(?!_)/g, '$1<em>$2</em>');

    // Strikethrough: ~~text~~
    text = text.replace(/~~(.*?)~~/g, '<del>$1</del>');

    // Links: [text](url)
    text = text.replace(/\[([^\]]+)\]\(([^)]+)\)/g, '<a href="$2" target="_blank" rel="noopener noreferrer" class="markdown-link">$1</a>');

    return text;
}

function renderTableHtml(rows) {
    if (!rows || rows.length === 0) return '';
    let html = '<div class="table-responsive"><table class="markdown-table">';
    let inTbody = false;

    rows.forEach((rowStr, idx) => {
        const rawCols = rowStr.split('|').map(c => c.trim());
        const cols = rawCols.filter((c, i, arr) => !(i === 0 && c === '') && !(i === arr.length - 1 && c === ''));
        
        if (cols.every(c => /^[-:]+$/.test(c))) {
            return;
        }

        if (idx === 0) {
            html += '<thead><tr>';
            cols.forEach(col => {
                html += `<th>${parseInlineMarkdown(col)}</th>`;
            });
            html += '</tr></thead><tbody>';
            inTbody = true;
        } else {
            if (!inTbody) {
                html += '<tbody>';
                inTbody = true;
            }
            html += '<tr>';
            cols.forEach(col => {
                html += `<td>${parseInlineMarkdown(col)}</td>`;
            });
            html += '</tr>';
        }
    });

    if (inTbody) html += '</tbody>';
    html += '</table></div>';
    return html;
}

function escapeHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

// ============================================================================
// Knowledge Base Document Management Logic
// ============================================================================

// Drag & drop listeners on kbDropzone
const kbDropzone = document.getElementById('kbDropzone');
if (kbDropzone) {
    ['dragenter', 'dragover'].forEach(eventName => {
        kbDropzone.addEventListener(eventName, (e) => {
            e.preventDefault();
            e.stopPropagation();
            kbDropzone.classList.add('dragover');
        });
    });

    ['dragleave', 'drop'].forEach(eventName => {
        kbDropzone.addEventListener(eventName, (e) => {
            e.preventDefault();
            e.stopPropagation();
            kbDropzone.classList.remove('dragover');
        });
    });

    kbDropzone.addEventListener('drop', (e) => {
        const dt = e.dataTransfer;
        const files = dt.files;
        if (files && files.length > 0) {
            uploadFiles(files);
        }
    });
}

function handleFileUpload(event) {
    const files = event.target.files;
    if (files && files.length > 0) {
        uploadFiles(files);
        event.target.value = ''; // Reset input
    }
}

async function uploadFiles(files) {
    for (let i = 0; i < files.length; i++) {
        const file = files[i];
        const formData = new FormData();
        formData.append('file', file);

        try {
            const response = await fetch('/workbench/documents/upload', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();
            if (data.success && data.document) {
                appendDocToUI(data.document);
            } else {
                alert('Upload failed: ' + (data.error || 'Unknown error'));
            }
        } catch (err) {
            console.error('File upload error:', err);
            alert('File upload network error: ' + err.message);
        }
    }
}

function appendDocToUI(doc) {
    // Hide empty notice if visible
    const emptyNotice = document.getElementById('kbEmptyNotice');
    if (emptyNotice) {
        emptyNotice.remove();
    }

    const docList = document.getElementById('kbDocList');
    if (!docList) return;

    // Check if item already exists
    const existing = document.getElementById(`kbDocItem-${doc.id}`);
    if (existing) {
        existing.remove();
    }

    const ext = (doc.file_type || 'txt').toLowerCase();
    const sizeFormatted = doc.file_size >= 1024 
        ? (doc.file_size / 1024).toFixed(1) + ' KB' 
        : doc.file_size + ' B';
    const charsFormatted = (doc.char_count || doc.file_size).toLocaleString() + ' chars';

    const itemDiv = document.createElement('div');
    itemDiv.className = 'kb-doc-item';
    itemDiv.id = `kbDocItem-${doc.id}`;
    itemDiv.innerHTML = `
        <div class="kb-doc-badge ${escapeHtml(ext)}">${escapeHtml(ext.slice(0, 4))}</div>
        <div class="kb-doc-info">
            <div class="kb-doc-title" title="${escapeHtml(doc.filename)}">
                ${escapeHtml(doc.filename)}
            </div>
            <div class="kb-doc-meta">
                <span>${sizeFormatted}</span>
                <span>&bull;</span>
                <span>${charsFormatted}</span>
            </div>
        </div>
        <div class="kb-doc-actions">
            <button type="button" class="kb-btn-icon" title="Preview Document" onclick="previewDocument(${doc.id})">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                    <circle cx="12" cy="12" r="3"></circle>
                </svg>
            </button>
            <button type="button" class="kb-btn-icon danger" title="Remove Document" onclick="deleteDocument(${doc.id}, '${escapeHtml(doc.filename)}')">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="3 6 5 6 21 6"></polyline>
                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                </svg>
            </button>
        </div>
    `;

    docList.prepend(itemDiv);
    updateDocCountBadge();
}

function updateDocCountBadge() {
    const items = document.querySelectorAll('#kbDocList .kb-doc-item');
    const badge = document.getElementById('kbDocCountBadge');
    if (badge) {
        badge.textContent = `${items.length} docs`;
    }
}

async function deleteDocument(id, filename) {
    if (!confirm(`Are you sure you want to remove document "${filename}" from the workbench knowledge base?`)) {
        return;
    }

    try {
        const response = await fetch('/workbench/documents/delete', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: id })
        });
        const data = await response.json();
        if (data.success) {
            const el = document.getElementById(`kbDocItem-${id}`);
            if (el) {
                el.style.opacity = '0';
                el.style.transform = 'translateX(20px)';
                el.style.transition = 'all 0.25s ease';
                setTimeout(() => {
                    el.remove();
                    updateDocCountBadge();
                    const remaining = document.querySelectorAll('#kbDocList .kb-doc-item');
                    if (remaining.length === 0) {
                        const docList = document.getElementById('kbDocList');
                        if (docList) {
                            docList.innerHTML = `
                                <div id="kbEmptyNotice" style="text-align: center; padding: 2rem 1rem; color: var(--text-muted); font-size: 0.85rem; border: 1px dashed var(--border-color); border-radius: var(--radius-sm); background: #fafbfc;">
                                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="color: #94a3b8; margin-bottom: 0.5rem;">
                                        <path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"></path>
                                        <polyline points="13 2 13 9 20 9"></polyline>
                                    </svg>
                                    <div style="font-weight: 600; color: var(--text-primary); margin-bottom: 0.2rem;">No documents loaded</div>
                                    <div style="font-size: 0.74rem; color: var(--text-muted); line-height: 1.4;">Uploaded documents serve as knowledge base and are passed into agent prompts.</div>
                                </div>
                            `;
                        }
                    }
                }, 250);
            }
        } else {
            alert('Failed to remove document: ' + (data.error || 'Unknown error'));
        }
    } catch (err) {
        console.error('Delete document error:', err);
        alert('Network error while removing document: ' + err.message);
    }
}

async function previewDocument(id) {
    try {
        const response = await fetch(`/workbench/documents/preview?id=${encodeURIComponent(id)}`);
        const data = await response.json();
        if (data.success && data.document) {
            const doc = data.document;
            const ext = (doc.file_type || 'txt').toUpperCase();
            const sizeFormatted = doc.file_size >= 1024 
                ? (doc.file_size / 1024).toFixed(1) + ' KB' 
                : doc.file_size + ' B';

            document.getElementById('kbPreviewModalTitle').textContent = doc.filename;
            document.getElementById('kbPreviewModalMeta').textContent = `${ext} Format • ${sizeFormatted} • Created ${doc.created_at || 'Recently'}`;
            document.getElementById('kbPreviewModalContent').textContent = doc.content || '(Empty document)';
            document.getElementById('kbPreviewModal').style.display = 'flex';
        } else {
            alert('Could not preview document: ' + (data.error || 'Not found'));
        }
    } catch (err) {
        console.error('Preview document error:', err);
        alert('Failed to fetch document preview: ' + err.message);
    }
}

function closePreviewModal() {
    document.getElementById('kbPreviewModal').style.display = 'none';
    const copyText = document.getElementById('kbCopyBtnText');
    if (copyText) copyText.textContent = 'Copy Content';
}

function copyPreviewContent() {
    const text = document.getElementById('kbPreviewModalContent').textContent;
    navigator.clipboard.writeText(text).then(() => {
        const copyText = document.getElementById('kbCopyBtnText');
        if (copyText) {
            copyText.textContent = 'Copied!';
            setTimeout(() => { copyText.textContent = 'Copy Content'; }, 2000);
        }
    }).catch(err => {
        console.error('Copy failed:', err);
    });
}

function openPasteDocModal() {
    document.getElementById('pasteDocTitle').value = '';
    document.getElementById('pasteDocContent').value = '';
    document.getElementById('kbPasteModal').style.display = 'flex';
    document.getElementById('pasteDocTitle').focus();
}

function closePasteDocModal() {
    document.getElementById('kbPasteModal').style.display = 'none';
}

async function savePastedDocument() {
    const title = document.getElementById('pasteDocTitle').value.trim();
    const content = document.getElementById('pasteDocContent').value.trim();

    if (!content) {
        alert('Please enter document content.');
        document.getElementById('pasteDocContent').focus();
        return;
    }

    const saveBtn = document.getElementById('savePastedDocBtn');
    saveBtn.disabled = true;
    saveBtn.innerHTML = `<span>Saving...</span>`;

    try {
        const ext = title.includes('.') ? title.split('.').pop() : 'txt';
        const response = await fetch('/workbench/documents/upload', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                filename: title || `Note_${new Date().toISOString().slice(0, 10)}.txt`,
                content: content,
                file_type: ext
            })
        });
        const data = await response.json();
        if (data.success && data.document) {
            appendDocToUI(data.document);
            closePasteDocModal();
        } else {
            alert('Failed to save document: ' + (data.error || 'Unknown error'));
        }
    } catch (err) {
        console.error('Save pasted document error:', err);
        alert('Network error while saving document: ' + err.message);
    } finally {
        saveBtn.disabled = false;
        saveBtn.innerHTML = `
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                <polyline points="17 21 17 13 7 13 7 21"></polyline>
                <polyline points="7 3 7 8 15 8"></polyline>
            </svg>
            <span>Save to Knowledge Base</span>
        `;
    }
}
</script>

<style>
@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.4; }
}
</style>

<?php
require __DIR__ . '/../layouts/footer.php';
?>
